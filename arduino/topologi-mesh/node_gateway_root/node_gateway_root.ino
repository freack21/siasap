/* =========================================================================================
 * SISTEM PEMANTAUAN KUALITAS UDARA LINGKUNGAN (SIMKU)
 * TOPOLOGI MESH - ESP32 NODE 1 (ROOT GATEWAY / SINK NODE)
 * =========================================================================================
 * Deskripsi Peran Node 1:
 * 1. Terkoneksi ke WiFi Router ("cargo") dan server backend Laravel SIMKU.
 * 2. Membaca sensor gas lokal miliknya sendiri (Node ID 1 - Ruang Tamu, Hop Count = 1,
 *    RSSI = WiFi.RSSI() link Node 1 -> AP).
 * 3. Berfungsi sebagai Sink / Gateway: Mendengarkan paket data dari Node 2 dan Node 3 melalui
 *    protokol ESP-NOW (RSSI link terakhir diukur dari rx_ctrl->rssi), kemudian meneruskan
 *    (forward) paket tersebut ke REST API Laravel (hop_count +1 dan RSSI digabung dengan
 *    WiFi.RSSI() karena paket melewati satu link nirkabel lagi: Gateway -> AP).
 * 4. Deduplikasi: semua paket ESP-NOW bersifat broadcast, sehingga paket Node 3 bisa diterima
 *    langsung DAN via relay (seq_number sama). Paket dengan seq_number sama dengan seq terakhir
 *    yang sudah di-forward untuk node tersebut dibuang (dicatat di Serial Monitor).
 *
 * Definisi metrik (seragam untuk semua topologi, lihat arduino/README.md):
 * - rssi      : RSSI TERLEMAH (minimum) dari seluruh link nirkabel yang dilalui paket sampai
 *               ke WiFi Access Point.
 * - hop_count : jumlah transmisi nirkabel sampai paket mencapai WiFi AP.
 *
 * Kebutuhan: ESP32 Arduino core >= 3.0 (signature callback esp_now_recv_info_t).
 * ========================================================================================= */

#include <esp_arduino_version.h>
#if !defined(ESP_ARDUINO_VERSION_MAJOR) || ESP_ARDUINO_VERSION_MAJOR < 3
#error "Butuh ESP32 Arduino core >= 3.0 (Boards Manager: esp32 by Espressif Systems versi 3.x)."
#endif

#include <WiFi.h>
#include <esp_now.h>
#include <esp_wifi.h>
#include <HTTPClient.h>
#include <math.h>

// ======================== KONFIGURASI JARINGAN & SERVER ========================
const char* WIFI_SSID     = "cargo";
const char* WIFI_PASSWORD = "cargocargo";

// URL Endpoint Laravel SIMKU
const char* SERVER_URL    = "http://192.168.137.1:8000/api/telemetry";

// API Key opsional. Isi dengan nilai TELEMETRY_API_KEY di file .env Laravel bila backend
// mewajibkannya. Biarkan kosong ("") bila backend tidak memakai API key.
const char* API_KEY       = "";

const unsigned long INTERVAL_KIRIM = 5000;

// ======================== IDENTITAS NODE ========================
#define NODE_ID 1  // Node 1 (Ruang Tamu / Root Gateway)

// ======================== PIN HARDWARE SENSOR ========================
#define PIN_MQ2   34
#define PIN_MQ135 35

const float RL        = 1.0;
// PENTING: R0 berbeda untuk setiap sensor fisik. Setiap board WAJIB memakai R0 hasil
// kalibrasinya sendiri dari sketch arduino/kalibrasi_r0/kalibrasi_r0.ino (udara bersih,
// setelah burn-in 24-48 jam). Nilai di bawah hanya nilai awal/contoh.
const float R0_MQ2    = 0.174;
const float R0_MQ135  = 19.30;

unsigned long seqNumber = 0;
unsigned long lastSendTime = 0;

// ======================== STRUKTUR PAKET MESH ESP-NOW ========================
typedef struct struct_mesh_packet {
  int node_id;
  float co2;
  float nh3;
  float co;
  float h2;
  int rssi;
  int seq_number;
  int hop_count;
} struct_mesh_packet;

// ======================== ANTREAN PAKET ESP-NOW ========================
// HTTP POST bersifat blocking (bisa ratusan ms), sehingga paket ESP-NOW yang datang selama
// itu ditampung di antrean melingkar (ring buffer). Akses dilindungi critical section karena
// callback ESP-NOW berjalan di task WiFi, sedangkan loop() berjalan di task Arduino.
#define MAX_QUEUE 20
struct_mesh_packet packetQueue[MAX_QUEUE];
volatile int queueHead  = 0;   // indeks paket terdepan
volatile int queueCount = 0;   // jumlah paket dalam antrean
volatile unsigned long queueDropped = 0;  // paket dibuang karena antrean penuh
unsigned long queueDroppedReported = 0;
portMUX_TYPE queueMux = portMUX_INITIALIZER_UNLOCKED;

// ======================== DEDUPLIKASI ========================
// seq_number terakhir yang sudah di-forward per node_id (0 = belum ada; seq dimulai dari 1)
#define MAX_NODE_ID 10
int lastSeq[MAX_NODE_ID + 1] = {0};
unsigned long duplicateCount = 0;

// Forward Declaration
void forwardPacketToLaravel(const struct_mesh_packet &pkt);

// Gabungkan RSSI terukur ke paket: ambil nilai TERLEMAH (minimum) sepanjang jalur.
// rssi == 0 berarti belum ada link yang terukur.
int gabungRssi(int rssiPaket, int rssiTerukur) {
  if (rssiPaket == 0) return rssiTerukur;
  return (rssiTerukur < rssiPaket) ? rssiTerukur : rssiPaket;
}

// Callback saat menerima paket ESP-NOW dari Node 2 atau Node 3 (signature core 3.x)
void OnDataRecv(const esp_now_recv_info_t *info, const uint8_t *incomingData, int len) {
  if (len != sizeof(struct_mesh_packet)) return;

  struct_mesh_packet pkt;
  memcpy(&pkt, incomingData, sizeof(struct_mesh_packet));

  // Ukur RSSI link terakhir (pengirim -> Gateway) dari paket yang diterima
  int rssiTerukur = info->rx_ctrl->rssi;
  pkt.rssi = gabungRssi(pkt.rssi, rssiTerukur);

  portENTER_CRITICAL(&queueMux);
  if (queueCount < MAX_QUEUE) {
    int tail = (queueHead + queueCount) % MAX_QUEUE;
    packetQueue[tail] = pkt;
    queueCount++;
  } else {
    queueDropped++;
  }
  portEXIT_CRITICAL(&queueMux);
}

// Ambil satu paket dari antrean (true jika ada)
bool ambilPaketAntrean(struct_mesh_packet &out) {
  bool ada = false;
  portENTER_CRITICAL(&queueMux);
  if (queueCount > 0) {
    out = packetQueue[queueHead];
    queueHead = (queueHead + 1) % MAX_QUEUE;
    queueCount--;
    ada = true;
  }
  portEXIT_CRITICAL(&queueMux);
  return ada;
}

// ======================== FUNGSI KALKULASI SENSOR ========================
float hitungRsMQ2(int adcValue) {
  if (adcValue >= 4080) adcValue = 4080;
  if (adcValue <= 50)   adcValue = 50;
  float vOut = (adcValue / 4095.0) * 3.3;
  return ((3.3 - vOut) / vOut) * RL;
}

float hitungRsMQ135(int adcValue) {
  if (adcValue >= 4080) adcValue = 4080;
  if (adcValue <= 20)   adcValue = 20;
  float vOut = (adcValue / 4095.0) * 3.3;
  return ((5.0 - vOut) / vOut) * RL;
}

float hitungPPM(float rs, float r0, float a, float b) {
  float rasio = rs / r0;
  if (rasio <= 0.0) return 0.0;
  return a * pow(rasio, b);
}

// ======================== SETUP ========================
void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println("\n=======================================================");
  Serial.println("  SIMKU - ESP32 TOPOLOGI MESH [NODE 1: ROOT GATEWAY]");
  Serial.println("=======================================================");

  analogReadResolution(12);

  // Inisialisasi WiFi Mode Station
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  Serial.print("Menghubungkan ke WiFi Gateway ["); Serial.print(WIFI_SSID); Serial.print("]");
  int retry = 0;
  while (WiFi.status() != WL_CONNECTED && retry < 30) {
    delay(500);
    Serial.print(".");
    retry++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n[OK] Terhubung ke WiFi!");
    Serial.print("IP Address Root : "); Serial.println(WiFi.localIP());
    Serial.print("MAC Address     : "); Serial.println(WiFi.macAddress());
    Serial.print("WiFi Channel    : "); Serial.println(WiFi.channel());
  } else {
    Serial.println("\n[PERINGATAN] Gagal WiFi, akan terus mencoba di background.");
  }

  // Inisialisasi Protokol ESP-NOW
  if (esp_now_init() == ESP_OK) {
    Serial.println("[OK] ESP-NOW Berhasil Diinisialisasi pada Root Gateway.");
    esp_now_register_recv_cb(OnDataRecv);
  } else {
    Serial.println("[ERROR] Gagal menginisialisasi ESP-NOW!");
  }
}

// ======================== FUNGSI FORWARD KE LARAVEL ========================
void forwardPacketToLaravel(const struct_mesh_packet &pkt) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.print("[OFFLINE] WiFi terputus, paket Node #"); Serial.print(pkt.node_id);
    Serial.print(" Seq #"); Serial.print(pkt.seq_number); Serial.println(" tidak terkirim.");
    return;
  }

  HTTPClient http;
  http.begin(SERVER_URL);
  http.addHeader("Content-Type", "application/json");
  if (strlen(API_KEY) > 0) {
    http.addHeader("X-Api-Key", API_KEY);
  }

  String json = "{";
  json += "\"node_id\":"        + String(pkt.node_id) + ",";
  json += "\"co2\":"            + String(pkt.co2, 2) + ",";
  json += "\"nh3\":"            + String(pkt.nh3, 2) + ",";
  json += "\"co\":"             + String(pkt.co, 2) + ",";
  json += "\"h2\":"             + String(pkt.h2, 2) + ",";
  json += "\"rssi\":"           + String(pkt.rssi) + ",";
  json += "\"seq_number\":"     + String(pkt.seq_number) + ",";
  json += "\"hop_count\":"      + String(pkt.hop_count) + ",";
  json += "\"topology_type\":\"mesh\"";
  json += "}";

  int code = http.POST(json);
  Serial.print("[MESH FORWARD] Node #"); Serial.print(pkt.node_id);
  Serial.print(" (Hop: "); Serial.print(pkt.hop_count);
  Serial.print(", RSSI: "); Serial.print(pkt.rssi);
  Serial.print(" dBm, Seq: #"); Serial.print(pkt.seq_number);
  Serial.print(") -> HTTP Response: "); Serial.println(code);
  if (code <= 0) {
    Serial.print("[ERROR HTTP] "); Serial.println(http.errorToString(code).c_str());
  }

  http.end();
}

// ======================== LOOP UTAMA ========================
void loop() {
  // 1. Proses paket yang diterima dari Node 2 / Node 3 melalui antrean ESP-NOW
  struct_mesh_packet pkt;
  while (ambilPaketAntrean(pkt)) {
    // Validasi node_id agar indeks lastSeq aman
    if (pkt.node_id < 1 || pkt.node_id > MAX_NODE_ID || pkt.node_id == NODE_ID) {
      Serial.print("[MESH DROP] node_id tidak valid: "); Serial.println(pkt.node_id);
      continue;
    }

    // Deduplikasi: paket yang sama bisa tiba langsung DAN via relay (broadcast)
    if (pkt.seq_number == lastSeq[pkt.node_id]) {
      duplicateCount++;
      Serial.print("[MESH DUPLIKAT] Node #"); Serial.print(pkt.node_id);
      Serial.print(" Seq #"); Serial.print(pkt.seq_number);
      Serial.print(" (Hop: "); Serial.print(pkt.hop_count);
      Serial.print(") sudah di-forward, dibuang. Total duplikat: ");
      Serial.println(duplicateCount);
      continue;
    }
    lastSeq[pkt.node_id] = pkt.seq_number;

    // Paket melewati satu link nirkabel lagi: Gateway -> WiFi AP
    pkt.hop_count += 1;
    pkt.rssi = gabungRssi(pkt.rssi, (int) WiFi.RSSI());

    // Forward ke Laravel Backend
    forwardPacketToLaravel(pkt);
  }

  // Laporkan paket yang terbuang karena antrean gateway penuh (drop di sisi gateway,
  // BUKAN kehilangan pada link radio)
  unsigned long droppedNow;
  portENTER_CRITICAL(&queueMux);
  droppedNow = queueDropped;
  portEXIT_CRITICAL(&queueMux);
  if (droppedNow != queueDroppedReported) {
    Serial.print("[GATEWAY OVERFLOW] Antrean ESP-NOW penuh! Total paket dibuang di gateway: ");
    Serial.println(droppedNow);
    queueDroppedReported = droppedNow;
  }

  // 2. Baca sensor lokal milik Node 1 secara berkala
  if (millis() - lastSendTime >= INTERVAL_KIRIM) {
    lastSendTime = millis();

    // Sampling ADC sensor lokal
    long sum_mq2 = 0;
    long sum_mq135 = 0;
    for (int i = 0; i < 15; i++) {
      sum_mq2   += analogRead(PIN_MQ2);
      sum_mq135 += analogRead(PIN_MQ135);
      delay(10);
    }
    int adc_mq2   = sum_mq2 / 15;
    int adc_mq135 = sum_mq135 / 15;

    float rs_mq2   = hitungRsMQ2(adc_mq2);
    float rs_mq135 = hitungRsMQ135(adc_mq135);

    float ppm_co2 = 400.0 * pow((rs_mq135 / R0_MQ135), -2.762);
    float rasio_mq135 = rs_mq135 / R0_MQ135;
    float ppm_nh3 = 10.0 * pow(rasio_mq135, -2.473);
    if (rasio_mq135 >= 0.9) {
      ppm_nh3 = (1.1 - rasio_mq135) * 15.0;
      if (ppm_nh3 < 0.5) ppm_nh3 = 0.5;
    }

    float ppm_co  = hitungPPM(rs_mq2, R0_MQ2, 3697.4, -3.109);
    float ppm_h2  = hitungPPM(rs_mq2, R0_MQ2, 987.99, -2.162);

    seqNumber++;

    // Buat struct paket untuk data Node 1 sendiri (Hop Count = 1)
    struct_mesh_packet myPacket;
    myPacket.node_id    = NODE_ID;
    myPacket.co2        = ppm_co2;
    myPacket.nh3        = ppm_nh3;
    myPacket.co         = ppm_co;
    myPacket.h2         = ppm_h2;
    myPacket.rssi       = WiFi.RSSI();  // Link Node 1 -> AP (satu-satunya link nirkabel)
    myPacket.seq_number = seqNumber;
    myPacket.hop_count  = 1;  // Satu transmisi nirkabel: Node 1 -> AP

    // Kirim data sensor lokal ke server Laravel
    forwardPacketToLaravel(myPacket);
  }
}

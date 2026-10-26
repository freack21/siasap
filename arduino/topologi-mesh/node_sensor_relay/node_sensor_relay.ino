/* =========================================================================================
 * SISTEM PEMANTAUAN KUALITAS UDARA LINGKUNGAN (SIMKU)
 * TOPOLOGI MESH - ESP32 NODE 2 (INTERMEDIATE / RELAY SENSOR NODE)
 * =========================================================================================
 * Deskripsi Peran Node 2:
 * 1. Membaca sensor gas lokal miliknya sendiri (Node ID 2 - Kamar).
 * 2. Mengirim data lokal secara berkala ke Node 1 (Root Gateway) via ESP-NOW
 *    (hop_count = 1 dari Node 2; Gateway menambah +1 untuk hop ke AP -> total 2).
 * 3. Berfungsi sebagai RELAY / JEMBATAN: Mendengarkan paket dari Node 3 (Leaf yang berada di
 *    blind spot), MENGUKUR RSSI link 3->2 dari paket yang diterima, menambah Hop Count (+1),
 *    lalu meneruskannya (retransmit) ke Node 1. Paket dengan hop_count > 1 (sudah pernah
 *    di-relay) diabaikan agar relay tidak pernah me-relay ulang.
 *
 * Definisi metrik (seragam untuk semua topologi, lihat arduino/README.md):
 * - rssi      : RSSI TERLEMAH (minimum) dari seluruh link nirkabel yang dilalui paket sampai
 *               ke WiFi AP. Pengirim mengisi 0 ("belum terukur"); setiap penerima ESP-NOW
 *               menerapkan rssi = (rssi == 0) ? terukur : min(rssi, terukur).
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
#include <math.h>

// ======================== IDENTITAS NODE ========================
#define NODE_ID 2  // Node 2 (Kamar / Relay)

// Saluran WiFi (Harus sama dengan WiFi AP / Root Gateway, default: Channel 1)
#define WIFI_CHANNEL 1

const unsigned long INTERVAL_KIRIM = 5000;

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

// Broadcast MAC Address agar tidak perlu memasukkan MAC address statis manual
uint8_t broadcastAddress[] = {0xFF, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF};

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

// ======================== ANTREAN RELAY ========================
// Callback ESP-NOW berjalan di task WiFi, sehingga paket yang akan di-relay dimasukkan ke
// antrean dan dipancarkan ulang dari loop() (bukan dari dalam callback).
#define MAX_RELAY_QUEUE 10
struct_mesh_packet relayQueue[MAX_RELAY_QUEUE];
volatile int relayHead  = 0;   // indeks paket terdepan
volatile int relayCount = 0;   // jumlah paket dalam antrean
volatile unsigned long relayDropped = 0;  // paket dibuang karena antrean penuh
unsigned long relayDroppedReported = 0;
portMUX_TYPE relayMux = portMUX_INITIALIZER_UNLOCKED;

// Gabungkan RSSI terukur ke paket: ambil nilai TERLEMAH (minimum) sepanjang jalur.
// rssi == 0 berarti belum ada link yang terukur.
int gabungRssi(int rssiPaket, int rssiTerukur) {
  if (rssiPaket == 0) return rssiTerukur;
  return (rssiTerukur < rssiPaket) ? rssiTerukur : rssiPaket;
}

// Callback saat menerima paket ESP-NOW (signature ESP32 Arduino core 3.x)
void OnDataRecv(const esp_now_recv_info_t *info, const uint8_t *incomingData, int len) {
  if (len != sizeof(struct_mesh_packet)) return;

  struct_mesh_packet incomingPkt;
  memcpy(&incomingPkt, incomingData, sizeof(struct_mesh_packet));

  // Hanya relay paket node lain yang BELUM pernah di-relay (hop_count == 1).
  // Paket dengan hop_count > 1 sudah melewati relay -> abaikan (cegah relay ulang / loop).
  if (incomingPkt.node_id == NODE_ID || incomingPkt.hop_count > 1) return;

  // Ukur RSSI link (pengirim -> Node 2) dari paket yang BARU diterima ini
  int rssiTerukur = info->rx_ctrl->rssi;
  incomingPkt.rssi = gabungRssi(incomingPkt.rssi, rssiTerukur);
  incomingPkt.hop_count += 1;  // Transmisi ulang Relay -> Gateway

  portENTER_CRITICAL(&relayMux);
  if (relayCount < MAX_RELAY_QUEUE) {
    int tail = (relayHead + relayCount) % MAX_RELAY_QUEUE;
    relayQueue[tail] = incomingPkt;
    relayCount++;
  } else {
    relayDropped++;
  }
  portEXIT_CRITICAL(&relayMux);
}

// Ambil satu paket dari antrean relay (true jika ada)
bool ambilPaketRelay(struct_mesh_packet &out) {
  bool ada = false;
  portENTER_CRITICAL(&relayMux);
  if (relayCount > 0) {
    out = relayQueue[relayHead];
    relayHead = (relayHead + 1) % MAX_RELAY_QUEUE;
    relayCount--;
    ada = true;
  }
  portEXIT_CRITICAL(&relayMux);
  return ada;
}

// ======================== SETUP ========================
void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println("\n=======================================================");
  Serial.println("  SIMKU - ESP32 TOPOLOGI MESH [NODE 2: RELAY NODE]");
  Serial.println("=======================================================");

  analogReadResolution(12);

  // Set ESP32 WiFi Station Mode pada channel yang ditentukan
  WiFi.mode(WIFI_STA);
  WiFi.disconnect();

  // Set channel Wi-Fi
  esp_wifi_set_promiscuous(true, true);
  esp_wifi_set_channel(WIFI_CHANNEL, WIFI_SECOND_CHAN_NONE);
  esp_wifi_set_promiscuous(false, true);

  Serial.print("MAC Address Node 2 : "); Serial.println(WiFi.macAddress());

  // Inisialisasi ESP-NOW
  if (esp_now_init() != ESP_OK) {
    Serial.println("[ERROR] Gagal inisialisasi ESP-NOW!");
    return;
  }

  esp_now_register_recv_cb(OnDataRecv);

  // Daftarkan Peer Broadcast
  esp_now_peer_info_t peerInfo = {};
  memcpy(peerInfo.peer_addr, broadcastAddress, 6);
  peerInfo.channel = WIFI_CHANNEL;
  peerInfo.encrypt = false;

  if (esp_now_add_peer(&peerInfo) != ESP_OK) {
    Serial.println("[ERROR] Gagal mendaftarkan peer broadcast!");
  } else {
    Serial.println("[OK] Peer Broadcast ESP-NOW Berhasil Terdaftar.");
  }
}

// ======================== LOOP UTAMA ========================
void loop() {
  // 0. Pancarkan ulang paket relay (dari Node 3) yang menunggu di antrean
  struct_mesh_packet relayPkt;
  while (ambilPaketRelay(relayPkt)) {
    esp_err_t res = esp_now_send(broadcastAddress, (uint8_t *) &relayPkt, sizeof(struct_mesh_packet));
    Serial.println("-------------------------------------------------------");
    Serial.print("[MESH RELAY] Paket Node #"); Serial.print(relayPkt.node_id);
    Serial.print(" Seq #"); Serial.print(relayPkt.seq_number);
    Serial.print(" diteruskan ke Root Gateway (Hop: "); Serial.print(relayPkt.hop_count);
    Serial.print(", RSSI link terukur: "); Serial.print(relayPkt.rssi);
    Serial.print(" dBm) -> "); Serial.println((res == ESP_OK) ? "Sukses Masuk Antrean Kirim" : "Gagal");
  }

  // Laporkan paket relay yang terbuang karena antrean penuh (drop di sisi relay, bukan link radio)
  unsigned long droppedNow;
  portENTER_CRITICAL(&relayMux);
  droppedNow = relayDropped;
  portEXIT_CRITICAL(&relayMux);
  if (droppedNow != relayDroppedReported) {
    Serial.print("[RELAY OVERFLOW] Antrean relay penuh! Total paket dibuang di relay: ");
    Serial.println(droppedNow);
    relayDroppedReported = droppedNow;
  }

  // Transmisi data lokal Node 2 secara berkala
  if (millis() - lastSendTime < INTERVAL_KIRIM) {
    return;
  }
  lastSendTime = millis();

  // 1. Sampling ADC sensor lokal
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

  // 2. Hitung Nilai PPM 4 Parameter Gas
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

  // 3. Susun Paket Data Node 2
  //    rssi = 0 -> "belum terukur"; Gateway mengisinya dari rx_ctrl->rssi saat menerima.
  //    hop_count = 1 -> transmisi ESP-NOW Node 2 -> Gateway; Gateway +1 untuk hop ke AP.
  struct_mesh_packet myPacket;
  myPacket.node_id    = NODE_ID;
  myPacket.co2        = ppm_co2;
  myPacket.nh3        = ppm_nh3;
  myPacket.co         = ppm_co;
  myPacket.h2         = ppm_h2;
  myPacket.rssi       = 0;   // Belum terukur (diukur di sisi penerima)
  myPacket.seq_number = seqNumber;
  myPacket.hop_count  = 1;   // Hop pertama: Node 2 -> Gateway

  // 4. Kirim Paket Data via ESP-NOW ke Root Gateway
  esp_err_t result = esp_now_send(broadcastAddress, (uint8_t *) &myPacket, sizeof(struct_mesh_packet));

  Serial.println("-------------------------------------------------------");
  Serial.print("[KIRIM LOKAL] Node #"); Serial.print(NODE_ID);
  Serial.print(" Paket #"); Serial.print(seqNumber);
  Serial.print(" -> Status ESP-NOW: ");
  Serial.println((result == ESP_OK) ? "Sukses Masuk Antrean Kirim" : "Gagal");

  Serial.print("CO2: "); Serial.print(ppm_co2, 2); Serial.print(" ppm | ");
  Serial.print("NH3: "); Serial.print(ppm_nh3, 2); Serial.print(" ppm | ");
  Serial.print("CO: "); Serial.print(ppm_co, 2); Serial.print(" ppm | ");
  Serial.print("H2: "); Serial.print(ppm_h2, 2); Serial.println(" ppm");
}

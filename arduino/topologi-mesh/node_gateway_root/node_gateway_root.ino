/* =========================================================================================
 * SISTEM PEMANTAUAN KUALITAS UDARA LINGKUNGAN (SIASAP)
 * TOPOLOGI MESH - ESP32 NODE 1 (ROOT GATEWAY / SINK NODE)
 * =========================================================================================
 * Deskripsi Peran Node 1:
 * 1. Terkoneksi ke WiFi Router ("cargo") dan server backend Laravel SIASAP.
 * 2. Membaca sensor gas lokal miliknya sendiri (Node ID 1 - Ruang Tamu, Hop Count = 1).
 * 3. Berfungsi sebagai Sink / Gateway: Mendengarkan paket data dari Node 2 dan Node 3 melalui
 *    protokol ESP-NOW, kemudian meneruskan (forward) paket tersebut ke REST API Laravel.
 * ========================================================================================= */

#include <WiFi.h>
#include <esp_now.h>
#include <esp_wifi.h>
#include <HTTPClient.h>
#include <math.h>

// ======================== KONFIGURASI JARINGAN & SERVER ========================
const char* WIFI_SSID     = "cargo";
const char* WIFI_PASSWORD = "cargocargo";

// URL Endpoint Laravel SIASAP
const char* SERVER_URL    = "http://192.168.137.1:8000/api/telemetry";

const unsigned long INTERVAL_KIRIM = 5000;

// ======================== IDENTITAS NODE ========================
#define NODE_ID 1  // Node 1 (Ruang Tamu / Root Gateway)

// ======================== PIN HARDWARE SENSOR ========================
#define PIN_MQ2   34
#define PIN_MQ135 35

const float RL        = 1.0;
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

// Antrean buffer paket yang diterima dari mesh
#define MAX_QUEUE 5
struct_mesh_packet packetQueue[MAX_QUEUE];
volatile int queueCount = 0;

// Forward Declaration
void forwardPacketToLaravel(struct_mesh_packet pkt);

// Callback saat menerima paket ESP-NOW dari Node 2 atau Node 3
void OnDataRecv(const uint8_t *mac_addr, const uint8_t *incomingData, int len) {
  if (len == sizeof(struct_mesh_packet) && queueCount < MAX_QUEUE) {
    memcpy(&packetQueue[queueCount], incomingData, sizeof(struct_mesh_packet));
    queueCount++;
  }
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
  Serial.println("  SIASAP - ESP32 TOPOLOGI MESH [NODE 1: ROOT GATEWAY]");
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
    esp_now_register_recv_cb(esp_now_recv_cb_t(OnDataRecv));
  } else {
    Serial.println("[ERROR] Gagal menginisialisasi ESP-NOW!");
  }
}

// ======================== FUNGSI FORWARD KE LARAVEL ========================
void forwardPacketToLaravel(struct_mesh_packet pkt) {
  if (WiFi.status() != WL_CONNECTED) {
    return;
  }

  HTTPClient http;
  http.begin(SERVER_URL);
  http.addHeader("Content-Type", "application/json");

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
  Serial.print(", Seq: #"); Serial.print(pkt.seq_number);
  Serial.print(") -> HTTP Response: "); Serial.println(code);

  http.end();
}

// ======================== LOOP UTAMA ========================
void loop() {
  // 1. Proses paket yang diterima dari Node 2 / Node 3 melalui antrean ESP-NOW
  while (queueCount > 0) {
    struct_mesh_packet pkt = packetQueue[0];
    
    // Geser antrean
    for (int i = 0; i < queueCount - 1; i++) {
      packetQueue[i] = packetQueue[i + 1];
    }
    queueCount--;

    // Forward ke Laravel Backend
    forwardPacketToLaravel(pkt);
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
    myPacket.rssi       = WiFi.RSSI();
    myPacket.seq_number = seqNumber;
    myPacket.hop_count  = 1;  // Root Node langsung terhubung (Hop = 1)

    // Kirim data sensor lokal ke server Laravel
    forwardPacketToLaravel(myPacket);
  }
}

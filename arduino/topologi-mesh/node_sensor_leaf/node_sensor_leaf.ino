/* =========================================================================================
 * SISTEM PEMANTAUAN KUALITAS UDARA LINGKUNGAN (SIMKU)
 * TOPOLOGI MESH - ESP32 NODE 3 (LEAF / BLIND SPOT SENSOR NODE)
 * =========================================================================================
 * Deskripsi Peran Node 3:
 * 1. Berada di area Blind Spot (terhalang dinding / jauh dari router & Node 1 Gateway).
 * 2. Membaca sensor gas lokal miliknya sendiri (Node ID 3 - Dapur).
 * 3. Mengirim paket telemetri via ESP-NOW broadcast (ditujukan ke Node 2 / Relay Node).
 * 4. Node 2 menerima paket ini, mengukur RSSI link 3->2, menaikkan Hop Count, lalu
 *    meneruskannya ke Node 1 (Root Gateway) untuk disimpan di database Laravel SIMKU.
 *
 * Definisi metrik (seragam untuk semua topologi, lihat arduino/README.md):
 * - rssi      : RSSI TERLEMAH (minimum) dari seluruh link nirkabel yang dilalui paket sampai
 *               ke WiFi Access Point. RSSI hanya bisa diukur oleh PENERIMA, sehingga node
 *               pengirim mengisi rssi = 0 ("belum terukur"); setiap penerima ESP-NOW dan
 *               Root Gateway (link WiFi ke AP) yang mengisinya dengan nilai terukur.
 * - hop_count : jumlah transmisi nirkabel sampai paket mencapai WiFi AP. Leaf mengisi 1
 *               (transmisi ESP-NOW miliknya), Relay +1, Root Gateway +1 (hop gateway -> AP).
 *               Via relay = 3 hop; bila tertangkap langsung oleh gateway = 2 hop.
 *
 * Kebutuhan: ESP32 Arduino core >= 3.0.
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
#define NODE_ID 3  // Node 3 (Dapur / Leaf / Blind Spot)

// Saluran WiFi (Harus sama dengan WiFi AP & Relay Node, default: Channel 1)
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

// Broadcast MAC Address agar otomatis tertangkap oleh Node 2 (Relay)
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

// ======================== SETUP ========================
void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println("\n=======================================================");
  Serial.println("  SIMKU - ESP32 TOPOLOGI MESH [NODE 3: LEAF / BLIND SPOT]");
  Serial.println("=======================================================");

  analogReadResolution(12);

  // Set ESP32 WiFi Station Mode pada channel yang ditentukan
  WiFi.mode(WIFI_STA);
  WiFi.disconnect();

  // Set channel Wi-Fi
  esp_wifi_set_promiscuous(true, true);
  esp_wifi_set_channel(WIFI_CHANNEL, WIFI_SECOND_CHAN_NONE);
  esp_wifi_set_promiscuous(false, true);

  Serial.print("MAC Address Node 3 : "); Serial.println(WiFi.macAddress());

  // Inisialisasi ESP-NOW
  if (esp_now_init() != ESP_OK) {
    Serial.println("[ERROR] Gagal inisialisasi ESP-NOW!");
    return;
  }

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
  // Transmisi data berkala
  if (millis() - lastSendTime < INTERVAL_KIRIM) {
    return;
  }
  lastSendTime = millis();

  // 1. Sampling ADC sensor lokal dengan averaging
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

  // 3. Susun Paket Data Node 3
  //    rssi = 0 -> "belum terukur"; diisi oleh penerima (Relay / Gateway) dari rx_ctrl->rssi.
  //    hop_count = 1 -> transmisi ESP-NOW milik Node 3; Relay & Gateway masing-masing +1.
  struct_mesh_packet myPacket;
  myPacket.node_id    = NODE_ID;
  myPacket.co2        = ppm_co2;
  myPacket.nh3        = ppm_nh3;
  myPacket.co         = ppm_co;
  myPacket.h2         = ppm_h2;
  myPacket.rssi       = 0;   // Belum terukur (diukur di sisi penerima)
  myPacket.seq_number = seqNumber;
  myPacket.hop_count  = 1;   // Hop pertama: Node 3 -> (Relay / Gateway)

  // 4. Kirim Paket Data via ESP-NOW ke Node 2 (Relay)
  esp_err_t result = esp_now_send(broadcastAddress, (uint8_t *) &myPacket, sizeof(struct_mesh_packet));

  Serial.println("-------------------------------------------------------");
  Serial.print("[LEAF TRANSMIT] Node #"); Serial.print(NODE_ID);
  Serial.print(" Paket #"); Serial.print(seqNumber);
  Serial.print(" -> Status ESP-NOW: ");
  Serial.println((result == ESP_OK) ? "Sukses Masuk Antrean Kirim" : "Gagal");

  Serial.print("CO2: "); Serial.print(ppm_co2, 2); Serial.print(" ppm | ");
  Serial.print("NH3: "); Serial.print(ppm_nh3, 2); Serial.print(" ppm | ");
  Serial.print("CO: "); Serial.print(ppm_co, 2); Serial.print(" ppm | ");
  Serial.print("H2: "); Serial.print(ppm_h2, 2); Serial.println(" ppm");
}

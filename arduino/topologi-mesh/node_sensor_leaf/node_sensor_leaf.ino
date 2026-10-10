/* =========================================================================================
 * SISTEM PEMANTAUAN KUALITAS UDARA LINGKUNGAN (SIASAP)
 * TOPOLOGI MESH - ESP32 NODE 3 (LEAF / BLIND SPOT SENSOR NODE)
 * =========================================================================================
 * Deskripsi Peran Node 3:
 * 1. Berada di area Blind Spot (terhalang dinding / jauh dari router & Node 1 Gateway).
 * 2. Membaca sensor gas lokal miliknya sendiri (Node ID 3 - Dapur).
 * 3. Mengirim paket telemetri via ESP-NOW ke Node 2 (Relay Node).
 * 4. Node 2 akan menerima paket ini, menaikkan Hop Count (menjadi 2 Hop), dan meneruskannya
 *    ke Node 1 (Root Gateway) untuk disimpan di database Laravel SIASAP.
 * ========================================================================================= */

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

// Callback status pengiriman ESP-NOW
void OnDataSent(const uint8_t *mac_addr, esp_now_send_status_t status) {
  // Status pengiriman paket
}

// ======================== SETUP ========================
void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println("\n=======================================================");
  Serial.println("  SIASAP - ESP32 TOPOLOGI MESH [NODE 3: LEAF / BLIND SPOT]");
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

  esp_now_register_send_cb(OnDataSent);

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

  // 3. Susun Paket Data Node 3 (Awal Hop Count = 1 sebelum diestafet oleh Relay)
  struct_mesh_packet myPacket;
  myPacket.node_id    = NODE_ID;
  myPacket.co2        = ppm_co2;
  myPacket.nh3        = ppm_nh3;
  myPacket.co         = ppm_co;
  myPacket.h2         = ppm_h2;
  myPacket.rssi       = -78; // Estimasi sinyal jarak jauh/terhalang dinding
  myPacket.seq_number = seqNumber;
  myPacket.hop_count  = 1;   // Node 2 (Relay) akan menaikkan nilai ini menjadi 2 Hop!

  // 4. Kirim Paket Data via ESP-NOW ke Node 2 (Relay)
  esp_err_t result = esp_now_send(broadcastAddress, (uint8_t *) &myPacket, sizeof(struct_mesh_packet));

  Serial.println("-------------------------------------------------------");
  Serial.print("[LEAF TRANSMIT] Node #"); Serial.print(NODE_ID);
  Serial.print(" Paket #"); Serial.print(seqNumber);
  Serial.print(" -> Status ESP-NOW: ");
  Serial.println((result == ESP_OK) ? "Sukses Terpancar ke Relay" : "Gagal");

  Serial.print("CO2: "); Serial.print(ppm_co2, 2); Serial.print(" ppm | ");
  Serial.print("NH3: "); Serial.print(ppm_nh3, 2); Serial.print(" ppm | ");
  Serial.print("CO: "); Serial.print(ppm_co, 2); Serial.print(" ppm | ");
  Serial.print("H2: "); Serial.print(ppm_h2, 2); Serial.println(" ppm");
}

/* =========================================================================================
 * SISTEM PEMANTAUAN KUALITAS UDARA LINGKUNGAN (SIASAP)
 * TOPOLOGI MESH - ESP32 NODE 2 (INTERMEDIATE / RELAY SENSOR NODE)
 * =========================================================================================
 * Deskripsi Peran Node 2:
 * 1. Membaca sensor gas lokal miliknya sendiri (Node ID 2 - Kamar, Hop Count = 1).
 * 2. Mengirim data lokal secara berkala ke Node 1 (Root Gateway) via ESP-NOW.
 * 3. Berfungsi sebagai RELAY / JEMBATAN: Mendengarkan paket dari Node 3 (Leaf yang berada di
 *    blind spot), menambahkan Hop Count (Hop = 2), lalu meneruskannya (retransmit) ke Node 1.
 * ========================================================================================= */

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

// Callback saat berhasil / gagal mengirim via ESP-NOW
void OnDataSent(const uint8_t *mac_addr, esp_now_send_status_t status) {
  // Status pengiriman
}

// Callback saat menerima paket dari Node 3 (Leaf / Blind Spot)
void OnDataRecv(const uint8_t *mac_addr, const uint8_t *incomingData, int len) {
  if (len == sizeof(struct_mesh_packet)) {
    struct_mesh_packet incomingPkt;
    memcpy(&incomingPkt, incomingData, sizeof(struct_mesh_packet));

    // Jika paket berasal dari Node 3, lakukan RELAY (Hop Count bertambah)
    if (incomingPkt.node_id == 3) {
      incomingPkt.hop_count += 1; // Lompatan bertambah menjadi 2 Hop!
      
      Serial.println("-------------------------------------------------------");
      Serial.print("[MESH RELAY] Menerima data dari Node #3. Meneruskan ke Root Gateway...");
      Serial.print(" (Hop Baru: "); Serial.print(incomingPkt.hop_count); Serial.println(")");

      // Teruskan paket ke Root Gateway via Broadcast ESP-NOW
      esp_now_send(broadcastAddress, (uint8_t *) &incomingPkt, sizeof(struct_mesh_packet));
    }
  }
}

// ======================== SETUP ========================
void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println("\n=======================================================");
  Serial.println("  SIASAP - ESP32 TOPOLOGI MESH [NODE 2: RELAY NODE]");
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

  esp_now_register_send_cb(OnDataSent);
  esp_now_register_recv_cb(esp_now_recv_cb_t(OnDataRecv));

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

  // 3. Susun Paket Data Node 2 (Hop Count = 1)
  struct_mesh_packet myPacket;
  myPacket.node_id    = NODE_ID;
  myPacket.co2        = ppm_co2;
  myPacket.nh3        = ppm_nh3;
  myPacket.co         = ppm_co;
  myPacket.h2         = ppm_h2;
  myPacket.rssi       = -60; // Estimasi kekuatan sinyal radio ESP-NOW
  myPacket.seq_number = seqNumber;
  myPacket.hop_count  = 1;   // Transmisi langsung ke Node 1 (1 Hop)

  // 4. Kirim Paket Data via ESP-NOW ke Root Gateway
  esp_err_t result = esp_now_send(broadcastAddress, (uint8_t *) &myPacket, sizeof(struct_mesh_packet));

  Serial.println("-------------------------------------------------------");
  Serial.print("[KIRIM LOKAL] Node #"); Serial.print(NODE_ID);
  Serial.print(" Paket #"); Serial.print(seqNumber);
  Serial.print(" -> Status ESP-NOW: ");
  Serial.println((result == ESP_OK) ? "Sukses Terpancar" : "Gagal");

  Serial.print("CO2: "); Serial.print(ppm_co2, 2); Serial.print(" ppm | ");
  Serial.print("NH3: "); Serial.print(ppm_nh3, 2); Serial.print(" ppm | ");
  Serial.print("CO: "); Serial.print(ppm_co, 2); Serial.print(" ppm | ");
  Serial.print("H2: "); Serial.print(ppm_h2, 2); Serial.println(" ppm");
}

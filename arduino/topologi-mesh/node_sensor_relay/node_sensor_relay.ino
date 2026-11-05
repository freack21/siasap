/* =========================================================================================
 * SIMKU - TOPOLOGI MESH: ESP32 NODE 2 (RELAY)
 * =========================================================================================
 * Deskripsi peran Node 2:
 * 1. Membaca sensor MQ-135 dan MQ-2 miliknya sendiri dengan logika yang SAMA dengan
 *    arduino/simku_monitor: pemanasan 30 detik, baseline = rata-rata 50 pembacaan ADC, lalu
 *    konversi Solihat, S. M., Salsabila, S., & Susilawati, S. (2026), JITET 14(1), hlm. 1596:
 *      ppm = max(0, adc - baseline) * (5.0 / 4096.0) * k,  k: CO2 100, NH3 80, CO 50, H2 30.
 *    Nilai adalah INDIKATOR RELATIF terhadap kondisi udara saat perangkat dinyalakan.
 *    Data lokal dikirim ke Node 1 (Root Gateway) via ESP-NOW dan masuk aplikasi sebagai AIR-002.
 * 2. Berfungsi sebagai RELAY: paket dari Node 3 (Leaf) diukur RSSI-nya, hop_count +1, lalu
 *    dipancarkan ulang ke Root Gateway. Paket dengan hop_count > 1 (sudah di-relay) diabaikan.
 *    Relay tetap bekerja selama sensor lokal masih memanas.
 *
 * Node ini tidak punya WiFi/NTP. Waktu pembacaan (timestamp) diisi Root Gateway dari jam
 * NTP-nya saat paket diterima.
 *
 * Definisi metrik (lihat arduino/README.md):
 * - rssi      : RSSI terlemah sampai WiFi AP; pengirim mengisi 0, penerima menggabungkannya.
 * - hop_count : jumlah transmisi nirkabel sampai WiFi AP.
 *
 * Kebutuhan: ESP32 Arduino core versi 3 atau lebih baru.
 * ========================================================================================= */

#include <esp_arduino_version.h>
#if !defined(ESP_ARDUINO_VERSION_MAJOR) || ESP_ARDUINO_VERSION_MAJOR < 3
#error "Butuh ESP32 Arduino core >= 3.0 (Boards Manager: esp32 by Espressif Systems versi 3.x)."
#endif

#include <WiFi.h>
#include <esp_now.h>
#include <esp_wifi.h>

// ======================== IDENTITAS NODE ========================
#define NODE_ID 2  // dikirim ke aplikasi sebagai AIR-002

// Kanal WiFi: harus sama dengan kanal WiFi AP yang dipakai Root Gateway.
#define WIFI_CHANNEL 1

const unsigned long INTERVAL_KIRIM = 5000;

// ======================== PIN SENSOR (sesuai rangkaian perangkat; dapat diubah) ========================
const int PIN_MQ135 = 35;  // CO2, NH3 (AO MQ-135 ke GPIO35)
const int PIN_MQ2   = 34;  // CO, H2 (AO MQ-2 ke GPIO34)

// ======================== PARAMETER KONVERSI & BASELINE ========================
const unsigned long DURASI_PEMANASAN_MS = 30000;  // pemanasan sensor 30 detik
const int JUMLAH_SAMPEL_BASELINE        = 50;     // rata-rata 50 pembacaan
const int JEDA_SAMPEL_BASELINE_MS       = 100;

const float K_CO2 = 100.0;  // MQ-135
const float K_NH3 = 80.0;   // MQ-135
const float K_CO  = 50.0;   // MQ-2
const float K_H2  = 30.0;   // MQ-2

// ======================== DIAGNOSIS ========================
const bool TAMPILKAN_ADC_MENTAH = true;
const int JUMLAH_SAMPEL_SEBARAN = 10;
const int JEDA_SAMPEL_SEBARAN_MS = 2;

bool sensorSiap = false;          // true setelah pemanasan dan baseline selesai
unsigned long waktuMenyala = 0;
int baselineMq135 = 0;
int baselineMq2   = 0;
unsigned long seqNumber = 0;
unsigned long lastSendTime = 0;

uint8_t broadcastAddress[] = {0xFF, 0xFF, 0xFF, 0xFF, 0xFF, 0xFF};

// ======================== STRUKTUR PAKET MESH ESP-NOW ========================
// Harus identik di Root Gateway, Relay, dan Leaf.
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

// ======================== FUNGSI SENSOR ========================

/**
 * Konversi nilai ADC ke indikator ppm relatif (Solihat dkk., 2026, hlm. 1596).
 * Selisih negatif (lebih bersih dari baseline) dipotong menjadi 0.
 */
float hitungPpm(int nilaiAdc, int baseline, float konstanta) {
  int selisih = nilaiAdc - baseline;
  if (selisih < 0) {
    selisih = 0;
  }
  return (float)selisih * (5.0 / 4096.0) * konstanta;
}

int rataRataBaseline(int pin) {
  long total = 0;
  for (int i = 0; i < JUMLAH_SAMPEL_BASELINE; i++) {
    total += analogRead(pin);
    delay(JEDA_SAMPEL_BASELINE_MS);
  }
  return (int)(total / JUMLAH_SAMPEL_BASELINE);
}

/**
 * Cetak ADC mentah satu sensor untuk diagnosis kabel/daya. Rentang besar dalam beberapa
 * milidetik menandakan pin mengambang atau daya tidak stabil.
 */
void cetakAdcMentah(const char* nama, int pin, int nilaiAdc, int baseline) {
  int minimum = 4095;
  int maksimum = 0;
  for (int i = 0; i < JUMLAH_SAMPEL_SEBARAN; i++) {
    int sampel = analogRead(pin);
    if (sampel < minimum) {
      minimum = sampel;
    }
    if (sampel > maksimum) {
      maksimum = sampel;
    }
    delay(JEDA_SAMPEL_SEBARAN_MS);
  }
  Serial.print(nama);
  Serial.print(" adc="); Serial.print(nilaiAdc);
  Serial.print(" base="); Serial.print(baseline);
  Serial.print(" selisih="); Serial.print(nilaiAdc - baseline);
  Serial.print(" ("); Serial.print(analogReadMilliVolts(pin)); Serial.print(" mV)");
  Serial.print(" | "); Serial.print(JUMLAH_SAMPEL_SEBARAN); Serial.print("x: min="); Serial.print(minimum);
  Serial.print(" maks="); Serial.print(maksimum);
  Serial.print(" rentang="); Serial.println(maksimum - minimum);
}

// ======================== ANTREAN RELAY ========================
// Callback ESP-NOW berjalan di task WiFi, sehingga paket yang akan di-relay dimasukkan ke
// antrean dan dipancarkan ulang dari loop() (bukan dari dalam callback).
#define MAX_RELAY_QUEUE 10
struct_mesh_packet relayQueue[MAX_RELAY_QUEUE];
volatile int relayHead  = 0;
volatile int relayCount = 0;
volatile unsigned long relayDropped = 0;  // paket dibuang karena antrean penuh
unsigned long relayDroppedReported = 0;
portMUX_TYPE relayMux = portMUX_INITIALIZER_UNLOCKED;

// Gabungkan RSSI terukur ke paket: ambil nilai TERLEMAH (minimum) sepanjang jalur.
// rssi == 0 berarti belum ada link yang terukur.
int gabungRssi(int rssiPaket, int rssiTerukur) {
  if (rssiPaket == 0) return rssiTerukur;
  return (rssiTerukur < rssiPaket) ? rssiTerukur : rssiPaket;
}

void OnDataRecv(const esp_now_recv_info_t *info, const uint8_t *incomingData, int len) {
  if (len != sizeof(struct_mesh_packet)) return;

  struct_mesh_packet paket;
  memcpy(&paket, incomingData, sizeof(struct_mesh_packet));

  // Hanya relay paket node lain yang belum pernah di-relay (cegah relay ulang / loop).
  if (paket.node_id == NODE_ID || paket.hop_count > 1) return;

  paket.rssi = gabungRssi(paket.rssi, info->rx_ctrl->rssi);
  paket.hop_count += 1;  // transmisi ulang Relay -> Gateway

  portENTER_CRITICAL(&relayMux);
  if (relayCount < MAX_RELAY_QUEUE) {
    relayQueue[(relayHead + relayCount) % MAX_RELAY_QUEUE] = paket;
    relayCount++;
  } else {
    relayDropped++;
  }
  portEXIT_CRITICAL(&relayMux);
}

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
  Serial.println("  SIMKU - ESP32 TOPOLOGI MESH [NODE 2: RELAY]");
  Serial.print("  Device ID : AIR-00"); Serial.println(NODE_ID);
  Serial.println("=======================================================");

  analogReadResolution(12);

  WiFi.mode(WIFI_STA);
  WiFi.disconnect();
  esp_wifi_set_promiscuous(true);
  esp_wifi_set_channel(WIFI_CHANNEL, WIFI_SECOND_CHAN_NONE);
  esp_wifi_set_promiscuous(false);
  Serial.print("MAC Address Node 2 : "); Serial.println(WiFi.macAddress());

  if (esp_now_init() != ESP_OK) {
    Serial.println("[ERROR] Gagal inisialisasi ESP-NOW!");
  } else {
    esp_now_register_recv_cb(OnDataRecv);
    esp_now_peer_info_t peerInfo = {};
    memcpy(peerInfo.peer_addr, broadcastAddress, 6);
    peerInfo.channel = WIFI_CHANNEL;
    peerInfo.encrypt = false;
    if (esp_now_add_peer(&peerInfo) != ESP_OK) {
      Serial.println("[ERROR] Gagal mendaftarkan peer broadcast!");
    } else {
      Serial.println("[OK] Peer broadcast ESP-NOW terdaftar.");
    }
  }

  waktuMenyala = millis();
  Serial.println("Memanaskan sensor selama 30 detik (relay tetap berjalan)...");
}

// ======================== LOOP UTAMA ========================
void loop() {
  // 1. Pancarkan ulang paket Node 3 yang menunggu di antrean
  struct_mesh_packet relayPkt;
  while (ambilPaketRelay(relayPkt)) {
    esp_err_t res = esp_now_send(broadcastAddress, (uint8_t *) &relayPkt, sizeof(struct_mesh_packet));
    Serial.print("[RELAY] Node #"); Serial.print(relayPkt.node_id);
    Serial.print(" Seq #"); Serial.print(relayPkt.seq_number);
    Serial.print(" (Hop: "); Serial.print(relayPkt.hop_count);
    Serial.print(", RSSI: "); Serial.print(relayPkt.rssi);
    Serial.print(" dBm) -> "); Serial.println((res == ESP_OK) ? "masuk antrean kirim" : "gagal");
  }

  unsigned long droppedNow;
  portENTER_CRITICAL(&relayMux);
  droppedNow = relayDropped;
  portEXIT_CRITICAL(&relayMux);
  if (droppedNow != relayDroppedReported) {
    Serial.print("[RELAY OVERFLOW] Antrean relay penuh! Total paket dibuang di relay: ");
    Serial.println(droppedNow);
    relayDroppedReported = droppedNow;
  }

  // 2. Pemanasan dan baseline sensor lokal (tanpa menghentikan relay)
  if (!sensorSiap) {
    if (millis() - waktuMenyala < DURASI_PEMANASAN_MS) {
      return;
    }
    Serial.println("Mengukur baseline (udara saat perangkat menyala)...");
    baselineMq135 = rataRataBaseline(PIN_MQ135);
    baselineMq2   = rataRataBaseline(PIN_MQ2);
    Serial.print("Baseline MQ-135 : "); Serial.println(baselineMq135);
    Serial.print("Baseline MQ-2   : "); Serial.println(baselineMq2);
    sensorSiap = true;
  }

  // 3. Kirim data lokal Node 2 secara berkala
  if (millis() - lastSendTime < INTERVAL_KIRIM) {
    return;
  }
  lastSendTime = millis();

  int adcMq135 = analogRead(PIN_MQ135);
  int adcMq2   = analogRead(PIN_MQ2);

  if (TAMPILKAN_ADC_MENTAH) {
    Serial.print("[ADC] "); cetakAdcMentah("MQ-135", PIN_MQ135, adcMq135, baselineMq135);
    Serial.print("[ADC] "); cetakAdcMentah("MQ-2  ", PIN_MQ2, adcMq2, baselineMq2);
  }

  seqNumber++;

  // rssi = 0: belum terukur, diisi Gateway. hop_count = 1: transmisi Node 2 -> Gateway.
  struct_mesh_packet paket;
  paket.node_id    = NODE_ID;
  paket.co2        = hitungPpm(adcMq135, baselineMq135, K_CO2);
  paket.nh3        = hitungPpm(adcMq135, baselineMq135, K_NH3);
  paket.co         = hitungPpm(adcMq2, baselineMq2, K_CO);
  paket.h2         = hitungPpm(adcMq2, baselineMq2, K_H2);
  paket.rssi       = 0;
  paket.seq_number = seqNumber;
  paket.hop_count  = 1;

  esp_err_t hasil = esp_now_send(broadcastAddress, (uint8_t *) &paket, sizeof(struct_mesh_packet));

  Serial.print("[KIRIM ESP-NOW] Seq #"); Serial.print(seqNumber);
  Serial.print(" CO2="); Serial.print(paket.co2, 2);
  Serial.print(" NH3="); Serial.print(paket.nh3, 2);
  Serial.print(" CO="); Serial.print(paket.co, 2);
  Serial.print(" H2="); Serial.print(paket.h2, 2);
  Serial.print(" -> "); Serial.println((hasil == ESP_OK) ? "masuk antrean kirim" : "gagal");
}

/* =========================================================================================
 * SIMKU - TOPOLOGI MESH: ESP32 NODE 3 (LEAF / BLIND SPOT)
 * =========================================================================================
 * Deskripsi peran Node 3:
 * 1. Berada di area tanpa jangkauan WiFi (blind spot).
 * 2. Membaca sensor MQ-135 dan MQ-2 miliknya sendiri dengan logika yang SAMA dengan
 *    arduino/simku_monitor: pemanasan 30 detik, baseline = rata-rata 50 pembacaan ADC, lalu
 *    konversi Solihat, S. M., Salsabila, S., & Susilawati, S. (2026), JITET 14(1), hlm. 1596:
 *      ppm = max(0, adc - baseline) * (5.0 / 4096.0) * k,  k: CO2 100, NH3 80, CO 50, H2 30.
 *    Nilai adalah INDIKATOR RELATIF terhadap kondisi udara saat perangkat dinyalakan.
 * 3. Memancarkan paket via ESP-NOW broadcast. Node 2 (Relay) meneruskannya ke Node 1
 *    (Root Gateway), yang mengirimnya ke aplikasi SIMKU sebagai device_id AIR-003.
 *
 * Node ini tidak punya WiFi/NTP. Waktu pembacaan (timestamp) diisi Root Gateway dari jam
 * NTP-nya saat paket diterima.
 *
 * Definisi metrik (lihat arduino/README.md):
 * - rssi      : RSSI terlemah sampai WiFi AP. Pengirim mengisi 0 ("belum terukur"); setiap
 *               penerima ESP-NOW menggabungkannya dengan RSSI yang diukurnya.
 * - hop_count : jumlah transmisi nirkabel sampai WiFi AP. Leaf mengisi 1, Relay +1,
 *               Root Gateway +1 (via relay = 3; tertangkap langsung oleh gateway = 2).
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
#define NODE_ID 3  // dikirim ke aplikasi sebagai AIR-003

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
  uint32_t umur_ms;  // total lama paket ditahan di relay sebelum dipancarkan ulang
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

// ======================== SETUP ========================
void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println("\n=======================================================");
  Serial.println("  SIMKU - ESP32 TOPOLOGI MESH [NODE 3: LEAF / BLIND SPOT]");
  Serial.print("  Device ID : AIR-00"); Serial.println(NODE_ID);
  Serial.println("=======================================================");

  analogReadResolution(12);

  WiFi.mode(WIFI_STA);
  WiFi.disconnect();
  esp_wifi_set_promiscuous(true);
  esp_wifi_set_channel(WIFI_CHANNEL, WIFI_SECOND_CHAN_NONE);
  esp_wifi_set_promiscuous(false);
  Serial.print("MAC Address Node 3 : "); Serial.println(WiFi.macAddress());

  if (esp_now_init() != ESP_OK) {
    Serial.println("[ERROR] Gagal inisialisasi ESP-NOW!");
  } else {
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

  Serial.println("Memanaskan sensor selama 30 detik...");
  delay(DURASI_PEMANASAN_MS);

  Serial.println("Mengukur baseline (udara saat perangkat menyala)...");
  baselineMq135 = rataRataBaseline(PIN_MQ135);
  baselineMq2   = rataRataBaseline(PIN_MQ2);
  Serial.print("Baseline MQ-135 : "); Serial.println(baselineMq135);
  Serial.print("Baseline MQ-2   : "); Serial.println(baselineMq2);
}

// ======================== LOOP UTAMA ========================
void loop() {
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

  // rssi = 0: belum terukur, diisi penerima. hop_count = 1: transmisi Node 3 -> Relay/Gateway.
  struct_mesh_packet paket;
  paket.node_id    = NODE_ID;
  paket.co2        = hitungPpm(adcMq135, baselineMq135, K_CO2);
  paket.nh3        = hitungPpm(adcMq135, baselineMq135, K_NH3);
  paket.co         = hitungPpm(adcMq2, baselineMq2, K_CO);
  paket.h2         = hitungPpm(adcMq2, baselineMq2, K_H2);
  paket.rssi       = 0;
  paket.seq_number = seqNumber;
  paket.hop_count  = 1;
  paket.umur_ms    = 0;

  esp_err_t hasil = esp_now_send(broadcastAddress, (uint8_t *) &paket, sizeof(struct_mesh_packet));

  Serial.print("[KIRIM ESP-NOW] Seq #"); Serial.print(seqNumber);
  Serial.print(" CO2="); Serial.print(paket.co2, 2);
  Serial.print(" NH3="); Serial.print(paket.nh3, 2);
  Serial.print(" CO="); Serial.print(paket.co, 2);
  Serial.print(" H2="); Serial.print(paket.h2, 2);
  Serial.print(" -> "); Serial.println((hasil == ESP_OK) ? "masuk antrean kirim" : "gagal");
}

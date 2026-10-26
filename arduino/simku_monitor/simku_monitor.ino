/* =========================================================================================
 * SIMKU - FIRMWARE PERANGKAT TUNGGAL (ESP32, WiFi LANGSUNG)
 * =========================================================================================
 * Deskripsi:
 * Satu perangkat ESP32 membaca sensor MQ-135 (CO2, NH3) dan MQ-2 (CO, H2), lalu mengirim
 * data tiap INTERVAL_KIRIM ke aplikasi SIMKU lewat HTTP POST /api/telemetry.
 *
 * CATATAN PENTING (tanggung jawab penelitian IoT):
 * Penyesuaian rumus konversi dan kalibrasi sensor adalah tanggung jawab penelitian IoT dan
 * perlu ditinjau oleh rekan IoT sebelum dipakai untuk pengambilan data resmi.
 *
 * Rumus konversi mengikuti Solihat, S. M., Salsabila, S., & Susilawati, S. (2026), JITET
 * 14(1), hlm. 1596 (fungsi calculatePPM): selisih ADC terhadap baseline dikalikan
 * (5.0 / 4096.0) lalu dikalikan konstanta gas (CO2 100, NH3 80, CO 50, H2 30).
 * Nilai yang dihasilkan adalah INDIKATOR RELATIF terhadap kondisi udara saat perangkat
 * dinyalakan (baseline), bukan konsentrasi absolut hasil alat ukur referensi. Ambang
 * kategori kualitas udara di aplikasi mengacu pada sumber yang sama.
 *
 * Kebutuhan: ESP32 Arduino core versi 3 atau lebih baru.
 * ========================================================================================= */

#include <WiFi.h>
#include <HTTPClient.h>
#include <time.h>

// ======================== KONFIGURASI JARINGAN & SERVER ========================
const char* WIFI_SSID     = "ISI_SSID_WIFI";
const char* WIFI_PASSWORD = "ISI_PASSWORD_WIFI";

// Ganti <ip> dengan IP komputer/server yang menjalankan Laravel.
const char* SERVER_URL    = "http://<ip>:8000/api/telemetry";

// Opsional: isi dengan TELEMETRY_API_KEY di .env Laravel. Kosongkan ("") bila tidak dipakai.
const char* API_KEY       = "";

const char* DEVICE_ID     = "AIR-001";

// Interval pengiriman data (milidetik): 5 detik.
const unsigned long INTERVAL_KIRIM = 5000;

// ======================== PIN SENSOR (sesuai paper; dapat diubah) ========================
const int PIN_MQ135 = 33;  // CO2, NH3
const int PIN_MQ2   = 32;  // CO, H2

// ======================== PARAMETER KONVERSI & BASELINE ========================
const unsigned long DURASI_PEMANASAN_MS = 30000;  // pemanasan sensor 30 detik
const int JUMLAH_SAMPEL_BASELINE        = 50;     // rata-rata 50 pembacaan
const int JEDA_SAMPEL_BASELINE_MS       = 100;

const float K_CO2 = 100.0;  // MQ-135
const float K_NH3 = 80.0;   // MQ-135
const float K_CO  = 50.0;   // MQ-2
const float K_H2  = 30.0;   // MQ-2

// ======================== DIAGNOSIS ========================
// true: setiap kiriman juga mencetak ADC mentah, baseline, tegangan pin, dan sebaran
// 10 pembacaan cepat. Hanya untuk Serial Monitor; nilai yang dikirim ke server tidak berubah.
const bool TAMPILKAN_ADC_MENTAH = true;
const int JUMLAH_SAMPEL_SEBARAN = 10;
const int JEDA_SAMPEL_SEBARAN_MS = 2;

// Tahun minimum agar waktu dianggap sudah tersinkron NTP (sebelum sinkron, tahun = 1970).
const int TAHUN_MINIMUM_VALID = 2024;

int baselineMq135 = 0;
int baselineMq2   = 0;
unsigned long lastSendTime = 0;

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
 * Cetak ADC mentah satu sensor untuk diagnosis kabel/daya, contoh:
 * "MQ-135 adc=2450 base=450 selisih=2000 (1975 mV) | 10x: min=2380 maks=2510 rentang=130"
 * Rentang besar dalam beberapa milidetik menandakan pin mengambang atau daya tidak stabil.
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

// ======================== FUNGSI WAKTU ========================

/**
 * Mengisi buffer dengan waktu lokal (WIB) berformat "Y-m-d H:i:s".
 * Mengembalikan false bila waktu belum tersinkron lewat NTP.
 */
bool ambilTimestamp(char* buffer, size_t ukuran) {
  struct tm waktu;
  if (!getLocalTime(&waktu, 0) || (waktu.tm_year + 1900) < TAHUN_MINIMUM_VALID) {
    return false;
  }
  strftime(buffer, ukuran, "%Y-%m-%d %H:%M:%S", &waktu);
  return true;
}

// ======================== SETUP ========================
void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println("\n=======================================================");
  Serial.println("  SIMKU - ESP32 PERANGKAT TUNGGAL");
  Serial.print("  Device ID : "); Serial.println(DEVICE_ID);
  Serial.println("=======================================================");

  analogReadResolution(12);

  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  Serial.print("Menghubungkan ke WiFi ["); Serial.print(WIFI_SSID); Serial.print("]");
  int percobaan = 0;
  while (WiFi.status() != WL_CONNECTED && percobaan < 30) {
    delay(500);
    Serial.print(".");
    percobaan++;
  }
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n[OK] Terhubung ke WiFi.");
    Serial.print("IP Perangkat : "); Serial.println(WiFi.localIP());
  } else {
    Serial.println("\n[PERINGATAN] Gagal terhubung ke WiFi, akan dicoba lagi di loop.");
  }

  // Sinkronisasi waktu NTP, zona WIB (UTC+7).
  configTime(7 * 3600, 0, "pool.ntp.org", "time.google.com");

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

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[PERINGATAN] WiFi terputus, mencoba menyambung ulang...");
    WiFi.reconnect();
    return;
  }

  char timestamp[20];
  if (!ambilTimestamp(timestamp, sizeof(timestamp))) {
    Serial.println("[MENUNGGU] Waktu belum tersinkron NTP, data belum dikirim.");
    return;
  }

  int adcMq135 = analogRead(PIN_MQ135);
  int adcMq2   = analogRead(PIN_MQ2);

  if (TAMPILKAN_ADC_MENTAH) {
    Serial.print("[ADC] "); cetakAdcMentah("MQ-135", PIN_MQ135, adcMq135, baselineMq135);
    Serial.print("[ADC] "); cetakAdcMentah("MQ-2  ", PIN_MQ2, adcMq2, baselineMq2);
  }

  float co2 = hitungPpm(adcMq135, baselineMq135, K_CO2);
  float nh3 = hitungPpm(adcMq135, baselineMq135, K_NH3);
  float co  = hitungPpm(adcMq2, baselineMq2, K_CO);
  float h2  = hitungPpm(adcMq2, baselineMq2, K_H2);

  String payload = "{";
  payload += "\"device_id\":\"" + String(DEVICE_ID) + "\",";
  payload += "\"timestamp\":\"" + String(timestamp) + "\",";
  payload += "\"co2_ppm\":" + String(co2, 2) + ",";
  payload += "\"nh3_ppm\":" + String(nh3, 2) + ",";
  payload += "\"co_ppm\":"  + String(co, 2) + ",";
  payload += "\"h2_ppm\":"  + String(h2, 2);
  payload += "}";

  HTTPClient http;
  http.begin(SERVER_URL);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");
  if (strlen(API_KEY) > 0) {
    http.addHeader("X-Api-Key", API_KEY);
  }

  int kodeRespons = http.POST(payload);
  if (kodeRespons > 0) {
    Serial.print("[KIRIM] "); Serial.print(payload);
    Serial.print(" -> HTTP "); Serial.println(kodeRespons);
    if (kodeRespons == 422) {
      Serial.print("[GALAT 422] "); Serial.println(http.getString());
    }
  } else {
    Serial.print("[GAGAL] Pengiriman data: "); Serial.println(http.errorToString(kodeRespons));
  }
  http.end();
}

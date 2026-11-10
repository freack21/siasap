/* =========================================================================================
 * SIMKU - TOPOLOGI STAR: ESP32 SENSOR NODE (WiFi LANGSUNG)
 * =========================================================================================
 * Deskripsi:
 * Setiap node ESP32 terhubung langsung ke WiFi Access Point dan mengirim data tiap
 * INTERVAL_KIRIM ke halaman uji topologi SIMKU lewat HTTP POST /api/uji-topologi (data
 * terpisah dari halaman SIMKU). Satu sketsa ini dipakai untuk semua node star; yang dibedakan
 * hanya NODE_ID (menjadi device_id AIR-00x).
 *
 * Logika pembacaan sensor SAMA dengan arduino/simku_monitor (perangkat tunggal):
 * pemanasan 30 detik, baseline = rata-rata 50 pembacaan ADC, lalu konversi Solihat, S. M.,
 * Salsabila, S., & Susilawati, S. (2026), JITET 14(1), hlm. 1596 (fungsi calculatePPM):
 *   ppm = max(0, adc - baseline) * (5.0 / 4096.0) * k,  k: CO2 100, NH3 80, CO 50, H2 30.
 * Nilai adalah INDIKATOR RELATIF terhadap kondisi udara saat perangkat dinyalakan, bukan
 * konsentrasi absolut. Penyesuaian rumus dan kalibrasi adalah tanggung jawab penelitian IoT.
 *
 * Payload mengikuti format aplikasi (device_id, timestamp, co2_ppm, nh3_ppm, co_ppm, h2_ppm).
 * Metrik jaringan (rssi, seq_number, hop_count, topology_type) dan timestamp_ms ikut dikirim
 * dan diringkas (PDR, packet loss, delay, jitter, throughput, RSSI, hop) di halaman uji
 * topologi. Node 1 (AIR-001) juga mengirim ke monitoring SIMKU bila KIRIM_KE_SIMKU = true.
 * - rssi      : RSSI terlemah sampai WiFi AP. Star hanya punya satu link (Node -> AP).
 * - hop_count : jumlah transmisi nirkabel sampai WiFi AP. Star = 1.
 *
 * Handshake: setelah tersambung ke WiFi (dan setiap kali tersambung ulang atau IP berubah), node
 * mengirim device_id, IP, MAC, topologi, dan RSSI ke HANDSHAKE_URL. Handshake dicoba paling
 * banyak 3 kali; bila tetap gagal, Serial Monitor mencetak penyebabnya dan data tetap dikirim.
 *
 * Kebutuhan: ESP32 Arduino core versi 3 atau lebih baru.
 * ========================================================================================= */

#include <WiFi.h>
#include <HTTPClient.h>
#include <time.h>
#include <sys/time.h>
#include <esp_system.h>

// ======================== KONFIGURASI JARINGAN & SERVER ========================
const char* WIFI_SSID     = "cargo";
const char* WIFI_PASSWORD = "cargocargo";

// Endpoint uji topologi (tersembunyi, data terpisah dari SIMKU): /api/<TOPOLOGY_TEST_PATH>.
// Bawaan: server lokal 192.168.137.1:8000 (laptop hotspot) agar lalu lintas bisa direkam Wireshark.
const char* SERVER_URL    = "http://192.168.137.1:8000/api/uji-topologi";
// Handshake: setelah tersambung WiFi, node melaporkan IP-nya sebelum mengirim data.
const char* HANDSHAKE_URL = "http://192.168.137.1:8000/api/uji-topologi/handshake";

// Node 1 (AIR-001) juga mengirim data yang sama ke monitoring SIMKU (ruangan RC1TI) bila
// KIRIM_KE_SIMKU = true. SIMKU hanya menerima AIR-001; node lain hanya ke endpoint uji.
// Pengiriman ke SIMKU dilakukan sesudah pengiriman uji sehingga tidak menambah delay uji.
const bool  KIRIM_KE_SIMKU = true;
const char* SIMKU_URL     = "https://siasap.ptsigmamutuindonesia.com/api/telemetry";

// Server waktu NTP. Hotspot laptop harus meneruskan internet; bila tidak, jalankan server NTP di
// laptop dan isi NTP_SERVER dengan "192.168.137.1". Data tidak dikirim sebelum waktu tersinkron.
const char* NTP_SERVER    = "pool.ntp.org";

// Opsional: isi dengan TELEMETRY_API_KEY di .env Laravel. Kosongkan ("") bila tidak dipakai.
const char* API_KEY       = "";

// Interval pengiriman data (milidetik): 5 detik.
const unsigned long INTERVAL_KIRIM = 5000;

// ======================== IDENTITAS NODE ========================
// 1 -> AIR-001, 2 -> AIR-002, 3 -> AIR-003.
#define NODE_ID 1

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
// true: setiap kiriman juga mencetak ADC mentah, baseline, tegangan pin, dan sebaran
// 10 pembacaan cepat. Hanya untuk Serial Monitor; nilai yang dikirim tidak berubah.
const bool TAMPILKAN_ADC_MENTAH = true;
const int JUMLAH_SAMPEL_SEBARAN = 10;
const int JEDA_SAMPEL_SEBARAN_MS = 2;

// Tahun minimum agar waktu dianggap sudah tersinkron NTP (sebelum sinkron, tahun = 1970).
const int TAHUN_MINIMUM_VALID = 2024;

char deviceId[12];
int baselineMq135 = 0;
int baselineMq2   = 0;
unsigned long seqNumber = 0;
// Handshake dicoba paling banyak MAKS_PERCOBAAN_HANDSHAKE kali setiap kali WiFi tersambung.
// Bila tetap gagal, handshake dilewati dan data tetap dikirim (server mencatat IP dari paket data).
const int MAKS_PERCOBAAN_HANDSHAKE = 3;
bool handshakeSelesai = false;   // true bila berhasil atau sudah dilewati
int percobaanHandshake = 0;
String ipHandshake = "";         // IP saat handshake berhasil ("" bila belum/dilewati)
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
 * Mengisi buffer dengan waktu lokal (WIB) berformat "Y-m-d H:i:s" dan milidetik-nya
 * (timestamp_ms, untuk menghitung delay). Mengembalikan false bila waktu belum tersinkron NTP.
 */
bool ambilTimestamp(char* buffer, size_t ukuran, int* milidetik) {
  struct timeval sekarang;
  gettimeofday(&sekarang, nullptr);
  struct tm waktu;
  localtime_r(&sekarang.tv_sec, &waktu);
  if ((waktu.tm_year + 1900) < TAHUN_MINIMUM_VALID) {
    return false;
  }
  strftime(buffer, ukuran, "%Y-%m-%d %H:%M:%S", &waktu);
  *milidetik = (int) (sekarang.tv_usec / 1000);
  return true;
}

// ======================== FUNGSI KIRIM ========================

/**
 * Kirim payload JSON ke satu alamat dan cetak hasilnya.
 */
void kirimPost(const char* url, const String &payload, const char* label) {
  HTTPClient http;
  http.begin(url);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");
  if (strlen(API_KEY) > 0) {
    http.addHeader("X-Api-Key", API_KEY);
  }

  int kodeRespons = http.POST(payload);
  if (kodeRespons > 0) {
    Serial.print("["); Serial.print(label); Serial.print("] HTTP "); Serial.println(kodeRespons);
    if (kodeRespons == 422) {
      Serial.print("[GALAT 422] "); Serial.println(http.getString());
    }
  } else {
    Serial.print("[GAGAL "); Serial.print(label); Serial.print("] "); Serial.println(http.errorToString(kodeRespons));
  }
  http.end();
}

/**
 * Cetak kemungkinan penyebab handshake gagal agar mudah diperbaiki dari Serial Monitor.
 */
void jelaskanGagalHandshake(int kode) {
  Serial.print("[HANDSHAKE GAGAL] ");
  if (kode < 0) {
    Serial.println("server tidak terjangkau. Jalankan 'php artisan serve --host=0.0.0.0 --port=8000',"
                   " izinkan port 8000 di firewall, dan cek HANDSHAKE_URL.");
  } else if (kode == 404) {
    Serial.println("alamat tidak ditemukan. Pastikan server sudah git pull dan path sama dengan TOPOLOGY_TEST_PATH.");
  } else if (kode == 401) {
    Serial.println("API_KEY tidak sama dengan TELEMETRY_API_KEY di .env server.");
  } else if (kode == 422) {
    Serial.println("device_id tidak dikenal endpoint uji (lihat config/topology_test.php).");
  } else if (kode == 429) {
    Serial.println("terlalu banyak permintaan; tunggu sebentar.");
  } else if (kode >= 500) {
    Serial.println("galat server. Jalankan 'php artisan migrate' lalu cek storage/logs/laravel.log.");
  } else {
    Serial.println("jawaban server tidak terduga.");
  }
}

/**
 * Cetak alasan ESP32 menyala ulang. Handshake yang terus berulang bisa berarti board terus
 * restart (mis. daya kurang saat WiFi aktif), bukan handshake yang gagal.
 */
void cetakAlasanRestart() {
  esp_reset_reason_t alasan = esp_reset_reason();
  Serial.print("Alasan menyala: ");
  switch (alasan) {
    case ESP_RST_POWERON:  Serial.println("daya dinyalakan"); break;
    case ESP_RST_SW:       Serial.println("restart oleh program"); break;
    case ESP_RST_PANIC:    Serial.println("PANIC/crash program"); break;
    case ESP_RST_INT_WDT:
    case ESP_RST_TASK_WDT:
    case ESP_RST_WDT:      Serial.println("watchdog (program macet)"); break;
    case ESP_RST_BROWNOUT: Serial.println("BROWNOUT (tegangan turun, periksa catu daya)"); break;
    default:               Serial.print("kode "); Serial.println((int) alasan); break;
  }
}

/**
 * Handshake ke server uji: kirim device_id, IP, MAC, topologi, dan RSSI. Berhasil bila HTTP 2xx.
 */
bool lakukanHandshake() {
  String payload = "{";
  payload += "\"device_id\":\"" + String(deviceId) + "\",";
  payload += "\"ip\":\"" + WiFi.localIP().toString() + "\",";
  payload += "\"mac\":\"" + WiFi.macAddress() + "\",";
  payload += "\"topology_type\":\"star\",";
  payload += "\"rssi\":" + String(WiFi.RSSI());
  payload += "}";

  HTTPClient http;
  http.setConnectTimeout(3000);
  http.setTimeout(3000);
  http.begin(HANDSHAKE_URL);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");
  if (strlen(API_KEY) > 0) {
    http.addHeader("X-Api-Key", API_KEY);
  }
  int kodeRespons = http.POST(payload);
  bool berhasil = kodeRespons >= 200 && kodeRespons < 300;
  Serial.print("[HANDSHAKE "); Serial.print(percobaanHandshake); Serial.print("/"); Serial.print(MAKS_PERCOBAAN_HANDSHAKE);
  Serial.print("] "); Serial.print(payload); Serial.print(" -> ");
  if (kodeRespons > 0) {
    Serial.print("HTTP "); Serial.print(kodeRespons); Serial.print(" "); Serial.println(http.getString());
  } else {
    Serial.println(http.errorToString(kodeRespons));
  }
  http.end();

  if (berhasil) {
    ipHandshake = WiFi.localIP().toString();
  } else {
    jelaskanGagalHandshake(kodeRespons);
  }
  return berhasil;
}

// ======================== SETUP ========================
void setup() {
  Serial.begin(115200);
  delay(1000);

  snprintf(deviceId, sizeof(deviceId), "AIR-%03d", NODE_ID);

  Serial.println("\n=======================================================");
  Serial.println("  SIMKU - ESP32 TOPOLOGI STAR");
  Serial.print("  Device ID : "); Serial.println(deviceId);
  Serial.println("=======================================================");
  cetakAlasanRestart();

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
  configTime(7 * 3600, 0, NTP_SERVER, "time.google.com");

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
    handshakeSelesai = false;  // handshake lagi setelah tersambung ulang
    percobaanHandshake = 0;
    WiFi.reconnect();
    return;
  }

  // IP berubah setelah handshake berhasil (tersambung ulang otomatis): handshake lagi.
  if (handshakeSelesai && ipHandshake != "" && WiFi.localIP().toString() != ipHandshake) {
    handshakeSelesai = false;
    percobaanHandshake = 0;
  }

  // Handshake dulu sebelum mengirim data, paling banyak MAKS_PERCOBAAN_HANDSHAKE kali.
  if (!handshakeSelesai) {
    percobaanHandshake++;
    if (lakukanHandshake()) {
      handshakeSelesai = true;
    } else if (percobaanHandshake >= MAKS_PERCOBAAN_HANDSHAKE) {
      handshakeSelesai = true;
      ipHandshake = "";
      Serial.println("[HANDSHAKE] Dilewati setelah gagal berulang. Data tetap dikirim; IP tercatat dari paket data.");
    } else {
      Serial.println("[MENUNGGU] Handshake dicoba lagi 5 detik lagi, data belum dikirim.");
      return;
    }
  }

  char timestamp[20];
  int timestampMs = 0;
  if (!ambilTimestamp(timestamp, sizeof(timestamp), &timestampMs)) {
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

  long rssi = WiFi.RSSI();  // Satu-satunya link nirkabel: Node -> AP
  seqNumber++;

  // Waktu kirim diambil tepat sebelum dikirim agar delay mencakup jaringan saja.
  ambilTimestamp(timestamp, sizeof(timestamp), &timestampMs);

  String payload = "{";
  payload += "\"device_id\":\"" + String(deviceId) + "\",";
  payload += "\"timestamp\":\"" + String(timestamp) + "\",";
  payload += "\"timestamp_ms\":" + String(timestampMs) + ",";
  payload += "\"co2_ppm\":" + String(co2, 2) + ",";
  payload += "\"nh3_ppm\":" + String(nh3, 2) + ",";
  payload += "\"co_ppm\":"  + String(co, 2) + ",";
  payload += "\"h2_ppm\":"  + String(h2, 2) + ",";
  payload += "\"rssi\":" + String(rssi) + ",";
  payload += "\"seq_number\":" + String(seqNumber) + ",";
  payload += "\"hop_count\":1,";
  payload += "\"topology_type\":\"star\"";
  payload += "}";

  Serial.print("[DATA] "); Serial.println(payload);
  kirimPost(SERVER_URL, payload, "UJI");
  if (KIRIM_KE_SIMKU && NODE_ID == 1) {
    kirimPost(SIMKU_URL, payload, "SIMKU");
  }
}

/* =========================================================================================
 * SIMKU - TOPOLOGI MESH: ESP32 NODE 1 (ROOT GATEWAY / SINK)
 * =========================================================================================
 * Deskripsi peran Node 1:
 * 1. Terhubung ke WiFi Access Point dan halaman uji topologi SIMKU (HTTP POST
 *    /api/uji-topologi; data terpisah dari halaman SIMKU).
 * 2. Membaca sensor MQ-135 dan MQ-2 miliknya sendiri dengan logika yang SAMA dengan
 *    arduino/simku_monitor: pemanasan 30 detik, baseline = rata-rata 50 pembacaan ADC, lalu
 *    konversi Solihat, S. M., Salsabila, S., & Susilawati, S. (2026), JITET 14(1), hlm. 1596:
 *      ppm = max(0, adc - baseline) * (5.0 / 4096.0) * k,  k: CO2 100, NH3 80, CO 50, H2 30.
 *    Nilai adalah INDIKATOR RELATIF terhadap kondisi udara saat perangkat dinyalakan.
 *    Data lokal dikirim sebagai device_id AIR-001.
 * 3. Menerima paket ESP-NOW dari Node 2 (Relay) dan Node 3 (Leaf), lalu meneruskannya ke
 *    endpoint uji sebagai AIR-002 / AIR-003. Node tersebut tidak punya NTP, sehingga gateway
 *    mengisi timestamp dari jam NTP-nya sendiri: waktu terima dikurangi lama paket ditahan.
 *    Penerusan ditahan sampai waktu tersinkron.
 * 4. Deduplikasi: paket broadcast Node 3 bisa tiba langsung DAN via relay dengan seq_number
 *    sama; salinan kedua dibuang.
 *
 * Payload mengikuti format aplikasi (device_id, timestamp, co2_ppm, nh3_ppm, co_ppm, h2_ppm).
 * Metrik jaringan (rssi, seq_number, hop_count, topology_type) dan timestamp_ms ikut dikirim
 * dan diringkas (PDR, packet loss, delay, jitter, throughput, RSSI, hop) di halaman uji
 * topologi. Untuk paket Node 2 dan Node 3, timestamp dimundurkan sebesar lama paket ditahan
 * di relay (umur_ms) dan di antrean gateway, sehingga menunjuk waktu kirim node asal.
 * Data Node 1 (AIR-001) juga dikirim ke monitoring SIMKU bila KIRIM_KE_SIMKU = true.
 * - rssi      : RSSI terlemah sepanjang jalur sampai WiFi AP.
 * - hop_count : jumlah transmisi nirkabel sampai WiFi AP.
 *
 * Handshake: setelah tersambung ke WiFi (dan setiap kali tersambung ulang), gateway mengirim
 * device_id, IP, MAC, topologi, dan RSSI ke HANDSHAKE_URL. Data baru dikirim atau diteruskan
 * setelah server menjawab HTTP 200.
 *
 * Kebutuhan: ESP32 Arduino core versi 3 atau lebih baru (callback esp_now_recv_info_t).
 * ========================================================================================= */

#include <esp_arduino_version.h>
#if !defined(ESP_ARDUINO_VERSION_MAJOR) || ESP_ARDUINO_VERSION_MAJOR < 3
#error "Butuh ESP32 Arduino core >= 3.0 (Boards Manager: esp32 by Espressif Systems versi 3.x)."
#endif

#include <WiFi.h>
#include <esp_now.h>
#include <esp_wifi.h>
#include <HTTPClient.h>
#include <time.h>
#include <sys/time.h>

// ======================== KONFIGURASI JARINGAN & SERVER ========================
const char* WIFI_SSID     = "cargo";
const char* WIFI_PASSWORD = "cargocargo";

// Endpoint uji topologi (tersembunyi, data terpisah dari SIMKU): /api/<TOPOLOGY_TEST_PATH>.
// Bawaan: server lokal 192.168.137.1:8000 (laptop hotspot) agar lalu lintas bisa direkam Wireshark.
const char* SERVER_URL    = "http://192.168.137.1:8000/api/uji-topologi";
// Handshake: setelah tersambung WiFi, gateway melaporkan IP-nya sebelum mengirim data.
const char* HANDSHAKE_URL = "http://192.168.137.1:8000/api/uji-topologi/handshake";

// Data Node 1 (AIR-001) juga dikirim ke monitoring SIMKU (ruangan RC1TI) bila KIRIM_KE_SIMKU =
// true. SIMKU hanya menerima AIR-001; data Node 2 dan Node 3 hanya ke endpoint uji.
// Pengiriman ke SIMKU dilakukan sesudah pengiriman uji sehingga tidak menambah delay uji.
const bool  KIRIM_KE_SIMKU = true;
const char* SIMKU_URL     = "https://siasap.ptsigmamutuindonesia.com/api/telemetry";

// Server waktu NTP. Hotspot laptop harus meneruskan internet; bila tidak, jalankan server NTP di
// laptop dan isi NTP_SERVER dengan "192.168.137.1". Data tidak dikirim sebelum waktu tersinkron.
const char* NTP_SERVER    = "pool.ntp.org";

// Opsional: isi dengan TELEMETRY_API_KEY di .env Laravel. Kosongkan ("") bila tidak dipakai.
const char* API_KEY       = "";

const unsigned long INTERVAL_KIRIM = 5000;

// ======================== IDENTITAS NODE ========================
#define NODE_ID 1  // dikirim ke aplikasi sebagai AIR-001

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

// Tahun minimum agar waktu dianggap sudah tersinkron NTP (sebelum sinkron, tahun = 1970).
const int TAHUN_MINIMUM_VALID = 2024;

bool sensorSiap = false;          // true setelah pemanasan dan baseline selesai
unsigned long waktuMenyala = 0;
int baselineMq135 = 0;
int baselineMq2   = 0;
unsigned long seqNumber = 0;
bool handshakeOk = false;  // diulang setiap kali WiFi tersambung ulang atau IP berubah
String ipHandshake = "";
unsigned long terakhirHandshake = 0;
unsigned long lastSendTime = 0;

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

// Paket di antrean beserta waktu diterima (millis) untuk mengisi timestamp.
typedef struct paket_antrean {
  struct_mesh_packet paket;
  unsigned long diterimaMs;
} paket_antrean;

// ======================== ANTREAN PAKET ESP-NOW ========================
// HTTP POST bersifat blocking, sehingga paket ESP-NOW yang datang selama itu ditampung di
// ring buffer. Akses dilindungi critical section karena callback berjalan di task WiFi.
#define MAX_QUEUE 20
paket_antrean packetQueue[MAX_QUEUE];
volatile int queueHead  = 0;
volatile int queueCount = 0;
volatile unsigned long queueDropped = 0;  // paket dibuang karena antrean penuh
unsigned long queueDroppedReported = 0;
portMUX_TYPE queueMux = portMUX_INITIALIZER_UNLOCKED;

// ======================== DEDUPLIKASI ========================
// seq_number terakhir yang sudah diteruskan per node_id (0 = belum ada; seq dimulai dari 1).
#define MAX_NODE_ID 10
int lastSeq[MAX_NODE_ID + 1] = {0};
unsigned long duplicateCount = 0;

// Gabungkan RSSI terukur ke paket: ambil nilai TERLEMAH (minimum) sepanjang jalur.
// rssi == 0 berarti belum ada link yang terukur.
int gabungRssi(int rssiPaket, int rssiTerukur) {
  if (rssiPaket == 0) return rssiTerukur;
  return (rssiTerukur < rssiPaket) ? rssiTerukur : rssiPaket;
}

void OnDataRecv(const esp_now_recv_info_t *info, const uint8_t *incomingData, int len) {
  if (len != sizeof(struct_mesh_packet)) return;

  paket_antrean masuk;
  memcpy(&masuk.paket, incomingData, sizeof(struct_mesh_packet));
  masuk.paket.rssi = gabungRssi(masuk.paket.rssi, info->rx_ctrl->rssi);  // link pengirim -> Gateway
  masuk.diterimaMs = millis();

  portENTER_CRITICAL(&queueMux);
  if (queueCount < MAX_QUEUE) {
    packetQueue[(queueHead + queueCount) % MAX_QUEUE] = masuk;
    queueCount++;
  } else {
    queueDropped++;
  }
  portEXIT_CRITICAL(&queueMux);
}

bool ambilPaketAntrean(paket_antrean &out) {
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

// ======================== FUNGSI WAKTU ========================

/**
 * Mengisi buffer dengan waktu lokal (WIB) berformat "Y-m-d H:i:s" dan milidetik-nya untuk saat
 * (sekarang - mundurMs). Mengembalikan false bila waktu belum tersinkron lewat NTP.
 */
bool ambilTimestamp(char* buffer, size_t ukuran, int* milidetik, unsigned long mundurMs) {
  struct timeval sekarang;
  gettimeofday(&sekarang, nullptr);
  long long epochMs = (long long) sekarang.tv_sec * 1000 + sekarang.tv_usec / 1000 - (long long) mundurMs;
  time_t detik = (time_t) (epochMs / 1000);
  struct tm waktu;
  localtime_r(&detik, &waktu);
  if ((waktu.tm_year + 1900) < TAHUN_MINIMUM_VALID) {
    return false;
  }
  strftime(buffer, ukuran, "%Y-%m-%d %H:%M:%S", &waktu);
  *milidetik = (int) (epochMs % 1000);
  return true;
}

bool waktuTersinkron() {
  char buffer[20];
  int milidetik;
  return ambilTimestamp(buffer, sizeof(buffer), &milidetik, 0);
}

// ======================== KIRIM KE APLIKASI ========================

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
 * Kirim satu paket ke endpoint uji (dan ke SIMKU untuk data Node 1). mundurMs = lama sejak
 * paket dikirim node asalnya, sehingga timestamp menunjuk waktu kirim asli untuk menghitung delay.
 */
void kirimKeServer(const struct_mesh_packet &paket, unsigned long mundurMs) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.print("[OFFLINE] WiFi terputus, data AIR-00"); Serial.print(paket.node_id);
    Serial.print(" Seq #"); Serial.print(paket.seq_number); Serial.println(" tidak terkirim.");
    return;
  }

  char deviceId[12];
  snprintf(deviceId, sizeof(deviceId), "AIR-%03d", paket.node_id);

  char timestamp[20];
  int timestampMs = 0;
  ambilTimestamp(timestamp, sizeof(timestamp), &timestampMs, mundurMs);

  String payload = "{";
  payload += "\"device_id\":\"" + String(deviceId) + "\",";
  payload += "\"timestamp\":\"" + String(timestamp) + "\",";
  payload += "\"timestamp_ms\":" + String(timestampMs) + ",";
  payload += "\"co2_ppm\":" + String(paket.co2, 2) + ",";
  payload += "\"nh3_ppm\":" + String(paket.nh3, 2) + ",";
  payload += "\"co_ppm\":"  + String(paket.co, 2) + ",";
  payload += "\"h2_ppm\":"  + String(paket.h2, 2) + ",";
  payload += "\"rssi\":" + String(paket.rssi) + ",";
  payload += "\"seq_number\":" + String(paket.seq_number) + ",";
  payload += "\"hop_count\":" + String(paket.hop_count) + ",";
  payload += "\"topology_type\":\"mesh\"";
  payload += "}";

  Serial.print("[DATA] "); Serial.println(payload);
  kirimPost(SERVER_URL, payload, "UJI");
  if (KIRIM_KE_SIMKU && paket.node_id == NODE_ID) {
    kirimPost(SIMKU_URL, payload, "SIMKU");
  }
}

/**
 * Handshake ke server uji: kirim device_id, IP, MAC, topologi, dan RSSI. Berhasil bila HTTP 200.
 * Node 2 dan Node 3 tidak punya IP (ESP-NOW), sehingga hanya gateway yang melakukan handshake.
 */
bool lakukanHandshake() {
  char deviceId[12];
  snprintf(deviceId, sizeof(deviceId), "AIR-%03d", NODE_ID);

  String payload = "{";
  payload += "\"device_id\":\"" + String(deviceId) + "\",";
  payload += "\"ip\":\"" + WiFi.localIP().toString() + "\",";
  payload += "\"mac\":\"" + WiFi.macAddress() + "\",";
  payload += "\"topology_type\":\"mesh\",";
  payload += "\"rssi\":" + String(WiFi.RSSI());
  payload += "}";

  HTTPClient http;
  http.begin(HANDSHAKE_URL);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");
  if (strlen(API_KEY) > 0) {
    http.addHeader("X-Api-Key", API_KEY);
  }
  int kodeRespons = http.POST(payload);
  bool berhasil = kodeRespons == 200;
  if (berhasil) {
    ipHandshake = WiFi.localIP().toString();
  }
  Serial.print("[HANDSHAKE] "); Serial.print(payload); Serial.print(" -> ");
  if (kodeRespons > 0) {
    Serial.print("HTTP "); Serial.print(kodeRespons); Serial.print(" "); Serial.println(http.getString());
  } else {
    Serial.println(http.errorToString(kodeRespons));
  }
  http.end();
  return berhasil;
}

// ======================== SETUP ========================
void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println("\n=======================================================");
  Serial.println("  SIMKU - ESP32 TOPOLOGI MESH [NODE 1: ROOT GATEWAY]");
  Serial.print("  Device ID : AIR-00"); Serial.println(NODE_ID);
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
    Serial.print("MAC Address  : "); Serial.println(WiFi.macAddress());
    Serial.print("Kanal WiFi   : "); Serial.print(WiFi.channel());
    Serial.println(" (samakan WIFI_CHANNEL di sketsa relay dan leaf)");
  } else {
    Serial.println("\n[PERINGATAN] Gagal terhubung ke WiFi, akan dicoba lagi di loop.");
  }

  // Sinkronisasi waktu NTP, zona WIB (UTC+7).
  configTime(7 * 3600, 0, NTP_SERVER, "time.google.com");

  if (esp_now_init() == ESP_OK) {
    esp_now_register_recv_cb(OnDataRecv);
    Serial.println("[OK] ESP-NOW aktif pada Root Gateway.");
  } else {
    Serial.println("[ERROR] Gagal menginisialisasi ESP-NOW!");
  }

  waktuMenyala = millis();
  Serial.println("Memanaskan sensor selama 30 detik (penerusan paket tetap berjalan)...");
}

// ======================== LOOP UTAMA ========================
void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    static unsigned long terakhirSambung = 0;
    if (millis() - terakhirSambung >= INTERVAL_KIRIM) {
      terakhirSambung = millis();
      Serial.println("[PERINGATAN] WiFi terputus, mencoba menyambung ulang...");
      WiFi.reconnect();
    }
    handshakeOk = false;
  } else {
    if (handshakeOk && WiFi.localIP().toString() != ipHandshake) {
      handshakeOk = false;  // IP berubah setelah tersambung ulang
    }
    // Handshake dulu sebelum mengirim data; dicoba lagi tiap INTERVAL_KIRIM sampai berhasil.
    if (!handshakeOk && millis() - terakhirHandshake >= INTERVAL_KIRIM) {
      terakhirHandshake = millis();
      handshakeOk = lakukanHandshake();
    }
  }

  // 1. Teruskan paket Node 2 / Node 3. Ditahan di antrean sampai handshake berhasil dan waktu
  //    NTP tersinkron.
  if (handshakeOk && waktuTersinkron()) {
    paket_antrean masuk;
    while (ambilPaketAntrean(masuk)) {
      struct_mesh_packet &paket = masuk.paket;

      if (paket.node_id < 1 || paket.node_id > MAX_NODE_ID || paket.node_id == NODE_ID) {
        Serial.print("[MESH DROP] node_id tidak valid: "); Serial.println(paket.node_id);
        continue;
      }

      if (paket.seq_number == lastSeq[paket.node_id]) {
        duplicateCount++;
        Serial.print("[MESH DUPLIKAT] AIR-00"); Serial.print(paket.node_id);
        Serial.print(" Seq #"); Serial.print(paket.seq_number);
        Serial.print(" sudah diteruskan, dibuang. Total duplikat: "); Serial.println(duplicateCount);
        continue;
      }
      lastSeq[paket.node_id] = paket.seq_number;

      // Paket melewati satu link nirkabel lagi: Gateway -> WiFi AP
      paket.hop_count += 1;
      paket.rssi = gabungRssi(paket.rssi, (int) WiFi.RSSI());

      // Waktu kirim asli = sekarang - (lama di antrean gateway + lama ditahan relay).
      kirimKeServer(paket, (millis() - masuk.diterimaMs) + paket.umur_ms);
    }
  }

  unsigned long droppedNow;
  portENTER_CRITICAL(&queueMux);
  droppedNow = queueDropped;
  portEXIT_CRITICAL(&queueMux);
  if (droppedNow != queueDroppedReported) {
    Serial.print("[GATEWAY OVERFLOW] Antrean ESP-NOW penuh! Total paket dibuang di gateway: ");
    Serial.println(droppedNow);
    queueDroppedReported = droppedNow;
  }

  // 2. Pemanasan dan baseline sensor lokal (tanpa menghentikan penerusan)
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

  // 3. Kirim data sensor lokal Node 1 secara berkala
  if (millis() - lastSendTime < INTERVAL_KIRIM) {
    return;
  }
  lastSendTime = millis();

  if (!handshakeOk) {
    Serial.println("[MENUNGGU] Handshake belum berhasil, data belum dikirim.");
    return;
  }
  if (!waktuTersinkron()) {
    Serial.println("[MENUNGGU] Waktu belum tersinkron NTP, data belum dikirim.");
    return;
  }

  int adcMq135 = analogRead(PIN_MQ135);
  int adcMq2   = analogRead(PIN_MQ2);

  if (TAMPILKAN_ADC_MENTAH) {
    Serial.print("[ADC] "); cetakAdcMentah("MQ-135", PIN_MQ135, adcMq135, baselineMq135);
    Serial.print("[ADC] "); cetakAdcMentah("MQ-2  ", PIN_MQ2, adcMq2, baselineMq2);
  }

  seqNumber++;

  struct_mesh_packet paket;
  paket.node_id    = NODE_ID;
  paket.co2        = hitungPpm(adcMq135, baselineMq135, K_CO2);
  paket.nh3        = hitungPpm(adcMq135, baselineMq135, K_NH3);
  paket.co         = hitungPpm(adcMq2, baselineMq2, K_CO);
  paket.h2         = hitungPpm(adcMq2, baselineMq2, K_H2);
  paket.rssi       = WiFi.RSSI();  // satu-satunya link nirkabel: Node 1 -> AP
  paket.seq_number = seqNumber;
  paket.hop_count  = 1;
  paket.umur_ms    = 0;

  kirimKeServer(paket, 0);
}

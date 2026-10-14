/* =========================================================================================
 * SISTEM PEMANTAUAN KUALITAS UDARA LINGKUNGAN (SIASAP)
 * SKETCH KALIBRASI R0 - SENSOR MQ-2 & MQ-135 (PER BOARD)
 * =========================================================================================
 * Tujuan:
 * Nilai R0 (resistansi sensor pada udara referensi) berbeda untuk SETIAP sensor fisik.
 * Sketch ini mengukur R0 masing-masing board agar konsentrasi gas yang dikirim node sensor
 * benar-benar hasil pengukuran, bukan memakai R0 yang sama untuk semua board.
 *
 * Prosedur:
 * 1. Burn-in: sensor MQ baru WAJIB dinyalakan terus-menerus 24-48 jam sebelum kalibrasi.
 * 2. Letakkan board di UDARA BERSIH (luar ruangan / ruangan berventilasi baik, jauh dari
 *    kompor, asap, parfum, alkohol, knalpot).
 * 3. Upload sketch ini, buka Serial Monitor 115200 baud.
 * 4. Tunggu fase pemanasan (WAKTU_PEMANASAN_MS), lalu sketch merata-rata ADC selama
 *    ~60 detik per siklus dan mencetak R0. Siklus diulang terus; tunggu sampai nilai R0
 *    stabil (selisih antar siklus < ~2%).
 * 5. Salin nilai R0_MQ2 & R0_MQ135 ke sketch node yang akan di-flash ke board YANG SAMA.
 *
 * Rumus Rs (hitungRsMQ2 / hitungRsMQ135) disalin PERSIS dari sketch node agar konsisten.
 * R0 = Rs_udara_bersih / RASIO_UDARA_BERSIH
 * ========================================================================================= */

#include <math.h>

// ======================== PIN HARDWARE SENSOR (sama dengan sketch node) ========================
#define PIN_MQ2   34  // Pin ADC untuk Sensor MQ-2 (CO, H2)
#define PIN_MQ135 35  // Pin ADC untuk Sensor MQ-135 (CO2, NH3)

const float RL = 1.0;

// ======================== PARAMETER KALIBRASI ========================
// MQ-2: rasio Rs/R0 di udara bersih ~9.83 (datasheet MQ-2). Kurva CO/H2 pada sketch node
// (hitungPPM dengan R0_MQ2) mengikuti konvensi ini.
const float RASIO_UDARA_BERSIH_MQ2   = 9.83;

// MQ-135: datasheet menyebut Rs/R0 udara bersih ~3.6. NAMUN rumus CO2 pada sketch node
// adalah ppm = 400 * (Rs/R0)^-2.762, artinya R0 didefinisikan sebagai Rs pada udara
// referensi ~400 ppm CO2 (rasio = 1.0 -> 400 ppm). Bila memakai 3.6, CO2 udara bersih akan
// terbaca ~11 ppm (tidak masuk akal). Oleh karena itu dipakai 1.0 agar konsisten dengan
// rumus node. Ubah ke 3.6 HANYA jika rumus PPM di sketch node juga diganti ke kurva datasheet.
const float RASIO_UDARA_BERSIH_MQ135 = 1.0;

// Pemanasan sebelum sampling (setelah burn-in 24-48 jam, sensor tetap perlu pemanasan)
const unsigned long WAKTU_PEMANASAN_MS = 10UL * 60UL * 1000UL;  // 10 menit
// Durasi rata-rata per siklus & jeda antar sampel
const unsigned long DURASI_SAMPLING_MS = 60UL * 1000UL;         // ~60 detik
const unsigned long JEDA_SAMPEL_MS     = 100;                   // ~600 sampel / sensor

int siklus = 0;

// ======================== FUNGSI KALKULASI SENSOR (SALINAN PERSIS DARI NODE) ========================

// Hitung Rs MQ-2 (Skala operasi ADC 3.3V ESP32)
float hitungRsMQ2(int adcValue) {
  if (adcValue >= 4080) adcValue = 4080;
  if (adcValue <= 50)   adcValue = 50;
  float vOut = (adcValue / 4095.0) * 3.3;
  return ((3.3 - vOut) / vOut) * RL;
}

// Hitung Rs MQ-135 (Skala referensi 5.0V pembagi tegangan)
float hitungRsMQ135(int adcValue) {
  if (adcValue >= 4080) adcValue = 4080;
  if (adcValue <= 20)   adcValue = 20;
  float vOut = (adcValue / 4095.0) * 3.3;
  return ((5.0 - vOut) / vOut) * RL;
}

// ======================== SETUP ========================
void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println("\n=======================================================");
  Serial.println("  SIASAP - KALIBRASI R0 SENSOR MQ-2 & MQ-135");
  Serial.println("=======================================================");
  Serial.println("[INGAT] Sensor baru wajib burn-in 24-48 jam (dinyalakan terus)");
  Serial.println("        sebelum kalibrasi. Letakkan board di UDARA BERSIH.");
  Serial.print("Rasio udara bersih MQ-2   : "); Serial.println(RASIO_UDARA_BERSIH_MQ2, 2);
  Serial.print("Rasio udara bersih MQ-135 : "); Serial.println(RASIO_UDARA_BERSIH_MQ135, 2);

  analogReadResolution(12);

  // Fase pemanasan dengan hitung mundur per 30 detik
  Serial.print("Pemanasan sensor selama "); Serial.print(WAKTU_PEMANASAN_MS / 1000);
  Serial.println(" detik...");
  unsigned long mulai = millis();
  while (millis() - mulai < WAKTU_PEMANASAN_MS) {
    unsigned long sisa = (WAKTU_PEMANASAN_MS - (millis() - mulai)) / 1000;
    Serial.print("  Sisa pemanasan: "); Serial.print(sisa); Serial.println(" detik");
    delay(30000);
  }
  Serial.println("[OK] Pemanasan selesai. Mulai sampling.");
}

// ======================== LOOP UTAMA ========================
void loop() {
  siklus++;
  Serial.println("-------------------------------------------------------");
  Serial.print("Siklus #"); Serial.print(siklus);
  Serial.print(": merata-rata ADC selama "); Serial.print(DURASI_SAMPLING_MS / 1000);
  Serial.println(" detik...");

  // 1. Rata-rata ADC kedua sensor selama ~60 detik
  unsigned long long sum_mq2 = 0;
  unsigned long long sum_mq135 = 0;
  unsigned long jumlahSampel = 0;
  unsigned long mulai = millis();
  while (millis() - mulai < DURASI_SAMPLING_MS) {
    sum_mq2   += analogRead(PIN_MQ2);
    sum_mq135 += analogRead(PIN_MQ135);
    jumlahSampel++;
    delay(JEDA_SAMPEL_MS);
  }
  int adc_mq2   = (int) (sum_mq2 / jumlahSampel);
  int adc_mq135 = (int) (sum_mq135 / jumlahSampel);

  // 2. Hitung Rs dengan rumus yang SAMA seperti sketch node
  float rs_mq2   = hitungRsMQ2(adc_mq2);
  float rs_mq135 = hitungRsMQ135(adc_mq135);

  // 3. R0 = Rs udara bersih / rasio udara bersih
  float r0_mq2   = rs_mq2 / RASIO_UDARA_BERSIH_MQ2;
  float r0_mq135 = rs_mq135 / RASIO_UDARA_BERSIH_MQ135;

  Serial.print("Jumlah sampel / sensor : "); Serial.println(jumlahSampel);
  Serial.print("MQ-2   ADC rata-rata: "); Serial.print(adc_mq2);
  Serial.print(" | Rs: "); Serial.print(rs_mq2, 4);
  Serial.print(" | R0: "); Serial.println(r0_mq2, 4);
  Serial.print("MQ-135 ADC rata-rata: "); Serial.print(adc_mq135);
  Serial.print(" | Rs: "); Serial.print(rs_mq135, 4);
  Serial.print(" | R0: "); Serial.println(r0_mq135, 4);

  // Peringatan bila ADC menyentuh batas clamp (hasil R0 tidak valid)
  if (adc_mq2 <= 50 || adc_mq2 >= 4080) {
    Serial.println("[PERINGATAN] ADC MQ-2 di batas clamp -> cek wiring / RL, R0 TIDAK valid.");
  }
  if (adc_mq135 <= 20 || adc_mq135 >= 4080) {
    Serial.println("[PERINGATAN] ADC MQ-135 di batas clamp -> cek wiring / RL, R0 TIDAK valid.");
  }

  // 4. Format siap salin ke sketch node (board yang sama)
  Serial.println("Salin ke sketch node untuk board INI:");
  Serial.print("  const float R0_MQ2    = "); Serial.print(r0_mq2, 4); Serial.println(";");
  Serial.print("  const float R0_MQ135  = "); Serial.print(r0_mq135, 4); Serial.println(";");
  Serial.println("Tunggu sampai nilai R0 stabil antar siklus (selisih < ~2%).");
}

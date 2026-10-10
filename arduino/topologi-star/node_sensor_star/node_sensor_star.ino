/* =========================================================================================
 * SISTEM PEMANTAUAN KUALITAS UDARA LINGKUNGAN (SIASAP)
 * TOPOLOGI STAR - ESP32 SENSOR NODE (STANDALONE / DIRECT WIFI)
 * =========================================================================================
 * Deskripsi:
 * Perangkat ESP32 membaca 4 konsentrasi gas (CO2, NH3, CO, H2) dari sensor MQ-135 & MQ-2,
 * lalu mengirimkan data telemetri secara berkala langsung ke server Laravel SIASAP via HTTP POST.
 *
 * Konfigurasi Node:
 * - Ubah NODE_ID (1: Ruang Tamu, 2: Kamar, 3: Dapur) sesuai penempatan perangkat.
 * - Ubah SSID, PASSWORD WiFi, dan IP_SERVER sesuai jaringan lokal Anda.
 * ========================================================================================= */

#include <WiFi.h>
#include <HTTPClient.h>
#include <math.h>

// ======================== KONFIGURASI JARINGAN & SERVER ========================
const char* WIFI_SSID     = "cargo";
const char* WIFI_PASSWORD = "cargocargo";

// URL Endpoint Laravel SIASAP (Ganti IP dengan IP komputer/server Anda yang menjalankan Laravel)
// Contoh: "http://192.168.137.1:8000/api/telemetry"
const char* SERVER_URL    = "http://192.168.137.1:8000/api/telemetry";

// Interval pengiriman data (milidetik) -> Default: 5000 ms (5 detik)
const unsigned long INTERVAL_KIRIM = 5000;

// ======================== IDENTITAS NODE PERANGKAT ========================
// Pilihan: 1 (Ruang Tamu), 2 (Kamar), 3 (Dapur)
#define NODE_ID 1

// ======================== PIN HARDWARE SENSOR ========================
#define PIN_MQ2   34  // Pin ADC untuk Sensor MQ-2 (CO, H2)
#define PIN_MQ135 35  // Pin ADC untuk Sensor MQ-135 (CO2, NH3)

// Parameter Hambatan Beban & Kalibrasi R0
const float RL        = 1.0;
const float R0_MQ2    = 0.174;
const float R0_MQ135  = 19.30;

// Variabel Global Telemetri
unsigned long seqNumber = 0;       // Sequence counter untuk perhitungan PDR & Packet Loss
unsigned long lastSendTime = 0;

// ======================== FUNGSI KALKULASI SENSOR ========================

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

// Rumus kurva karakteristik PPM: PPM = a * (Rs/R0)^b
float hitungPPM(float rs, float r0, float a, float b) {
  float rasio = rs / r0;
  if (rasio <= 0.0) return 0.0;
  return a * pow(rasio, b);
}

// Klasifikasi Kualitas Udara Sesuai Tabel 2.2 (Solihat et al., 2025)
String tentukanStatusKU(float co2, float nh3, float co, float h2) {
  int k_co2 = (co2 < 600.0) ? 1 : (co2 < 1000.0 ? 2 : (co2 < 1500.0 ? 3 : 4));
  int k_nh3 = (nh3 < 40.0)  ? 1 : (nh3 < 80.0   ? 2 : (nh3 < 120.0  ? 3 : 4));
  int k_co  = (co < 20.0)   ? 1 : (co < 50.0    ? 2 : (co < 80.0    ? 3 : 4));
  int k_h2  = (h2 < 15.0)   ? 1 : (h2 < 40.0    ? 2 : (h2 < 60.0    ? 3 : 4));

  int ku_score = max(max(k_co2, k_nh3), max(k_co, k_h2));
  switch (ku_score) {
    case 1: return "Baik";
    case 2: return "Sedang";
    case 3: return "Tidak Sehat";
    case 4: return "Berbahaya";
    default: return "Tidak Diketahui";
  }
}

// ======================== SETUP ========================
void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println("\n=======================================================");
  Serial.println("  SIASAP - ESP32 TOPOLOGI STAR SENSOR NODE");
  Serial.print("  Node ID Terpasang : "); Serial.println(NODE_ID);
  Serial.println("=======================================================");

  // Set resolusi pembacaan ADC ESP32 (12-bit: 0 - 4095)
  analogReadResolution(12);

  // Inisialisasi Koneksi WiFi (Station Mode)
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  Serial.print("Menghubungkan ke WiFi ["); Serial.print(WIFI_SSID); Serial.print("]");
  int retry = 0;
  while (WiFi.status() != WL_CONNECTED && retry < 30) {
    delay(500);
    Serial.print(".");
    retry++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n[OK] Terhubung ke WiFi!");
    Serial.print("IP Address Node : "); Serial.println(WiFi.localIP());
    Serial.print("Target Server   : "); Serial.println(SERVER_URL);
  } else {
    Serial.println("\n[PERINGATAN] Gagal terhubung ke WiFi, akan mencoba kembali di loop...");
  }
}

// ======================== LOOP UTAMA ========================
void loop() {
  // Cek interval pengiriman berkala
  if (millis() - lastSendTime < INTERVAL_KIRIM) {
    return;
  }
  lastSendTime = millis();

  // 1. Sampling ADC dengan averaging 15 kali untuk stabilitas
  long sum_mq2 = 0;
  long sum_mq135 = 0;
  for (int i = 0; i < 15; i++) {
    sum_mq2   += analogRead(PIN_MQ2);
    sum_mq135 += analogRead(PIN_MQ135);
    delay(10);
  }
  int adc_mq2   = sum_mq2 / 15;
  int adc_mq135 = sum_mq135 / 15;

  // 2. Hitung Resistansi Sensor (Rs)
  float rs_mq2   = hitungRsMQ2(adc_mq2);
  float rs_mq135 = hitungRsMQ135(adc_mq135);

  // 3. Hitung Konsentrasi 4 Parameter Gas (PPM)
  // MQ-135: CO2 & NH3
  float ppm_co2 = 400.0 * pow((rs_mq135 / R0_MQ135), -2.762);
  float rasio_mq135 = rs_mq135 / R0_MQ135;
  float ppm_nh3 = 10.0 * pow(rasio_mq135, -2.473);
  if (rasio_mq135 >= 0.9) {
    ppm_nh3 = (1.1 - rasio_mq135) * 15.0;
    if (ppm_nh3 < 0.5) ppm_nh3 = 0.5;
  }

  // MQ-2: CO & H2
  float ppm_co  = hitungPPM(rs_mq2, R0_MQ2, 3697.4, -3.109);
  float ppm_h2  = hitungPPM(rs_mq2, R0_MQ2, 987.99, -2.162);

  // 4. Evaluasi Kualitas Udara & Indikator Sinyal
  String status_ku = tentukanStatusKU(ppm_co2, ppm_nh3, ppm_co, ppm_h2);
  long rssi = WiFi.RSSI();
  seqNumber++;

  // 5. Susun Payload JSON Sesuai Kebutuhan Backend Laravel SIASAP
  String jsonPayload = "{";
  jsonPayload += "\"node_id\":"        + String(NODE_ID) + ",";
  jsonPayload += "\"co2\":"            + String(ppm_co2, 2) + ",";
  jsonPayload += "\"nh3\":"            + String(ppm_nh3, 2) + ",";
  jsonPayload += "\"co\":"             + String(ppm_co, 2) + ",";
  jsonPayload += "\"h2\":"             + String(ppm_h2, 2) + ",";
  jsonPayload += "\"rssi\":"           + String(rssi) + ",";
  jsonPayload += "\"seq_number\":"     + String(seqNumber) + ",";
  jsonPayload += "\"hop_count\":1,";
  jsonPayload += "\"topology_type\":\"star\"";
  jsonPayload += "}";

  // 6. Pengiriman ke Server Backend Melalui HTTP POST
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(SERVER_URL);
    http.addHeader("Content-Type", "application/json");

    int httpResponseCode = http.POST(jsonPayload);

    Serial.println("-------------------------------------------------------");
    Serial.print("Transmisi Paket #"); Serial.print(seqNumber);
    Serial.print(" [Node "); Serial.print(NODE_ID); Serial.print("] -> HTTP Code: ");
    Serial.println(httpResponseCode);

    if (httpResponseCode > 0) {
      String response = http.getString();
      Serial.print("Response Server: "); Serial.println(response);
    } else {
      Serial.print("[ERROR HTTP] Gagal mengirim paket: ");
      Serial.println(http.errorToString(httpResponseCode).c_str());
    }
    http.end();
  } else {
    Serial.println("[OFFLINE] WiFi terputus. Mencoba rekoneksi...");
    WiFi.reconnect();
  }

  // 7. Debug Monitor Data Sensor
  Serial.print("CO2: "); Serial.print(ppm_co2, 2); Serial.print(" ppm | ");
  Serial.print("NH3: "); Serial.print(ppm_nh3, 2); Serial.print(" ppm | ");
  Serial.print("CO: "); Serial.print(ppm_co, 2); Serial.print(" ppm | ");
  Serial.print("H2: "); Serial.print(ppm_h2, 2); Serial.print(" ppm | ");
  Serial.print("KU: "); Serial.print(status_ku); Serial.print(" | RSSI: ");
  Serial.print(rssi); Serial.println(" dBm");
}

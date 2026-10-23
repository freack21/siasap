# Panduan Firmware ESP32 — SIASAP

## Perangkat untuk aplikasi ini

Aplikasi SIASAP saat ini hanya memakai **satu perangkat ESP32 yang terhubung langsung lewat WiFi**: [`siasap_monitor/siasap_monitor.ino`](siasap_monitor/siasap_monitor.ino) (ESP32 Arduino core 3 atau lebih baru).

- Sensor: MQ-135 (CO₂, NH₃) di GPIO 33 dan MQ-2 (CO, H₂) di GPIO 32 (dapat diubah di sketsa).
- Alur: pemanasan 30 detik, baseline = rata-rata 50 pembacaan ADC tiap sensor, lalu kirim data tiap 5 detik.
- Konversi mengikuti Solihat, S. M., Salsabila, S., & Susilawati, S. (2026), JITET 14(1), hlm. 1596: `ppm = max(0, adc - baseline) * (5.0 / 4096.0) * k` dengan `k` = 100 (CO₂), 80 (NH₃), 50 (CO), 30 (H₂). Nilai adalah indikator relatif terhadap kondisi udara saat perangkat menyala, bukan konsentrasi absolut.
- Waktu diambil dari NTP (WIB); data tidak dikirim sebelum waktu tersinkron.
- Penyesuaian rumus/kalibrasi adalah tanggung jawab penelitian IoT dan perlu ditinjau rekan IoT.

Isi konfigurasi di bagian atas sketsa: `WIFI_SSID`, `WIFI_PASSWORD`, `SERVER_URL` (`http://<ip>:8000/api/telemetry`), `API_KEY` (opsional, header `X-Api-Key`), dan `DEVICE_ID` (`AIR-001`).

Payload `POST /api/telemetry`:

```json
{
  "device_id": "AIR-001",
  "timestamp": "2026-10-23 14:05:30",
  "co2_ppm": 12.50,
  "nh3_ppm": 10.00,
  "co_ppm": 6.25,
  "h2_ppm": 3.75
}
```

---

> **Arsip penelitian IoT, tidak dipakai aplikasi.** Seluruh bagian di bawah ini (sketsa topologi star/mesh, kalibrasi R0, metrik RSSI/hop/QoS, dan format payload `node_id`/`seq_number`) berasal dari penelitian IoT sebelumnya dan **tidak digunakan** oleh aplikasi SIASAP saat ini.


Direktori ini berisi kode program (*firmware*) Arduino C++ untuk perangkat keras **ESP32 DevKit V1** yang terintegrasi langsung dengan aplikasi web **SIASAP (Laravel + Livewire)**.

---

## 📁 Struktur Direktori Firmware

```
arduino/
├── topologi-star/
│   └── node_sensor_star/
│       └── node_sensor_star.ino       # 1 Firmware serbaguna untuk seluruh node (Node 1, 2, 3) pada Topologi Star
│
├── topologi-mesh/
│   ├── node_gateway_root/
│   │   └── node_gateway_root.ino      # Node 1: Root Gateway / Sink (Terkoneksi ke WiFi & Laravel API)
│   ├── node_sensor_relay/
│   │   └── node_sensor_relay.ino      # Node 2: Intermediate / Relay Node (Estafet paket dari Node 3 ke Node 1)
│   └── node_sensor_leaf/
│       └── node_sensor_leaf.ino       # Node 3: Leaf / Blind Spot Node (Di area terhalang/jauh)
│
├── kalibrasi_r0/
│   └── kalibrasi_r0.ino               # Sketch kalibrasi R0 MQ-2 & MQ-135 (dijalankan per board)
│
└── README.md                          # Dokumentasi teknis & panduan wiring
```

---

## 🔌 Skema Rangkaian Pin Hardware (Pinout)

Setiap perangkat ESP32 terhubung dengan 2 sensor gas:

| Komponen Hardware | Pin Sensor | Pin ESP32 | Keterangan / Fungsi |
|---|---|---|---|
| **MQ-2** | VCC | 5V / VIN | Sumber daya sensor |
| | GND | GND | Ground bersama |
| | AOUT | **GPIO 34** | ADC pembacaan $\text{CO}$ & $\text{H}_2$ |
| **MQ-135** | VCC | 5V / VIN | Sumber daya sensor |
| | GND | GND | Ground bersama |
| | AOUT | **GPIO 35** | ADC pembacaan $\text{CO}_2$ & $\text{NH}_3$ |

> [!NOTE]
> ESP32 menggunakan resolusi ADC 12-bit (rentang nilai pembacaan `0 - 4095`). Rumus pada kode telah disesuaikan dengan skala operasi tegangan pembagi resistor $R_L = 1.0\text{ k}\Omega$ dan nilai kalibrasi $R_0$.

---

## ⚙️ Kebutuhan Toolchain

- **ESP32 Arduino core >= 3.0** (Boards Manager: *esp32 by Espressif Systems* versi 3.x). Sketch Mesh memakai signature callback ESP-NOW core 3.x:
  ```cpp
  void OnDataRecv(const esp_now_recv_info_t *info, const uint8_t *data, int len); // RSSI: info->rx_ctrl->rssi
  ```
  Sketch Mesh akan berhenti kompilasi dengan pesan `#error "Butuh ESP32 Arduino core >= 3.0 ..."` jika dikompilasi dengan core 2.x.
- Callback *send* ESP-NOW tidak dipakai (signature-nya berbeda antara core 3.0–3.2 dan 3.3+, dan pada broadcast tidak ada ACK sehingga statusnya tidak informatif).

---

## 🧪 Kalibrasi Sensor (R0) — Wajib per Board

Nilai `R0_MQ2` dan `R0_MQ135` berbeda untuk **setiap sensor fisik**. Memakai R0 yang sama untuk semua board membuat konsentrasi gas antar-node tidak dapat dibandingkan. Setiap board wajib memakai R0 hasil kalibrasinya sendiri.

### Langkah-langkah:
1. **Burn-in**: nyalakan sensor MQ-2 & MQ-135 baru secara terus-menerus selama **24–48 jam** (cukup sekali untuk sensor baru).
2. Letakkan board di **udara bersih** (ruangan berventilasi baik / luar ruangan, jauh dari kompor, asap, parfum, alkohol, knalpot).
3. Upload `arduino/kalibrasi_r0/kalibrasi_r0.ino` ke board tersebut dan buka Serial Monitor (`115200 baud`).
4. Sketch melakukan pemanasan (default 10 menit), lalu merata-rata ADC kedua sensor selama **~60 detik** per siklus menggunakan rumus Rs yang **sama persis** dengan sketch node (`hitungRsMQ2` / `hitungRsMQ135`), dan mencetak:
   - `R0_MQ2 = Rs / 9.83` (rasio Rs/R0 udara bersih MQ-2 menurut datasheet)
   - `R0_MQ135 = Rs / 1.0` (lihat catatan di bawah)
5. Tunggu beberapa siklus sampai nilai R0 **stabil** (selisih antar siklus < ~2%). Abaikan hasil jika muncul peringatan ADC di batas clamp.
6. Salin baris `const float R0_MQ2 = ...;` dan `const float R0_MQ135 = ...;` ke sketch node (Star atau Mesh) yang akan di-flash ke **board yang sama**. Catat pasangan *board ↔ R0* agar tidak tertukar.

> [!NOTE]
> Datasheet MQ-135 menyebut rasio Rs/R0 udara bersih ≈ 3.6. Namun rumus CO2 pada sketch node adalah `ppm = 400 * (Rs/R0)^-2.762`, yang mendefinisikan R0 sebagai Rs pada udara referensi ±400 ppm CO2 (rasio 1.0 → 400 ppm). Bila memakai 3.6, CO2 udara bersih akan terbaca ±11 ppm. Karena itu sketch kalibrasi memakai rasio **1.0** untuk MQ-135 (konstanta `RASIO_UDARA_BERSIH_MQ135`). Ganti ke 3.6 hanya bila rumus PPM node juga diganti ke kurva datasheet.

---

## 📡 1. Panduan Penggunaan: Topologi Star

Pada topologi **Star**, setiap node terhubung langsung ke router WiFi lokal (*Access Point*) dan mengirim data secara simultan langsung ke endpoint API Laravel.

### Langkah-langkah:
1. Buka file `arduino/topologi-star/node_sensor_star/node_sensor_star.ino` di Arduino IDE.
2. Sesuaikan konfigurasi WiFi & IP Server di baris 20–25:
   ```cpp
   const char* WIFI_SSID     = "cargo";
   const char* WIFI_PASSWORD = "cargocargo";
   const char* SERVER_URL    = "http://192.168.137.1:8000/api/telemetry"; // Ganti dengan IP lokal laptop/server Laravel Anda
   const char* API_KEY       = "";  // Opsional, lihat bagian "API Key"
   ```
3. Sesuaikan **`NODE_ID`** untuk setiap board ESP32 yang di-flash:
   - Untuk perangkat di **Ruang Tamu**: `#define NODE_ID 1`
   - Untuk perangkat di **Kamar**: `#define NODE_ID 2`
   - Untuk perangkat di **Dapur**: `#define NODE_ID 3`
4. Pilih Board: **DOIT ESP32 DEVKIT V1**, pilih Port COM yang sesuai, lalu klik **Upload**.
5. Isi `R0_MQ2` / `R0_MQ135` dengan hasil kalibrasi board tersebut (lihat bagian Kalibrasi).
6. Buka Serial Monitor (`115200 baud`) untuk memantau status pengiriman HTTP POST.

---

## 🕸️ 2. Panduan Penggunaan: Topologi Mesh (ESP-NOW Multi-Hop)

Pada topologi **Mesh**, sistem mengatasi skenario area *blind spot* (misalnya Node 3 diletakkan jauh di belakang dinding tebal tanpa jangkauan WiFi):

```
[Node 3 (Leaf)] ──(ESP-NOW, hop 1)──> [Node 2 (Relay)] ──(ESP-NOW, hop 2)──> [Node 1 (Root Gateway)] ──(WiFi, hop 3)──> [AP] ──> [Laravel SIASAP]
```

Semua transmisi ESP-NOW dikirim ke alamat **broadcast** (`FF:FF:FF:FF:FF:FF`), dan semua node ESP-NOW harus berada pada channel yang sama dengan channel WiFi AP (`WIFI_CHANNEL` pada sketch relay & leaf).

### Penjelasan Peran Perangkat:
1. **Node 1 (`node_gateway_root.ino`) - Ruang Tamu**:
   - Terkoneksi ke WiFi router dan server Laravel.
   - Membaca sensor MQ lokal miliknya sendiri (`Node 1`, `hop_count = 1`, `rssi = WiFi.RSSI()`).
   - Bertindak sebagai **Sink / Gateway**: menerima paket ESP-NOW dari Node 2 dan Node 3 (mengukur RSSI link terakhir), lalu mem-forward ke REST API Laravel dengan `hop_count + 1` dan `rssi = min(rssi, WiFi.RSSI())` (hop Gateway → AP).
   - **Deduplikasi**: karena broadcast, paket Node 3 bisa diterima langsung *dan* via relay dengan `seq_number` sama. Gateway menyimpan `lastSeq[node_id]` dan membuang paket yang `seq_number`-nya sama dengan seq terakhir yang sudah di-forward untuk node tersebut (log `[MESH DUPLIKAT]`). Salinan yang tiba lebih dulu yang dipakai.
   - **Antrean**: paket ESP-NOW ditampung di ring buffer 20 paket (dilindungi `portENTER_CRITICAL`) selama HTTP POST berjalan. Paket yang dibuang karena antrean penuh dihitung dan dicetak (`[GATEWAY OVERFLOW]`) agar *drop* di sisi gateway tidak tertukar dengan kehilangan pada link radio.
   - *Upload file ini ke ESP32 Node 1.*

2. **Node 2 (`node_sensor_relay.ino`) - Kamar**:
   - Membaca sensor MQ lokal miliknya sendiri dan mengirimnya ke Node 1 via ESP-NOW (`hop_count = 1`, `rssi = 0`; tiba di server sebagai `hop_count = 2`).
   - Bertindak sebagai **Relay**: mendengarkan paket dari Node 3, **mengukur RSSI link 3→2** dari paket yang diterima, menambah `hop_count` (+1), lalu meneruskannya ke Node 1.
   - Paket dengan `hop_count > 1` (sudah pernah di-relay) diabaikan sehingga relay tidak pernah me-relay ulang.
   - *Upload file ini ke ESP32 Node 2.*

3. **Node 3 (`node_sensor_leaf.ino`) - Dapur (Blind Spot)**:
   - Membaca sensor MQ lokal miliknya sendiri (`Node 3`).
   - Berada di blind spot tanpa jangkauan WiFi langsung.
   - Memancarkan datanya melalui ESP-NOW broadcast (`hop_count = 1`, `rssi = 0`). Tiba di server sebagai `hop_count = 3` bila lewat relay, atau `2` bila tertangkap langsung oleh gateway.
   - *Upload file ini ke ESP32 Node 3.*

---

## 📏 Definisi Metrik Jaringan (Seragam untuk Star & Mesh)

Agar perbandingan Star vs Mesh valid, `rssi` dan `hop_count` didefinisikan sama di semua topologi dan **selalu hasil pengukuran** (tidak ada nilai *hardcoded*).

### `rssi` — RSSI link terlemah sampai AP
`rssi` = nilai RSSI **terlemah (minimum, dBm)** di antara seluruh link nirkabel yang dilalui paket dari node sensor sampai WiFi Access Point. RSSI hanya bisa diukur oleh **penerima**, sehingga:

1. Node pengirim ESP-NOW mengisi `rssi = 0` (artinya "belum terukur").
2. Setiap penerima ESP-NOW (Relay, Gateway) membaca `info->rx_ctrl->rssi` lalu menerapkan:
   `rssi = (rssi == 0) ? terukur : min(rssi, terukur)`.
3. Root Gateway, saat mem-forward paket apa pun via WiFi, menerapkan `min` yang sama dengan `WiFi.RSSI()` (link Gateway → AP).

| Topologi / Node | Link yang dilalui | Nilai `rssi` |
|---|---|---|
| Star (semua node) | Node → AP | `WiFi.RSSI()` |
| Mesh Node 1 (data sendiri) | Node 1 → AP | `WiFi.RSSI()` |
| Mesh Node 2 | 2 → 1, 1 → AP | `min(RSSI 2→1, RSSI 1→AP)` |
| Mesh Node 3 via relay | 3 → 2, 2 → 1, 1 → AP | `min(RSSI 3→2, RSSI 2→1, RSSI 1→AP)` |

### `hop_count` — jumlah transmisi nirkabel sampai AP
`hop_count` = jumlah transmisi nirkabel sampai paket mencapai WiFi AP. Pengirim ESP-NOW mengisi `1` untuk transmisinya sendiri, Relay menambah `+1`, dan Root Gateway menambah `+1` saat mem-forward paket hasil ESP-NOW (hop Gateway → AP).

| Topologi / Node | `hop_count` |
|---|---|
| Star (semua node) | 1 |
| Mesh Node 1 (data sendiri) | 1 |
| Mesh Node 2 | 2 |
| Mesh Node 3 via relay | 3 |
| Mesh Node 3 tertangkap langsung oleh gateway | 2 |

---

## 🔑 API Key (Opsional)

Jika backend Laravel mengaktifkan `TELEMETRY_API_KEY` di file `.env`, setiap request wajib membawa header `X-Api-Key`. Isi konstanta berikut di `node_sensor_star.ino` dan `node_gateway_root.ino` dengan nilai yang sama:

```cpp
const char* API_KEY = "isi-dengan-TELEMETRY_API_KEY";
```

Header `X-Api-Key` hanya dikirim bila `API_KEY` tidak kosong. Node relay & leaf tidak memerlukannya karena tidak berkomunikasi langsung dengan server.

---

## 📦 Format Data Payload JSON ke Backend Laravel

Setiap paket yang dikirim ke endpoint `POST /api/telemetry` memiliki format standar:

```json
{
  "node_id": 1,
  "co2": 420.50,
  "nh3": 12.30,
  "co": 4.50,
  "h2": 1.80,
  "rssi": -65,
  "seq_number": 101,
  "hop_count": 1,
  "topology_type": "star"
}
```

### Keterangan Parameter:
- `node_id` *(integer)*: ID unik perangkat (`1`, `2`, atau `3`).
- `co2` *(float)*: Konsentrasi gas Karbon Dioksida dalam satuan PPM.
- `nh3` *(float)*: Konsentrasi gas Amonia dalam satuan PPM.
- `co` *(float)*: Konsentrasi gas Karbon Monoksida dalam satuan PPM.
- `h2` *(float)*: Konsentrasi gas Hidrogen dalam satuan PPM.
- `rssi` *(integer)*: RSSI link nirkabel **terlemah** sepanjang jalur paket sampai AP, dalam dBm (lihat Definisi Metrik).
- `seq_number` *(integer, wajib)*: Nomor urut paket per node (mulai dari 1, reset saat node restart) untuk kalkulasi **Packet Delivery Ratio (PDR)** dan **Packet Loss Rate** di modul QoS Analytics.
- `hop_count` *(integer, wajib)*: Jumlah transmisi nirkabel sampai AP (lihat Definisi Metrik).
- `topology_type` *(string)*: `"star"` atau `"mesh"`.


---

## ⚠️ Batasan

- **"Mesh" di sini adalah relay multi-hop statis**, bukan mesh dinamis. Rute tetap 3 → 2 → 1: tidak ada *route discovery*, tidak ada *re-routing* otomatis bila Node 2 mati, dan relay hanya meneruskan paket satu kali (`hop_count == 1`). Karena semua transmisi adalah broadcast, Node 3 kadang tertangkap langsung oleh gateway (tercatat `hop_count = 2`); duplikat dibuang di gateway.
- **Topologi dan protokol berubah bersamaan**: Star memakai WiFi (802.11 + TCP/HTTP langsung ke server), sedangkan Mesh memakai ESP-NOW (frame vendor-specific tanpa koneksi) di antara node dan hanya gateway yang memakai WiFi/HTTP. Perbedaan PDR, latensi, atau RSSI antara Star dan Mesh karena itu tidak dapat diatribusikan murni pada topologi — sebagian berasal dari perbedaan protokol.
- ESP-NOW broadcast tidak memiliki ACK/retransmisi di lapisan MAC, sedangkan WiFi unicast memilikinya; hal ini memengaruhi perbandingan PDR.
- Paket yang dibuang karena antrean penuh di gateway/relay dicatat di Serial Monitor (`[GATEWAY OVERFLOW]` / `[RELAY OVERFLOW]`) dan tetap muncul sebagai *packet loss* di server; periksa log tersebut sebelum menyimpulkan kehilangan berasal dari link radio.
- Kalibrasi R0 memakai rasio udara bersih dan kurva karakteristik dari datasheet/literatur; konsentrasi absolut (ppm) tetap merupakan estimasi, bukan hasil alat ukur referensi tersertifikasi.

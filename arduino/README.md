# Panduan Firmware ESP32 — SIMKU

Semua sketsa di folder ini membaca sensor dengan **logika yang sama** dan mengirim data dengan
**format yang sama** ke aplikasi SIMKU. Yang berbeda hanya cara data sampai ke server.

| Sketsa | Dipakai untuk | Jalur data |
|---|---|---|
| [`simku_monitor/`](simku_monitor/simku_monitor.ino) | Satu perangkat (AIR-001), dipakai aplikasi dan penelitian saat ini | ESP32 → WiFi → SIMKU |
| [`topologi-star/node_sensor_star/`](topologi-star/node_sensor_star/node_sensor_star.ino) | Uji topologi star, tiga perangkat masing-masing langsung ke WiFi | Setiap node → WiFi → halaman uji topologi |
| [`topologi-mesh/`](topologi-mesh/) | Uji topologi mesh, node yang jauh dari WiFi meneruskan lewat node lain | Node 3 → Node 2 → Node 1 (ESP-NOW) → WiFi → halaman uji topologi |

Kebutuhan: **ESP32 Arduino core versi 3 atau lebih baru** (Boards Manager: *esp32 by Espressif
Systems* 3.x). Board: DOIT ESP32 DEVKIT V1. Serial Monitor 115200 baud.

---

## Logika pembacaan sensor (sama di semua sketsa)

- Sensor: MQ-135 (CO₂, NH₃) di **GPIO 35** dan MQ-2 (CO, H₂) di **GPIO 34**.
- Saat menyala: pemanasan **30 detik**, lalu **baseline** = rata-rata 50 pembacaan ADC tiap sensor.
- Setiap 5 detik: ppm dihitung dengan rumus Solihat, S. M., Salsabila, S., & Susilawati, S. (2026),
  JITET 14(1), hlm. 1596:

  `ppm = max(0, adc - baseline) * (5.0 / 4096.0) * k`, dengan `k` = 100 (CO₂), 80 (NH₃), 50 (CO), 30 (H₂).

  Nilai adalah **indikator relatif** terhadap kondisi udara saat perangkat dinyalakan, bukan
  konsentrasi absolut. Jangan menyalakan ulang perangkat di tengah pengujian, karena baseline diukur ulang.
- `TAMPILKAN_ADC_MENTAH = true` mencetak ADC mentah, baseline, tegangan pin, dan sebaran 10 pembacaan
  cepat di Serial Monitor untuk memeriksa kabel dan daya. Nilai yang dikirim tidak berubah.
- Waktu pembacaan diambil dari NTP (WIB). Data tidak dikirim sebelum waktu tersinkron.
- Penyesuaian rumus dan kalibrasi adalah tanggung jawab penelitian IoT dan perlu ditinjau rekan IoT.

## Format data

```json
{
  "device_id": "AIR-002",
  "timestamp": "2026-11-07 14:05:30",
  "timestamp_ms": 512,
  "co2_ppm": 12.50,
  "nh3_ppm": 10.00,
  "co_ppm": 6.25,
  "h2_ppm": 3.75,
  "rssi": -71,
  "seq_number": 120,
  "hop_count": 2,
  "topology_type": "mesh"
}
```

Kolom `device_id`, `timestamp`, dan keempat `*_ppm` sama dengan yang diterima SIMKU
(`POST /api/telemetry`). `simku_monitor` hanya mengirim kolom tersebut. Sketsa star dan mesh
menambahkan `timestamp_ms` (milidetik waktu kirim, untuk delay) dan metrik jaringan (`rssi`,
`seq_number`, `hop_count`, `topology_type`).

## Halaman uji topologi (star dan mesh)

Sketsa star dan mesh mengirim ke **`POST /api/uji-topologi`**, bukan ke `/api/telemetry`. Datanya
disimpan terpisah, sehingga tidak tampil di Beranda, Riwayat, atau halaman SIMKU lain. Hasilnya
dilihat di halaman **`/uji-topologi`**. Halaman ini tidak ada di menu dan tidak diindeks mesin
pencari, jadi hanya bisa dibuka dengan mengetik alamatnya. Isinya:

- status ketiga perangkat (terhubung/terputus, data terakhir, topologi, RSSI, hop, seq);
- ringkasan QoS per perangkat dan topologi: paket diterima dan diharapkan, PDR, packet loss,
  duplikat, **delay**, **jitter**, **throughput**, RSSI rata-rata dan terlemah, hop rata-rata,
  selang antardata;
- daftar paket dan tombol Unduh CSV, dengan filter topologi dan rentang waktu sesi uji.

Perangkat yang diterima: AIR-001, AIR-002, AIR-003 (atur di `config/topology_test.php`). Perangkat ini
**tidak perlu didaftarkan** di SIMKU.

Alamatnya diatur `TOPOLOGY_TEST_PATH` di `.env` (bawaan `uji-topologi`). Ganti dengan kata yang sulit
ditebak, misalnya `TOPOLOGY_TEST_PATH=uji-topologi-7f3k`, lalu samakan `SERVER_URL` di sketsa star dan
gateway (`https://<domain>/api/uji-topologi-7f3k`) dan jalankan `php artisan config:clear`. Bila
`TELEMETRY_API_KEY` diisi, endpoint ini juga mewajibkan header `X-Api-Key`.

### Uji lokal dan Wireshark

Alamat bawaan sketsa star dan gateway mesh adalah server lokal **`http://192.168.137.1:8000`**
(IP laptop saat hotspot Windows aktif), sehingga lalu lintas HTTP bisa direkam Wireshark tanpa enkripsi.

1. Nyalakan hotspot laptop dan sambungkan ESP32 ke hotspot itu (`WIFI_SSID`, `WIFI_PASSWORD`).
2. Jalankan aplikasi agar bisa diakses dari jaringan hotspot, bukan hanya dari laptop:

   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

   Izinkan port 8000 di Windows Firewall bila diminta.
3. Hotspot harus meneruskan internet agar jam NTP perangkat tersinkron. Tanpa internet, jalankan
   server NTP di laptop dan isi `NTP_SERVER = "192.168.137.1"`.
4. Di Wireshark, rekam antarmuka hotspot (biasanya *Local Area Connection\** atau *Microsoft Wi-Fi
   Direct Virtual Adapter*) dengan filter tampilan `tcp.port == 8000` atau `http.request.method == "POST"`.

Bila server dijalankan di alamat lain, ganti `SERVER_URL` dan `HANDSHAKE_URL` di sketsa.

### Handshake

Setelah tersambung ke WiFi, node star dan gateway mesh mengirim handshake ke
`POST /api/uji-topologi/handshake` sebelum mengirim data:

```json
{ "device_id": "AIR-001", "ip": "192.168.137.23", "mac": "A4:CF:12:9B:3E:01", "topology_type": "star", "rssi": -58 }
```

- Server menjawab HTTP 200 berisi `server_time`; data baru dikirim setelah jawaban ini diterima.
- Bila gagal, handshake dicoba lagi tiap 5 detik **paling banyak 3 kali**. Setelah itu handshake
  dilewati dan data tetap dikirim (server mencatat IP dari paket data). Serial Monitor mencetak
  penyebab gagalnya, misalnya server tidak terjangkau, alamat 404, atau galat 500 (`php artisan migrate`).
- Handshake diulang hanya saat WiFi tersambung ulang atau IP berubah.
- Saat menyala, Serial Monitor mencetak alasan board menyala (mis. `BROWNOUT`). Bila handshake
  terus muncul dengan alasan selain "daya dinyalakan", board-nya yang terus restart; periksa catu daya.
- Server hanya mewajibkan `device_id`; isian lain yang formatnya salah diabaikan, bukan ditolak.
- Halaman `/uji-topologi` menampilkan IP setiap perangkat, IP yang terlihat oleh server (berbeda bila
  ada NAT), MAC, jumlah handshake, dan waktu handshake terakhir.
- Node 2 dan Node 3 mesh tidak punya IP (komunikasinya lewat ESP-NOW), sehingga hanya gateway yang
  melakukan handshake.

### Delay, jitter, dan throughput

- **Delay** setiap paket = waktu terima server − waktu kirim (`timestamp` + `timestamp_ms`), dalam
  milidetik. Waktu kirim diambil tepat sebelum HTTP POST. Ketelitiannya bergantung pada jam NTP
  perangkat dan server.
- Pada mesh, Node 2 dan Node 3 tidak punya NTP. Relay menambahkan lama paket ditahan di antreannya ke
  `umur_ms`, lalu gateway memundurkan timestamp sebesar `umur_ms` + lama paket di antrean gateway.
  Hasilnya, delay mesh mencakup perjalanan dari node asal sampai server.
- **Jitter** = rata-rata selisih mutlak delay dua paket berurutan.
- **Throughput** = total bit payload yang diterima ÷ rentang waktu paket pertama sampai terakhir (bit/s).

### Data Node 1 ke monitoring SIMKU

SIMKU utama (`POST /api/telemetry`, monitoring ruangan Lab. DSK) hanya menerima **AIR-001**. Dengan
`KIRIM_KE_SIMKU = true` (bawaan), Node 1 star dan gateway mesh juga mengirim data AIR-001 ke
`SIMKU_URL`, sesudah pengiriman ke endpoint uji agar tidak menambah delay uji. Data AIR-002 dan
AIR-003 hanya masuk ke halaman uji topologi. `SIMKU_URL` bawaan mengarah ke server online (HTTPS); saat uji lokal
dengan Wireshark, isi `KIRIM_KE_SIMKU = false` bila rekaman hanya boleh berisi lalu lintas ke laptop.

## Konfigurasi di bagian atas sketsa

| Konstanta | Ada di | Isi |
|---|---|---|
| `WIFI_SSID`, `WIFI_PASSWORD` | simku_monitor, star, gateway | Jaringan WiFi |
| `SERVER_URL` | simku_monitor, star, gateway | `simku_monitor`: `…/api/telemetry`. Star dan gateway: `http://192.168.137.1:8000/api/uji-topologi` |
| `HANDSHAKE_URL` | star, gateway | `http://192.168.137.1:8000/api/uji-topologi/handshake` |
| `NTP_SERVER` | star, gateway | Server waktu, bawaan `pool.ntp.org` |
| `KIRIM_KE_SIMKU`, `SIMKU_URL` | star, gateway | Data AIR-001 juga dikirim ke `…/api/telemetry` |
| `API_KEY` | simku_monitor, star, gateway | Opsional, sama dengan `TELEMETRY_API_KEY` di `.env` |
| `DEVICE_ID` / `NODE_ID` | semua | `simku_monitor`: `DEVICE_ID`. Star dan mesh: `NODE_ID` 1/2/3 → `AIR-001`/`AIR-002`/`AIR-003` |
| `WIFI_SSID_GATEWAY` | relay, leaf | Nama hotspot yang dipakai gateway (sama dengan `WIFI_SSID` gateway). Saat menyala, relay dan leaf memindai kanal hotspot ini otomatis, karena ESP-NOW hanya sampai bila kanalnya sama |
| `WIFI_CHANNEL` | relay, leaf | Kanal cadangan bila hotspot tidak ditemukan saat pemindaian |
| `PIN_MQ135`, `PIN_MQ2` | semua | Pin ADC sensor |

---

## Topologi star

Satu sketsa untuk semua node. Flash `node_sensor_star.ino` ke setiap board dengan `NODE_ID`
berbeda (1, 2, 3). Setiap node terhubung langsung ke WiFi dan mengirim datanya sendiri
(`hop_count = 1`, `rssi = WiFi.RSSI()`).

## Topologi mesh (ESP-NOW multi-hop)

```
[Node 3 Leaf] ──ESP-NOW──> [Node 2 Relay] ──ESP-NOW──> [Node 1 Root Gateway] ──WiFi──> [AP] ──> SIMKU
```

| Node | Sketsa | Peran |
|---|---|---|
| 1 (AIR-001) | `node_gateway_root` | Membaca sensornya sendiri, menerima paket ESP-NOW, dan mengirim semua data ke SIMKU lewat WiFi |
| 2 (AIR-002) | `node_sensor_relay` | Membaca sensornya sendiri dan meneruskan paket Node 3 ke Node 1 |
| 3 (AIR-003) | `node_sensor_leaf` | Membaca sensornya sendiri di area tanpa WiFi dan memancarkannya |

- Node 2 dan Node 3 tidak punya WiFi dan jam NTP. **Root Gateway mengisi `timestamp`** dari jam
  NTP-nya sesuai waktu paket diterima. Paket ditahan di antrean sampai jam gateway tersinkron.
- Pemanasan sensor di gateway dan relay tidak menghentikan penerusan paket node lain.
- Semua paket ESP-NOW dikirim broadcast, sehingga paket Node 3 bisa tiba langsung dan lewat relay.
  Gateway membuang salinan kedua berdasarkan `seq_number` (log `[MESH DUPLIKAT]`).
- Paket yang terbuang karena antrean penuh dicatat (`[GATEWAY OVERFLOW]`, `[RELAY OVERFLOW]`),
  agar tidak tertukar dengan kehilangan di link radio.
- Struktur paket ESP-NOW (termasuk `umur_ms`) harus sama di ketiga sketsa; flash ulang ketiganya bersamaan.

### Metrik jaringan

| Topologi / Node | `hop_count` | `rssi` (link terlemah sampai AP) |
|---|---|---|
| Star (semua node) | 1 | `WiFi.RSSI()` |
| Mesh Node 1 | 1 | RSSI Node 1 → AP |
| Mesh Node 2 | 2 | min(RSSI 2 → 1, RSSI 1 → AP) |
| Mesh Node 3 lewat relay | 3 | min(RSSI 3 → 2, 2 → 1, 1 → AP) |
| Mesh Node 3 tertangkap langsung gateway | 2 | min(RSSI 3 → 1, 1 → AP) |

Pengirim ESP-NOW mengisi `rssi = 0` (belum terukur); setiap penerima mengambil nilai terkecil
dari RSSI paket dan RSSI yang diukurnya.

## Batasan

- "Mesh" di sini adalah relay multi-hop statis (rute tetap 3 → 2 → 1), bukan mesh dinamis: tidak
  ada pencarian rute dan tidak ada pengalihan otomatis bila Node 2 mati.
- Star memakai WiFi (dengan ACK dan TCP), mesh memakai ESP-NOW broadcast tanpa ACK di antara node.
  Perbedaan hasil star dan mesh karena itu sebagian berasal dari perbedaan protokol, bukan hanya topologi.
- Folder `kalibrasi_r0/` berisi sketsa kalibrasi R0 untuk rumus lama berbasis kurva datasheet.
  Rumus Solihat yang dipakai sekarang tidak memerlukan R0, sehingga sketsa tersebut tidak dipakai lagi.

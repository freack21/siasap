# Panduan Firmware ESP32 — SIMKU

Semua sketsa di folder ini membaca sensor dengan **logika yang sama** dan mengirim data dengan
**format yang sama** ke aplikasi SIMKU. Yang berbeda hanya cara data sampai ke server.

| Sketsa | Dipakai untuk | Jalur data |
|---|---|---|
| [`simku_monitor/`](simku_monitor/simku_monitor.ino) | Satu perangkat (AIR-001), dipakai aplikasi dan penelitian saat ini | ESP32 → WiFi → SIMKU |
| [`topologi-star/node_sensor_star/`](topologi-star/node_sensor_star/node_sensor_star.ino) | Beberapa perangkat, masing-masing langsung ke WiFi | Setiap node → WiFi → SIMKU |
| [`topologi-mesh/`](topologi-mesh/) | Tiga perangkat, node yang jauh dari WiFi meneruskan lewat node lain | Node 3 → Node 2 → Node 1 (ESP-NOW) → WiFi → SIMKU |

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

## Format data ke `POST /api/telemetry`

```json
{
  "device_id": "AIR-002",
  "timestamp": "2026-11-07 14:05:30",
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

Enam kolom pertama dipakai aplikasi. `simku_monitor` hanya mengirim enam kolom tersebut. Sketsa
star dan mesh menambahkan metrik jaringan (`rssi`, `seq_number`, `hop_count`, `topology_type`)
untuk analisis penelitian IoT; aplikasi SIMKU menerimanya tetapi tidak menyimpannya.

## Mendaftarkan perangkat AIR-002 dan AIR-003

Aplikasi hanya menerima `device_id` yang terdaftar. Data awal hanya berisi **AIR-001**. Sebelum
memakai star atau mesh dengan lebih dari satu perangkat, daftarkan perangkat lainnya sekali saja
dari folder aplikasi:

```bash
php artisan tinker --execute '$r = App\Models\Room::where("code", "ROOM-01")->firstOrFail(); foreach (["AIR-002" => "Perangkat IoT 2", "AIR-003" => "Perangkat IoT 3"] as $kode => $nama) { App\Models\Node::updateOrCreate(["code" => $kode], ["room_id" => $r->id, "name" => $nama, "is_active" => true]); }'
```

Perintah di atas memasang ketiga perangkat di ruangan RC1TI. Aplikasi menampilkan data terbaru
per ruangan, jadi bila perangkat dipasang di lokasi berbeda, buat ruangan terpisah untuk masing-masing.
Tanpa pendaftaran ini, data AIR-002/AIR-003 ditolak dengan HTTP 422 (`device_id tidak terdaftar`).

## Konfigurasi di bagian atas sketsa

| Konstanta | Ada di | Isi |
|---|---|---|
| `WIFI_SSID`, `WIFI_PASSWORD` | simku_monitor, star, gateway | Jaringan WiFi |
| `SERVER_URL` | simku_monitor, star, gateway | URL aplikasi, mis. `http://192.168.137.1:8000/api/telemetry` |
| `API_KEY` | simku_monitor, star, gateway | Opsional, sama dengan `TELEMETRY_API_KEY` di `.env` |
| `DEVICE_ID` / `NODE_ID` | semua | `simku_monitor`: `DEVICE_ID`. Star dan mesh: `NODE_ID` 1/2/3 → `AIR-001`/`AIR-002`/`AIR-003` |
| `WIFI_CHANNEL` | relay, leaf | Kanal WiFi AP; lihat baris `Kanal WiFi` di Serial Monitor gateway |
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
- Struktur paket ESP-NOW harus sama di ketiga sketsa; ubah ketiganya bersamaan.

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

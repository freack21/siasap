# Panduan Firmware ESP32 — Sistem Pemantauan Kualitas Udara (SIASAP)

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

## 📡 1. Panduan Penggunaan: Topologi Star

Pada topologi **Star**, setiap node terhubung langsung ke router WiFi lokal (*Access Point*) dan mengirim data secara simultan langsung ke endpoint API Laravel.

### Langkah-langkah:
1. Buka file `arduino/topologi-star/node_sensor_star/node_sensor_star.ino` di Arduino IDE.
2. Sesuaikan konfigurasi WiFi & IP Server di baris 20–25:
   ```cpp
   const char* WIFI_SSID     = "cargo";
   const char* WIFI_PASSWORD = "cargocargo";
   const char* SERVER_URL    = "http://192.168.137.1:8000/api/telemetry"; // Ganti dengan IP lokal laptop/server Laravel Anda
   ```
3. Sesuaikan **`NODE_ID`** di baris 33 untuk setiap board ESP32 yang di-flash:
   - Untuk perangkat di **Ruang Tamu**: `#define NODE_ID 1`
   - Untuk perangkat di **Kamar**: `#define NODE_ID 2`
   - Untuk perangkat di **Dapur**: `#define NODE_ID 3`
4. Pilih Board: **DOIT ESP32 DEVKIT V1**, pilih Port COM yang sesuai, lalu klik **Upload**.
5. Buka Serial Monitor (`115200 baud`) untuk memantau status pengiriman HTTP POST.

---

## 🕸️ 2. Panduan Penggunaan: Topologi Mesh (ESP-NOW Multi-Hop)

Pada topologi **Mesh**, sistem mengatasi skenario area *blind spot* (misalnya Node 3 diletakkan jauh di belakang dinding tebal tanpa jangkauan WiFi):

```
[Node 3 (Leaf)] ──(Hop 1)──> [Node 2 (Relay)] ──(Hop 2)──> [Node 1 (Root Gateway)] ──(WiFi)──> [Laravel SIASAP]
```

### Penjelasan Peran Perangkat:
1. **Node 1 (`node_gateway_root.ino`) - Ruang Tamu**:
   - Terkoneksi ke WiFi router dan server Laravel.
   - Membaca sensor MQ lokal miliknya sendiri (`Node 1`, `Hop Count = 1`).
   - Bertindak sebagai **Sink / Gateway**: menerima paket ESP-NOW dari Node 2 dan Node 3, lalu otomatis mem-forward ke REST API Laravel.
   - *Upload file ini ke ESP32 Node 1.*

2. **Node 2 (`node_sensor_relay.ino`) - Kamar**:
   - Membaca sensor MQ lokal miliknya sendiri (`Node 2`, `Hop Count = 1`).
   - Mengirim datanya ke Node 1 via ESP-NOW.
   - Bertindak sebagai **Relay**: mendengarkan paket dari Node 3, menambahkan nilai `hop_count` (+1 menjadi 2 Hop), dan meneruskannya ke Node 1.
   - *Upload file ini ke ESP32 Node 2.*

3. **Node 3 (`node_sensor_leaf.ino`) - Dapur (Blind Spot)**:
   - Membaca sensor MQ lokal miliknya sendiri (`Node 3`).
   - Berada di blind spot tanpa jangkauan WiFi langsung.
   - Memancarkan datanya ke Node 2 melalui ESP-NOW broadcast.
   - *Upload file ini ke ESP32 Node 3.*

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
- `rssi` *(integer)*: Kekuatan sinyal radio transmisi dalam satuan dBm.
- `seq_number` *(integer)*: Nomor urut paket untuk kalkulasi **Packet Delivery Ratio (PDR)** dan **Packet Loss Rate** di modul QoS Analytics.
- `hop_count` *(integer)*: Jumlah lompatan paket (`1` untuk Star atau transmisi langsung, `2` untuk transmisi estafet Mesh).
- `topology_type` *(string)*: `"star"` atau `"mesh"`.

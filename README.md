# SIMKU — Sistem Monitoring Kualitas Udara Ruangan Berbasis IoT

Aplikasi web untuk memantau kualitas udara ruangan secara *real-time* dari data perangkat IoT ESP32 (sensor MQ-135 dan MQ-2). Perangkat mengirim nilai ppm empat gas (CO₂, NH₃, CO, H₂) setiap 5 detik. Server memvalidasi data, menentukan kategori tiap gas dan kategori keseluruhan berdasarkan ambang batas, lalu menampilkan rekomendasi tindakan preventif sesuai kategori. Aplikasi hanya menyajikan data; tidak ada analisis tren maupun prediksi.

Dikembangkan dengan model **Waterfall**. Pengujian memakai *black-box testing* (lihat [`docs/pengujian-black-box.md`](docs/pengujian-black-box.md)) dan evaluasi pengguna memakai kuesioner **UEQ** (*User Experience Questionnaire*, 26 butir).

## Fitur

Seluruh halaman bersifat publik (tanpa login) dan diakses lewat navigasi atas. Halaman pemantauan diperbarui otomatis setiap 5 detik.

| Halaman | Rute | Isi |
|---|---|---|
| Beranda | `/` | Kategori keseluruhan, penyebab utama, kartu 4 gas (ppm + kategori), rekomendasi tindakan, status perangkat. Bila hanya ada 1 ruangan, langsung menampilkan ruangan tersebut. |
| Detail Ruangan | `/ruangan/{room}` | Grafik per gas (CO₂/NH₃/CO/H₂) dengan garis ambang kategori, rentang 1 jam / 24 jam / 7 hari, statistik, 10 pembacaan terakhir |
| Riwayat | `/riwayat` | Filter ruangan, tanggal, dan kategori; ringkasan periode; *pagination*; unduh CSV |
| Rekomendasi | `/rekomendasi` | Kategori dan rekomendasi saat ini, tabel 4 aturan, dasar rekomendasi (U.S. EPA), pernyataan bukan diagnosis medis |
| Perangkat | `/perangkat` | Device ID, gas yang dikirim, waktu data terakhir, status koneksi (terhubung bila data terakhir ≤ 30 detik), interval terukur, alur data, 10 penolakan data terakhir |
| Panduan | `/panduan` | Cara membaca kategori, tabel ambang batas, keterangan 4 gas, FAQ, batasan |
| Kuesioner | `/kuesioner` | Kuesioner UEQ 26 butir, skala 7 titik, nama responden opsional (maksimal 3 pengisian per jam per IP) |

## Klasifikasi dan rekomendasi

Ambang batas (ppm) diambil dari `config/air_quality.php`, bersumber dari Solihat, S. M., Salsabila, S., & Susilawati, S. (2026). *Sistem Pemantauan Kualitas Udara Berbasis ESP32 Menggunakan Sensor Gas MQ-135 dan MQ-2*. JITET, 14(1), 1592–1599. doi:[10.23960/jitet.v14i1.5568](https://doi.org/10.23960/jitet.v14i1.5568).

| Gas | Baik | Sedang | Tidak Sehat | Berbahaya |
|---|---|---|---|---|
| CO₂ | < 50 | 50 – < 100 | 100 – < 150 | ≥ 150 |
| NH₃ | < 40 | 40 – < 80 | 80 – < 120 | ≥ 120 |
| CO | < 20 | 20 – < 50 | 50 – < 80 | ≥ 80 |
| H₂ | < 15 | 15 – < 40 | 40 – < 60 | ≥ 60 |

- Batas bersifat eksklusif: nilai tepat di batas masuk kategori berikutnya (mis. CO₂ 50 → Sedang, CO₂ 150 → Berbahaya).
- **Kategori keseluruhan** = kategori terburuk di antara keempat gas. **Penyebab utama** = semua gas dengan kategori tersebut (tidak ditampilkan bila Baik).
- Nilai ppm adalah **indikator relatif** terhadap udara saat perangkat dinyalakan (baseline), mengikuti metode konversi Solihat et al. (2026), bukan konsentrasi absolut.
- Rekomendasi (`config/recommendations.php`) ditentukan dari kategori keseluruhan: Baik → Normal, Sedang → Waspada, Tidak Sehat → Perlu Tindakan, Berbahaya → Segera Bertindak.

## Teknologi

- PHP 8.3, Laravel 13, Livewire 4
- Tailwind CSS dan Vite (aset *frontend*), Chart.js (grafik)
- SQLite (bawaan) atau MySQL
- PHPUnit 12 untuk pengujian
- Firmware ESP32 (Arduino core 3) dengan sensor MQ-135 dan MQ-2

## Menjalankan

Kebutuhan: PHP 8.3+, Composer, Node.js, dan basis data (SQLite bawaan atau MySQL).

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

`php artisan migrate --seed` membuat tabel dan mengisi data awal: satu ruangan **Ruang Uji** (kode `ROOM-01`) dengan satu perangkat aktif **Perangkat IoT 1** (kode `AIR-001`). Kode perangkat harus sama dengan `DEVICE_ID` di firmware.

Mode pengembangan (server, *queue*, dan Vite sekaligus):

```bash
composer run dev
```

**Agar dapat dijangkau ESP32**, server harus mendengarkan di jaringan lokal, bukan hanya `localhost`:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Lalu isi `SERVER_URL` di sketsa firmware dengan `http://<ip-laptop>:8000/api/telemetry` (IP laptop di jaringan WiFi yang sama dengan ESP32).

## API untuk perangkat IoT

### `POST /api/telemetry`

Kirim JSON (`Content-Type: application/json`) dengan format naskah 3.9.1:

```json
{
  "device_id": "AIR-001",
  "timestamp": "2026-05-01 10:00:00",
  "co2_ppm": 70,
  "nh3_ppm": 30,
  "co_ppm": 55,
  "h2_ppm": 10
}
```

| Field | Aturan |
|---|---|
| `device_id` | Wajib; kode perangkat yang terdaftar dan aktif |
| `timestamp` | Wajib; format `Y-m-d H:i:s` (waktu perangkat) |
| `co2_ppm`, `nh3_ppm`, `co_ppm`, `h2_ppm` | Wajib; angka ≥ 0 |

**Autentikasi (opsional).** Bila `TELEMETRY_API_KEY` di `.env` diisi, setiap permintaan wajib menyertakan header `X-Api-Key` dengan nilai yang sama; bila tidak, server menjawab HTTP 401. Kosongkan untuk menonaktifkan.

**Batas laju.** Endpoint dibatasi 60 permintaan per menit; permintaan berlebih dijawab HTTP 429. Interval kirim firmware (5 detik) jauh di bawah batas ini.

**Data valid → HTTP 201.** Data disimpan bersama kategori tiap gas, kategori keseluruhan, penyebab utama, dan tingkat rekomendasi. Contoh respons untuk payload di atas:

```json
{
  "status": "success",
  "data": {
    "id": 1,
    "device_id": "AIR-001",
    "recorded_at": "2026-05-01 10:00:00",
    "categories": { "co2": "Sedang", "nh3": "Baik", "co": "Tidak Sehat", "h2": "Baik" },
    "overall_category": "Tidak Sehat",
    "dominant_gases": ["co"],
    "recommendation": {
      "level": "Perlu Tindakan",
      "text": "Tingkatkan ventilasi, periksa dan kurangi sumber polutan seperti asap atau gas, serta kurangi aktivitas di dalam ruangan."
    }
  }
}
```

**Data tidak valid → HTTP 422.** Respons berisi `status: "error"` dan objek `errors` yang memuat daftar pesan galat (berbahasa Indonesia) per field yang gagal. Bentuknya:

```json
{
  "status": "error",
  "errors": {
    "co2_ppm": ["<pesan galat untuk co2_ppm>"]
  }
}
```

**Penolakan dicatat.** Setiap data yang ditolak disimpan di tabel `telemetry_rejections` (device_id, payload, errors) dan ditulis ke log channel `telemetry` (`storage/logs/telemetry-*.log`). Payload yang disimpan hanya memuat field yang dikenal. Sepuluh penolakan terakhir tampil di halaman **Perangkat**.

**Format lama (sementara).** Selama transisi firmware, format lama `node_id`, `co2`, `nh3`, `co`, `h2` masih diterima dan dipetakan ke format baru: `node_id` diterjemahkan menjadi kode perangkat dan `timestamp` diisi waktu server. Field tambahan dari format lama diabaikan. Format ini akan dihapus setelah seluruh firmware memakai format baru.

### `GET /api/telemetry`

Mengembalikan 10 pembacaan terakhir untuk pemeriksaan cepat (memakai aturan `X-Api-Key` yang sama).

## Firmware

Sketsa perangkat ada di [`arduino/simku_monitor/simku_monitor.ino`](arduino/simku_monitor/simku_monitor.ino); panduan lengkap di [`arduino/README.md`](arduino/README.md). Isi konfigurasi di bagian atas sketsa: `WIFI_SSID`, `WIFI_PASSWORD`, `SERVER_URL`, `API_KEY` (opsional), dan `DEVICE_ID` (`AIR-001`).

Saat dinyalakan, perangkat memanaskan sensor, mengambil **baseline** dari udara saat itu, menyinkronkan waktu lewat NTP, lalu mengirim data tiap 5 detik. Nyalakan perangkat di udara ruangan normal dan jangan di-*restart* di tengah sesi pengujian agar baseline tidak berubah. Konversi ppm dan kalibrasi sensor merupakan tanggung jawab penelitian IoT.

## Perintah Artisan

| Perintah | Fungsi |
|---|---|
| `php artisan ueq:report` | Ringkasan hasil kuesioner UEQ: jumlah responden, rata-rata dan simpangan baku per skala, kategori *benchmark*, Cronbach's alpha, serta ekspor CSV data mentah (kolom butir 1–26 bernilai 1–7) yang kompatibel dengan *UEQ Data Analysis Tool*. Lokasi CSV bawaan `storage/app/ueq-export.csv`; ubah dengan `--csv=<path>`. |
| `php artisan telemetry:clear` | Mengosongkan data sensor dan penolakan data; data ruangan dan perangkat tetap. Tambahkan `--force` untuk melewati konfirmasi. |
| `php artisan telemetry:sweep --dari=2026-10-26 --sampai=2026-10-26` | Menghapus data sensor pada rentang waktu terima server (tanggal saja = sehari penuh, atau `"Y-m-d H:i:s"`). Opsi: `--perangkat=AIR-001`, `--pratinjau` (lihat dulu tanpa menghapus), `--force`. |

## Pengujian

```bash
php artisan test --compact                       # seluruh pengujian
php artisan test --compact tests/Feature/TelemetryApiTest.php
php artisan test --compact --filter=test_table_3_8_classification_via_api
```

Hasil pengujian dan pemetaannya ke Tabel 3.5–3.10 naskah ada di [`docs/pengujian-black-box.md`](docs/pengujian-black-box.md).

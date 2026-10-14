# SIASAP — Sistem Monitoring Kualitas Udara Ruangan Berbasis IoT

Aplikasi web untuk menyajikan data kualitas udara ruangan yang dikirim oleh node sensor ESP32 (MQ-2 dan MQ-135). Setiap node mengirim 4 parameter gas dalam ppm (CO₂, NH₃, CO, H₂); server menghitung **Indeks KU** dan kategori kualitas udara, lalu menampilkannya secara real-time.

Dikembangkan dengan metode **Research and Development (R&D)** dan model pengembangan **Waterfall**.

## Fitur

**Halaman pengguna** (publik, navigasi atas)

| Halaman | Isi |
|---|---|
| Beranda `/` | Status kualitas udara saat ini, kartu per ruangan, status perangkat |
| Detail Ruangan `/ruangan/{id}` | Nilai terkini 4 gas, sub-indeks, grafik tren 1 jam / 24 jam / 7 hari, statistik |
| Riwayat `/riwayat` | Filter ruangan, kategori, dan tanggal; ringkasan periode; unduh CSV |
| Panduan `/panduan` | Cara membaca Indeks KU, arti kategori, ambang batas tiap gas, FAQ |
| Kuesioner `/kuesioner` | Kuesioner System Usability Scale (SUS) untuk uji coba produk |

**Panel admin** (login, sidebar)

| Halaman | Isi |
|---|---|
| Ringkasan `/admin` | Statistik, status perangkat, grafik data per jam, peringatan terbaru |
| Data Sensor `/admin/data-sensor` | Tabel data lengkap dengan filter dan ekspor CSV |
| Ruangan & Perangkat `/admin/ruangan` | Kelola ruangan dan node sensor |
| Pengujian Jaringan `/admin/pengujian-jaringan` | Modul pendukung: QoS topologi Star vs Mesh (PDR, packet loss, RSSI, hop) |
| Hasil Kuesioner `/admin/kuesioner` | Skor SUS, grade, rata-rata per butir, ekspor CSV |

## Perhitungan Indeks KU

1. Setiap gas dikonversi menjadi **sub-indeks 0–500** dengan interpolasi linier antar titik batas (lihat `config/air_quality.php`).
2. **Indeks KU = sub-indeks tertinggi**; gas penyebabnya ditampilkan sebagai *parameter dominan*.
3. Kategori: Baik (0–50), Sedang (51–150), Tidak Sehat (151–300), Berbahaya (>300).

Ambang batas merupakan rujukan awal dan wajib dikonfirmasi bersama dosen pembimbing.

## Menjalankan

Kebutuhan: PHP 8.3+, Composer, Node.js, database (SQLite/MySQL).

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed --seeder=SetupRbacSeeder
npm install && npm run build
php artisan serve --host=0.0.0.0 --port=8000
```

Akun admin bawaan: `admin@siasap.test` / `password` (ganti setelah instalasi).

Perintah berguna:

- `php artisan test` — menjalankan seluruh pengujian otomatis
- `php artisan telemetry:clear` — mengosongkan data sensor dan sesi pengujian (data master tetap)

## API untuk perangkat IoT

`POST /api/telemetry` (JSON)

| Field | Wajib | Keterangan |
|---|---|---|
| `node_id` | ya | ID node terdaftar |
| `co2`, `nh3`, `co`, `h2` | ya | Konsentrasi gas (ppm) |
| `seq_number` | ya | Nomor urut paket per node |
| `hop_count` | ya | Jumlah lompatan nirkabel sampai access point |
| `rssi` | tidak | dBm; kosong bila tidak terukur |
| `topology_type` | tidak | `star` / `mesh` (diabaikan bila ada sesi pengujian aktif) |

Bila `TELEMETRY_API_KEY` diisi di `.env`, setiap permintaan wajib mengirim header `X-Api-Key`.

Firmware dan panduan perangkat ada di [`arduino/`](arduino/README.md).

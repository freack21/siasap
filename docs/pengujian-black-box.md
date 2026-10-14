# 4.x Pengujian Sistem (Black-Box Testing)

## 4.x.1 Pendahuluan

Pengujian sistem dilaksanakan pada tahap *Testing* dalam model pengembangan Waterfall, setelah tahap implementasi selesai. Metode yang digunakan adalah pengujian *black-box*, yaitu pengujian yang berfokus pada kesesuaian keluaran sistem terhadap masukan yang diberikan tanpa memperhatikan struktur kode program. Skenario uji disusun dengan dua pendekatan:

1. **Skenario fungsional**, yaitu memeriksa setiap fungsi yang tersedia pada halaman pengguna, panel pengelola, dan *endpoint* API sesuai kebutuhan fungsional.
2. **Equivalence partitioning dan analisis nilai batas (boundary value analysis)**, yaitu membagi data masukan ke dalam kelas valid dan tidak valid (misalnya nilai RSSI di dalam dan di luar rentang −120 s.d. 0 dBm) serta menguji nilai tepat pada batas kategori (misalnya indeks KU 50, 150, dan 300).

Lingkungan pengujian yang digunakan adalah sebagai berikut.

| Komponen | Spesifikasi |
|---|---|
| Bahasa pemrograman | PHP 8.3.6 (persyaratan proyek: PHP ^8.3) |
| *Framework* | Laravel Framework v13.34.0 |
| Komponen antarmuka | Livewire v4.4.7 |
| *Framework* pengujian | PHPUnit 12.5.37 |
| Basis data pengujian | SQLite *in-memory* (`DB_DATABASE=:memory:`), di-*reset* setiap kasus uji (`RefreshDatabase`) |
| Peramban (uji manual) | (diisi saat uji manual, misalnya nama dan versi peramban) |

Sebagian besar skenario pada bab ini telah diotomasi menggunakan PHPUnit (*feature test* dan *unit test*) sehingga dapat dijalankan ulang secara konsisten dengan perintah `php artisan test`. Hasil eksekusi terakhir menunjukkan **143 kasus uji lulus dengan 453 *assertion*** (0 gagal, durasi ±3,7 detik). Jumlah tersebut mencakup kasus uji dengan *data provider* (setiap set data dihitung sebagai satu kasus uji) dan dua kasus uji bawaan Laravel (`ExampleTest`). Kolom **Hasil Aktual** dan **Status** pada tabel berikut hanya diisi "Sesuai harapan" / "Valid" untuk skenario yang dicakup oleh kasus uji otomatis yang lulus. Skenario yang belum diotomasi ditandai "(diisi saat uji manual)" dengan status "—".

Akun uji yang digunakan berasal dari *seeder* `SetupRbacSeeder`, yaitu `admin@siasap.test` (peran *admin*, seluruh hak akses) dan peran *researcher* (hak akses `telemetry.view`, `qos.view`, `session.manage`, `rooms.manage`, tanpa `survey.view`).

---

## 4.x.2 Hasil Pengujian per Modul

### A. API Penerimaan Data Sensor (`POST /api/telemetry`)

Data uji dasar (payload valid): `node_id` = ID node terdaftar, `co2` = 420, `nh3` = 1, `co` = 0,5, `h2` = 5, `rssi` = −62, `seq_number` = 101, `hop_count` = 1.

| No | Skenario Pengujian | Langkah / Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| A1 | Mengirim payload valid | POST `/api/telemetry` dengan payload dasar | HTTP 201, `status: success`, data tersimpan di `sensor_logs` dengan `ku_status` = Baik, `seq_number` = 101, `rssi` = −62 | Sesuai harapan | Valid |
| A2 | Identitas node dikirim melalui field `node` (bukan `node_id`) | Payload dasar tanpa `node_id`, ditambah `node` = `"NODE_<id>"` | HTTP 201, data tersimpan dengan `node_id` hasil konversi | Sesuai harapan | Valid |
| A3 | Field `seq_number` kosong | Payload dasar tanpa `seq_number` | HTTP 422, galat validasi pada `seq_number`, tidak ada data tersimpan | Sesuai harapan | Valid |
| A4 | Field `hop_count` kosong | Payload dasar tanpa `hop_count` | HTTP 422, galat validasi pada `hop_count` | Sesuai harapan | Valid |
| A5 | Field konsentrasi gas kosong | Payload dasar tanpa `co2` | HTTP 422, galat validasi pada `co2`, tidak ada data tersimpan | (diisi saat uji manual) | — |
| A6 | Node tidak terdaftar | Payload dasar dengan `node_id` = 999 | HTTP 422, galat validasi pada `node_id` | Sesuai harapan | Valid |
| A7 | Nilai konsentrasi negatif | Payload dasar dengan `co2` = −5 | HTTP 422, galat validasi pada `co2` (aturan `min:0`), tidak ada data tersimpan | (diisi saat uji manual) | — |
| A8 | Field `rssi` kosong | Payload dasar tanpa `rssi` | HTTP 201, data tersimpan dengan `rssi` = NULL | Sesuai harapan | Valid |
| A9 | Nilai `rssi` di luar rentang | Payload dasar dengan `rssi` = 10 (rentang valid −120 s.d. 0) | HTTP 422, galat validasi pada `rssi` | Sesuai harapan | Valid |
| A10 | API key dikonfigurasi | `services.telemetry.api_key` = `rahasia-node`; kirim (a) tanpa header, (b) `X-Api-Key: salah`, (c) `X-Api-Key: rahasia-node`; juga `GET /api/telemetry` tanpa dan dengan kunci | (a) dan (b) HTTP 401 `status: error`; GET tanpa kunci HTTP 401; (c) POST HTTP 201 dan GET HTTP 200 | Sesuai harapan | Valid |
| A11 | API key tidak dikonfigurasi | `services.telemetry.api_key` = `''`; POST dan GET tanpa header | POST HTTP 201, GET HTTP 200 | Sesuai harapan | Valid |
| A12 | Data diterima saat ada sesi pengujian aktif | Mulai sesi bertopologi `mesh`, lalu kirim payload dasar dengan `topology_type` = `star` | Data tersimpan dengan `testing_session_id` = ID sesi aktif dan `topology_type` = `mesh` (mengikuti sesi) | (diisi saat uji manual) | — |

Catatan: skenario berstatus Valid dicakup oleh kelas uji `Tests\Feature\TelemetryApiTest`.

### B. Perhitungan Indeks KU dan Kategori

Indeks KU dihitung sebagai sub-indeks tertinggi dari empat gas (interpolasi linier antar-*breakpoint* pada `config/air_quality.php`); gas penyumbang sub-indeks tertinggi menjadi parameter dominan. Batas kategori: Baik ≤ 50, Sedang ≤ 150, Tidak Sehat ≤ 300, selebihnya Berbahaya.

| No | Skenario Pengujian | Langkah / Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| B1 | Konsentrasi nol | CO, CO₂, NH₃, H₂ masing-masing 0 ppm | Sub-indeks setiap gas = 0 | Sesuai harapan | Valid |
| B2 | Konsentrasi tepat pada *breakpoint* | CO 4,4 / 9,4 / 30,4 / 50,4 ppm; CO₂ 1000 / 5000 ppm; NH₃ 25 / 35 ppm; H₂ 4000 / 40000 ppm | Sub-indeks berturut-turut 50 / 100 / 300 / 500; 50 / 300; 50 / 150; 150 / 500 | Sesuai harapan | Valid |
| B3 | Konsentrasi di antara *breakpoint* (interpolasi) | CO 6,9 dan 40,4 ppm; CO₂ 500 dan 1500 ppm; NH₃ 30 ppm; H₂ 6000 ppm | Sub-indeks 75 dan 400; 25 dan 100; 100; 225 | Sesuai harapan | Valid |
| B4 | Konsentrasi di atas *breakpoint* terakhir | CO 100 ppm; CO₂ 100000 ppm; NH₃ 1000 ppm; H₂ 50000 ppm | Sub-indeks dibatasi (*clamp*) pada 500 | Sesuai harapan | Valid |
| B5 | Jenis gas tidak dikenal | Hitung sub-indeks untuk gas `o3` | Sistem menolak dengan `InvalidArgumentException` | Sesuai harapan | Valid |
| B6 | Batas kategori status | Indeks 50; 50,1; 150; 300; 300,1 | Baik; Sedang; Sedang; Tidak Sehat; Berbahaya | Sesuai harapan | Valid |
| B7 | Penentuan parameter dominan | CO₂ 420, NH₃ 30, CO 0,5, H₂ 5 ppm | KU = 100, kategori Sedang, dominan `nh3`, sub-indeks CO₂ = 21 | Sesuai harapan | Valid |
| B8 | Contoh pembacaan udara ambien melalui API | POST payload dasar (CO₂ 420, NH₃ 1, CO 0,5, H₂ 5) | `ku_status` = Baik, `ku_value` ≤ 50 | Sesuai harapan | Valid |
| B9 | Contoh pembacaan CO tinggi melalui API | POST payload dasar dengan `co` = 35 | `ku_status` = Berbahaya, `ku_dominant` = `co`, tersimpan di basis data | Sesuai harapan | Valid |

Catatan: skenario berstatus Valid dicakup oleh kelas uji `Tests\Unit\AirQualityIndexTest` (B1–B7) dan `Tests\Feature\TelemetryApiTest` (B8–B9).

### C. Beranda (`/`)

| No | Skenario Pengujian | Langkah / Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| C1 | Tamu membuka beranda tanpa login | GET `/` tanpa sesi login | HTTP 200 | Sesuai harapan | Valid |
| C2 | Menampilkan ruangan dan status keseluruhan | Ruang Kelas A (CO₂ 600 → Baik) dan Dapur Umum (CO₂ 1500 → Sedang); buka `/` | Tampil "Kualitas udara ruangan saat ini", nama kedua ruangan, status "Sedang", "Penyebab utama" CO₂, serta tautan ke `/ruangan/{id}` | Sesuai harapan | Valid |
| C3 | Ruangan dengan node nonaktif disembunyikan | Ruangan "Gudang Tertutup" dengan node `is_active` = false | Ruangan tidak tampil; muncul "Belum ada ruangan yang dipantau" | Sesuai harapan | Valid |
| C4 | Belum ada ruangan | Basis data tanpa ruangan | Tampil "Belum ada ruangan yang dipantau" | Sesuai harapan | Valid |
| C5 | Ruangan belum menerima data | Ruangan "Ruang Baru" dengan node aktif tanpa log | Tampil "Belum ada data" dan "Sensor terputus" | Sesuai harapan | Valid |
| C6 | Penandaan perangkat terputus | Ruang Aktif (data baru) dan Ruang Lama (data terakhir 10 menit lalu; ambang 30 detik) | Ruang Aktif "Sensor terhubung", Ruang Lama "Sensor terputus", perangkat daring 1 dari 2 | Sesuai harapan | Valid |
| C7 | Navigasi publik | Buka `/` | Tampil navigasi atas dengan tautan Riwayat, Panduan, Kuesioner, dan "Masuk Admin"; sidebar admin tidak tampil | Sesuai harapan | Valid |
| C8 | Pembaruan data otomatis | Buka `/`, kirim data baru melalui API, tunggu ±5 detik tanpa memuat ulang halaman | Nilai dan status ruangan diperbarui otomatis | (diisi saat uji manual) | — |

Catatan: skenario berstatus Valid dicakup oleh kelas uji `Tests\Feature\PublicDashboardTest`, `Tests\Feature\AuthTest`, `Tests\Feature\ExampleTest`, dan `Tests\Feature\NavigationTest`.

### D. Detail Ruangan (`/ruangan/{room}`)

| No | Skenario Pengujian | Langkah / Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| D1 | Menampilkan nilai terkini | Ruangan "Laboratorium" dengan log CO₂ 820,5 (2 jam lalu) dan CO₂ 1500 (terbaru); buka `/ruangan/{id}` | Tampil nama dan deskripsi ruangan, "Karbon Dioksida", nilai "1.500,00", "Sub-indeks", "Rekomendasi", grafik, dan "10 pembacaan terakhir" | Sesuai harapan | Valid |
| D2 | Rentang bawaan 24 jam | Data sama dengan D1, buka tanpa parameter | `range` = `24h`, grafik ≤ 120 titik dengan 2 titik berisi, statistik 2 data, distribusi Sedang 50% | Sesuai harapan | Valid |
| D3 | Rentang 1 jam | Log 3 hari lalu, 3 jam lalu, dan terbaru; pilih rentang `1h` | Statistik hanya memuat 1 data, grafik ≤ 120 titik | Sesuai harapan | Valid |
| D4 | Rentang 7 hari | Data sama dengan D3; pilih rentang `7d` | Statistik memuat 3 data, grafik ≤ 120 titik | Sesuai harapan | Valid |
| D5 | Nilai rentang tidak valid | Panggil `setRange('bogus')` | Rentang kembali ke `24h` | Sesuai harapan | Valid |
| D6 | Ruangan belum memiliki data | Ruangan "Ruang Kosong" tanpa log | Tampil "Belum ada data dari ruangan ini" | Sesuai harapan | Valid |
| D7 | Ruangan tidak ada | GET `/ruangan/9999` | HTTP 404 | Sesuai harapan | Valid |

Catatan: skenario berstatus Valid dicakup oleh kelas uji `Tests\Feature\PublicDashboardTest`.

### E. Riwayat (`/riwayat`)

Data uji: Dapur Utama (KU 42,5 Baik pada 01-10-2026 08:00; KU 220 Tidak Sehat pada 02-10-2026 09:30) dan Kamar Tidur (KU 120 Sedang pada 03-10-2026 10:15).

| No | Skenario Pengujian | Langkah / Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| E1 | Tamu membuka halaman riwayat | GET `/riwayat` tanpa login | HTTP 200, tampil "Riwayat Kualitas Udara", tombol "Unduh CSV", nama ruangan, dan waktu "01 Okt 2026 08:00:00" | Sesuai harapan | Valid |
| E2 | Ringkasan data | Buka tanpa filter | Jumlah 3 data, rata-rata KU 127,5, kategori terburuk Tidak Sehat, distribusi Baik 1 dan Berbahaya 0 | Sesuai harapan | Valid |
| E3 | Filter ruangan | Pilih ruangan Kamar Tidur | 1 data dengan kategori Sedang | Sesuai harapan | Valid |
| E4 | Filter kategori | Pilih kategori Tidak Sehat | 1 data dengan KU 220; ringkasan ikut menyesuaikan | Sesuai harapan | Valid |
| E5 | Filter rentang tanggal | (a) Dari 2026-10-02 sampai 2026-10-02; (b) URL `?dari=2026-10-02` | (a) 1 data Tidak Sehat; (b) filter terbaca dari URL, 2 data | Sesuai harapan | Valid |
| E6 | Reset filter | Isi filter ruangan Dapur Utama dan kategori Baik, klik reset | Semua filter kosong, tampil kembali 3 data | Sesuai harapan | Valid |
| E7 | Filter tanpa hasil | Pilih kategori Berbahaya | Tampil "Tidak ada pengukuran yang cocok dengan filter" | Sesuai harapan | Valid |
| E8 | Unduh CSV sesuai filter | Filter ruangan Dapur Utama, klik "Unduh CSV" | Berkas `riwayat-kualitas-udara-YYYYMMDD_HHMMSS.csv` dengan header `waktu,ruangan,indeks_ku,kategori,parameter_dominan,co2_ppm,nh3_ppm,co_ppm,h2_ppm`, memuat 2 baris Dapur Utama dan tidak memuat Kamar Tidur | Sesuai harapan | Valid |
| E9 | Format tanggal tidak valid | Buka `/riwayat?dari=abc` | Filter tanggal diabaikan tanpa galat; seluruh data tampil | (diisi saat uji manual) | — |

Catatan: skenario berstatus Valid dicakup oleh kelas uji `Tests\Feature\PublicHistoryGuideTest`.

### F. Panduan (`/panduan`)

| No | Skenario Pengujian | Langkah / Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| F1 | Tamu membuka panduan | GET `/panduan` tanpa login | HTTP 200, tampil "Panduan Kualitas Udara" dan "Cara membaca Indeks KU" | Sesuai harapan | Valid |
| F2 | Informasi kategori dan gas | Buka halaman panduan | Tampil empat kategori (Baik, Sedang, Tidak Sehat, Berbahaya), empat gas (Karbon Dioksida, Amonia, Karbon Monoksida, Hidrogen), sensor MQ-135 dan MQ-2, bagian "Pertanyaan umum", dan keterangan "bukan diagnosis medis" | Sesuai harapan | Valid |
| F3 | Contoh perhitungan | Contoh CO₂ 850, NH₃ 2, CO 6, H₂ 120 ppm | Tampil "Indeks KU =" dengan nilai hasil perhitungan sistem, kategori Sedang, parameter dominan CO | Sesuai harapan | Valid |
| F4 | Ambang konsentrasi tiap kategori | Hitung ambang ppm dari *breakpoint* | CO₂: 1000 / 2000 / 5000 ppm; CO: "0 – 4,4 ppm" (Baik) dan "> 12,4 – 30,4 ppm" (Tidak Sehat) | Sesuai harapan | Valid |

Catatan: skenario berstatus Valid dicakup oleh kelas uji `Tests\Feature\PublicHistoryGuideTest`.

### G. Kuesioner SUS (`/kuesioner`)

| No | Skenario Pengujian | Langkah / Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| G1 | Tamu membuka kuesioner | GET `/kuesioner` | HTTP 200, tampil "Kuesioner Kegunaan Website", butir pernyataan, dan skala "Sangat Tidak Setuju" | Sesuai harapan | Valid |
| G2 | Mengisi kuesioner lengkap | Nama "Budi", kelompok `ahli_media`, komentar "Tampilannya jelas.", jawaban 4,2,5,1,4,2,3,2,4,3 | Tersimpan; skor SUS 75; tampil "Terima kasih" dan grade "B/C"; IP disimpan sebagai *hash* 64 karakter | Sesuai harapan | Valid |
| G3 | Nama dan komentar dikosongkan | Seluruh jawaban bernilai 3, nama dan komentar kosong | Tersimpan dengan nama dan komentar NULL, skor SUS 50 | Sesuai harapan | Valid |
| G4 | Kuesioner tidak lengkap | (a) Butir 7 dikosongkan; (b) kirim tanpa jawaban | (a) Galat `required` pada butir 7; (b) galat pada butir 1 s.d. 10; tidak ada data tersimpan | Sesuai harapan | Valid |
| G5 | Nilai jawaban dan kelompok tidak valid | Butir 1 = 6, butir 8 = 0, kelompok `tidak-ada` | Galat validasi `between` pada butir 1 dan 8 serta `in` pada kelompok; tidak ada data tersimpan | Sesuai harapan | Valid |
| G6 | Pembatasan pengiriman (*rate limit*) | Kirim 4 kali dari IP yang sama dalam 1 jam (batas 3 per jam) | Pengiriman ke-4 ditolak dengan pesan "Silakan coba lagi"; hanya 3 data tersimpan | Sesuai harapan | Valid |
| G7 | Perhitungan skor SUS | (a) Semua 3; (b) 5,1,5,1,5,1,5,1,5,1; (c) 1,5,1,5,1,5,1,5,1,5; (d) 4,2,5,1,4,2,3,2,4,3 | (a) 50; (b) 100; (c) 0; (d) 75 | Sesuai harapan | Valid |
| G8 | Masukan perhitungan tidak valid | (a) Hanya 3 jawaban; (b) salah satu jawaban bernilai 6 | Ditolak dengan `InvalidArgumentException` | Sesuai harapan | Valid |
| G9 | Batas grade dan *acceptability* | Skor 80,3; 80,29; 68; 67,99; 51; 50,99 serta 70; 69,99; 50; 49,99 | Grade A; B/C; B/C; D; D; F serta Acceptable; Marginal; Marginal; Not Acceptable | Sesuai harapan | Valid |
| G10 | Skor dihitung ulang oleh sistem | Simpan respons semua jawaban 3 dengan `sus_score` = 99 | Skor tersimpan tetap 50 (dihitung dari jawaban) | Sesuai harapan | Valid |

Catatan: skenario berstatus Valid dicakup oleh kelas uji `Tests\Feature\SurveyTest` (G1–G6, G10) dan `Tests\Unit\SusTest` (G7–G9).

### H. Login dan Hak Akses

| No | Skenario Pengujian | Langkah / Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| H1 | Membuka halaman login | GET `/login` sebagai tamu | HTTP 200, tampil "Masuk Panel Pengelola" dan tautan "Kembali ke halaman utama" | Sesuai harapan | Valid |
| H2 | Login dengan kredensial benar | Email `admin@siasap.test`, kata sandi `password` | Berhasil masuk, diarahkan ke `/admin` | Sesuai harapan | Valid |
| H3 | Login dengan kata sandi salah | Email `admin@siasap.test`, kata sandi `salah` | Galat pada field email, pengguna tetap belum login | Sesuai harapan | Valid |
| H4 | Logout | Admin login, kirim POST `/logout` | Diarahkan ke `/`, sesi login berakhir | Sesuai harapan | Valid |
| H5 | Akses panel admin tanpa login | GET `/admin` sebagai tamu | Diarahkan ke `/login` | Sesuai harapan | Valid |
| H6 | Akses panel admin tanpa *permission* | Pengguna dengan peran tanpa hak akses membuka `/admin` | HTTP 403 | Sesuai harapan | Valid |
| H7 | Menu sidebar mengikuti hak akses | (a) Admin membuka `/admin`; (b) pengguna berperan *researcher* membuka `/admin` | (a) Tautan Data Sensor, Ruangan, Pengujian Jaringan, Kuesioner tampil; (b) tautan Pengujian Jaringan tampil, tautan Hasil Kuesioner tidak tampil | Sesuai harapan | Valid |
| H8 | Rute lama yang sudah dihapus | Admin membuka `/admin/telemetry`, `/admin/qos-analytics`, `/admin/rooms`, `/admin/sessions-management`, `/admin/rbac-management`, `/admin/navigation-management` | HTTP 404 | Sesuai harapan | Valid |
| H9 | Field login kosong atau format email salah | (a) Email dan kata sandi kosong; (b) email `admin` | Galat validasi `required` / `email`, tidak login | (diisi saat uji manual) | — |
| H10 | Pengguna yang sudah login membuka halaman login | Admin login, lalu GET `/login` | Tidak menampilkan form login (dialihkan oleh *middleware guest*) | (diisi saat uji manual) | — |

Catatan: skenario berstatus Valid dicakup oleh kelas uji `Tests\Feature\AuthTest`, `Tests\Feature\NavigationTest`, `Tests\Feature\AdminOverviewTest`, dan `Tests\Feature\ExampleTest`.

### I. Ringkasan Admin (`/admin`)

| No | Skenario Pengujian | Langkah / Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| I1 | Admin membuka ringkasan | Login sebagai admin, GET `/admin` | HTTP 200 | Sesuai harapan | Valid |
| I2 | Status perangkat dan jumlah | Node Alfa (data terbaru, RSSI −61, seq 4242, hop 2), Node Beta (data 10 menit lalu, RSSI kosong), sesi aktif "Uji Mesh Pagi" | Tampil kedua node, ruangan "Laboratorium Uji", status "Daring" dan "Luring", "-61 dBm", "4242", "1 / 2", dan nama sesi aktif | Sesuai harapan | Valid |
| I3 | Daftar peringatan terbaru | Log KU 350 Berbahaya, dominan CO | Tampil "Peringatan Terbaru", "Berbahaya", "Dominan: CO" | Sesuai harapan | Valid |
| I4 | Tidak ada peringatan | Hanya log berkategori Baik, tanpa sesi aktif | Tampil "Tidak ada peringatan" dan keterangan tidak ada sesi aktif | Sesuai harapan | Valid |

Catatan: skenario berstatus Valid dicakup oleh kelas uji `Tests\Feature\AdminOverviewTest` dan `Tests\Feature\ExampleTest`. Skenario akses tamu dan akses tanpa *permission* untuk halaman ini tercantum pada modul H (H5, H6).

### J. Data Sensor Admin (`/admin/data-sensor`)

| No | Skenario Pengujian | Langkah / Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| J1 | Akses tanpa *permission* `telemetry.view` | Pengguna dengan peran tanpa hak akses membuka `/admin/data-sensor` | HTTP 403 | Sesuai harapan | Valid |
| J2 | Menampilkan tabel data | Log node "Node Dapur Uji" dengan `rssi` NULL, `seq_number` 777, CO₂ 812,5 | Tampil nama node, ruangan, "812.50", "777"; RSSI kosong ditampilkan "—" | Sesuai harapan | Valid |
| J3 | Filter node | Pilih node dapur | Hanya log seq 1001 yang tampil | Sesuai harapan | Valid |
| J4 | Filter kategori | Pilih kategori Tidak Sehat | Hanya log seq 1001 yang tampil | Sesuai harapan | Valid |
| J5 | Filter sesi pengujian | Pilih sesi "Sesi Mesh A" | Hanya log milik sesi tersebut (seq 1001) yang tampil | Sesuai harapan | Valid |
| J6 | Filter data di luar sesi | Pilih opsi "di luar sesi" | Log seq 2002 dan 3003 tampil, log bersesi (1001) tidak tampil | Sesuai harapan | Valid |
| J7 | Filter tanggal dan reset | Tanggal dari kemarin s.d. hari ini, lalu klik reset | Log 10 hari lalu (3003) tersembunyi; setelah reset tampil kembali | Sesuai harapan | Valid |
| J8 | Filter melalui URL | GET `/admin/data-sensor?node={id node kamar}` | Hanya log node kamar (2002) yang tampil | Sesuai harapan | Valid |
| J9 | Ekspor CSV sesuai filter | Filter node dapur, klik ekspor | Berkas CSV berisi header `id,waktu,node_id,node,ruangan,co2_ppm,...,testing_session_id,sesi` dan tepat 1 baris data node dapur; RSSI kosong diekspor sebagai sel kosong | Sesuai harapan | Valid |

Catatan: skenario berstatus Valid dicakup oleh kelas uji `Tests\Feature\SensorDataTest`.

### K. Ruangan dan Perangkat (`/admin/ruangan`)

| No | Skenario Pengujian | Langkah / Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| K1 | Admin membuka halaman | Login sebagai admin, GET `/admin/ruangan` | HTTP 200, tampil "Ruangan & Perangkat" serta ruangan Ruang Tamu, Kamar, Dapur | Sesuai harapan | Valid |
| K2 | Menambah ruangan | Nama "Garasi Utama", kode `ROOM-GARASI`, deskripsi "Area tempat parkir kendaraan." | Tanpa galat, data tersimpan di tabel `rooms` | Sesuai harapan | Valid |
| K3 | Menambah node dan menempatkan ke ruangan | Ruangan Ruang Tamu, nama "Node Testing Extra", kode `NODE-99`, aktif | Tanpa galat, data tersimpan di tabel `nodes` dengan `room_id` Ruang Tamu | Sesuai harapan | Valid |
| K4 | Tab node dan modal | Node "Node Cadangan" (`NODE-OFF`, nonaktif, tanpa ruangan); buka tab node, buka dan tutup modal tambah node | Tampil "Nonaktif" dan "Belum ditempatkan"; modal "Tambah Node Sensor" terbuka lalu tertutup | Sesuai harapan | Valid |
| K5 | Menghapus node dan ruangan | Hapus "Node Gudang", lalu hapus ruangan "Gudang" | Kedua data terhapus dari basis data | Sesuai harapan | Valid |
| K6 | Mengubah data ruangan | Pilih ubah pada Ruang Tamu, ganti nama menjadi "Ruang Keluarga", simpan | Nama ruangan diperbarui, pesan "berhasil diperbarui" | (diisi saat uji manual) | — |
| K7 | Mengubah data node | Pilih ubah pada sebuah node, ganti nama dan status aktif, simpan | Data node diperbarui | (diisi saat uji manual) | — |
| K8 | Validasi nama ruangan kosong | Simpan ruangan dengan nama kosong | Galat "Nama ruangan wajib diisi.", data tidak tersimpan | (diisi saat uji manual) | — |
| K9 | Validasi kode ruangan duplikat | Tambah ruangan dengan kode `ROOM-TAMU` (sudah ada) | Galat "Kode ruangan sudah digunakan." | (diisi saat uji manual) | — |
| K10 | Validasi kode node kosong atau duplikat | (a) Kode node kosong; (b) kode node yang sudah dipakai | (a) "Kode unik node wajib diisi."; (b) "Kode node sudah digunakan." | (diisi saat uji manual) | — |
| K11 | Menghapus ruangan yang masih memiliki node | Hapus ruangan yang memiliki node | Ruangan terhapus; node tetap ada dengan status "Belum ditempatkan" (relasi `nullOnDelete`) | (diisi saat uji manual) | — |
| K12 | Akses tanpa *permission* `rooms.manage` | Pengguna tanpa peran membuka `/admin/ruangan` | HTTP 403 | Sesuai harapan | Valid |
| K13 | Aksi tetap diperiksa setelah hak akses dicabut | Admin membuka halaman, peran dicabut, lalu memanggil aksi hapus Ruang Tamu | HTTP 403, ruangan tidak terhapus | Sesuai harapan | Valid |

Catatan: skenario berstatus Valid dicakup oleh kelas uji `Tests\Feature\RoomManagementTest`.

### L. Pengujian Jaringan (`/admin/pengujian-jaringan`)

| No | Skenario Pengujian | Langkah / Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| L1 | Admin membuka halaman | Login sebagai admin, GET `/admin/pengujian-jaringan` | HTTP 200, tampil "Topologi Star", "Topologi Mesh", "Mulai Sesi Pengujian", "Catatan metodologi", "Hipotesis penelitian" | Sesuai harapan | Valid |
| L2 | Akses tanpa *permission* `qos.view` | Pengguna hanya dengan hak `user.view` | HTTP 403 | Sesuai harapan | Valid |
| L3 | Memulai sesi tanpa *permission* `session.manage` | Pengguna dengan `qos.view` saja; isi nama "Sesi Ilegal", panggil mulai sesi | Tombol "Mulai Sesi Pengujian" tidak tampil; aksi ditolak HTTP 403; sesi tidak tersimpan | Sesuai harapan | Valid |
| L4 | Menghentikan sesi tanpa *permission* `session.manage` | Pengguna dengan `qos.view` saja; ada sesi aktif; panggil hentikan sesi | HTTP 403; status sesi tetap `active` | Sesuai harapan | Valid |
| L5 | Memulai sesi oleh pengguna berhak | Pengguna dengan `qos.view` dan `session.manage`; nama "Sesi Mesh #1", topologi `mesh` | Sesi tersimpan dengan status `active` dan topologi `mesh` | Sesuai harapan | Valid |
| L6 | Menghentikan sesi oleh pengguna berhak | Ada sesi aktif; admin klik hentikan sesi | Status sesi menjadi `completed` dan `ended_at` terisi | (diisi saat uji manual) | — |
| L7 | Memulai sesi baru saat masih ada sesi aktif | Sesi A aktif; mulai sesi B | Sesi A otomatis `completed`; hanya sesi B yang aktif | (diisi saat uji manual) | — |
| L8 | Validasi form sesi | (a) Nama sesi kosong; (b) topologi selain `star`/`mesh` | Galat validasi, sesi tidak tersimpan | (diisi saat uji manual) | — |
| L9 | Ekspor CSV log sesi | Sesi "Sesi Star A" berisi 1 log (seq 7, RSSI −61) dan 1 log tanpa sesi (seq 99); pilih sesi, klik ekspor | Berkas `siasap-qos-sensor-logs-sesi-{id}-20261014_100000.csv`, header sesuai kolom ekspor, hanya 1 baris (log tanpa sesi tidak ikut) | Sesuai harapan | Valid |
| L10 | Perhitungan PDR dan *packet loss* | Interval kirim 5 detik; node 1 mengirim seq 1–10 kecuali 4 dan 7; node 2 dan 3 lengkap | Node 1: diterima 8, diharapkan 10, PDR 80%, *loss* 20%; total 28/30, PDR 93,3% | Sesuai harapan | Valid |
| L11 | Node tidak mengirim paket sama sekali | Sesi 10 menit; node 1 dan 2 masing-masing 120 paket, node 3 tidak mengirim | Node 3 tetap dihitung: diharapkan 120 (estimasi 600 s / 5 s), PDR 0%; total 240/360, PDR 66,7% | Sesuai harapan | Valid |
| L12 | Paket duplikat (diterima langsung dan via relay) | Topologi mesh; seq 4, 7, dan 9 diterima dua kali | Node 3: diterima 10, diharapkan 10, PDR 100%; RSSI dan hop dari penerimaan pertama (−70 dBm, 1 hop) | Sesuai harapan | Valid |
| L13 | Nomor urut ter-*reset* karena node *reboot* | Segmen 1: seq 1–20 tanpa seq 18; segmen 2: seq 1, 2, 4, 5 | Node 1: diterima 23, diharapkan 25, PDR 92% | Sesuai harapan | Valid |
| L14 | Data dua sesi tidak tercampur saat tanpa filter sesi | Sesi A: node 1 seq 1–10; sesi B: node 1 seq 100–109 tanpa 103 dan 106 | 2 sesi terhitung; node 1: 18/20, PDR 90%; total 58/60 | Sesuai harapan | Valid |
| L15 | Log di luar sesi diabaikan | Satu sesi lengkap (30 paket) + 3 log tanpa sesi (RSSI −99) | Total 30/30, rata-rata RSSI −55 dBm (log tanpa sesi tidak memengaruhi) | Sesuai harapan | Valid |
| L16 | RSSI kosong dikecualikan dari statistik | Node 1 RSSI −50, NULL, −60, NULL, −70 | Diterima 5, sampel RSSI 3, rata-rata −60 dBm, simpangan baku 10 | Sesuai harapan | Valid |
| L17 | Node yang didaftarkan setelah sesi | Node 4 didaftarkan 5 hari setelah sesi selesai | Node 4 tidak diikutkan dalam sesi tersebut; PDR 100% | Sesuai harapan | Valid |
| L18 | Tampilan hasil dan penanda estimasi | Node terdaftar tanpa paket dan log dari node #77 tak terdaftar | Tampil nama node, "Node #77", "0/120", keterangan "diestimasi"; tombol "Generate Dataset Uji Skenario" tidak ada dan fungsi pembangkit data sintetis telah dihapus | Sesuai harapan | Valid |

Catatan: skenario berstatus Valid dicakup oleh kelas uji `Tests\Feature\QosAnalyticsTest` (L1–L5, L9, L18) dan `Tests\Feature\QosCalculatorTest` (L10–L17).

### M. Hasil Kuesioner (`/admin/kuesioner`)

| No | Skenario Pengujian | Langkah / Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|
| M1 | Akses tanpa login | GET `/admin/kuesioner` sebagai tamu | Diarahkan ke `/login` | Sesuai harapan | Valid |
| M2 | Akses tanpa *permission* `survey.view` | Pengguna dengan peran hanya `telemetry.view`; buka halaman dan panggil komponen | HTTP 403; data respons tidak berubah | Sesuai harapan | Valid |
| M3 | Statistik hasil kuesioner | Tiga respons dengan skor 100, 50 (anonim), 75 ("Pakar") | Rata-rata "75,00", simpangan baku "25,00", "Good", "Acceptable", "Anonim", "Pakar", tombol "Unduh CSV" | Sesuai harapan | Valid |
| M4 | Filter kelompok responden | Pilih kelompok `pengguna` | Rata-rata "75,00", simpangan baku "35,36", respons "Pakar" tidak tampil | Sesuai harapan | Valid |
| M5 | Belum ada respons | Tanpa data kuesioner | Tampil "Belum ada respons" | Sesuai harapan | Valid |
| M6 | Ekspor CSV | Respons "Siti", kelompok `pengguna`, jawaban 4,2,5,1,4,2,3,2,4,3, komentar "Mantap"; klik ekspor | CSV dengan header `no,waktu,nama,kelompok,q1,...,q10,skor_sus,grade,...` dan baris berisi skor 75.00, B/C, Good, Acceptable | Sesuai harapan | Valid |
| M7 | Menghapus respons | Admin menghapus satu respons | Respons terhapus dari basis data | Sesuai harapan | Valid |
| M8 | Peran *researcher* membuka hasil kuesioner | Login sebagai pengguna berperan *researcher*, GET `/admin/kuesioner` | HTTP 403 (peran tidak memiliki `survey.view`) | (diisi saat uji manual) | — |

Catatan: skenario berstatus Valid dicakup oleh kelas uji `Tests\Feature\SurveyTest`.

---

## 4.x.3 Rekapitulasi Hasil Pengujian

| No | Modul | Jumlah Skenario | Valid | Belum Diuji |
|---|---|---|---|---|
| A | API Penerimaan Data Sensor | 12 | 9 | 3 |
| B | Perhitungan Indeks KU & Kategori | 9 | 9 | 0 |
| C | Beranda | 8 | 7 | 1 |
| D | Detail Ruangan | 7 | 7 | 0 |
| E | Riwayat | 9 | 8 | 1 |
| F | Panduan | 4 | 4 | 0 |
| G | Kuesioner SUS | 10 | 10 | 0 |
| H | Login & Hak Akses | 10 | 8 | 2 |
| I | Ringkasan Admin | 4 | 4 | 0 |
| J | Data Sensor Admin | 9 | 9 | 0 |
| K | Ruangan & Perangkat | 13 | 7 | 6 |
| L | Pengujian Jaringan | 18 | 15 | 3 |
| M | Hasil Kuesioner | 8 | 7 | 1 |
| | **Total** | **121** | **104** | **17** |
| | **Persentase** | **100%** | **85,95%** | **14,05%** |

## 4.x.4 Kesimpulan Pengujian

Berdasarkan pengujian *black-box* terhadap 13 modul, disusun 121 skenario uji. Sebanyak 104 skenario (85,95%) telah dijalankan secara otomatis melalui PHPUnit dan seluruhnya memberikan hasil sesuai harapan (status Valid); eksekusi `php artisan test` menghasilkan 143 kasus uji lulus dengan 453 *assertion* tanpa kegagalan. Skenario yang telah tervalidasi mencakup penerimaan dan validasi data sensor melalui API, perhitungan indeks KU beserta batas kategorinya, perhitungan metrik QoS jaringan (PDR, *packet loss*, RSSI, *hop count*) termasuk kasus tepi, perhitungan skor SUS, serta pembatasan hak akses pada halaman dan aksi panel pengelola.

Sebanyak 17 skenario (14,05%) belum memiliki kasus uji otomatis sehingga hasilnya belum dapat dinyatakan valid. Skenario tersebut terutama berkaitan dengan validasi form ubah ruangan/node, penghentian sesi pengujian oleh pengguna berhak, penolakan nilai konsentrasi negatif dan field gas kosong pada API, serta keterikatan data kiriman sensor dengan sesi pengujian yang sedang aktif. Skenario-skenario ini perlu diuji secara manual (atau ditambahkan kasus uji otomatis) dan hasilnya dicatat pada kolom Hasil Aktual sebelum dapat disimpulkan bahwa seluruh fungsi sistem berjalan sesuai kebutuhan. Pengujian ini juga terbatas pada lapisan aplikasi web; kesesuaian format data yang dikirim *firmware* ESP32 terhadap validator API perlu dibuktikan melalui pengujian integrasi dengan perangkat secara langsung.

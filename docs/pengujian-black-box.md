# Pengujian Sistem (Bab 3.12): Black-Box Testing SIMKU

Dokumen ini menyajikan rancangan dan hasil pengujian aplikasi web SIMKU (Sistem Monitoring Kualitas Udara Ruangan Berbasis IoT) mengikuti struktur Bab 3.12 naskah, yaitu Tabel 3.22 sampai Tabel 3.27. Hasilnya dilaporkan di Bab IV naskah (subbab 4.2, Tabel 4.2 sampai Tabel 4.7; skenario kondisi ruangan di subbab 4.3, Tabel 4.8 dan 4.9). Pengujian memakai metode *black-box*: setiap skenario dinilai dari masukan dan keluaran, tanpa melihat struktur kode program. Perangkat IoT berperan sebagai penyedia data dan tidak termasuk objek pengujian.

## Aturan pengisian status

| Status | Arti |
|---|---|
| **Valid** | Skenario dibuktikan oleh kasus uji otomatis (PHPUnit) yang **lulus** pada eksekusi tanggal 26 Oktober 2026. Kasus ujinya disebut dalam format `KelasUji::metode` (kelas di `tests/Feature` atau `tests/Unit`). |
| **Belum diuji (manual)** | Belum ada kasus uji otomatis yang membuktikan skenario ini. Skenario harus diuji manual (di peramban atau dengan perangkat IoT), lalu kolom Hasil Aktual dan Status diisi dari pengamatan. |

Kolom **Hasil Aktual** hanya berisi hasil yang benar-benar diperiksa oleh kasus uji yang disebut. Tidak ada hasil yang diisi tanpa eksekusi.

## Lingkungan dan hasil eksekusi otomatis

| Komponen | Spesifikasi |
|---|---|
| Bahasa pemrograman | PHP 8.3.6 |
| *Framework* | Laravel Framework 13.34.0 |
| Komponen antarmuka | Livewire 4.4.7 |
| *Framework* pengujian | PHPUnit 12.5.37 |
| Basis data pengujian | SQLite *in-memory*, di-*reset* untuk setiap kasus uji (`RefreshDatabase`); diulang pada MariaDB 10.11 (kompatibel MySQL) |
| Perintah | `php artisan test --compact` |
| Tanggal eksekusi | 26 Oktober 2026 |
| Peramban (uji manual) | (diisi saat uji manual: nama dan versi peramban) |

Hasil eksekusi `php artisan test --compact` pada 26 Oktober 2026: **150 kasus uji lulus, 606 *assertion*, 0 gagal** (durasi ±6 detik). Jumlah ini terdiri atas 94 kasus uji *feature* dan 56 kasus uji *unit*. Rangkaian yang sama dijalankan ulang pada MariaDB 10.11 (`DB_CONNECTION=mariadb`) dengan hasil identik: 150 lulus, 606 *assertion*. Kasus uji dengan *data provider* dihitung satu per set data, misalnya `TelemetryApiTest::test_table_3_8_classification_via_api` dihitung 7 kali (baris 1–7).

Pembagian kasus uji per kelas:

| Kelas uji | Jumlah | Cakupan |
|---|---|---|
| `Feature\TelemetryApiTest` | 26 | API `POST /api/telemetry`: validasi (Tabel 3.24), klasifikasi lewat API (Tabel 3.25), format lama, API key, pembatasan laju, pembulatan ppm, JSON rusak |
| `Feature\PublicDashboardTest` | 18 | Beranda `/` dan Detail Ruangan `/ruangan/{room}` |
| `Feature\PublicHistoryTest` | 12 | Riwayat `/riwayat`: filter, *pagination*, ekspor CSV |
| `Feature\DeviceStatusTest` | 11 | Status Perangkat `/perangkat` |
| `Feature\SurveyTest` | 9 | Kuesioner UEQ `/kuesioner` dan perintah `ueq:report` |
| `Feature\PublicGuideTest` | 5 | Panduan `/panduan` |
| `Feature\RecommendationPageTest` | 4 | Rekomendasi `/rekomendasi` |
| `Feature\PublicAccessTest` | 2 | Halaman publik tanpa login; rute login lama sudah dihapus |
| `Feature\RoomDeviceSeederTest` | 2 | *Seeder* ruangan/perangkat, perintah `telemetry:clear` |
| `Feature\SensorLogNodeRelationTest` | 2 | *Foreign key* `sensor_logs.node_id` dan penghapusan berantai |
| `Feature\NavigationTest` | 1 | Navigasi atas |
| `Feature\ExampleTest` | 1 | Beranda dapat diakses tanpa login |
| `Unit\AirQualityClassifierTest` | 35 | Klasifikasi per gas, nilai batas, kategori keseluruhan, penyebab utama |
| `Unit\UeqTest` | 9 | Skoring UEQ |
| `Unit\RecommendationTest` | 8 | Aturan rekomendasi (Tabel 3.26) |
| `Unit\AirQualityTest` | 4 | Label rentang ambang, urutan kategori |

---

## 3.12.1 Rencana Pengujian

**Tabel 3.22 Rencana Pengujian Sistem**

| No | Jenis Pengujian | Aspek yang Diuji | Tujuan Pengujian | Cara Pengujian | Tabel Hasil |
|---|---|---|---|---|---|
| 1 | Pengujian Fungsional Aplikasi | Beranda (*dashboard*), Detail Ruangan (*sensor charts*), Riwayat (*historical data*), Rekomendasi Tindakan, Status Perangkat (*IoT system*), Panduan, Kuesioner | Memastikan setiap fitur aplikasi web berjalan sesuai kebutuhan sistem | Otomatis (*feature test* Livewire/HTTP); tampilan visual di peramban diuji manual | Tabel 3.23 |
| 2 | Pengujian Validasi Data | Validasi masukan `POST /api/telemetry` (`device_id`, `timestamp`, `co2_ppm`, `nh3_ppm`, `co_ppm`, `h2_ppm`) dan pencatatan data yang ditolak | Memastikan hanya data lengkap dan berformat benar yang diproses dan disimpan, sedangkan data tidak valid ditolak dan dicatat | Otomatis (*feature test* API) | Tabel 3.24 |
| 3 | Pengujian Pengolahan Data Sensor (klasifikasi) | Kategori setiap gas berdasarkan ambang batas, nilai tepat di batas, kategori keseluruhan (kategori terburuk), penyebab utama | Memastikan data ppm CO₂, NH₃, CO, dan H₂ diklasifikasikan dengan benar | Otomatis (*unit test* dan *feature test* API) | Tabel 3.25 |
| 4 | Pengujian Rekomendasi Tindakan Preventif | Pemetaan kategori keseluruhan ke rekomendasi berbasis aturan | Memastikan rekomendasi yang tampil sesuai kategori kualitas udara | Otomatis (*unit test* dan *feature test* halaman) | Tabel 3.26 |
| 5 | Pengujian pada Beberapa Kondisi Ruangan | Ruangan normal, tertutup, berasap, dan ventilasi berbeda | Memastikan sistem mampu memantau perubahan kondisi kualitas udara dengan perangkat IoT nyata | Manual dengan perangkat IoT (lembar isian) | Tabel 3.27 |

---

## 3.12.2 Pengujian Fungsional Aplikasi

Data uji dibuat oleh kasus uji itu sendiri (ruangan, perangkat, dan log sensor dengan nilai ppm tertentu). Kategori pada data uji mengikuti ambang Tabel 2.2 naskah.

**Tabel 3.23 Pengujian Fungsional Aplikasi**

| No | Halaman / Fitur | Skenario Pengujian | Masukan / Data Uji | Hasil yang Diharapkan | Hasil Aktual | Status | Kasus Uji |
|---|---|---|---|---|---|---|---|
| F1 | Beranda `/` | Membuka beranda saat data sudah masuk (satu ruangan) | 1 ruangan, data CO₂ 70, NH₃ 20, CO 55,5, H₂ 5 | Menampilkan kategori keseluruhan "Tidak Sehat", penyebab utama CO, kartu 4 gas dengan ppm dan badge kategori (Sedang, Baik, Tidak Sehat, Baik), tingkat "Perlu Tindakan" dengan teks rekomendasinya, status "Perangkat terhubung" | Sesuai harapan | Valid | `PublicDashboardTest::test_home_with_single_room_focuses_on_that_room` |
| F2 | Beranda `/` | Kategori keseluruhan Baik | Data CO₂ 30, NH₃ 20, CO 10, H₂ 5 | Tingkat "Normal" dan rekomendasi Baik tampil; penyebab utama tidak ditampilkan | Sesuai harapan | Valid | `PublicDashboardTest::test_home_hides_main_cause_when_overall_is_good` |
| F3 | Beranda `/` | Membuka beranda sebelum ada data | 1 ruangan tanpa log sensor | Pesan "Menunggu data dari perangkat IoT"; tidak ada rekomendasi | Sesuai harapan | Valid | `PublicDashboardTest::test_home_without_data_shows_waiting_state_without_recommendation` |
| F4 | Beranda `/` | Beberapa ruangan | 2 ruangan: satu Baik, satu Berbahaya (H₂ 65) | Satu kartu per ruangan berisi kategori, penyebab, tingkat, dan rekomendasi masing-masing | Sesuai harapan | Valid | `PublicDashboardTest::test_home_with_multiple_rooms_shows_a_card_per_room` |
| F5 | Beranda `/` | Ruangan tanpa perangkat aktif / tanpa ruangan | Perangkat nonaktif; basis data tanpa ruangan | Ruangan tersebut tidak ditampilkan; pesan "Belum ada ruangan yang dipantau" | Sesuai harapan | Valid | `PublicDashboardTest::test_home_hides_rooms_without_active_nodes`, `PublicDashboardTest::test_home_shows_empty_state_without_rooms` |
| F6 | Beranda `/` | Perangkat berhenti mengirim data | Data terakhir 31 detik lalu | Ruangan bertanda "Perangkat terputus"; ruangan dengan data baru bertanda "Perangkat terhubung" | Sesuai harapan | Valid | `PublicDashboardTest::test_home_marks_stale_devices_disconnected` |
| F7 | Beranda `/` | Halaman memuat pembaruan berkala | Membuka beranda | Komponen memuat *polling* tiap 5 detik (`wire:poll.5s`) | Sesuai harapan | Valid | `PublicDashboardTest::test_home_with_single_room_focuses_on_that_room` |
| F8 | Beranda `/` | Tampilan berubah sendiri saat data baru diterima | Perangkat IoT mengirim data baru dengan kategori berbeda saat beranda terbuka di peramban | Nilai ppm, kategori, dan rekomendasi berubah tanpa memuat ulang halaman | (diisi saat uji manual) | Belum diuji (manual) | — |
| F9 | Detail Ruangan `/ruangan/{room}` | Membuka detail ruangan | 2 data CO₂ (30,5 dan 70) | Grafik CO₂ (bawaan) rentang 24 jam dengan 3 garis ambang putus-putus 50/100/150 ppm, statistik (rata-rata 50,25, min 30,5, maks 70), daftar "10 pembacaan terakhir", *polling* 5 detik | Sesuai harapan | Valid | `PublicDashboardTest::test_room_detail_defaults_to_co2_chart_with_threshold_lines` |
| F10 | Detail Ruangan | Memilih parameter gas (CO₂, NH₃, CO, atau H₂) | `?gas=co`; aksi pilih H₂ | Grafik menampilkan gas terpilih dengan garis ambangnya (CO: 20/50/80) | Sesuai harapan | Valid | `PublicDashboardTest::test_room_detail_gas_query_selects_gas_and_its_thresholds`, `PublicDashboardTest::test_room_detail_unknown_gas_falls_back_to_co2` |
| F11 | Detail Ruangan | Memilih gas yang tidak dikenal | `?gas=xyz`; aksi pilih `bogus` | Kembali ke CO₂ | Sesuai harapan | Valid | `PublicDashboardTest::test_room_detail_unknown_gas_falls_back_to_co2` |
| F12 | Detail Ruangan | Mengganti rentang waktu 1 jam / 24 jam / 7 hari | Data 3 hari, 3 jam, dan baru saja | 1 jam: 1 data; 7 hari: 3 data; rentang tidak dikenal kembali ke 24 jam | Sesuai harapan | Valid | `PublicDashboardTest::test_room_detail_range_switching` |
| F13 | Detail Ruangan | Daftar 10 pembacaan terakhir | 12 data per menit | Hanya 10 data terbaru, urut dari yang terbaru | Sesuai harapan | Valid | `PublicDashboardTest::test_room_detail_lists_ten_latest_readings_by_created_at` |
| F14 | Detail Ruangan | Ruangan tanpa data / ruangan tidak ada | Ruangan tanpa log; ID ruangan 9999 | Pesan "Menunggu data dari perangkat IoT"; HTTP 404 untuk ruangan yang tidak ada | Sesuai harapan | Valid | `PublicDashboardTest::test_room_detail_without_data_shows_empty_state`, `PublicDashboardTest::test_unknown_room_returns_404` |
| F15 | Detail Ruangan | Grafik tergambar di peramban | Membuka halaman di peramban | Grafik garis dan garis ambang terlihat, sumbu waktu terbaca | (diisi saat uji manual) | Belum diuji (manual) | — |
| F16 | Riwayat `/riwayat` | Membuka halaman riwayat | 3 data di 2 ruangan | Tabel data, tombol "Unduh CSV", *polling* 5 detik | Sesuai harapan | Valid | `PublicHistoryTest::test_history_page_renders_for_guests` |
| F17 | Riwayat | Memfilter berdasarkan tanggal | Dari/sampai 2026-10-02; dari 2026-10-02 saja | Hanya data pada rentang tanggal tersebut (1 data; 2 data) | Sesuai harapan | Valid | `PublicHistoryTest::test_history_filters_by_date_range` |
| F18 | Riwayat | Memfilter berdasarkan status (kategori) | Kategori "Tidak Sehat"; kategori tidak dikenal "Sangat Baik" | Hanya 1 data Tidak Sehat; kategori tidak dikenal diabaikan (3 data) | Sesuai harapan | Valid | `PublicHistoryTest::test_history_filters_by_category`, `PublicHistoryTest::test_history_ignores_unknown_category` |
| F19 | Riwayat | Memfilter berdasarkan ruangan dan mengatur ulang filter | Ruangan "Kamar Tidur"; lalu atur ulang | 1 data Sedang; setelah atur ulang kembali 3 data | Sesuai harapan | Valid | `PublicHistoryTest::test_history_filters_by_room`, `PublicHistoryTest::test_reset_filters_clears_all_filters` |
| F20 | Riwayat | Ringkasan periode mengikuti filter | Tanpa filter | Jumlah 3, kategori terburuk Tidak Sehat, distribusi per kategori | Sesuai harapan | Valid | `PublicHistoryTest::test_history_summary_reflects_filtered_set` |
| F21 | Riwayat | Pembagian halaman (*pagination*) | 23 data | Halaman pertama 20 baris, total 2 halaman | Sesuai harapan | Valid | `PublicHistoryTest::test_history_paginates_twenty_rows_per_page` |
| F22 | Riwayat | Berpindah ke halaman berikutnya | Menekan halaman 2 di peramban (23 data) | Halaman 2 menampilkan 3 data sisanya | (diisi saat uji manual) | Belum diuji (manual) | — |
| F23 | Riwayat | Tidak ada data yang cocok | Kategori "Berbahaya" | Pesan "Tidak ada pengukuran yang cocok dengan filter" | Sesuai harapan | Valid | `PublicHistoryTest::test_history_shows_empty_state` |
| F24 | Riwayat | Menekan tombol ekspor CSV | Filter ruangan "Dapur Utama"; filter kategori Sedang + tanggal 2026-10-03 | Berkas `riwayat-kualitas-udara-<waktu>.csv` terunduh; kolom: waktu, ruangan, 4 ppm, 4 kategori, kategori keseluruhan, penyebab utama, rekomendasi; isinya hanya data sesuai filter | Sesuai harapan | Valid | `PublicHistoryTest::test_csv_download_has_exact_header_and_filtered_rows`, `PublicHistoryTest::test_csv_download_respects_category_and_date_filters` |
| F25 | Rekomendasi `/rekomendasi` | Membuka halaman rekomendasi | Tanpa data | Tabel 4 aturan (Normal, Waspada, Perlu Tindakan, Segera Bertindak) dengan teks lengkap, "Dasar rekomendasi", tautan sumber EPA, dan pernyataan "bukan diagnosis medis" | Sesuai harapan | Valid | `RecommendationPageTest::test_page_shows_all_four_rules_basis_sources_and_disclaimer` |
| F26 | Rekomendasi | Strategi EPA pada tabel aturan | Membuka halaman | Strategi "Peningkatan ventilasi" dan "Kendali sumber" tampil | Sesuai harapan | Valid | `RecommendationPageTest::test_rule_table_lists_epa_strategies` |
| F27 | Rekomendasi | Kondisi saat ini per ruangan | Data lama Baik, data terbaru CO 55 | Kondisi saat ini memakai data terbaru: Tidak Sehat, tingkat "Perlu Tindakan" | Sesuai harapan | Valid | `RecommendationPageTest::test_current_condition_shows_latest_category_and_recommendation_per_room` |
| F28 | Rekomendasi | Ruangan belum memiliki data | Ruangan dan perangkat tanpa log | Pesan "Belum ada data" | Sesuai harapan | Valid | `RecommendationPageTest::test_current_condition_is_empty_without_data` |
| F29 | Status Perangkat `/perangkat` | Membuka halaman IoT system | Perangkat AIR-001 dengan data 10 detik lalu | Menampilkan device ID AIR-001, ruangan, gas yang dikirim (CO₂, NH₃, CO, H₂), *polling* 5 detik | Sesuai harapan | Valid | `DeviceStatusTest::test_page_renders_device_with_room_and_gases` |
| F30 | Status Perangkat | Status koneksi terhubung | Data terakhir 10 detik lalu; tepat 30 detik lalu | "Terhubung" (batas ≤ 30 detik) | Sesuai harapan | Valid | `DeviceStatusTest::test_device_is_connected_when_last_data_is_recent`, `DeviceStatusTest::test_device_is_connected_at_exactly_thirty_seconds` |
| F31 | Status Perangkat | Status koneksi terputus | Data terakhir 120 detik lalu; perangkat belum pernah mengirim | "Terputus"; untuk perangkat tanpa data juga "Belum pernah mengirim data" | Sesuai harapan | Valid | `DeviceStatusTest::test_device_is_disconnected_when_last_data_is_old`, `DeviceStatusTest::test_device_without_data_is_disconnected` |
| F32 | Status Perangkat | Interval kirim terukur | 10 data berselang 6 detik (ditambah 2 data lama); 1 data saja | "6,0 detik"; interval tidak diketahui bila hanya 1 data | Sesuai harapan | Valid | `DeviceStatusTest::test_measured_interval_is_average_gap_of_last_ten_logs`, `DeviceStatusTest::test_interval_is_unknown_with_single_log` |
| F33 | Status Perangkat | Waktu data terakhir (*last update*) ditampilkan | Perangkat mengirim data | Waktu data terakhir sesuai waktu kirim | (diisi saat uji manual) | Belum diuji (manual) | — |
| F34 | Status Perangkat | Alur data | Membuka halaman | Urutan: sensor membaca gas → dikirim ke server → validasi format → klasifikasi → disimpan → ditampilkan | Sesuai harapan | Valid | `DeviceStatusTest::test_shows_data_flow_steps` |
| F35 | Status Perangkat | Daftar penolakan data | 11 penolakan; penolakan tanpa device_id; tanpa penolakan | 10 penolakan terbaru beserta pesan galatnya; device_id kosong ditampilkan "Tidak ada"; tanpa penolakan tampil "Belum ada data yang ditolak" | Sesuai harapan | Valid | `DeviceStatusTest::test_shows_ten_latest_rejections_with_short_errors`, `DeviceStatusTest::test_rejection_without_device_id_is_shown_with_placeholder`, `DeviceStatusTest::test_shows_empty_state_without_rejections` |
| F36 | Panduan `/panduan` | Membuka halaman panduan | Tanpa login | Judul "Panduan Kualitas Udara" dan bagian "Cara membaca kategori" | Sesuai harapan | Valid | `PublicGuideTest::test_guide_page_renders_for_guests` |
| F37 | Panduan | Tabel ambang batas | Membuka halaman | Rentang ppm tiap kategori untuk 4 gas dan sitasi Solihat et al. (2026) dengan DOI 10.23960/jitet.v14i1.5568 | Sesuai harapan | Valid | `PublicGuideTest::test_guide_shows_threshold_table_with_solihat_citation` |
| F38 | Panduan | Keterangan gas dan cara membaca | Membuka halaman | Penjelasan ppm sebagai indikator relatif, kategori keseluruhan = kategori gas terburuk, nama 4 gas, sensor MQ-135 dan MQ-2 | Sesuai harapan | Valid | `PublicGuideTest::test_guide_explains_relative_ppm_and_worst_category` |
| F39 | Panduan | FAQ dan batasan | Membuka halaman | Bagian "Pertanyaan umum" memuat interval 5 detik, arti "Terputus", dan "bukan diagnosis medis"; tidak memuat istilah metode perhitungan indeks yang tidak dipakai | Sesuai harapan | Valid | `PublicGuideTest::test_guide_faq_covers_interval_disconnection_and_disclaimer`, `PublicGuideTest::test_guide_has_no_sub_index_ku_index_or_co_aqi` |
| F40 | Kuesioner `/kuesioner` | Membuka kuesioner | Tanpa login | 26 butir UEQ, masing-masing 7 pilihan (182 tombol radio) | Sesuai harapan | Valid | `SurveyTest::test_guest_can_open_survey_page_with_26_items` |
| F41 | Kuesioner | Mengirim jawaban lengkap | 26 jawaban 1–7, nama "Budi"; tanpa nama | Tersimpan, pesan "Terima kasih"; nama boleh kosong | Sesuai harapan | Valid | `SurveyTest::test_guest_can_submit_complete_answers`, `SurveyTest::test_name_is_optional` |
| F42 | Kuesioner | Jawaban belum lengkap | Butir 7 kosong; semua kosong | Galat pada butir yang kosong, tidak tersimpan | Sesuai harapan | Valid | `SurveyTest::test_all_items_are_required` |
| F43 | Kuesioner | Jawaban di luar skala | Butir 1 = 8, butir 8 = 0 | Galat rentang 1–7, tidak tersimpan | Sesuai harapan | Valid | `SurveyTest::test_answers_must_be_between_one_and_seven` |
| F44 | Kuesioner | Pengisian berulang dari IP yang sama | 4 kali kirim dalam 1 jam | Pengiriman ke-4 ditolak dengan pesan "Silakan coba lagi"; hanya 3 tersimpan | Sesuai harapan | Valid | `SurveyTest::test_submissions_are_rate_limited_per_ip` |
| F45 | Seluruh halaman | Navigasi atas | Membuka halaman mana pun | Menu urut: Beranda, Riwayat, Rekomendasi, Perangkat, Panduan, Kuesioner | Sesuai harapan | Valid | `NavigationTest::test_top_navigation_lists_six_menus_in_order` |
| F46 | Seluruh halaman | Akses tanpa login | Membuka `/`; membuka rute login lama | Beranda dapat dibuka tanpa login; rute login lama tidak ada (HTTP 404) dan tidak ada tautan login | Sesuai harapan | Valid | `ExampleTest::test_public_home_is_accessible_without_login`, `PublicAccessTest::test_admin_and_login_routes_are_gone`, `PublicAccessTest::test_public_pages_have_no_admin_link` |
| F47 | Seluruh halaman | Tampilan di layar ponsel | Membuka setiap halaman di peramban ponsel | Tata letak terbaca, tabel dapat digulir, navigasi dapat dipakai | (diisi saat uji manual) | Belum diuji (manual) | — |

---

## 3.12.3 Pengujian Pengolahan Data Sensor

### a. Pengujian validasi data

Data uji dasar (format naskah 3.9.1): `device_id` = `AIR-001` (terdaftar dan aktif), `timestamp` = `2026-05-01 10:00:00`, `co2_ppm` = 30, `nh3_ppm` = 20, `co_ppm` = 10, `h2_ppm` = 5. Setiap baris mengubah satu bagian dari data dasar.

"Kesalahan dicatat" berarti data yang ditolak disimpan di tabel `telemetry_rejections` (device_id, payload, errors) dan ditulis ke log channel `telemetry` (`storage/logs/telemetry-*.log`). Penolakan juga terlihat di halaman Status Perangkat (Tabel 3.23 F35).

**Tabel 3.24 Pengujian Validasi Data**

| No | Kondisi Data Uji | Masukan | Hasil yang Diharapkan | Hasil Aktual | Status | Kasus Uji |
|---|---|---|---|---|---|---|
| 1 | Data lengkap dan format benar | Data uji dasar | HTTP 201, `status: success`; data tersimpan dengan kategori tiap gas, kategori keseluruhan Baik, penyebab utama kosong, rekomendasi tingkat Normal; tidak ada penolakan tercatat | Sesuai harapan | Valid | `TelemetryApiTest::test_complete_payload_is_stored_and_returns_classification` |
| 2 | Salah satu atribut nilai ppm gas tidak tersedia | Data dasar tanpa `co_ppm` | HTTP 422 dengan pesan galat field terkait (`co_ppm`); tidak ada data tersimpan; 1 penolakan tercatat | Sesuai harapan | Valid | `TelemetryApiTest::test_invalid_payload_is_rejected_and_logged` (set data "co_ppm tidak dikirim") |
| 3 | Nilai sensor bukan angka | `co2_ppm` = `"abc"` | HTTP 422 dengan pesan galat field terkait (`co2_ppm`); tidak ada data tersimpan; 1 penolakan tercatat | Sesuai harapan | Valid | `TelemetryApiTest::test_invalid_payload_is_rejected_and_logged` (set data "co2_ppm bukan angka") |
| 4 | `device_id` kosong | `device_id` = `""` | HTTP 422 dengan pesan galat field terkait (`device_id`); tidak ada data tersimpan; penolakan tercatat dengan device_id kosong (NULL) | Sesuai harapan | Valid | `TelemetryApiTest::test_invalid_payload_is_rejected_and_logged` (set data "device_id kosong") |
| 5 | Format timestamp tidak valid | `timestamp` = `"01-05-2026 10:00"` | HTTP 422 dengan pesan galat field terkait (`timestamp`); tidak ada data tersimpan; 1 penolakan tercatat | Sesuai harapan | Valid | `TelemetryApiTest::test_invalid_payload_is_rejected_and_logged` (set data "format timestamp salah") |
| 6 | `device_id` tidak terdaftar | `device_id` = `AIR-999` | HTTP 422 dengan pesan galat field terkait (`device_id`); penolakan tercatat dengan device_id AIR-999 | Sesuai harapan | Valid | `TelemetryApiTest::test_invalid_payload_is_rejected_and_logged` (set data "device_id tidak terdaftar") |
| 7 | Nilai ppm negatif | `nh3_ppm` = −1 | HTTP 422 dengan pesan galat field terkait (`nh3_ppm`); penolakan tercatat di tabel dan di log channel `telemetry` | Sesuai harapan | Valid | `TelemetryApiTest::test_negative_ppm_is_rejected` |
| 8 | Format lama (transisi firmware) | `node_id`, `co2` 70, `nh3` 30, `co` 10, `h2` 5, ditambah `rssi`, `seq_number`, `hop_count`, `topology_type` | HTTP 201; data tersimpan untuk perangkat AIR-001 dengan kategori Sedang; field tambahan diabaikan; waktu perangkat diisi waktu server | Sesuai harapan | Valid | `TelemetryApiTest::test_legacy_format_is_accepted_and_qos_fields_are_ignored` |
| 9 | Format lama dengan node tidak terdaftar | `node_id` = 999 | HTTP 422 dengan pesan galat field terkait (`device_id`); penolakan tercatat | Sesuai harapan | Valid | `TelemetryApiTest::test_legacy_format_with_unknown_node_is_rejected_and_logged` |
| 10 | Waktu perangkat berbeda dengan waktu server | `timestamp` = `2026-05-01 10:00:00`, waktu server 2026-10-23 12:00:00 | Waktu perangkat disimpan apa adanya; waktu terima memakai waktu server | Sesuai harapan | Valid | `TelemetryApiTest::test_device_timestamp_is_kept_while_created_at_is_server_time` |
| 11 | API key dikonfigurasi | `TELEMETRY_API_KEY` = `rahasia-node`; kirim tanpa header, dengan `X-Api-Key: salah`, lalu dengan kunci benar | Tanpa/salah kunci: HTTP 401, tidak dicatat sebagai penolakan validasi; kunci benar: HTTP 201 | Sesuai harapan | Valid | `TelemetryApiTest::test_api_key_is_enforced_when_configured_without_logging_rejection` |
| 12 | API key tidak dikonfigurasi | `TELEMETRY_API_KEY` kosong, tanpa header | HTTP 201 | Sesuai harapan | Valid | `TelemetryApiTest::test_api_key_is_not_required_when_empty` |
| 13 | `device_id` milik perangkat nonaktif | `device_id` perangkat dengan status tidak aktif | HTTP 422 dengan pesan galat field terkait (`device_id`); penolakan tercatat | Sesuai harapan | Valid | `TelemetryApiTest::test_inactive_device_is_rejected` |
| 14 | Pengiriman melebihi batas laju | Lebih dari 60 permintaan per menit dari sumber yang sama | Permintaan berlebih ditolak dengan HTTP 429 | Sesuai harapan | Valid | `TelemetryApiTest::test_telemetry_endpoint_is_throttled_to_sixty_requests_per_minute` |

### b. Pengujian klasifikasi kualitas udara

Ambang batas (ppm) mengikuti Tabel 2.2 naskah (Solihat et al., 2026) dan disimpan di `config/air_quality.php`. Batas bersifat eksklusif: nilai yang sama dengan batas masuk kategori berikutnya.

| Gas | Baik | Sedang | Tidak Sehat | Berbahaya |
|---|---|---|---|---|
| CO₂ | < 50 | 50 – < 100 | 100 – < 150 | ≥ 150 |
| NH₃ | < 40 | 40 – < 80 | 80 – < 120 | ≥ 120 |
| CO | < 20 | 20 – < 50 | 50 – < 80 | ≥ 80 |
| H₂ | < 15 | 15 – < 40 | 40 – < 60 | ≥ 60 |

Setiap baris Tabel 3.25 diuji dua kali: langsung pada fungsi klasifikasi (`AirQualityClassifierTest::test_overall_category_follows_table_3_8`) dan lewat `POST /api/telemetry` sampai data tersimpan (`TelemetryApiTest::test_table_3_8_classification_via_api`). Pada uji lewat API, kategori tiap gas yang tersimpan juga dibandingkan dengan hasil fungsi klasifikasi, dan tingkat rekomendasi yang tersimpan dibandingkan dengan aturan Tabel 3.26.

**Tabel 3.25 Pengujian Klasifikasi Kualitas Udara**

| No | CO₂ (ppm) | NH₃ (ppm) | CO (ppm) | H₂ (ppm) | Kategori per Gas yang Diharapkan (CO₂ / NH₃ / CO / H₂) | Kategori Keseluruhan yang Diharapkan | Hasil Aktual | Status | Kasus Uji (set data) |
|---|---|---|---|---|---|---|---|---|---|
| 1 | 30 | 20 | 10 | 5 | Baik / Baik / Baik / Baik | Baik | Baik | Valid | `AirQualityClassifierTest::test_overall_category_follows_table_3_8`, `TelemetryApiTest::test_table_3_8_classification_via_api` ("baris 1") |
| 2 | 70 | 30 | 10 | 5 | Sedang / Baik / Baik / Baik | Sedang | Sedang | Valid | sama, "baris 2" |
| 3 | 70 | 30 | 55 | 10 | Sedang / Baik / Tidak Sehat / Baik | Tidak Sehat | Tidak Sehat | Valid | sama, "baris 3" |
| 4 | 30 | 30 | 10 | 45 | Baik / Baik / Baik / Tidak Sehat | Tidak Sehat | Tidak Sehat | Valid | sama, "baris 4" |
| 5 | 40 | 130 | 10 | 5 | Baik / Berbahaya / Baik / Baik | Berbahaya | Berbahaya | Valid | sama, "baris 5" |
| 6 | 50 | 20 | 10 | 5 | Sedang / Baik / Baik / Baik | Sedang | Sedang | Valid | sama, "baris 6" |
| 7 | 150 | 20 | 10 | 5 | Berbahaya / Baik / Baik / Baik | Berbahaya | Berbahaya | Valid | sama, "baris 7" |

Baris 3 dan 4 menguji kategori keseluruhan dari parameter terburuk (CO dan H₂). Baris 6 dan 7 menguji nilai tepat di batas ambang CO₂.

Pengujian tambahan untuk klasifikasi:

| No | Skenario | Masukan | Hasil yang Diharapkan | Hasil Aktual | Status | Kasus Uji |
|---|---|---|---|---|---|---|
| 8 | Nilai di sekitar dan tepat di setiap batas, keempat gas (24 set data) | Untuk setiap batas *b* pada tabel ambang: *b* − 0,01 dan *b* (mis. CO₂ 49,99 / 50 / 99,99 / 100 / 149,99 / 150) | *b* − 0,01 masuk kategori di bawah batas; *b* masuk kategori berikutnya (mis. CO₂ 49,99 Baik, 50 Sedang, 149,99 Tidak Sehat, 150 Berbahaya) | Sesuai harapan untuk 24 set data | Valid | `AirQualityClassifierTest::test_category_boundaries_are_exclusive_upper_limits` |
| 9 | Penyebab utama lebih dari satu gas | CO₂ 70, NH₃ 30, CO 55, H₂ 45 | Kategori keseluruhan Tidak Sehat; penyebab utama CO dan H₂; tersimpan sebagai `co,h2` | Sesuai harapan | Valid | `AirQualityClassifierTest::test_dominant_lists_all_worst_gases`, `TelemetryApiTest::test_dominant_gases_are_stored_and_returned` |
| 10 | Penyebab utama saat semua Baik | Seluruh gas Baik | Penyebab utama kosong | Sesuai harapan | Valid | `AirQualityClassifierTest::test_dominant_empty_when_all_good`, `TelemetryApiTest::test_complete_payload_is_stored_and_returns_classification` |
| 11 | Urutan tingkat kategori | Baik, Sedang, Tidak Sehat, Berbahaya | Urutan Baik < Sedang < Tidak Sehat < Berbahaya | Sesuai harapan | Valid | `AirQualityClassifierTest::test_rank_orders_categories` |

---

## 3.12.4 Pengujian Rekomendasi Tindakan Preventif

Rekomendasi ditentukan hanya dari kategori keseluruhan. Teks yang diharapkan adalah teks Tabel 3.20 naskah (sama dengan Tabel 3.26).

**Tabel 3.26 Pengujian Rekomendasi Tindakan Preventif**

| No | Kategori Keseluruhan | Tingkat | Rekomendasi yang Diharapkan | Hasil Aktual | Status | Kasus Uji |
|---|---|---|---|---|---|---|
| 1 | Baik | Normal | Kondisi udara relatif baik. Pertahankan ventilasi ruangan. | Sesuai harapan | Valid | `RecommendationTest::test_for_category_matches_table_3_9` ("Baik"), `PublicDashboardTest::test_home_hides_main_cause_when_overall_is_good` |
| 2 | Sedang | Waspada | Tingkatkan sirkulasi udara, misalnya dengan membuka jendela atau pintu. | Sesuai harapan | Valid | `RecommendationTest::test_for_category_matches_table_3_9` ("Sedang"), `RecommendationPageTest::test_page_shows_all_four_rules_basis_sources_and_disclaimer` |
| 3 | Tidak Sehat | Perlu Tindakan | Tingkatkan ventilasi, periksa dan kurangi sumber polutan seperti asap atau gas, serta kurangi aktivitas di dalam ruangan. | Sesuai harapan | Valid | `RecommendationTest::test_for_category_matches_table_3_9` ("Tidak Sehat"), `PublicDashboardTest::test_home_with_single_room_focuses_on_that_room` |
| 4 | Berbahaya | Segera Bertindak | Segera tingkatkan ventilasi, hentikan sumber polutan, dan pertimbangkan meninggalkan ruangan sementara sampai kondisi udara membaik. | Sesuai harapan | Valid | `RecommendationTest::test_for_category_matches_table_3_9` ("Berbahaya"), `PublicDashboardTest::test_home_with_multiple_rooms_shows_a_card_per_room` |

Pengujian tambahan untuk rekomendasi:

| No | Skenario | Hasil yang Diharapkan | Hasil Aktual | Status | Kasus Uji |
|---|---|---|---|---|---|
| 5 | Daftar aturan lengkap dan berurutan | 4 aturan berurutan Baik, Sedang, Tidak Sehat, Berbahaya | Sesuai harapan | Valid | `RecommendationTest::test_all_returns_four_rules_in_order` |
| 6 | Setiap aturan memakai strategi EPA | Setiap aturan merujuk minimal satu strategi EPA (kendali sumber, ventilasi, pembersihan udara); sumber memiliki label dan URL | Sesuai harapan | Valid | `RecommendationTest::test_every_rule_cites_epa_strategy`, `RecommendationTest::test_sources_expose_label_and_url` |
| 7 | Tingkat rekomendasi tersimpan bersama data sensor | Tingkat yang tersimpan untuk setiap baris Tabel 3.25 sama dengan aturan kategorinya | Sesuai harapan | Valid | `TelemetryApiTest::test_table_3_8_classification_via_api` |
| 8 | Kategori yang tidak dikenal | Ditolak (pengecualian), tidak memberi rekomendasi asal | Sesuai harapan | Valid | `RecommendationTest::test_unknown_category_throws` |

---

## 3.12.5 Pengujian pada Beberapa Kondisi Ruangan

Pengujian ini dilakukan manual dengan perangkat IoT nyata (`arduino/simku_monitor`) di ruang uji yang sama. Tidak ada kasus uji otomatis untuk bagian ini, sehingga seluruh skenario berstatus "Belum diuji (manual)" sampai lembar isian diisi.

**Persiapan (wajib dibaca sebelum mulai):**

1. Firmware mengambil **baseline saat perangkat dinyalakan**: pemanasan sekitar 30 detik, lalu rata-rata 50 pembacaan ADC. Nilai ppm yang dikirim adalah selisih terhadap baseline ini (indikator relatif, sesuai metode Solihat et al., 2026). Karena itu:
   - Nyalakan perangkat di **udara ruangan normal** (pintu/jendela terbuka seperti biasa, tanpa sumber asap atau gas).
   - **Jangan me-*restart* atau mencabut daya perangkat di antara skenario.** Restart akan mengambil baseline baru dari udara saat itu sehingga nilai antar-skenario tidak dapat dibandingkan. Bila perangkat terlanjur restart, ulangi dari skenario 1.
2. Pastikan perangkat sudah tersinkron NTP dan halaman **Status Perangkat** menunjukkan "Terhubung" dengan interval terukur sekitar 5 detik sebelum memulai.
3. Jalankan server dengan `php artisan serve --host=0.0.0.0 --port=8000` agar dapat dijangkau ESP32.
4. Kosongkan data uji coba sebelumnya bila perlu (`php artisan telemetry:clear`).
5. Untuk setiap skenario, catat waktu mulai dan selesai, lalu ekspor CSV dari halaman Riwayat dengan filter tanggal tersebut sebagai bukti.

**Tabel 3.27 Skenario Pengujian pada Beberapa Kondisi Ruangan**

| No | Skenario | Kondisi Pengujian | Hal yang Diamati | Hasil yang Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| 1 | Ruangan normal | Ventilasi biasa, tanpa sumber polutan tambahan, ± 10 menit | Kategori dan nilai ppm | Kategori cenderung Baik dan rekomendasi sesuai kategori | (diisi saat uji manual) | Belum diuji (manual) |
| 2 | Ruangan tertutup | Pintu dan jendela ditutup, sirkulasi minim, ± 30 menit | Perubahan nilai ppm, terutama CO₂ | Nilai meningkat bertahap dan kategori berubah sesuai ambang batas | (diisi saat uji manual) | Belum diuji (manual) |
| 3 | Ruangan berasap | Sumber asap terkontrol (mis. asap dupa) di dekat sensor, ± 5 menit | Perubahan nilai CO dan H₂ | Kategori naik dan rekomendasi berubah sesuai kategori baru | (diisi saat uji manual) | Belum diuji (manual) |
| 4 | Ventilasi berbeda | Lanjutan skenario 2/3: pintu dan jendela dibuka, ± 15 menit | Penurunan nilai ppm dan kategori | Nilai menurun dan kategori membaik setelah ventilasi dibuka | (diisi saat uji manual) | Belum diuji (manual) |

**Lembar isian pengamatan** (isi satu baris per skenario; nilai ppm dibaca dari Beranda atau CSV Riwayat):

| No | Skenario | Tanggal | Waktu Mulai | Waktu Selesai | ppm Awal (CO₂ / NH₃ / CO / H₂) | ppm Puncak / Akhir (CO₂ / NH₃ / CO / H₂) | Kategori Awal → Akhir | Penyebab Utama | Rekomendasi yang Tampil | Data Diterima Tiap ± 5 Detik (Ya/Tidak) | Tersimpan di Riwayat (Ya/Tidak) | Sesuai Harapan (Ya/Tidak) | Catatan |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| 1 | Ruangan normal | | | | | | | | | | | | |
| 2 | Ruangan tertutup | | | | | | | | | | | | |
| 3 | Ruangan berasap | | | | | | | | | | | | |
| 4 | Ventilasi berbeda | | | | | | | | | | | | |

Status skenario diubah menjadi **Valid** bila seluruh kolom "Hal yang Diamati" sesuai harapan dan bukti CSV tersedia, atau **Tidak Valid** bila tidak sesuai, disertai catatan penyebabnya.

---

## Pengujian pendukung (di luar Tabel 3.22–3.27)

Kasus uji berikut mendukung evaluasi pengguna (UEQ) dan penyiapan data. Bagian ini tidak termasuk Tabel 3.22–3.27.

| No | Skenario | Hasil yang Diharapkan | Hasil Aktual | Status | Kasus Uji |
|---|---|---|---|---|---|
| P1 | Skoring UEQ: semua jawaban netral (4) | Nilai 0 pada setiap skala | Sesuai harapan | Valid | `UeqTest::test_all_neutral_answers_give_zero_on_every_scale` |
| P2 | Skoring UEQ: jawaban terbaik | Nilai +3 pada setiap skala | Sesuai harapan | Valid | `UeqTest::test_best_answers_give_plus_three_on_every_scale` |
| P3 | Butir terbalik | Butir 3 dijawab 1 menjadi +3 | Sesuai harapan | Valid | `UeqTest::test_reversed_item_3_answer_1_becomes_plus_3` |
| P4 | Pemetaan butir ke skala | 6 skala mencakup 26 butir, masing-masing sekali | Sesuai harapan | Valid | `UeqTest::test_scale_items_cover_all_26_items_once` |
| P5 | Kategori *benchmark* | Kategori Excellent/Good/Above Average/Below Average/Bad sesuai batas bawah | Sesuai harapan | Valid | `UeqTest::test_benchmark_categories_follow_lower_bounds` |
| P6 | Ringkasan: rata-rata, simpangan baku, Cronbach's alpha | Alpha dan simpangan baku kosong bila < 2 responden; terhitung bila ≥ 2 responden; ringkasan kosong bila tanpa responden | Sesuai harapan | Valid | `UeqTest::test_alpha_null_when_fewer_than_two_responses`, `UeqTest::test_summary_computes_mean_sd_and_alpha_for_multiple_responses`, `UeqTest::test_empty_summary_has_null_values` |
| P7 | Jawaban tidak lengkap atau di luar rentang pada skoring | Ditolak (pengecualian) | Sesuai harapan | Valid | `UeqTest::test_incomplete_or_out_of_range_answers_throw` |
| P8 | Perintah `php artisan ueq:report` | Menampilkan jumlah responden dan ringkasan per skala; menulis CSV kolom `id,waktu,nama,i1..i26`; tetap berhasil tanpa responden | Sesuai harapan | Valid | `SurveyTest::test_report_command_prints_summary_and_writes_csv`, `SurveyTest::test_report_command_handles_no_responses` |
| P9 | *Seeder* ruangan dan perangkat | 1 ruangan "Ruang Uji" (ROOM-01) dan 1 perangkat aktif "Perangkat IoT 1" (AIR-001); dijalankan dua kali tetap 1 | Sesuai harapan | Valid | `RoomDeviceSeederTest::test_seeder_creates_one_room_with_one_active_device_and_is_idempotent` |
| P10 | Perintah `php artisan telemetry:clear --force` | Log sensor dan penolakan dikosongkan; ruangan dan perangkat tetap | Sesuai harapan | Valid | `RoomDeviceSeederTest::test_telemetry_clear_empties_logs_and_rejections_but_keeps_master_data` |
| P11 | Label rentang ambang | Label rentang ppm per kategori benar; gas/kategori tidak dikenal ditolak; kategori tidak memakai rentang indeks | Sesuai harapan | Valid | `AirQualityTest::test_threshold_label_describes_each_category_range_in_ppm`, `AirQualityTest::test_threshold_label_rejects_unknown_gas_or_category`, `AirQualityTest::test_categories_have_no_index_range` |

---

## Rekapitulasi

| Tabel | Jumlah Skenario | Valid | Belum diuji (manual) |
|---|---|---|---|
| Tabel 3.23 Pengujian Fungsional Aplikasi | 47 | 42 | 5 (F8, F15, F22, F33, F47) |
| Tabel 3.24 Pengujian Validasi Data | 14 | 14 | 0 |
| Tabel 3.25 Pengujian Klasifikasi Kualitas Udara (termasuk pengujian tambahan) | 11 | 11 | 0 |
| Tabel 3.26 Pengujian Rekomendasi Tindakan Preventif (termasuk pengujian tambahan) | 8 | 8 | 0 |
| Tabel 3.27 Skenario Pengujian pada Beberapa Kondisi Ruangan | 4 | 0 | 4 |
| Pengujian pendukung | 11 | 11 | 0 |
| **Jumlah** | **95** | **86** | **9** |

Kesimpulan sementara (26 Oktober 2026): seluruh skenario yang diotomasi lulus (150 kasus uji, 606 *assertion*, 0 gagal). Kelima butir wajib Tabel 3.24 naskah, ketujuh baris Tabel 3.25, dan keempat baris Tabel 3.26 berstatus Valid. Skenario yang masih membutuhkan pengamatan di peramban atau dengan perangkat IoT nyata, termasuk seluruh Tabel 3.27, berstatus "Belum diuji (manual)" dan harus diisi dari pengujian lapangan sebelum dilaporkan di Bab IV.

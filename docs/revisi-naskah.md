# Daftar Revisi Naskah Skripsi SIASAP

Naskah: *Sistem Monitoring Kualitas Udara Ruangan Berbasis IoT*, Wan Salma Nurkhairin Fitri (NIM 2207125057). Acuan: naskah proposal terbaru dan spesifikasi penyelarasan `docs/superpowers/specs/2026-10-23-penyelarasan-naskah-design.md` (selanjutnya disebut **Spec**).

Aplikasi SIASAP telah diselaraskan dengan ruang lingkup yang disepakati: sistem hanya memantau data kualitas udara secara real-time (interval 5–10 detik) tanpa analisis tren maupun prediksi, diuji pada satu ruangan, dikembangkan dengan metode Waterfall, dan dievaluasi dengan black-box testing serta UEQ 26 butir. Dokumen ini mendaftar setiap bagian naskah yang perlu diubah agar isi naskah sama dengan perilaku aplikasi. Kalibrasi sensor dan rumus konversi ppm menjadi tanggung jawab penelitian IoT (rekan satu proyek), sehingga naskah ini cukup menjelaskan sifat nilai yang diterima aplikasi. Nomor halaman mengikuti naskah proposal. Keterangan "(cek)" menandai detail yang belum dapat dipastikan dari sumber yang tersedia.

**Fakta aplikasi yang dirujuk**

| Hal | Nilai di aplikasi | Berkas |
|---|---|---|
| Ambang batas (ppm) | CO₂ 50/100/150, NH₃ 40/80/120, CO 20/50/80, H₂ 15/40/60; batas eksklusif (`<`) | `config/air_quality.php` |
| Sumber ambang | Solihat, Salsabila & Susilawati (2026), JITET 14(1), 1592–1599, doi:10.23960/jitet.v14i1.5568 | `config/air_quality.php` |
| Interval kirim perangkat | 5 detik | `config/air_quality.php`, `arduino/siasap_monitor/siasap_monitor.ino` |
| Batas status terhubung | data terakhir ≤ 30 detik (3 × interval maksimum 10 detik) | `config/air_quality.php` |
| Rumus konversi di perangkat | ppm = max(0, ADC − baseline) × (5,0/4096) × k; k: CO₂ 100, NH₃ 80, CO 50, H₂ 30; baseline = rata-rata 50 pembacaan ADC saat perangkat menyala | `siasap_monitor.ino`; Solihat et al. (2026, hlm. 1596) |
| Format data | `device_id`, `timestamp` (`Y-m-d H:i:s`), `co2_ppm`, `nh3_ppm`, `co_ppm`, `h2_ppm` | `TelemetryApiController.php` |
| Validasi | semua atribut wajib; ppm numerik ≥ 0; `device_id` terdaftar; format waktu valid. Data ditolak dijawab HTTP 422 dan dicatat pada log penolakan | `TelemetryApiController.php` |
| Rekomendasi | 4 aturan if-then sesuai Tabel 3.3; dasar: tiga strategi EPA *Improving Indoor Air Quality* dan prinsip gradasi AQI (EPA-403/B-26-003, Tabel 5) | `config/recommendations.php` |
| UEQ | 26 butir versi Indonesia, skala 1–7, 6 skala, butir terbalik 3, 4, 5, 9, 10, 12, 17, 18, 19, 21, 23, 24, 25 | `config/ueq.php` |

---

## Halaman Judul dan Prakata

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| Halaman sampul (judul) | "RANCANG BANGUN SISTEM PREDIKSI KUALITAS UDARA RUANGAN BERBASIS IOT" | "RANCANG BANGUN SISTEM MONITORING KUALITAS UDARA RUANGAN BERBASIS IOT" | Spec §1 dan §9: sistem hanya memantau, tidak memprediksi. |
| Prakata, paragraf 1 (hlm. ii) | judul "Rancang Bangun Sistem Prediksi Kualitas Udara Ruangan Berbasis Iot Sebagai Upaya Deteksi Dini Risiko Penyakit Pernapasan" | Ganti dengan "…yang berjudul "Rancang Bangun Sistem Monitoring Kualitas Udara Ruangan Berbasis IoT"." Hapus frasa "Sebagai Upaya Deteksi Dini Risiko Penyakit Pernapasan". | Spec §9: hapus "prediksi" dan "deteksi dini". Aplikasi tidak menilai risiko penyakit. Rekomendasinya informatif dan bukan diagnosis medis (`config/recommendations.php`, `disclaimer`). |

## Daftar Isi, Daftar Gambar, dan Daftar Tabel

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| Daftar Isi (hlm. iii–iv) | Subbab 3.11 dan 3.12 tanpa sub-subbab | Perbarui setelah penomoran diperbaiki: 3.11.1–3.11.5 dan 3.12.1–3.12.7 (termasuk 3.12.6 Evaluasi Pengguna dengan UEQ). Tambahkan 3.8.7 Wireframe Halaman Kuesioner UEQ. | Spec §9: perbaiki penomoran ganda (lihat subbab 3.11 dan 3.12 di bawah). |
| Daftar Gambar (hlm. v) | "Gambar 3.1 Flowchart Sistem Monitoring dan Prediksi Kualitas Udara"; "Gambar 3.4 Wireframe Sensor Charts"; "Gambar 3.5 Historical Data"; "Gambar 3.6 Exposure Duration dan Health Advisory"; "Gambar 3.7 Wireframe IoT System" | Gambar 3.1 Flowchart Sistem Monitoring Kualitas Udara<br>Gambar 3.2 Use Case Diagram Sistem Monitoring<br>Gambar 3.3 Wireframe Halaman Beranda<br>Gambar 3.4 Wireframe Halaman Detail Ruangan (Grafik Sensor)<br>Gambar 3.5 Wireframe Halaman Riwayat<br>Gambar 3.6 Wireframe Halaman Panduan<br>Gambar 3.7 Wireframe Halaman Rekomendasi Tindakan<br>Gambar 3.8 Wireframe Halaman Status Perangkat (IoT System)<br>Gambar 3.9 Wireframe Halaman Kuesioner UEQ | Spec §9 (Gambar 3.1/3.6/3.7) dan §6 (7 halaman). Di batang tubuh, Gambar 3.6 dan 3.7 sama-sama berjudul "Exposure Duration dan Health Advisory", dan wireframe IoT System bernomor 3.8, tidak sesuai dengan Daftar Gambar. |
| Daftar Tabel (hlm. vi) | Tabel 3.3 dipakai dua kali di batang tubuh (hlm. 54 dan 56); daftar hanya memuat Tabel 3.3 Aturan Rekomendasi | Pertahankan Tabel 3.3 = Aturan Rekomendasi Tindakan Preventif dan Tabel 3.4–3.10 tetap. Tambahkan Tabel 3.11 Pemetaan Butir UEQ ke Skala dan Tabel 3.12 Benchmark UEQ. | Spec §9 (penomoran tabel ganda). Spec §3 dan §8 merujuk "Tabel 3.3" untuk aturan rekomendasi dan "Tabel 3.5–3.10" untuk pengujian, sehingga nomor-nomor itu dipertahankan. |

## BAB I Pendahuluan

### 1.1 Latar Belakang

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| 1.1, paragraf 5 (hlm. 3) | "…agar mampu mengelola data sensor, melakukan analisis data, serta menampilkan informasi secara terstruktur." | "…agar mampu menerima, mengolah, dan menyimpan data sensor serta menampilkan informasi secara terstruktur." | Spec §1: tanpa analisis tren maupun prediksi. |
| 1.1, paragraf 8 (hlm. 4) | "…tanpa alat ukur terkalibrasi laboratorium (Solihat et al., 2025)." dan "…berdasarkan ambang batas konsentrasi gas (Solihat et al., 2025)." | Ubah tahun menjadi **(Solihat et al., 2026)** di kedua kutipan. Sesudah kalimat "Perangkat mengirimkan nilai konsentrasi…", tambahkan: "Nilai ppm tersebut dihitung oleh perangkat dengan rumus konversi Solihat et al. (2026) terhadap nilai acuan (*baseline*) yang diukur saat perangkat dinyalakan, sehingga bersifat indikator relatif terhadap kondisi udara awal dan bukan konsentrasi absolut." | Spec §9 (tahun Solihat 2026; ppm sebagai indikator relatif berbasis baseline). Daftar pustaka naskah sudah mencantumkan 2026. |
| 1.1, paragraf 10 (hlm. 5) | "…meneliti dengan judul "Rancang Bangun Sistem Prediksi Kualitas Udara Ruangan Berbasis IoT"." | "…meneliti dengan judul "Rancang Bangun Sistem Monitoring Kualitas Udara Ruangan Berbasis IoT"." | Spec §9 (judul). |
| 1.1, paragraf 10 (hlm. 5) | "…serta menguji fungsionalitas dan kemampuan sistem dalam memantau perubahan kondisi kualitas udara pada beberapa kondisi ruangan." | "…menguji fungsionalitas sistem dalam memantau perubahan kondisi kualitas udara pada beberapa skenario kondisi di satu ruangan uji, serta mengevaluasi pengalaman pengguna dengan *User Experience Questionnaire* (UEQ)." | Spec §1 (1 ruangan; testing black-box dan UEQ). |

### 1.2 Rumusan Masalah dan 1.3 Tujuan Penelitian

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| 1.2 butir 3 (hlm. 5) | "Bagaimana melakukan pengujian terhadap fungsionalitas dan kemampuan sistem … pada beberapa kondisi ruangan?" | "Bagaimana menguji fungsionalitas sistem dalam memantau perubahan kondisi kualitas udara pada beberapa skenario kondisi ruangan, serta mengevaluasi pengalaman pengguna terhadap aplikasi?" | Spec §1 dan §7: evaluasi pengguna dengan UEQ termasuk metode penelitian dan perlu muncul di rumusan masalah. |
| 1.3 butir 3 (hlm. 6) | "Melakukan pengujian sistem pada beberapa kondisi ruangan untuk mengetahui kemampuan sistem dalam mendeteksi perubahan kondisi kualitas udara." | "Melakukan pengujian fungsional (black-box) pada beberapa skenario kondisi di satu ruangan uji serta mengukur pengalaman pengguna dengan UEQ 26 butir." | Spec §1 dan §7. |

### 1.4 Batasan Masalah

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| 1.4 butir 4 (hlm. 6) | "Penelitian ini tidak membahas perancangan dan implementasi perangkat keras IoT…" | Tambahkan di akhir butir: "Kalibrasi sensor, akurasi nilai ppm, dan rumus konversi pada perangkat menjadi tanggung jawab penelitian IoT yang dikerjakan rekan satu proyek. Aplikasi hanya memvalidasi format data yang diterima." | Spec §1 (validitas sensor). |
| 1.4 butir 6 (hlm. 6) | "Pengujian sistem dilakukan pada beberapa kondisi ruangan, seperti kondisi ruangan normal, tertutup, dan kondisi dengan perubahan kualitas udara." | "Pengujian sistem dilakukan pada satu ruangan uji dengan empat skenario kondisi: ruangan normal, ruangan tertutup, ruangan berasap, dan ventilasi berbeda (Tabel 3.10)." | Spec §1 (1 ruangan). Disamakan dengan Tabel 3.10. |
| 1.4, butir baru 7 | — | "7. Sistem hanya memantau data real-time yang dikirim perangkat IoT secara berkala dengan interval 5–10 detik. Grafik dan riwayat hanya ditampilkan, tidak dianalisis, dan sistem tidak melakukan analisis tren maupun prediksi kualitas udara." | Spec §1 (fungsi sistem). |
| 1.4, butir baru 8 | — | "8. Klasifikasi memakai satu rujukan ambang batas, yaitu Solihat et al. (2026), untuk keempat gas. Rujukan tersebut tidak menjelaskan dasar penetapan ambangnya, dan ambang hanya bermakna bila perangkat memakai rumus konversi yang sama (indikator relatif terhadap baseline saat perangkat dinyalakan)." | Spec §2 dan §9 (keterbatasan dasar ambang). |
| 1.4, butir baru 9 | — | "9. Aplikasi dapat diakses tanpa login dan tidak memiliki panel administrator. Aktor sistem hanya Pengguna dan Perangkat IoT." | Spec §1 (aktor) dan §6 (halaman publik). |

### 1.5 Manfaat Penelitian dan 1.6 Sistematika Penulisan

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| 1.5 butir 3 (hlm. 7) | "…sistem monitoring berbasis IoT yang terintegrasi dengan analisis data dalam bidang Rekayasa Perangkat Lunak." | "…sistem monitoring berbasis IoT yang terintegrasi dengan pengolahan dan penyajian data dalam bidang Rekayasa Perangkat Lunak." | Spec §1 (tanpa analisis tren/prediksi). |
| 1.6, BAB III (hlm. 7) | "…metode pengembangan sistem cerdas…" | "…metode pengembangan sistem (Waterfall)…" | Spec §1 (Waterfall). Sistem berbasis aturan, bukan sistem cerdas. |

## BAB II Landasan Teori

### 2.1 Penelitian Terdahulu

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| 2.1, paragraf 1 (hlm. 9) | "Penelitian mengenai sistem pemantauan dan prediksi kualitas udara telah banyak dilakukan…" | "Penelitian mengenai sistem pemantauan kualitas udara telah banyak dilakukan…" | Spec §9 (hapus "prediksi"). |
| 2.1, Fikri et al. (hlm. 9) | "Namun, penelitian tersebut masih berfokus pada monitoring tanpa adanya fitur prediksi kualitas udara." | "Namun, penelitian tersebut belum menyajikan saran tindakan preventif yang sesuai dengan kategori kondisi udara." | Spec §9 (hapus kritik "belum ada prediksi"). Celah diganti dengan fokus penelitian ini: penyajian kategori dan rekomendasi. |
| 2.1, Khoeriah Utami et al. (hlm. 9) | "…tetapi belum mengembangkan sistem prediksi sebagai deteksi dini risiko kesehatan." | "…tetapi informasi yang disajikan masih berupa nilai parameter dan grafik, belum diterjemahkan menjadi kategori kondisi udara yang mudah dipahami pengguna awam." | Spec §9 (hapus kritik prediksi dan "deteksi dini"). |
| 2.1, Rivangga et al. (hlm. 9–10) | "Namun, penelitian ini belum mengintegrasikan hasil prediksi ke dalam sistem dashboard berbasis web secara interaktif." | Ringkasan penelitian boleh dipertahankan. Ganti kalimat kritik dengan: "Penelitian ini menunjukkan pemanfaatan data historis untuk prediksi. Penelitian yang diusulkan tidak menempuh arah tersebut dan berfokus pada penyajian kondisi terkini secara real-time." | Spec §1 (tanpa prediksi). Kritik semula menyiratkan bahwa penelitian ini akan mengintegrasikan prediksi. |
| 2.1, akhir uraian Sung & Hsiao (hlm. 10) | "…sebagian penelitian masih berfokus pada aspek monitoring tanpa mengintegrasikan fitur prediksi sebagai upaya deteksi dini risiko penyakit pernapasan." | "…sebagian penelitian masih berfokus pada penyajian nilai sensor dan belum menerjemahkannya menjadi kategori kondisi udara beserta saran tindakan preventif." | Spec §9. |
| 2.1, Hananta et al. (hlm. 12) | "…belum mengembangkan fitur analisis lanjutan seperti prediksi kondisi udara, analisis durasi paparan, maupun sistem rekomendasi kesehatan berbasis aturan. Oleh karena itu, penelitian ini hadir … melalui pendekatan analitik berbasis perangkat lunak." | "…belum menyediakan rekomendasi tindakan preventif berbasis aturan. Oleh karena itu, penelitian ini mengembangkan sistem yang tidak hanya menampilkan data, tetapi juga mengklasifikasikan nilai indikator gas berdasarkan ambang batas dan menyajikan saran tindakan sesuai kategori kondisi udara." | Spec §1 (tanpa prediksi; analisis durasi paparan tidak ada di aplikasi). |
| Tabel 2.1, kepala kolom (hlm. 13–15) | "No \| Tahun \| Tahun \| Judul \| Metode \| Hasil" | "No \| Peneliti \| Tahun \| Judul \| Metode \| Hasil" | Kolom kedua berisi nama peneliti, tetapi kepalanya tertulis "Tahun". |

### 2.3 Internet of Things (IoT)

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| 2.3, paragraf 2 (hlm. 18) dan paragraf 2 lanjutan (hlm. 19) | "Atzori et al. (2025) menjelaskan…" dan "Lebih lanjut, Atzori et al. (2025) menyatakan…" | Ganti menjadi **Atzori et al. (2010)** di kedua tempat. | Spec §9 (tahun Atzori 2010). Artikel *Computer Networks* 54(15) dengan DOI 10.1016/j.comnet.2010.05.010 terbit tahun 2010. |
| 2.3, Shinde & Karode (hlm. 19) | "…mengetahui pola perubahan kualitas udara atau sebagai dasar pengembangan sistem prediksi." | "…menelusuri kembali kondisi kualitas udara pada waktu tertentu." | Spec §1. Kalimat ini menghindarkan kesan bahwa penelitian ini menganalisis pola atau memprediksi. |

### 2.4 Sistem Monitoring Kualitas Udara Ruangan Berbasis IoT

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| 2.4, paragraf 3 (hlm. 21) | "…dapat disimpan sebagai data historis, sehingga dapat digunakan untuk analisis tren kualitas udara dalam periode tertentu." | "…dapat disimpan sebagai data historis, sehingga dapat ditampilkan kembali sebagai riwayat kualitas udara dalam periode tertentu." | Spec §1 (grafik/riwayat hanya ditampilkan, tidak dianalisis). |

### 2.7 Klasifikasi Indikator Kualitas Udara

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| 2.7, paragraf 1 (hlm. 26) | "(Solihat et al., 2025)" | "(Solihat et al., 2026)" | Spec §9 (tahun Solihat). |
| 2.7, paragraf 2 (hlm. 26), sesudah "…melainkan digunakan sebagai indikator kualitas udara." | — | Tambahkan: "Pada Solihat et al. (2026, hlm. 1596), nilai ppm dihitung dengan rumus ppm = (ADC − baseline) × (5,0/4096) × k, dengan k bernilai 100 untuk CO₂, 80 untuk NH₃, 50 untuk CO, dan 30 untuk H₂. Baseline adalah rata-rata 50 pembacaan ADC saat perangkat dinyalakan, dan selisih negatif dibulatkan menjadi nol. Akibatnya, nilai yang dihasilkan mendekati nol pada udara awal dan naik ketika kadar gas di sekitar sensor bertambah. Nilai ini merupakan indikator relatif terhadap kondisi udara saat perangkat dinyalakan, bukan konsentrasi absolut hasil alat ukur terkalibrasi." | Spec §2 dan §9 (ppm sebagai indikator relatif berbasis baseline). Rumus sama dengan fungsi `hitungPpm()` di `siasap_monitor.ino`. |
| 2.7, paragraf 3 (hlm. 26) | "…berdasarkan ambang batas konsentrasi gas yang terdeteksi (Solihat et al., 2025)." | "(Solihat et al., 2026)" | Spec §9. |

### 2.8 Klasifikasi Berbasis Ambang Batas

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| 2.8, paragraf 3 (hlm. 27) | "Berdasarkan penelitian yang dilakukan oleh Solihat et al. (2025)…" | "Solihat et al. (2026)" | Spec §9. |
| Tabel 2.2 (hlm. 27) | Tabel tanpa keterangan sumber | Tambahkan baris di bawah tabel: "Sumber: Solihat et al. (2026, hlm. 1597)." Nilai ambang tidak berubah. | Spec §2: satu sumber ambang untuk semua gas. Nilai tabel sama dengan `config/air_quality.php` dan fungsi `evaluateAirQuality()` pada paper. |
| 2.8, sesudah rumus KU (hlm. 28) | "KU = max(KCO2, KNH3, KCO, KH2)" dan keterangannya | Tambahkan: "Setiap batas pada Tabel 2.2 bersifat eksklusif. Nilai tepat pada batas masuk ke kategori berikutnya, misalnya CO₂ = 50 ppm termasuk Sedang dan CO₂ = 150 ppm termasuk Berbahaya. Urutan kategori adalah Baik < Sedang < Tidak Sehat < Berbahaya. Gas yang kategorinya sama dengan KU disebut penyebab utama (dapat lebih dari satu) dan tidak ditampilkan bila KU = Baik. Logika ini setara dengan fungsi evaluasi pada Solihat et al. (2026): udara Baik bila semua gas di bawah ambang Baik, Sedang bila semua gas di bawah ambang Sedang, dan seterusnya." | Spec §2 (aturan batas, penyebab utama, kesetaraan dengan paper). |
| 2.8, paragraf "Pada penelitian ini, threshold-based classification digunakan…" (hlm. 28) | "…merupakan hasil estimasi dari pembacaan sensor MQ dan tidak melalui proses kalibrasi…" | Tambahkan paragraf keterbatasan: "Solihat et al. (2026) tidak mencantumkan dasar penetapan ambang pada Tabel 2.2. Ambang tersebut juga hanya bermakna bila nilai ppm dihitung dengan rumus konversi yang sama, yaitu relatif terhadap baseline saat perangkat dinyalakan. Oleh karena itu, kategori pada penelitian ini menunjukkan perubahan kondisi udara relatif terhadap kondisi awal ruangan, bukan tingkat bahaya menurut standar kesehatan. Paper tersebut juga menampilkan Sedang dan Tidak Sehat dengan satu LED kuning, sedangkan fungsi evaluasinya menghasilkan empat kategori terpisah. Penelitian ini memakai empat kategori tersebut." | Spec §2 dan §9 (keterbatasan dasar ambang). |

### 2.9 Metode Waterfall

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| 2.9, paragraf penutup (hlm. 31) | "…untuk merancang dan membangun sistem prediksi kualitas udara ruangan berbasis Internet of Things (IoT)…" | "…untuk merancang dan membangun sistem monitoring kualitas udara ruangan berbasis Internet of Things (IoT)…" | Spec §9 (hapus "prediksi"); metode tetap Waterfall (Spec §1). |

## BAB III Metodologi Penelitian

### 3.1 Metode Penelitian

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| 3.1, paragraf 3 (hlm. 32) | "…yang dikirimkan perangkat IoT setiap 30 detik. Nilai ppm tersebut merupakan estimasi dari perangkat…" | "…yang dikirimkan perangkat IoT secara berkala dengan interval 5–10 detik (perangkat uji dikonfigurasi setiap 5 detik). Nilai ppm tersebut dihitung oleh perangkat dengan rumus konversi Solihat et al. (2026) relatif terhadap baseline saat perangkat dinyalakan dan tidak dikalibrasi dengan alat ukur standar laboratorium, sehingga digunakan sebagai indikator relatif kualitas udara ruangan. Sistem menerima nilai ppm apa adanya tanpa melakukan konversi." | Spec §9 (interval 30 → 5–10 detik; ppm relatif). Interval di `config/air_quality.php` dan `INTERVAL_KIRIM = 5000` pada firmware. |
| 3.1, paragraf 4 (hlm. 32) | "…sedangkan data yang tidak valid ditolak dan dicatat sebagai kesalahan." | "…sedangkan data yang tidak valid ditolak dengan respons HTTP 422 dan dicatat pada log penolakan data." | `TelemetryApiController::rejectPayload()`; Spec §4. |
| 3.1, paragraf 5 (hlm. 33) | "Tahapan yang dilakukan meliputi analisis kebutuhan, perancangan sistem, implementasi, dan pengujian." | Tambahkan paragraf: "Evaluasi sistem dilakukan dengan dua cara. Pertama, *black-box testing* menguji kesesuaian fungsi terhadap kebutuhan (Tabel 3.5–3.10). Kedua, evaluasi pengalaman pengguna memakai *User Experience Questionnaire* (UEQ) 26 butir versi Bahasa Indonesia (Laugwitz et al., 2008; Santoso et al., 2016), yang hasilnya dibandingkan dengan benchmark UEQ (Schrepp et al., 2017). Pengujian dilakukan pada satu ruangan uji." | Spec §9 (3.1 metode evaluasi UEQ) dan Spec §1 (1 ruangan). |

### 3.2 Objek Penelitian

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| Tabel 3.1, baris "Output sistem" (hlm. 35) | "Dashboard real-time, grafik per gas, status/kategori kualitas udara, rekomendasi tindakan preventif" | "Tujuh halaman web: Beranda, Detail Ruangan (grafik sensor), Riwayat (dengan ekspor CSV), Rekomendasi Tindakan, Status Perangkat, Panduan, dan Kuesioner UEQ" | Spec §6. |
| Tabel 3.1, baris baru | — | "Lokasi uji \| Satu ruangan dengan satu perangkat IoT (device_id AIR-001)" | Spec §1 (1 ruangan) dan §5 (seeder 1 ruangan, 1 perangkat). |
| Tabel 3.1, baris "Batasan objek" | "Tidak membahas perancangan/pengoperasian perangkat keras IoT, tidak melakukan kalibrasi sensor laboratorium…" | Tambahkan: "…tidak melakukan analisis tren maupun prediksi; kalibrasi dan rumus konversi menjadi tanggung jawab penelitian IoT." | Spec §1. |

### 3.3 Sumber Data

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| 3.3, paragraf 2 (hlm. 35) | "Nilai ppm yang diterima sistem merupakan estimasi dari perangkat dan tidak melalui kalibrasi…" | "Nilai ppm yang diterima sistem merupakan indikator relatif yang dihitung perangkat terhadap baseline saat perangkat dinyalakan (Solihat et al., 2026) dan tidak melalui kalibrasi…" | Spec §9 (ppm relatif). |
| Tabel 3.2, baris "Timestamp" (hlm. 36) | "Sistem IoT / server — Waktu saat data sensor dikirimkan atau diterima oleh server" | "Perangkat IoT — Waktu pembacaan oleh perangkat dengan format `YYYY-MM-DD HH:MM:SS`" | Aplikasi menyimpan waktu dari perangkat (`recorded_at`, format `Y-m-d H:i:s`); `TelemetryApiController`. |
| Tabel 3.2, baris "Device ID" | "Identitas perangkat yang mengirimkan data sensor" | "Identitas perangkat yang mengirimkan data sensor; harus sudah terdaftar di sistem" | Validasi `exists:nodes,code`; Spec §4. |
| Tabel 3.2, baris baru | — | "Jawaban kuesioner UEQ \| Responden (pengguna aplikasi) \| 26 butir skala 1–7, diisi melalui halaman Kuesioner" | Spec §7. |

### 3.4 Teknik Pengumpulan Data

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| 3.4, butir 1) Observasi (hlm. 37) | "…mengetahui format data, interval pengiriman data…" | Tambahkan: "…dengan interval pengiriman yang dirancang 5–10 detik…" | Spec §9 (interval). |
| 3.4, butir baru 5) (hlm. 38) | — | "5). Kuesioner UEQ. Kuesioner *User Experience Questionnaire* (UEQ) 26 butir versi Bahasa Indonesia diberikan kepada responden setelah mereka menggunakan aplikasi. Setiap butir berupa pasangan kata berlawanan dengan skala 7 titik. Kuesioner diisi melalui halaman Kuesioner pada aplikasi, dan nama responden bersifat opsional. Jumlah responden: (cek)." | Spec §7 dan §9 (UEQ). |

### 3.5 Metode Pengembangan Sistem

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| 3.5, tahap 3) Implementation (hlm. 39) | "…serta menampilkan informasi monitoring dan prediksi kualitas udara melalui dashboard web." | "…serta menampilkan informasi monitoring, kategori kualitas udara, dan rekomendasi tindakan melalui aplikasi web." | Spec §9 (hapus "prediksi"). |
| 3.5, tahap 4) Testing (hlm. 39) | "Pengujian dilakukan terhadap fitur-fitur utama sistem … serta pemberian rekomendasi tindakan. Hasil dari tahap pengujian digunakan untuk mengetahui apakah sistem telah berjalan dengan baik dan bebas dari kesalahan fungsional." | Ganti kalimat terakhir dengan: "Pengujian terdiri atas *black-box testing* terhadap setiap fungsi dan halaman (Tabel 3.5–3.10) serta evaluasi pengalaman pengguna dengan UEQ 26 butir. *Black-box testing* menunjukkan apakah sistem bebas dari kesalahan fungsional, sedangkan UEQ menunjukkan penilaian pengguna terhadap aplikasi." | Spec §9 (3.4–3.5 tahap Testing). |

### 3.6 Arsitektur Sistem

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| 3.6, paragraf 2 (hlm. 40) | "…IoT Layer, Analysis Layer, dan Presentation Layer… pengujian.." | Ganti "Analysis Layer" menjadi "Processing Layer (lapisan pengolahan)" di paragraf ini dan di judul 3.6.2. Hapus titik ganda pada "pengujian..". | Spec §1. Lapisan ini hanya mengklasifikasikan dan memetakan rekomendasi, tidak menganalisis tren. |
| 3.6.1 IoT Layer (hlm. 40) | "Data dikirim secara berkala setiap 30 detik melalui jaringan internet menggunakan REST API (HTTP)." | "Data dikirim secara berkala setiap 5–10 detik (perangkat uji: 5 detik) melalui jaringan internet ke endpoint `POST /api/telemetry` (REST API, HTTP). Nilai ppm dihitung perangkat relatif terhadap baseline saat dinyalakan (Solihat et al., 2026)." | Spec §9 (interval) dan §4 (endpoint). |
| 3.6.3 Presentation Layer (hlm. 41) | Empat butir: ppm real-time, kategori, rekomendasi, grafik dan tabel historis | Tambahkan butir: "• gas penyebab utama;" "• status koneksi perangkat dan data yang ditolak;" "• panduan membaca kategori beserta tabel ambang;" "• kuesioner UEQ." | Spec §6 (7 halaman). |
| 3.6.4 Alur Kerja Sistem, langkah 1–2 (hlm. 41) | "1. Perangkat IoT mengirimkan nilai ppm … ke server setiap 30 detik." "2. … Data yang tidak valid ditolak dan dicatat sebagai error." | "1. Perangkat IoT mengirimkan nilai ppm CO₂, NH₃, CO, dan H₂ beserta device_id dan timestamp ke server setiap 5–10 detik." "2. Sistem memvalidasi data. Data yang tidak valid ditolak (HTTP 422) dan dicatat pada log penolakan data." | Spec §9 (interval) dan §4. |

### 3.7 Perancangan Sistem

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| 3.7.1, paragraf 2 (hlm. 42) | "Apabila data tidak valid, sistem mencatat kesalahan pada error handler, menolak data tersebut…" | "Apabila data tidak valid, sistem menolak data dengan respons HTTP 422, mencatatnya pada log penolakan data (ditampilkan di halaman Status Perangkat), dan menunggu pengiriman berikutnya." | Spec §4 (pencatatan penolakan) dan §6 (halaman Perangkat menampilkan 10 penolakan terakhir). |
| Gambar 3.1 (hlm. 43) | "Gambar 3.1 Flowchart Sistem Monitoring dan Prediksi Kualitas Udara" | Ganti judul menjadi "Gambar 3.1 Flowchart Sistem Monitoring Kualitas Udara". **Gambar ulang** dengan alur: terima data → validasi → (tidak valid: tolak 422 dan catat penolakan) → simpan → klasifikasi per gas (Tabel 2.2) → KU = kategori terburuk → penyebab utama → rekomendasi (Tabel 3.3) → simpan hasil → tampil di halaman. Hapus blok prediksi atau analisis tren bila ada. | Spec §9 (Gambar 3.1). |
| 3.7.2 Use Case, paragraf 2 (hlm. 44) | "…dengan dua aktor, yaitu pengguna dan perangkat IoT. … dengan tiga fungsi yang dapat diakses, yaitu melihat dashboard real-time, melihat rekomendasi tindakan preventif, dan melihat grafik serta data historis." | "…dengan dua aktor, yaitu Pengguna dan Perangkat IoT. Tidak ada aktor administrator karena seluruh halaman dapat diakses tanpa login. Pengguna dapat melihat beranda real-time, melihat grafik sensor per ruangan, melihat dan mengekspor riwayat data, melihat rekomendasi tindakan preventif, melihat status perangkat, membaca panduan, dan mengisi kuesioner UEQ. Perangkat IoT mengirimkan data ppm CO₂, NH₃, CO, dan H₂." | Spec §9 (use case 2 aktor) dan §6 (7 halaman). |
| Gambar 3.2 (hlm. 45) | Use case dengan tiga fungsi pengguna | **Gambar ulang**: aktor Pengguna dan Perangkat IoT; use case Pengguna ditambah *Melihat Grafik Sensor*, *Melihat dan Mengekspor Riwayat*, *Melihat Status Perangkat*, *Membaca Panduan*, dan *Mengisi Kuesioner UEQ*. Relasi «include» untuk Menerima dan Memvalidasi Data, Menentukan Kategori, dan Menghasilkan Rekomendasi tetap dipakai. | Spec §1 dan §6. |

### 3.8 Perancangan Aplikasi Web

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| 3.8, pengantar baru (hlm. 45) | Langsung ke 3.8.1 | Tambahkan sebelum 3.8.1: "Aplikasi web terdiri atas tujuh halaman publik yang diakses melalui navigasi atas tanpa login: Beranda (`/`), Detail Ruangan (`/ruangan/{ruangan}`), Riwayat (`/riwayat`), Rekomendasi Tindakan (`/rekomendasi`), Status Perangkat (`/perangkat`), Panduan (`/panduan`), dan Kuesioner UEQ (`/kuesioner`). Data pada halaman diperbarui otomatis mengikuti interval kirim perangkat (5 detik)." | Spec §6 dan §9 (3.8 diselaraskan dengan 7 halaman). |
| 3.8.1, paragraf 2 (hlm. 45) | "Bagian navigasi atas menyediakan menu Beranda, Riwayat, Panduan, dan Kuesioner, serta tombol Masuk Admin untuk mengakses panel administrator. Bagian ringkasan utama menampilkan kategori kualitas udara terburuk dari seluruh ruangan, nama ruangan dengan kondisi terburuk, jumlah ruangan yang dipantau, jumlah sensor aktif…" | "Bagian navigasi atas menyediakan menu Beranda, Riwayat, Rekomendasi, Perangkat, Panduan, dan Kuesioner. Karena penelitian ini memakai satu ruangan uji, Beranda langsung menampilkan kondisi ruangan tersebut: kategori kualitas udara keseluruhan, gas penyebab utama, kartu empat gas (nilai ppm dan label kategori), kartu rekomendasi tindakan, dan waktu pembaruan data terakhir." | Spec §1 (tanpa admin; 1 ruangan) dan §6 (Beranda). |
| 3.8.1, paragraf 3–4 (hlm. 45–46) | "…kategori kualitas udara, nilai indeks KU, gas yang menjadi penyebab utama…" dan "…legenda kategori … beserta rentang nilai indeksnya." | Hapus "nilai indeks KU". Ganti legenda menjadi: "…legenda empat kategori (Baik, Sedang, Tidak Sehat, Berbahaya) dengan warna masing-masing; rincian ambang ppm per gas tersedia di halaman Panduan." | Spec §1: sub-indeks 0–500 dan Indeks KU dihapus. Klasifikasi langsung memakai ambang ppm (Tabel 2.2). |
| Gambar 3.3 (hlm. 46) | "Gambar 3.3 Wireframe Dashboard" | Ganti judul menjadi "Gambar 3.3 Wireframe Halaman Beranda". **Gambar ulang** tanpa tombol Masuk Admin, kartu banyak ruangan, dan Indeks KU. | Spec §6. |
| 3.8.2, paragraf 1 (hlm. 46) | "…menyajikan status kualitas udara terkini, nilai konsentrasi gas, tren perubahan kualitas udara, statistik pembacaan, serta riwayat data sensor." | "…menyajikan status kualitas udara terkini, nilai ppm tiap gas, grafik nilai gas terhadap waktu, dan sepuluh pembacaan terakhir." | Spec §1 (grafik hanya ditampilkan, tidak dianalisis) dan §6. |
| 3.8.2, paragraf 3–7 (hlm. 47) | Bagian ringkasan dengan "Indeks KU"; kartu gas dengan "sub-indeks"; "tren Indeks KU"; "Bagian statistik … rata-rata, terendah, dan tertinggi Indeks KU"; "Bagian proporsi waktu per kategori…" | Hapus "Indeks KU", "sub-indeks", bagian statistik, dan bagian proporsi waktu per kategori. Ganti paragraf grafik dengan: "Bagian grafik sensor menyediakan pemilih gas (CO₂, NH₃, CO, atau H₂) dan pemilih rentang waktu (1 jam, 24 jam, atau 7 hari). Grafik menampilkan nilai ppm gas terpilih terhadap waktu beserta garis ambang kategori dari Tabel 2.2 agar pengguna dapat melihat posisi nilai terhadap batas kategori. Grafik hanya menampilkan data dan tidak dipakai untuk analisis tren." Kartu gas memuat nama gas, nilai ppm, dan kategori. | Spec §1 (sub-indeks dan Indeks KU dihapus; tanpa analisis tren) dan §6 (Detail Ruangan). |
| 3.8.2, paragraf 8 (hlm. 47–48) | "…waktu pembacaan, status atau kategori kualitas udara, Indeks KU, serta nilai ppm…" | "…waktu pembacaan, kategori kualitas udara keseluruhan, serta nilai ppm CO₂, NH₃, CO, dan H₂." | Spec §1. |
| Gambar 3.4 (hlm. 48) | "Gambar 3.4 Wireframe Sensor Charts" | Ganti judul menjadi "Gambar 3.4 Wireframe Halaman Detail Ruangan (Grafik Sensor)". **Gambar ulang** sesuai uraian di atas. | Spec §6. |
| 3.8.3 (hlm. 48–49) | "…berdasarkan ruangan, kategori, dan rentang tanggal tertentu. Halaman ini menyediakan fitur filter data, ringkasan periode, tabel pengukuran, serta unduhan data dalam format CSV." dan "…melihat nilai Indeks KU…" | "Halaman Riwayat menampilkan data pembacaan yang tersimpan di basis data. Pengguna dapat memfilter data berdasarkan rentang tanggal dan kategori, berpindah halaman (pagination), dan mengunduh data dalam format CSV. Kolom CSV: waktu, ruangan, nilai ppm empat gas, kategori empat gas, kategori keseluruhan, penyebab utama, dan rekomendasi." Hapus "ringkasan periode" dan "Indeks KU". | Spec §6 (Riwayat) dan §1. |
| Gambar 3.5 (hlm. 49) | "Gambar 3.5 Historical Data" | "Gambar 3.5 Wireframe Halaman Riwayat". **Gambar ulang** tanpa Indeks KU. | Spec §6. |
| 3.8.4 (hlm. 49–50) | "…cara membaca nilai Indeks KU…"; "Bagian Cara Membaca Indeks KU menjelaskan … sub-indeks setiap gas, penentuan Indeks KU berdasarkan sub-indeks tertinggi…"; "…beserta rentang indeks…"; "…ambang batas mengacu pada konfigurasi sistem…" | "Halaman Panduan menjelaskan cara membaca kategori kualitas udara: kategori tiap gas ditentukan dari ambang pada Tabel 2.2, kategori keseluruhan adalah kategori terburuk, dan penyebab utama adalah gas dengan kategori terburuk tersebut. Contoh perhitungan memakai data pada subbab 3.9.2. Halaman ini juga memuat tabel ambang (sumber: Solihat et al., 2026), keterangan empat gas yang dipantau beserta sensornya, pertanyaan umum, dan batasan sistem: nilai ppm adalah indikator relatif terhadap baseline perangkat, dasar penetapan ambang tidak dicantumkan oleh sumbernya, dan informasi pada aplikasi bukan diagnosis medis." | Spec §1, §2, dan §6 (Panduan). |
| Gambar 3.6 (hlm. 50) | "Gambar 3.6 Exposure Duration dan Health Advisory" | Ganti judul menjadi "Gambar 3.6 Wireframe Halaman Panduan". **Gambar ulang**: tanpa Indeks KU, sub-indeks, durasi paparan, maupun *health advisory*. | Spec §9 (Gambar 3.6). Judul lama tidak sesuai isi subbab dan merujuk fitur durasi paparan yang tidak ada. |
| 3.8.5, paragraf 2 (hlm. 51) | "Halaman ini juga menampilkan daftar aturan rekomendasi untuk seluruh kategori… Rekomendasi bersifat informatif dan preventif, bukan diagnosis medis." | Tambahkan sebelum kalimat terakhir: "Halaman ini menampilkan pula dasar penyusunan rekomendasi, yaitu tiga strategi U.S. EPA (kendali sumber, peningkatan ventilasi, dan pembersihan udara) dan prinsip gradasi urgensi penyampaian AQI EPA (lihat 3.10.1)." | Spec §3 dan §6 (Rekomendasi); `config/recommendations.php` (`basis`). |
| Gambar 3.7 (hlm. 51) | "Gambar 3.7 Exposure Duration dan Health Advisory" | Ganti judul menjadi "Gambar 3.7 Wireframe Halaman Rekomendasi Tindakan". **Gambar ulang**: kategori dan rekomendasi saat ini, tabel empat aturan (Tabel 3.3), dasar EPA, dan *disclaimer*. | Spec §9 (Gambar 3.7). |
| 3.8.6 (hlm. 51–52) | "…perangkat dinyatakan terhubung apabila data masih diterima dalam batas waktu tertentu dari interval pengiriman 30 detik." | "…perangkat dinyatakan terhubung apabila data terakhir diterima tidak lebih dari 30 detik yang lalu, yaitu tiga kali interval pengiriman maksimum 10 detik." Tambahkan di akhir paragraf 2: "Halaman ini juga menampilkan sepuluh data terakhir yang ditolak beserta alasan penolakannya." | Spec §6 (Status Perangkat) dan §9 (interval); `online_threshold_seconds = 30`. |
| Gambar 3.8 (hlm. 52) | "Gambar 3.8 Wireframe IoT System" (di Daftar Gambar tertulis 3.7) | Gunakan "Gambar 3.8 Wireframe Halaman Status Perangkat (IoT System)" dan samakan dengan Daftar Gambar. **Gambar ulang** dengan tabel penolakan data. | Penomoran gambar tidak konsisten (Spec §9). |
| 3.8.7 (baru, sesudah 3.8.6) | — | "3.8.7 Wireframe Halaman Kuesioner UEQ. Halaman Kuesioner menampilkan 26 butir UEQ versi Bahasa Indonesia. Setiap butir berupa pasangan kata berlawanan, misalnya *menyusahkan – menyenangkan*, dengan tujuh pilihan di antaranya. Responden dapat mengisi nama secara opsional. Satu alamat IP dibatasi tiga kali pengisian per jam untuk mencegah pengisian berulang. Wireframe halaman ditunjukkan pada Gambar 3.9." Sisipkan **Gambar 3.9 Wireframe Halaman Kuesioner UEQ** (baru). | Spec §6 (halaman 7) dan §7. |

### 3.9 Pengolahan Data Indikator Kualitas Udara

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| 3.9, paragraf 2 (hlm. 52–53) | "Nilai ppm yang diterima dari perangkat IoT merupakan hasil estimasi berdasarkan pembacaan sensor…" | Tambahkan: "Perangkat menghitung nilai tersebut dengan rumus Solihat et al. (2026), yaitu ppm = max(0, ADC − baseline) × (5,0/4096) × k, sehingga nilainya mendekati nol pada kondisi udara saat perangkat dinyalakan. Rumus ini diterapkan oleh penelitian IoT; aplikasi web tidak bergantung pada rumus, hanya pada ambang Tabel 2.2." | Spec §2 dan §9 (ppm relatif berbasis baseline). |
| 3.9.1, paragraf 1 (hlm. 53) | "…yang dikirimkan oleh perangkat IoT setiap 30 detik." | "…yang dikirimkan oleh perangkat IoT setiap 5–10 detik (perangkat uji: 5 detik)." | Spec §9 (interval). |
| 3.9.1, contoh payload (hlm. 53) | `{"device_id": "AIR-001", "timestamp": "2026-05-01 10:00:00", "co2_ppm": 70, "nh3_ppm": 30, "co_ppm": 55, "h2_ppm": 10}` | Contoh dipertahankan karena sudah sama dengan format aplikasi. Tambahkan sesudah contoh: "Data dikirim ke endpoint `POST /api/telemetry` dalam format JSON. Aturan validasinya: semua atribut wajib ada; `co2_ppm`, `nh3_ppm`, `co_ppm`, dan `h2_ppm` berupa angka ≥ 0; `device_id` harus terdaftar di sistem; dan `timestamp` berformat `YYYY-MM-DD HH:MM:SS`. Data valid dijawab HTTP 201 beserta kategori dan rekomendasinya. Data tidak valid dijawab HTTP 422 dan dicatat pada log penolakan data." | Spec §4 dan §9 (3.9.1 contoh payload); `TelemetryApiController::store()`. |
| 3.9.2, sesudah contoh (hlm. 54) | "…maka kategori keseluruhan adalah Tidak Sehat, mengikuti parameter CO." | Tambahkan: "Gas yang kategorinya sama dengan kategori keseluruhan disebut penyebab utama; pada contoh ini penyebab utamanya adalah CO. Bila lebih dari satu gas berada pada kategori terburuk, semuanya ditampilkan sebagai penyebab utama. Bila kategori keseluruhan Baik, penyebab utama tidak ditampilkan." | Spec §2 (penyebab utama). |
| 3.9.3, paragraf 1 (hlm. 54) | "Setiap nilai dibandingkan dengan rentang ambang batas…" | Tambahkan: "Perbandingan dilakukan berurutan dengan batas eksklusif (`<`) sesuai Tabel 2.2, sehingga nilai tepat pada batas masuk ke kategori yang lebih buruk." | Spec §2 (aturan batas); diuji pada Tabel 3.8 baris 6–7. |
| Tabel 3.3 Kategori Kualitas Udara Ruangan (hlm. 54–55) | Tabel kedua bernomor 3.3 (kriteria Baik, Sedang, Tidak Sehat, Berbahaya) | Hapus tabel ini dan ganti kalimat pengantar "Tabel 3.3 menunjukkan kriteria penentuan…" dengan: "Kriteria setiap kategori mengikuti ambang pada Tabel 2.2 dan aturan kategori terburuk pada subbab 3.9.2." Dengan begitu, Tabel 3.3 hanya dipakai untuk aturan rekomendasi. | Spec §9 (penomoran tabel ganda). Isi tabel ini sudah tercakup dalam Tabel 2.2 dan rumus KU. Mempertahankan nomor Tabel 3.3–3.10 menjaga kesesuaian dengan Spec dan berkas pengujian. Alternatifnya, beri tabel ini nomor sendiri lalu geser semua nomor tabel sesudahnya. |

### 3.10 Rekomendasi Tindakan Preventif (Rule-Based)

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| 3.10.1, paragraf 2 (hlm. 55) | "Jenis tindakan pada Tabel 3.4 mengacu pada strategi … yang dijelaskan oleh U.S. Environmental Protection Agency (2026)…" | Ganti "Tabel 3.4" menjadi "Tabel 3.3". Tahun sitasi EPA mengikuti entri daftar pustaka baru "Improving Indoor Air Quality" (tahun: cek). | Penomoran tabel (Spec §9). Kutipan EPA ini belum punya entri di daftar pustaka. |
| 3.10.1, sesudah kalimat "…terutama saat berlangsung aktivitas yang melepaskan polutan." (hlm. 56) | — | Tambahkan: "Setiap aturan memakai satu atau lebih strategi tersebut. Kategori Baik dan Sedang memakai peningkatan ventilasi, sedangkan Tidak Sehat dan Berbahaya memakai peningkatan ventilasi dan kendali sumber. Tingkat urgensi (Normal, Waspada, Perlu Tindakan, Segera Bertindak) mengikuti prinsip penyampaian AQI U.S. EPA (EPA-403/B-26-003, Tabel 5): anjuran makin tegas seiring kategori memburuk. Penelitian ini hanya mengadopsi prinsip bertingkat tersebut, bukan angka AQI." | Spec §3 (dasar rekomendasi); `config/recommendations.php` (`strategies`, `basis`). |
| 3.10.2, paragraf 1 (hlm. 56) | "Aturan tersebut dirangkum pada Tabel 3.4." | "Aturan tersebut dirangkum pada Tabel 3.3." | Penomoran tabel. |
| Tabel 3.3 Aturan Rekomendasi (hlm. 56) | Empat aturan | Tidak diubah. Teks aplikasi sama persis dengan tabel ini. | Spec §3; `config/recommendations.php`. |
| 3.10.3 (hlm. 56–57) | "Rekomendasi diperbarui otomatis setiap kali sistem menerima data baru dari perangkat IoT." | "Rekomendasi disimpan bersama setiap data yang diterima dan tampilan diperbarui otomatis mengikuti interval kirim perangkat (5 detik)." | Spec §4 (level rekomendasi disimpan) dan §6 (interval polling). |

### 3.11 Implementasi Sistem

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| Sub-subbab 3.11 (hlm. 57–59) | "3.13.1 Integrasi Data IoT", "3.13.2", "3.13.3", "3.13.4", "3.13.5" | Ganti nomor menjadi 3.11.1–3.11.5. | Spec §9 (penomoran subbab ganda). |
| 3.11.1 (hlm. 57) | "Data tersebut dikirimkan ke server secara berkala setiap 30 detik…" dan "…melalui endpoint API yang disediakan…" | "…secara berkala setiap 5–10 detik…" dan "…melalui endpoint `POST /api/telemetry`… Data yang ditolak dicatat pada tabel log penolakan." | Spec §9 (interval) dan §4. |
| 3.11.2, paragraf Database (hlm. 58) | "…nilai pembacaan sensor, nilai ppm tiap parameter gas, kategori per gas, kategori keseluruhan, dan waktu pembacaan." | Tambahkan: "…gas penyebab utama, tingkat rekomendasi, data yang ditolak beserta alasannya, serta jawaban kuesioner UEQ." | Spec §5 (kolom `sensor_logs`; tabel `telemetry_rejections` dan `ueq_responses`). |
| 3.11.3 (hlm. 58–59) | Dua fitur: klasifikasi dan rekomendasi | Tambahkan: "3). Penerimaan dan Validasi Data. Fitur ini menerima data melalui API, memvalidasi format, dan mencatat data yang ditolak." "4). Kuesioner UEQ. Fitur ini mengumpulkan 26 butir jawaban responden dan menghitung skor per skala." | Spec §4 dan §7. |
| 3.11.5 (hlm. 59) | "…sebagaimana ditunjukkan pada Tabel 3.5." | "…sebagaimana ditunjukkan pada Tabel 3.4." | Penomoran tabel (tabelnya bernomor 3.4). |
| Tabel 3.4 Teknologi, baris Backend dan Frontend (hlm. 59) | "Backend: Laravel"; "Frontend: HTML, CSS, JavaScript" | "Backend: Laravel 13 (PHP 8.3)"; "Frontend: Blade, Livewire 4, Tailwind CSS 4, Chart.js 4". Sesuaikan nomor versi dengan versi terpasang (cek dengan `composer show` dan `package.json`). | Sesuai teknologi aplikasi (`composer.json`: `laravel/framework ^13.17`, `livewire/livewire ^4.4`; `package.json`: `tailwindcss ^4.0.0`, `chart.js ^4.4.4`). |

### 3.12 Pengujian Sistem

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| Sub-subbab 3.12 (hlm. 60–66) | "3.14.1 Rencana Pengujian" s.d. "3.14.6 Kesimpulan Pengujian" | Ganti nomor menjadi 3.12.1–3.12.5, sisipkan 3.12.6 Evaluasi Pengguna dengan UEQ (baru), lalu 3.12.7 Kesimpulan Pengujian. | Spec §9 (penomoran ganda; 3.12 + UEQ). |
| 3.12, paragraf 2 (hlm. 60) | "Metode yang digunakan adalah black-box testing dan pengujian validasi hasil pengolahan data." | Tambahkan: "Selain itu, pengalaman pengguna dievaluasi dengan *User Experience Questionnaire* (UEQ) 26 butir." | Spec §1 dan §7. |
| 3.12.1, paragraf 1 (hlm. 60) | "Pengujian dilakukan pada empat aspek utama." | "Pengujian dilakukan pada lima aspek utama…" Tambahkan kalimat: "Evaluasi pengguna dengan UEQ mengukur pengalaman pengguna terhadap aplikasi." | Spec §9 (3.12 + UEQ). |
| Tabel 3.5 baris 1 (hlm. 60) | "Dashboard, sensor charts, historical data, rekomendasi tindakan, dan IoT system" | "Beranda, detail ruangan (grafik sensor), riwayat, rekomendasi tindakan, status perangkat, panduan, dan kuesioner UEQ" | Spec §6 (7 halaman). |
| Tabel 3.5 baris 4 (hlm. 61) | "Ruangan normal, tertutup, berasap, dan ventilasi berbeda" | "Empat skenario kondisi pada satu ruangan uji: normal, tertutup, berasap, dan ventilasi berbeda" | Spec §1 (1 ruangan). |
| Tabel 3.5 baris baru 5 | — | "5 \| Evaluasi Pengguna (UEQ) \| Enam skala UEQ: daya tarik, kejelasan, efisiensi, ketepatan, stimulasi, kebaruan \| Mengetahui pengalaman pengguna dan membandingkannya dengan benchmark UEQ" | Spec §7. |
| 3.12.2, paragraf 1 (hlm. 61) | "Fitur yang diuji meliputi halaman dashboard, sensor charts, historical data, rekomendasi tindakan, dan IoT system. … Hasilnya dapat dilihat pada Tabel 3.7." | "Fitur yang diuji meliputi ketujuh halaman aplikasi. … Skenario pengujian dapat dilihat pada Tabel 3.6." | Spec §6; rujukan tabel salah (seharusnya 3.6). |
| Tabel 3.6 baris 1–2 (hlm. 61–62) | "Dashboard"; "Sensor charts" | "Beranda"; "Detail ruangan (grafik sensor)". Hasil baris 2: "Grafik menampilkan nilai gas terpilih pada rentang 1 jam, 24 jam, atau 7 hari beserta garis ambang kategori." | Spec §6. |
| Tabel 3.6 baris 7 (hlm. 62) | "IoT system — Menampilkan device ID, jenis sensor, last update, dan status koneksi" | "Status perangkat — Menampilkan device ID, gas yang dikirim, waktu data terakhir, status koneksi (terhubung bila data terakhir ≤ 30 detik), alur data, dan sepuluh penolakan terakhir" | Spec §6 (halaman 5). |
| Tabel 3.6 baris baru 8–9 | — | "8 \| Panduan \| Membuka halaman panduan \| Menampilkan cara membaca kategori, tabel ambang (Solihat et al., 2026), keterangan empat gas, pertanyaan umum, dan batasan"<br>"9 \| Kuesioner UEQ \| Mengisi 26 butir lalu mengirim \| Jawaban tersimpan dan muncul pesan berhasil; pengiriman dengan butir kosong ditolak" | Spec §6 (halaman 6–7) dan §8. |
| Tabel 3.7, kolom Hasil yang Diharapkan baris 2–5 (hlm. 63) | "Data ditolak dan kesalahan dicatat" | "Data ditolak (HTTP 422) dan dicatat pada log penolakan data". Baris 4 boleh diperluas menjadi "device_id kosong atau tidak terdaftar". | Spec §4; validasi `exists:nodes,code`. |
| 3.12.3, paragraf 2 (hlm. 62) | "…sedangkan data tidak valid ditolak dan dicatat pada error handler." | "…sedangkan data tidak valid ditolak dan dicatat pada log penolakan data yang dapat dilihat di halaman Status Perangkat." | Spec §4 dan §6. |
| Tabel 3.8 (hlm. 63) | Tujuh kombinasi nilai | Nilai tidak diubah; semua hasil yang diharapkan sesuai ambang aplikasi. Opsional: tambahkan kolom "Penyebab Utama" (1: –, 2: CO₂, 3: CO, 4: H₂, 5: NH₃, 6: CO₂, 7: CO₂). | Spec §2 dan §8. |
| Tabel 3.9 baris 3–4 (hlm. 64) | "Tingkatkan ventilasi, periksa dan kurangi sumber polutan, serta kurangi aktivitas di dalam ruangan." "Segera tingkatkan ventilasi, hentikan sumber polutan, dan pertimbangkan meninggalkan ruangan sementara." | Samakan persis dengan Tabel 3.3: "Tingkatkan ventilasi, periksa dan kurangi sumber polutan seperti asap atau gas, serta kurangi aktivitas di dalam ruangan." dan "Segera tingkatkan ventilasi, hentikan sumber polutan, dan pertimbangkan meninggalkan ruangan sementara sampai kondisi udara membaik." Tambahkan kolom "Tingkat" (Normal, Waspada, Perlu Tindakan, Segera Bertindak). | Spec §3: teks rekomendasi harus persis sama dengan Tabel 3.3, dan pengujian membandingkan teks secara utuh. |
| 3.12.5, paragraf 1 (hlm. 64) | "…perubahan kondisi udara dibuat secara terkontrol di ruangan yang sama." | Tambahkan: "Seluruh skenario dilakukan di satu ruangan uji. Karena nilai ppm dihitung relatif terhadap baseline saat perangkat dinyalakan, perangkat dinyalakan pada kondisi ruangan normal dengan ventilasi biasa dan tidak dinyalakan ulang di antara skenario, agar seluruh skenario memakai acuan yang sama." | Spec §1 (1 ruangan) dan §2 (baseline). |
| 3.12.5, paragraf penutup (hlm. 65) | "…sesuai interval pengiriman 30 detik dan tersimpan pada halaman historical data." | "…sesuai interval pengiriman 5–10 detik dan tersimpan pada halaman Riwayat." | Spec §9 (interval). |
| 3.12.6 Evaluasi Pengguna dengan UEQ (baru) | — | "Evaluasi pengguna memakai *User Experience Questionnaire* (UEQ) 26 butir (Laugwitz et al., 2008) versi Bahasa Indonesia (Santoso et al., 2016). Setiap butir berupa pasangan kata berlawanan dengan skala 1–7. Jawaban diubah ke rentang −3 sampai +3, dan nilai butir 3, 4, 5, 9, 10, 12, 17, 18, 19, 21, 23, 24, dan 25 dibalik karena istilah positifnya berada di sisi kiri. Butir dikelompokkan ke dalam enam skala (Tabel 3.11). Untuk setiap skala dihitung rata-rata, simpangan baku, dan Cronbach's alpha, lalu rata-rata skala dibandingkan dengan benchmark UEQ (Schrepp et al., 2017) untuk memperoleh kategori Excellent, Good, Above Average, Below Average, atau Bad (Tabel 3.12). Data mentah dapat diekspor ke format yang kompatibel dengan *UEQ Data Analysis Tool*. Jumlah dan kriteria responden: (cek)." | Spec §7 dan §9 (3.12 + UEQ); `config/ueq.php`; perintah `php artisan ueq:report`. |
| Tabel 3.11 (baru) Pemetaan Butir UEQ ke Skala | — | Attractiveness (daya tarik): 1, 12, 14, 16, 24, 25<br>Perspicuity (kejelasan): 2, 4, 13, 21<br>Efficiency (efisiensi): 9, 20, 22, 23<br>Dependability (ketepatan): 8, 11, 17, 19<br>Stimulation (stimulasi): 5, 6, 7, 18<br>Novelty (kebaruan): 3, 10, 15, 26 | Spec §7; `config/ueq.php` (`scales`). |
| Tabel 3.12 (baru) Benchmark UEQ | — | Batas bawah rata-rata untuk Excellent / Good / Above Average / Below Average (di bawahnya: Bad):<br>Attractiveness 1,75 / 1,52 / 1,17 / 0,70<br>Perspicuity 1,90 / 1,56 / 1,08 / 0,64<br>Efficiency 1,78 / 1,47 / 0,98 / 0,54<br>Dependability 1,65 / 1,48 / 1,14 / 0,78<br>Stimulation 1,55 / 1,31 / 0,99 / 0,50<br>Novelty 1,40 / 1,05 / 0,71 / 0,30<br>Seluruh angka: (cek) terhadap UEQ Handbook sebelum dicantumkan. | Spec §7: nilai benchmark wajib dicocokkan dengan UEQ Handbook; `config/ueq.php` (`benchmark`). |
| 3.12.7 Kesimpulan Pengujian, paragraf 1 (hlm. 65) | "…yaitu dashboard, sensor charts, historical data, rekomendasi tindakan, dan IoT system." | "…yaitu ketujuh halaman aplikasi: Beranda, Detail Ruangan, Riwayat, Rekomendasi Tindakan, Status Perangkat, Panduan, dan Kuesioner UEQ." | Spec §6. |
| 3.12.7, paragraf 2 (hlm. 65) | "…memastikan bahwa nilai pembacaan sensor dapat diolah menjadi nilai ppm per parameter gas dan diklasifikasikan…" | "…memastikan bahwa nilai ppm per parameter gas yang diterima dari perangkat divalidasi dan diklasifikasikan…" | Aplikasi tidak mengonversi pembacaan menjadi ppm; konversi dilakukan perangkat (3.1: "Sistem menerima nilai ppm apa adanya tanpa melakukan konversi"; Spec §1). |
| 3.12.7, paragraf baru sebelum paragraf penutup | — | "Evaluasi pengguna dengan UEQ dilakukan untuk mengetahui pengalaman pengguna pada enam skala dan membandingkannya dengan benchmark UEQ." | Spec §7. |

## Daftar Pustaka (perbaikan entri yang ada)

| Lokasi | Tertulis sekarang | Usulan revisi | Alasan |
|---|---|---|---|
| Entri Atzori (hlm. 67) | "Atzori, L., Iera, A., & Morabito, G. (2025). The Internet of Things : A survey. Computer Networks, 54(15), 2787–2805. https://doi.org/10.1016/j.comnet.2010.05.010" | "Atzori, L., Iera, A., & Morabito, G. (2010). The Internet of Things: A survey. *Computer Networks*, 54(15), 2787–2805. https://doi.org/10.1016/j.comnet.2010.05.010" | Spec §9 (tahun Atzori 2010). |
| Entri Solihat (hlm. 69) | "Solihat, S. M., Salsabila, S., & Susilawati, S. (2026). … Jurnal Informatika Dan Teknik Elektro Terapan, 14(1). https://doi.org/10.23960/jitet.v14i1.5568" | Tambahkan nomor halaman: "…*Jurnal Informatika dan Teknik Elektro Terapan*, 14(1), 1592–1599. https://doi.org/10.23960/jitet.v14i1.5568" | Halaman artikel 1592–1599 (teks paper; `config/air_quality.php`). Sitasi dalam teks diseragamkan menjadi 2026 (Spec §9). |
| Entri Makridakis et al. (1999) (hlm. 68) | "Forecasting: Methods and Applications" | Hapus. | Spec §9: referensi peramalan tidak dipakai. Tidak ada sitasi dalam teks. |
| Entri scikit-learn (2026) MinMaxScaler (hlm. 69) | "scikit-learn. (2026). MinMaxScaler…" | Hapus. | Spec §9; tidak ada sitasi dan tidak ada normalisasi data di aplikasi. |
| Entri U.S. EPA (2025a) Exposure Calculations, EPA ExpoBox (hlm. 70) | "U.S. Environmental Protection Agency. (2025a). Exposure Calculations. EPA ExpoBox…" | Hapus. Ubah "(2025b)" pada entri EPA lain menjadi tanpa huruf, atau hapus juga (lihat baris berikutnya). | Spec §9; fitur durasi paparan tidak ada. |
| Entri Turban et al. (2007) (hlm. 70) | "Decision Support and Business Intelligence Systems (7th ed.)" | Hapus. | Spec §9; tidak ada sitasi dalam teks. |
| Entri Power (2002) (hlm. 69) | "Decision Support Systems: Concepts and Resources for Managers." | Hapus. | Spec §9; tidak ada sitasi dalam teks. |
| Entri lain tanpa sitasi dalam teks | Méndez et al. (2023); Pant et al. (2023); Huda et al. (2025); Nurrohman et al. (2024); Hartiala et al. (2026); AirNow.gov (2026); U.S. EPA (2025b); World Health Organization (2024) | Periksa ulang. Hapus bila memang tidak dikutip, atau tambahkan sitasinya di teks. Méndez et al. dan Pant et al. membahas peramalan sehingga sebaiknya dihapus. | Pencarian pada teks proposal tidak menemukan sitasi entri-entri ini. Spec §1 (tanpa prediksi). |

---

## Checklist gambar dan tabel yang harus dibuat ulang

- [ ] **Gambar 3.1** Flowchart Sistem Monitoring Kualitas Udara: judul tanpa "Prediksi", dengan cabang tolak 422 dan catat penolakan serta langkah penyebab utama.
- [ ] **Gambar 3.2** Use Case Diagram: 2 aktor (Pengguna, Perangkat IoT), tanpa admin, dengan use case untuk 7 halaman.
- [ ] **Gambar 3.3** Wireframe Halaman Beranda: tanpa Masuk Admin, kartu banyak ruangan, dan Indeks KU.
- [ ] **Gambar 3.4** Wireframe Halaman Detail Ruangan (Grafik Sensor): pemilih gas, rentang 1 jam/24 jam/7 hari, garis ambang, 10 pembacaan terakhir; tanpa statistik dan proporsi waktu.
- [ ] **Gambar 3.5** Wireframe Halaman Riwayat: filter tanggal dan kategori, pagination, ekspor CSV; tanpa Indeks KU.
- [ ] **Gambar 3.6** Wireframe Halaman Panduan: menggantikan "Exposure Duration dan Health Advisory".
- [ ] **Gambar 3.7** Wireframe Halaman Rekomendasi Tindakan: menggantikan "Exposure Duration dan Health Advisory".
- [ ] **Gambar 3.8** Wireframe Halaman Status Perangkat: status ≤ 30 detik dan tabel 10 penolakan terakhir; samakan nomor dengan Daftar Gambar.
- [ ] **Gambar 3.9** Wireframe Halaman Kuesioner UEQ (baru).
- [ ] **Tabel 2.1**: kepala kolom kedua "Tahun" menjadi "Peneliti".
- [ ] **Tabel 2.2**: tambahkan keterangan sumber Solihat et al. (2026).
- [ ] **Tabel 3.1** dan **Tabel 3.2**: baris lokasi uji, timestamp perangkat, device_id terdaftar, dan data UEQ.
- [ ] **Tabel 3.3** (Kategori Kualitas Udara Ruangan, hlm. 54): hapus atau beri nomor baru agar tidak ganda.
- [ ] **Tabel 3.4** Teknologi: rincian stack backend dan frontend.
- [ ] **Tabel 3.5** Rencana Pengujian: 7 halaman, satu ruangan uji, dan baris UEQ.
- [ ] **Tabel 3.6** Pengujian Fungsional: nama halaman baru, baris Status Perangkat, baris Panduan dan Kuesioner UEQ.
- [ ] **Tabel 3.7** Pengujian Validasi Data: hasil "ditolak (HTTP 422) dan dicatat pada log penolakan".
- [ ] **Tabel 3.9** Pengujian Rekomendasi: teks persis Tabel 3.3 dan kolom Tingkat.
- [ ] **Tabel 3.11** Pemetaan Butir UEQ ke Skala (baru).
- [ ] **Tabel 3.12** Benchmark UEQ (baru; angka dicek terhadap UEQ Handbook).
- [ ] **Daftar Isi**, **Daftar Gambar**, dan **Daftar Tabel** diperbarui setelah semua perubahan di atas.

## Daftar pustaka yang perlu ditambahkan

1. Solihat, S. M., Salsabila, S., & Susilawati, S. (2026). Sistem pemantauan kualitas udara berbasis ESP32 menggunakan sensor gas MQ-135 dan MQ-2. *JITET (Jurnal Informatika dan Teknik Elektro Terapan)*, 14(1), 1592–1599. https://doi.org/10.23960/jitet.v14i1.5568 *(entri sudah ada; lengkapi halaman.)*
2. U.S. Environmental Protection Agency. ((cek) tahun). *Improving Indoor Air Quality*. https://www.epa.gov/indoor-air-quality-iaq/improving-indoor-air-quality *(dikutip di 3.10.1 sebagai "U.S. Environmental Protection Agency (2026)" tetapi belum ada di daftar pustaka; tanggal akses: (cek).)*
3. U.S. Environmental Protection Agency. ((cek) tahun). *Technical Assistance Document for the Reporting of Daily Air Quality – the Air Quality Index (AQI)* (EPA-403/B-26-003). https://document.airnow.gov/technical-assistance-document-for-the-reporting-of-daily-air-quailty.pdf *(judul lengkap dan tahun: cek pada dokumen.)*
4. Laugwitz, B., Held, T., & Schrepp, M. (2008). Construction and evaluation of a user experience questionnaire. *(nama prosiding/buku, halaman, dan DOI: cek.)*
5. Schrepp, M., Hinderks, A., & Thomaschewski, J. (2017). Construction of a benchmark for the User Experience Questionnaire (UEQ). *(jurnal, volume, halaman, dan DOI: cek.)*
6. Santoso, H. B., Schrepp, M., Isal, R. Y. K., Utomo, A. Y., & Priyogi, B. (2016). Measuring user experience of the student-centered e-learning environment. *(jurnal, volume, halaman, dan DOI: cek.)* Sumber butir UEQ versi Bahasa Indonesia.
7. Schrepp, M. ((cek) tahun). *User Experience Questionnaire Handbook*. ueq-online.org *(versi dan tahun: cek.)* Diperlukan untuk memverifikasi angka benchmark Tabel 3.12.

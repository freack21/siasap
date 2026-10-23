# Spec: Penyelarasan Aplikasi SIASAP dengan Naskah Skripsi

Tanggal: 2026-10-23 · Branch integrasi: `v2` · Status: menunggu review

## 1. Latar dan tujuan

Naskah proposal terbaru (Wan Salma Nurkhairin Fitri, 2207125057) menjadi acuan utama. Aplikasi di `v2` saat ini lebih luas dan memakai metode berbeda dari naskah. Spec ini menyelaraskan aplikasi dengan naskah sehingga setiap fitur, data uji, dan hasil yang diharapkan di Bab III dapat dibuktikan langsung pada aplikasi.

### Ruang lingkup yang disepakati

| Keputusan | Isi |
|---|---|
| Fungsi sistem | Hanya monitoring data real-time dari perangkat IoT (interval 5–10 detik); tanpa analisis tren maupun prediksi. Grafik/riwayat hanya ditampilkan, tidak dianalisis. |
| Fokus | Menyajikan data kualitas udara agar mudah dipahami + saran tindakan preventif sesuai kondisi saat itu. |
| Validitas sensor | Kalibrasi dan akurasi ppm adalah tanggung jawab penelitian IoT (rekan satu proyek). Aplikasi hanya memvalidasi format data. |
| Ruang uji | 1 ruangan. |
| Metode | Waterfall. Testing: black-box (fungsional) + evaluasi pengguna dengan UEQ 26 butir. |
| Ambang batas | Satu rujukan untuk semua gas: Solihat et al. (2026), Tabel 2.2 naskah. |
| Rekomendasi | 4 aturan if-then per kategori keseluruhan, teks sesuai Tabel 3.3 naskah. |
| Aktor | Pengguna dan Perangkat IoT saja. Tidak ada login/admin. |
| Dihapus | Modul QoS/Pengujian Jaringan, sesi pengujian, sub-indeks 0–500/Indeks KU, rekomendasi per gas (AQI CO), SUS, seluruh panel admin, RBAC. Tidak disebut di naskah. |
| API | Format naskah 3.9.1; format lama tetap diterima selama transisi firmware. |

### Di luar lingkup
- Perubahan rumus konversi ppm di firmware (milik penelitian IoT), kecuali penyesuaian format payload.
- Penulisan ulang naskah; spec ini hanya menghasilkan **daftar revisi naskah** (bagian 9).

## 2. Klasifikasi kualitas udara (threshold-based)

Sumber tunggal: Solihat, S. M., Salsabila, S., & Susilawati, S. (2026). *Sistem Pemantauan Kualitas Udara Berbasis ESP32 Menggunakan Sensor Gas MQ-135 dan MQ-2*. JITET, 14(1). doi:10.23960/jitet.v14i1.5568.

Ambang (ppm), sesuai Tabel 2.2 naskah:

| Kategori | CO₂ | NH₃ | CO | H₂ |
|---|---|---|---|---|
| Baik | < 50 | < 40 | < 20 | < 15 |
| Sedang | < 100 | < 80 | < 50 | < 40 |
| Tidak Sehat | < 150 | < 120 | < 80 | < 60 |
| Berbahaya | ≥ 150 | ≥ 120 | ≥ 80 | ≥ 60 |

Aturan:
- Kategori per gas: nilai dibandingkan berurutan; batas bersifat eksklusif (`< batas`). Contoh: CO₂ 50 → Sedang; CO₂ 150 → Berbahaya (sesuai Tabel 3.8 baris 6–7).
- Kategori keseluruhan `KU = max(K_CO2, K_NH3, K_CO, K_H2)` dengan urutan Baik < Sedang < Tidak Sehat < Berbahaya.
- **Penyebab utama** = semua gas yang kategorinya sama dengan KU (bisa lebih dari satu); bila KU = Baik, tidak ditampilkan.
- Ambang disimpan di `config/air_quality.php` dengan satu entri `source` (sitasi di atas). Tidak ada sumber lain untuk ambang.

**Risiko yang harus diketahui (bukan bagian implementasi):** firmware saat ini menghitung CO₂ ≈ 400 ppm pada udara bersih. Dengan ambang CO₂ < 50 ppm, ruangan normal akan selalu Berbahaya. Rumus konversi ppm di firmware harus disamakan dengan metode Solihat et al. oleh rekan IoT. Teks lengkap paper belum dapat diverifikasi (situs jurnal menolak akses otomatis); angka ambang diambil dari Tabel 2.2 naskah dan perlu dicocokkan dengan PDF paper.

## 3. Rekomendasi tindakan preventif (rule-based)

Satu masukan: kategori keseluruhan terkini. Satu keluaran per kategori, teks persis Tabel 3.3 naskah:

| Kategori | Tingkat | Rekomendasi |
|---|---|---|
| Baik | Normal | Kondisi udara relatif baik. Pertahankan ventilasi ruangan. |
| Sedang | Waspada | Tingkatkan sirkulasi udara, misalnya dengan membuka jendela atau pintu. |
| Tidak Sehat | Perlu Tindakan | Tingkatkan ventilasi, periksa dan kurangi sumber polutan seperti asap atau gas, serta kurangi aktivitas di dalam ruangan. |
| Berbahaya | Segera Bertindak | Segera tingkatkan ventilasi, hentikan sumber polutan, dan pertimbangkan meninggalkan ruangan sementara sampai kondisi udara membaik. |

Dasar (ditampilkan di halaman Rekomendasi dan dijelaskan di naskah 3.10.1):
1. **Isi tindakan** — tiga strategi U.S. EPA *Improving Indoor Air Quality*: kendali sumber (paling efektif), peningkatan ventilasi, pembersihan udara. Setiap baris memakai satu atau lebih strategi tersebut.
2. **Gradasi urgensi** — mengikuti prinsip penyampaian AQI EPA (EPA-403/B-26-003, Tabel 5): anjuran makin tegas seiring kategori memburuk. Yang diadopsi hanya prinsip bertingkatnya, bukan angka AQI.
3. Pemetaan adalah rancangan peneliti; bersifat informatif, bukan diagnosis medis.

Teks dan sumber disimpan di `config/recommendations.php`; logika di `App\Support\Recommendation::forCategory(string): array{category, level, text, strategies}`.

## 4. Penerimaan data (API)

`POST /api/telemetry`, format utama (naskah 3.9.1):

```json
{ "device_id": "AIR-001", "timestamp": "2026-05-01 10:00:00",
  "co2_ppm": 70, "nh3_ppm": 30, "co_ppm": 55, "h2_ppm": 10 }
```

- Validasi (Tabel 3.7): semua field wajib; `*_ppm` numerik ≥ 0; `device_id` harus terdaftar (tabel `nodes.code`); `timestamp` format `Y-m-d H:i:s`.
- Format lama (`node_id`, `co2`, `nh3`, `co`, `h2`) diterima sementara dan dipetakan ke format baru; field QoS lama (`rssi`, `seq_number`, `hop_count`, `topology_type`) diabaikan.
- Data ditolak → respons 422 **dan** dicatat ke tabel `telemetry_rejections` (`device_id` nullable, `payload` json, `errors` json, `created_at`) + log channel `telemetry`.
- Data valid → disimpan bersama kategori per gas, KU, penyebab utama, dan level rekomendasi.
- `TELEMETRY_API_KEY` opsional tetap berlaku.

## 5. Model data

`sensor_logs` (migration baru):
- Tambah: `recorded_at` (timestamp perangkat), `co2_category`, `nh3_category`, `co_category`, `h2_category`, `overall_category`, `dominant_gases` (string, dipisah koma), `recommendation_level`.
- Hapus: `ku_value`, `ku_status`, `ku_dominant`, `rssi`, `seq_number`, `hop_count`, `topology_type`, `testing_session_id`.
- Tabel dihapus: `testing_sessions`, `survey_responses` (SUS), `roles`, `permissions`, `role_user`, `permission_role`.
- Tabel baru: `telemetry_rejections`, `ueq_responses`.
- `rooms`, `nodes` tetap; diisi lewat seeder (1 ruangan, 1 perangkat dengan `code` = `device_id` firmware, default `AIR-001`).
- Data lama dikosongkan (`telemetry:clear` diperbarui), karena kategori lama tidak kompatibel.

## 6. Halaman (semua publik, navigasi atas)

| # | Halaman | Rute | Isi |
|---|---|---|---|
| 1 | Beranda | `/` | Kategori keseluruhan, penyebab utama, kartu 4 gas (ppm + badge kategori), kartu rekomendasi, waktu pembaruan. Jika hanya 1 ruangan, langsung menampilkan ruangan itu. |
| 2 | Detail Ruangan / Grafik Sensor | `/ruangan/{room}` | Pemilih gas (CO₂/NH₃/CO/H₂), rentang 1 jam/24 jam/7 hari, garis ambang kategori pada grafik, 10 pembacaan terakhir. |
| 3 | Riwayat | `/riwayat` | Filter tanggal & kategori, pagination, ekspor CSV (kolom: waktu, ruangan, 4 ppm, 4 kategori, KU, penyebab, rekomendasi). |
| 4 | Rekomendasi Tindakan | `/rekomendasi` | Kategori & rekomendasi saat ini; tabel 4 aturan; dasar (EPA IAQ + gradasi AQI); disclaimer. |
| 5 | Status Perangkat (IoT System) | `/perangkat` | device_id, gas yang dikirim, waktu data terakhir, status koneksi (terhubung bila data ≤ 3× interval), alur data, 10 penolakan terakhir. |
| 6 | Panduan | `/panduan` | Cara membaca kategori, tabel ambang (Solihat et al.), keterangan 4 gas, FAQ, batasan. |
| 7 | Kuesioner UEQ | `/kuesioner` | 26 butir UEQ versi Indonesia, skala 7 titik, data responden opsional (nama). |

Dihapus: `/login`, `/logout`, seluruh `/admin/*`, layout admin, `config/navigation.php`.

Interval polling halaman disesuaikan dengan interval kirim (5 detik).

## 7. Evaluasi pengguna: UEQ

- Instrumen: UEQ 26 butir (Laugwitz, Held & Schrepp, 2008), versi Indonesia resmi dari ueq-online.org (Santoso et al.). Teks butir **wajib** diambil dari berkas resmi; bila tidak dapat diunduh, implementasi memakai placeholder dan menandai berkas yang harus diisi.
- Skala 1–7 → nilai −3..+3; butir terbalik: 3, 4, 5, 9, 10, 12, 17, 18, 19, 21, 23, 24, 25.
- Skala dan butir: Attractiveness 1, 12, 14, 16, 24, 25 · Perspicuity 2, 4, 13, 21 · Efficiency 9, 20, 22, 23 · Dependability 8, 11, 17, 19 · Stimulation 5, 6, 7, 18 · Novelty 3, 10, 15, 26.
- Benchmark Schrepp, Hinderks & Thomaschewski (2017) disimpan di config (Excellent/Good/Above Average/Below Average/Bad per skala); nilai wajib dicocokkan dengan UEQ Handbook sebelum dipakai di naskah.
- `php artisan ueq:report`: jumlah responden, rata-rata & simpangan baku per skala, kategori benchmark, Cronbach's alpha per skala, ekspor CSV kompatibel *UEQ Data Analysis Tool* (kolom butir 1–26 bernilai 1–7).
- Rate limit pengisian 3×/jam per IP tetap.

## 8. Pengujian

- Unit: klasifikasi per gas termasuk nilai tepat di batas; KU; penyebab utama; rekomendasi 4 kategori; skoring UEQ (pembalikan butir, rata-rata skala, benchmark).
- Feature: data uji Tabel 3.7 (5 kasus validasi + pencatatan penolakan), Tabel 3.8 (7 kombinasi), Tabel 3.9 (4 rekomendasi), Tabel 3.6 (fungsional tiap halaman), UEQ submit & laporan.
- `docs/pengujian-black-box.md` ditulis ulang mengikuti struktur Bab 3.12 (Tabel 3.5–3.10).
- Skenario 4 kondisi ruangan (Tabel 3.10) disiapkan sebagai lembar uji manual.

## 9. Daftar revisi naskah (dokumen terpisah)

`docs/revisi-naskah.md`, per subbab: judul & prakata (hapus "prediksi"/"deteksi dini"), penelitian terdahulu (hapus kritik "belum ada prediksi"), interval 30 → 5–10 detik, 3.1 metode evaluasi UEQ, 3.4–3.5 tahap Testing, use case (2 aktor), 3.8 (selaraskan dengan 7 halaman), 3.9.1 contoh payload, 3.12 + UEQ, penomoran tabel/subbab ganda, Gambar 3.1/3.6/3.7, referensi yang tidak dipakai (Makridakis, MinMaxScaler, ExpoBox, Turban, Power), tahun Solihat & Atzori, catatan risiko ambang CO₂.

## 10. Urutan kerja (branch dari `v2`)

1. `refactor/hapus-admin-qos` — hapus admin, RBAC, QoS, sesi, SUS, layout admin.
2. `feat/klasifikasi-ambang-solihat` — config ambang, klasifikasi, rekomendasi 4 aturan, migration `sensor_logs`, API format baru + pencatatan penolakan, seeder 1 ruangan/1 perangkat, firmware payload.
3. `feat/halaman-sesuai-naskah` — 7 halaman publik (bergantung pada 2).
4. `feat/kuesioner-ueq` — UEQ + `ueq:report` (independen dari 2–3).
5. `docs/revisi-naskah-dan-pengujian` — black-box, daftar revisi naskah, wireframe.

Setiap branch di-merge `--no-ff` ke `v2` setelah `php artisan test`, Pint, dan `npm run build` lolos.

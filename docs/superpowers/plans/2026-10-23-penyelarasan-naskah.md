# Penyelarasan Aplikasi dengan Naskah — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menyelaraskan SIASAP dengan naskah: monitoring real-time publik tanpa login, klasifikasi ambang Solihat et al. (2026), 4 aturan rekomendasi, API format naskah, evaluasi UEQ, firmware dengan rumus konversi Solihat.

**Architecture:** Logika domain di `App\Support` (klasifikasi, rekomendasi, UEQ) bersifat murni dan diuji unit; Livewire hanya menyajikan. Pekerjaan dibagi ke branch per task dari `v2` dan di-merge `--no-ff` sesuai gelombang (wave) di bawah.

**Tech Stack:** Laravel 13, Livewire 4, Tailwind v4, Chart.js, Tabler icons, PHPUnit 12, Arduino (ESP32 core ≥ 3).

**Spec:** `docs/superpowers/specs/2026-10-23-penyelarasan-naskah-design.md`

## Global Constraints

- Kategori dan urutannya: `Baik` < `Sedang` < `Tidak Sehat` < `Berbahaya`.
- Ambang (ppm, eksklusif `<`): CO₂ 50/100/150 · NH₃ 40/80/120 · CO 20/50/80 · H₂ 15/40/60; di atas batas ketiga = Berbahaya.
- Satu sumber ambang: Solihat, S. M., Salsabila, S., & Susilawati, S. (2026), JITET 14(1), doi:10.23960/jitet.v14i1.5568.
- Teks rekomendasi persis Tabel 3.3 naskah (lihat Task 2).
- Tidak ada login, admin, RBAC, QoS, sesi pengujian, sub-indeks/Indeks KU, SUS di hasil akhir.
- Payload API utama: `device_id`, `timestamp` (`Y-m-d H:i:s`), `co2_ppm`, `nh3_ppm`, `co_ppm`, `h2_ppm`.
- Interval kirim 5 detik; perangkat "terhubung" bila data terakhir ≤ 30 detik.
- UI berbahasa Indonesia; gunakan design system yang ada (`resources/css/app.css`, `components/ui/*`, `App\Support\AirQuality`).
- Setiap branch: `php artisan test --compact`, `vendor/bin/pint --dirty --format agent`, `npm run build` lolos sebelum merge.

## Review Focus

1. **Nilai tepat di ambang** (mis. CO₂ = 50, NH₃ = 120) — harus masuk kategori di atasnya (Task 2, test batas).
2. **Firmware lama** (payload `node_id`, `co2`, … tanpa `timestamp`) — tetap diterima, `recorded_at` diisi waktu server (Task 5).
3. **`device_id` tidak terdaftar / payload rusak** — ditolak 422 dan tercatat di `telemetry_rejections` (Task 5).
4. **Jam perangkat salah** — urutan dan grafik memakai `created_at` server, bukan `recorded_at` (Task 5, Task 6).
5. **Belum ada data / perangkat terputus** — halaman tampil kosong yang jelas, rekomendasi tidak ditampilkan, status "Terputus" (Task 6, Task 7).

## Waves

| Wave | Task (branch) | Bergantung pada |
|---|---|---|
| 1 | T1 `refactor/hapus-admin-qos` · T2 `feat/klasifikasi-rekomendasi-inti` · T3 `feat/firmware-solihat` · T4 `feat/kuesioner-ueq` | — |
| 2 | T5 `feat/data-api-sesuai-naskah` | T1, T2 |
| 3 | T6 `feat/halaman-monitoring` · T7 `feat/halaman-rekomendasi-perangkat-panduan` | T5 (T7 merge setelah T6) |
| 4 | T8 `docs/pengujian-dan-revisi-naskah` | semua |

Merge order dalam wave 1: T1 → T2 → T3 → T4 (T4 menyelesaikan konflik `tests/Feature/SurveyTest.php` dengan versinya sendiri).

---

### Task 1: Hapus admin, RBAC, dan modul QoS

**Files:**
- Delete: `app/Livewire/{AdminOverview,TelemetryDashboard,RoomManagement,QosAnalytics}.php`, `app/Livewire/Auth/Login.php`, views terkait di `resources/views/livewire/` (`admin-overview`, `telemetry-dashboard`, `room-management`, `qos-analytics`, `auth/login`), `resources/views/layouts/admin.blade.php`, `resources/views/components/layouts/auth.blade.php`, `config/navigation.php`, `app/Support/QosCalculator.php`, `app/Models/{Role,Permission}.php`, `database/seeders/SetupRbacSeeder.php`, tests `{AdminOverviewTest,AuthTest,NavigationTest,QosAnalyticsTest,QosCalculatorTest,RoomManagementTest,SensorDataTest}.php`
- Delete: `app/Livewire/SurveyResults.php` + view, dan test admin di `tests/Feature/SurveyTest.php` (form publik tetap)
- Create: `database/migrations/2026_10_23_000001_drop_rbac_tables.php` (drop `permission_role`, `role_user`, `permissions`, `roles`; `down()` membuat ulang sesuai `2026_10_05_000003`)
- Modify: `routes/web.php` (hapus login, logout, grup `admin`), `resources/views/layouts/public.blade.php` (hapus tombol Masuk Admin/Panel Admin), `app/Models/User.php` (hapus `roles()`, `hasRole()`, `hasPermission()`), `database/seeders/DatabaseSeeder.php` (kosongkan pemanggilan RBAC), `tests/Feature/ExampleTest.php`
- Tidak menyentuh: `TestingSession`, API controller, `SensorLog` (Task 5).

**Interfaces:** Produces: rute publik `home`, `rooms.show`, `history`, `guide`, `survey` tetap ada; tidak ada rute `login`/`admin.*`.

- [ ] Tulis test `tests/Feature/PublicAccessTest.php`: `test_admin_and_login_routes_are_gone` (GET `/login`, `/admin`, `/admin/pengujian-jaringan`, `/admin/kuesioner` → 404); `test_public_pages_have_no_admin_link` (GET `/` tidak mengandung "Masuk Admin" maupun "Panel Admin").
- [ ] Jalankan, pastikan gagal.
- [ ] Hapus/ubah file di atas; `grep -rn "hasPermission\|admin\.\|QosCalculator\|SurveyResults\|route('login')" app resources routes tests` harus kosong.
- [ ] `php artisan test --compact` lolos; Pint; build.
- [ ] Commit: `refactor: remove admin panel, RBAC and network QoS module`.

### Task 2: Klasifikasi ambang dan rekomendasi 4 aturan (inti, aditif)

**Files:**
- Modify: `config/air_quality.php` — **tambahkan** kunci baru tanpa menghapus `breakpoints` lama (dihapus di Task 5):
  - `source` => `['label' => 'Solihat, S. M., Salsabila, S., & Susilawati, S. (2026). Sistem Pemantauan Kualitas Udara Berbasis ESP32 Menggunakan Sensor Gas MQ-135 dan MQ-2. JITET, 14(1), 1592–1599.', 'doi' => '10.23960/jitet.v14i1.5568']`
  - `category_order` => `['Baik', 'Sedang', 'Tidak Sehat', 'Berbahaya']`
  - `thresholds` => `['co2' => [50, 100, 150], 'nh3' => [40, 80, 120], 'co' => [20, 50, 80], 'h2' => [15, 40, 60]]`
- Create: `config/recommendations.php` — `rules` per kategori (`level`, `text`, `strategies`) dan `sources` (`epa_iaq` → https://www.epa.gov/indoor-air-quality-iaq/improving-indoor-air-quality, `epa_aqi` → https://document.airnow.gov/technical-assistance-document-for-the-reporting-of-daily-air-quailty.pdf) + `basis` (2 kalimat dasar, spec §3).
- Create: `app/Support/AirQualityClassifier.php`, `app/Support/Recommendation.php`
- Test: `tests/Unit/AirQualityClassifierTest.php`, `tests/Unit/RecommendationTest.php`

**Interfaces:**
- Produces:
  - `AirQualityClassifier::categoryFor(string $gas, float $ppm): string`
  - `AirQualityClassifier::classify(float $co2, float $nh3, float $co, float $h2): array{categories: array{co2: string, nh3: string, co: string, h2: string}, overall: string, dominant: list<string>}` — `dominant` = gas dengan kategori = `overall`, urutan `co2, nh3, co, h2`; kosong bila `overall` = Baik.
  - `AirQualityClassifier::rank(string $category): int` (0–3)
  - `Recommendation::forCategory(string $category): array{category: string, level: string, text: string, strategies: list<string>}`
  - `Recommendation::all(): list<array{category, level, text, strategies}>` urut Baik→Berbahaya
  - `Recommendation::sources(): list<array{label: string, url: string}>`

- [ ] Tulis `AirQualityClassifierTest` dengan data provider **Tabel 3.8** (7 baris, `[co2, nh3, co, h2, overall]`): `[30,20,10,5,'Baik']`, `[70,30,10,5,'Sedang']`, `[70,30,55,10,'Tidak Sehat']`, `[30,30,10,45,'Tidak Sehat']`, `[40,130,10,5,'Berbahaya']`, `[50,20,10,5,'Sedang']`, `[150,20,10,5,'Berbahaya']`; plus provider batas per gas (`49.99→Baik, 50→Sedang, 99.99→Sedang, 100→Tidak Sehat, 149.99→Tidak Sehat, 150→Berbahaya` untuk CO₂ dan pola sama untuk NH₃/CO/H₂ dari ambang Global Constraints); `test_dominant_lists_all_worst_gases` (`[70,30,55,45]` → `['co','h2']`); `test_dominant_empty_when_all_good`; `test_unknown_gas_throws` (`InvalidArgumentException`).
- [ ] Tulis `RecommendationTest` dengan provider **Tabel 3.9**: Baik/Normal → "Kondisi udara relatif baik. Pertahankan ventilasi ruangan."; Sedang/Waspada → "Tingkatkan sirkulasi udara, misalnya dengan membuka jendela atau pintu."; Tidak Sehat/Perlu Tindakan → "Tingkatkan ventilasi, periksa dan kurangi sumber polutan seperti asap atau gas, serta kurangi aktivitas di dalam ruangan."; Berbahaya/Segera Bertindak → "Segera tingkatkan ventilasi, hentikan sumber polutan, dan pertimbangkan meninggalkan ruangan sementara sampai kondisi udara membaik."; `test_all_returns_four_rules_in_order`; `test_every_rule_cites_epa_strategy` (strategies ⊆ `['kendali_sumber','ventilasi','pembersihan_udara']`, tidak kosong); `test_unknown_category_throws`.
- [ ] Jalankan, pastikan gagal.
- [ ] Implementasi; strategi per aturan: Baik `['ventilasi']`, Sedang `['ventilasi']`, Tidak Sehat `['ventilasi','kendali_sumber']`, Berbahaya `['ventilasi','kendali_sumber']`.
- [ ] Test lolos; Pint.
- [ ] Commit: `feat: threshold classification (Solihat et al. 2026) and four-rule recommendations`.

### Task 3: Firmware perangkat tunggal dengan rumus Solihat

**Files:**
- Create: `arduino/siasap_monitor/siasap_monitor.ino`
- Modify: `arduino/README.md` (bagian baru di atas: perangkat untuk aplikasi ini; sketsa star/mesh lama ditandai "arsip penelitian IoT, tidak dipakai aplikasi")

Isi sketsa (ESP32 core ≥ 3, satu perangkat WiFi langsung):
- Konfigurasi: `WIFI_SSID`, `WIFI_PASSWORD`, `SERVER_URL` (`http://<ip>:8000/api/telemetry`), `API_KEY` (opsional), `DEVICE_ID = "AIR-001"`, `INTERVAL_KIRIM = 5000` ms, pin MQ-135 = 33, MQ-2 = 32 (sesuai paper; bisa diubah).
- Pemanasan 30 dtk lalu baseline = rata-rata 50 pembacaan ADC tiap sensor (jeda 100 ms) — sesuai paper.
- Konversi sesuai paper: `ppm = max(0, adc - baseline) * (5.0 / 4096.0) * k`, `k` = 100 (CO₂, MQ-135), 80 (NH₃, MQ-135), 50 (CO, MQ-2), 30 (H₂, MQ-2). Komentar mengutip Solihat et al. (2026) hlm. 1596 dan menjelaskan nilai = indikator relatif terhadap kondisi saat perangkat menyala.
- Waktu: NTP (`configTime(7 * 3600, 0, "pool.ntp.org", "time.google.com")`), format `%Y-%m-%d %H:%M:%S`; tidak mengirim sebelum waktu tersinkron (log Serial).
- Payload JSON: `{"device_id","timestamp","co2_ppm","nh3_ppm","co_ppm","h2_ppm"}` (2 desimal); header `X-Api-Key` bila `API_KEY` tidak kosong; log kode respons dan isi galat 422.
- Bagian awal berkas: catatan bahwa penyesuaian rumus/kalibrasi adalah tanggung jawab penelitian IoT dan perlu ditinjau rekan IoT.

- [ ] Tulis sketsa dan README.
- [ ] Kompilasi dengan stub header (seperti verifikasi firmware sebelumnya) atau `arduino-cli` bila tersedia; catat hasilnya.
- [ ] Commit: `feat(firmware): single-device sketch with Solihat conversion and thesis payload`.

### Task 4: Kuesioner UEQ menggantikan SUS

**Files:**
- Delete: `app/Support/Sus.php`, `app/Models/SurveyResponse.php`, `database/factories/SurveyResponseFactory.php`, `tests/Unit/SusTest.php`
- Create: `database/migrations/2026_10_23_000002_replace_survey_with_ueq.php` (drop `survey_responses`; create `ueq_responses`: id, `respondent_name` nullable, `answers` json (26 nilai 1–7), `ip_hash` nullable, timestamps)
- Create: `config/ueq.php`, `app/Support/Ueq.php`, `app/Models/UeqResponse.php`, `database/factories/UeqResponseFactory.php`, `app/Console/Commands/UeqReportCommand.php`
- Modify: `app/Livewire/SurveyForm.php` + view (tetap rute `survey`, `/kuesioner`)
- Test: `tests/Unit/UeqTest.php`, `tests/Feature/SurveyTest.php` (ditulis ulang)

`config/ueq.php`:
- `items`: 26 pasangan `[kiri, kanan]` dari **berkas resmi UEQ bahasa Indonesia** (ueq-online.org, Santoso et al.); bila tidak dapat diunduh, isi pasangan bahasa Inggris resmi dan tandai `TODO-ISI-TERJEMAHAN-RESMI` di komentar berkas + laporkan.
- `reversed`: `[3, 4, 5, 9, 10, 12, 17, 18, 19, 21, 23, 24, 25]`
- `scales`: Attractiveness `[1,12,14,16,24,25]`, Perspicuity `[2,4,13,21]`, Efficiency `[9,20,22,23]`, Dependability `[8,11,17,19]`, Stimulation `[5,6,7,18]`, Novelty `[3,10,15,26]`
- `benchmark` (Schrepp, Hinderks & Thomaschewski, 2017; batas bawah Excellent/Good/Above Average/Below Average): Attractiveness `[1.75, 1.52, 1.17, 0.70]`, Perspicuity `[1.90, 1.56, 1.08, 0.64]`, Efficiency `[1.78, 1.47, 0.98, 0.54]`, Dependability `[1.65, 1.48, 1.14, 0.78]`, Stimulation `[1.55, 1.31, 0.99, 0.50]`, Novelty `[1.40, 1.05, 0.71, 0.30]`; komentar: wajib dicocokkan dengan UEQ Handbook sebelum dikutip.
- `references`: Laugwitz, Held & Schrepp (2008); Schrepp, Hinderks & Thomaschewski (2017); Santoso et al. (2016).

**Interfaces:**
- `Ueq::transform(array $answers): array<int, int>` — masukan 26 nilai 1–7 (key 1..26) → −3..+3 dengan pembalikan butir `reversed`.
- `Ueq::scaleMeans(array $answers): array<string, float>`
- `Ueq::summary(iterable $responses): array{count: int, scales: array<string, array{mean: float|null, sd: float|null, alpha: float|null, benchmark: string|null}>}`
- `Ueq::benchmarkCategory(string $scale, float $mean): string` — `Excellent`, `Good`, `Above Average`, `Below Average`, `Bad`.
- Artisan `ueq:report {--csv=storage/app/ueq-export.csv}`: tabel ringkasan + CSV kolom `id,waktu,nama,i1..i26` (nilai mentah 1–7).

- [ ] `UeqTest`: semua jawaban 4 → semua skala 0; jawaban terbaik (7 pada butir normal, 1 pada butir terbalik) → semua skala 3; `test_reversed_item_3_answer_1_becomes_plus_3`; benchmark Perspicuity 1.90 → Excellent, 1.89 → Good, 0.63 → Bad; `test_alpha_null_when_fewer_than_two_responses`; jawaban tidak lengkap → `InvalidArgumentException`.
- [ ] `SurveyTest`: halaman tampil untuk tamu dengan 26 butir; submit lengkap tersimpan; butir kosong/di luar 1–7 → error validasi; rate limit 3×/jam; `ueq:report` menghasilkan ringkasan dan CSV dengan header `id,waktu,nama,i1,...,i26`.
- [ ] Jalankan, pastikan gagal; implementasi; lolos; Pint; build.
- [ ] Commit: `feat: replace SUS with UEQ questionnaire and ueq:report command`.

### Task 5: Model data, API format naskah, seeder 1 ruangan

**Files:**
- Create: `database/migrations/2026_10_23_000003_align_sensor_logs_with_thesis.php` — `sensor_logs`: tambah `recorded_at` (timestamp nullable), `co2_category`, `nh3_category`, `co_category`, `h2_category`, `overall_category` (string), `dominant_gases` (string nullable), `recommendation_level` (string); hapus `ku_value`, `ku_status`, `ku_dominant`, `rssi`, `seq_number`, `hop_count`, `topology_type`, FK + kolom `testing_session_id`; drop tabel `testing_sessions`; data lama dihapus di `up()` (tidak kompatibel).
- Create: `database/migrations/2026_10_23_000004_create_telemetry_rejections_table.php`, `app/Models/TelemetryRejection.php`
- Create: `database/seeders/RoomDeviceSeeder.php` (Room `Ruang Uji`/`ROOM-01`; Node `Perangkat IoT 1`/`AIR-001`, aktif); `DatabaseSeeder` memanggilnya.
- Modify: `app/Models/SensorLog.php` (fillable/casts baru; hapus `computeSubIndex`, `computeKu*`, `GASES`), `app/Http/Controllers/Api/TelemetryApiController.php`, `config/logging.php` (channel `telemetry`, driver daily, `storage/logs/telemetry.log`), `config/air_quality.php` (hapus `breakpoints`, `categories` lama, `fallback_category`, `max_index`), `app/Console/Commands/ClearTransactionDataCommand.php` (kosongkan `sensor_logs` + `telemetry_rejections`; ringkasan master: rooms, nodes), `app/Support/AirQuality.php` (hapus `range` berbasis indeks; tambah `thresholdLabel(string $gas, string $category): string` mis. "50 – < 100 ppm")
- Delete: `app/Models/TestingSession.php`, `tests/Unit/AirQualityIndexTest.php`
- Modify views minimal agar suite hijau (ganti `ku_value`/`ku_status`/`ku_dominant` dengan `overall_category`/`dominant_gases`; hapus angka indeks): `user-dashboard`, `room-detail`, `user-history`, `air-quality-guide`, `components/ui/preventive-actions` (boleh dikosongkan; dihapus di Task 7). Redesain penuh ada di Task 6–7.
- Test: `tests/Feature/TelemetryApiTest.php` (ditulis ulang)

**Interfaces:**
- Consumes: `AirQualityClassifier::classify`, `Recommendation::forCategory` (Task 2).
- Produces: kolom `sensor_logs` di atas; `SensorLog::categories(): array{co2, nh3, co, h2}`; `SensorLog::dominantGases(): list<string>`; `SensorLog::recommendation(): array{category, level, text, strategies}`; `TelemetryRejection` (`device_id`, `payload` array, `errors` array).
- API `POST /api/telemetry` 201: `{status: 'success', data: {id, device_id, recorded_at, categories, overall_category, dominant_gases, recommendation: {level, text}}}`; 422: `{status: 'error', errors}`.

- [ ] Test **Tabel 3.7**: lengkap → 201 dan tersimpan; tanpa `co_ppm` → 422 + 1 baris `telemetry_rejections`; `co2_ppm = "abc"` → 422 + tercatat; `device_id` kosong → 422 + tercatat; `timestamp = "01-05-2026 10:00"` → 422 + tercatat; `device_id` tidak terdaftar → 422 + tercatat.
- [ ] Test **Tabel 3.8** lewat API (provider sama dengan Task 2) → `overall_category` tersimpan sesuai, kategori per gas tersimpan, `recommendation_level` sesuai.
- [ ] Test format lama: `{node_id: <id AIR-001>, co2, nh3, co, h2, rssi, seq_number, hop_count}` → 201; `recorded_at` = waktu server; field QoS diabaikan.
- [ ] Test API key: dikonfigurasi → tanpa header 401 (tidak dicatat sebagai penolakan data); kosong → tidak wajib.
- [ ] Test `recorded_at` dari perangkat disimpan apa adanya sedangkan `created_at` = waktu server.
- [ ] Jalankan, pastikan gagal; implementasi; `php artisan migrate:fresh --seed` lolos; suite hijau; Pint; build.
- [ ] Commit: `feat: thesis payload, per-gas categories and rejection log; single-room seed`.

### Task 6: Halaman monitoring — Beranda, Detail Ruangan, Riwayat

**Files:**
- Modify: `app/Livewire/{UserDashboard,RoomDetail,UserHistory}.php` + view masing-masing; `resources/views/components/ui/ku-badge.blade.php` (tetap menerima `status` kategori)
- Test: `tests/Feature/{PublicDashboardTest,PublicHistoryGuideTest(bagian Riwayat)}.php` (ditulis ulang bagian terkait)

**Interfaces:** Consumes: `SensorLog::categories()`, `dominantGases()`, `recommendation()`, `AirQuality::gases()`, `AirQuality::category()`, `AirQuality::thresholdLabel()`.

- Beranda: bila tepat 1 ruangan aktif → konten fokus ruangan itu (judul ruangan, kategori keseluruhan + ikon, penyebab utama, 4 kartu gas: ppm 2 desimal + badge kategori gas, kartu rekomendasi `level` + `text` + tautan ke `/rekomendasi`, waktu pembaruan, status perangkat). >1 ruangan → kartu per ruangan dengan isi yang sama secara ringkas. Tanpa data → empty state "Menunggu data dari perangkat IoT", tanpa rekomendasi.
- Detail Ruangan: pemilih gas (`gas` = `co2|nh3|co|h2`, default `co2`, `#[Url]`) + rentang (`1h|24h|7d`); grafik satu gas terpilih dengan garis ambang Baik/Sedang/Tidak Sehat (dataset putus-putus) dari config; label sumbu "ppm (indikator relatif)"; ringkasan rentang (rata-rata, min, maks ppm gas terpilih, proporsi kategori keseluruhan); 10 pembacaan terakhir (waktu, kategori keseluruhan, 4 ppm). Urut berdasarkan `created_at`.
- Riwayat: filter tanggal dari/sampai + kategori, pagination 20, CSV kolom `waktu,ruangan,co2_ppm,nh3_ppm,co_ppm,h2_ppm,kategori_co2,kategori_nh3,kategori_co,kategori_h2,kategori_keseluruhan,penyebab_utama,rekomendasi`.
- Polling `wire:poll.5s`.

- [ ] Test: Beranda 1 ruangan menampilkan nama ruangan, 4 badge kategori gas, teks rekomendasi Tabel 3.3 sesuai data; Beranda tanpa data menampilkan empty state dan tidak menampilkan teks rekomendasi; Detail `?gas=co` menghasilkan `data-chart` berisi label "CO" dan 3 dataset ambang; `?gas=xyz` jatuh ke `co2`; Riwayat filter kategori & tanggal; CSV header persis di atas.
- [ ] Jalankan, gagal; implementasi; lolos; Pint; build; screenshot cek visual.
- [ ] Commit: `feat(ui): monitoring pages aligned with thesis (categories, per-gas chart)`.

### Task 7: Halaman Rekomendasi, Status Perangkat, Panduan, navigasi

**Files:**
- Create: `app/Livewire/RecommendationPage.php` (`/rekomendasi`, name `recommendations`), `app/Livewire/DeviceStatus.php` (`/perangkat`, name `devices`) + views
- Modify: `routes/web.php`, `resources/views/layouts/public.blade.php` (menu: Beranda, Riwayat, Rekomendasi, Perangkat, Panduan, Kuesioner), `app/Livewire/AirQualityGuide.php` + view
- Delete (setelah merge `origin/v2` berisi Task 6): `app/Support/PreventiveActions.php`, `config/preventive_actions.php`, `resources/views/components/ui/preventive-actions.blade.php`, `tests/Unit/PreventiveActionsTest.php`, `tests/Feature/PreventiveActionsPageTest.php`
- Test: `tests/Feature/{RecommendationPageTest,DeviceStatusTest}.php`, `tests/Feature/PublicHistoryGuideTest.php` (bagian Panduan)

**Interfaces:** Consumes: `Recommendation::all()`, `::sources()`, `config('recommendations.basis')`, `config('air_quality.thresholds')`, `config('air_quality.source')`, `TelemetryRejection`.

- Rekomendasi: kartu kondisi saat ini (per ruangan; kosong bila belum ada data); tabel 4 aturan (kategori, tingkat, rekomendasi, strategi EPA); bagian "Dasar rekomendasi" (2 poin spec §3 + tautan sumber); disclaimer bukan diagnosis medis.
- Status Perangkat: per node `code` (device_id), ruangan, gas dikirim (CO₂, NH₃, CO, H₂), waktu data terakhir, status Terhubung/Terputus (≤ 30 dtk), interval terukur (selisih rata-rata 10 data terakhir); diagram alur 6 langkah (spec §4); tabel 10 penolakan terakhir (waktu, device_id, galat ringkas). `wire:poll.5s`.
- Panduan: hapus sub-indeks/contoh hitungan/AQI CO; tampilkan tabel ambang (Global Constraints) dengan sitasi Solihat; penjelasan "nilai ppm adalah indikator relatif terhadap kondisi saat perangkat dinyalakan"; cara membaca kategori keseluruhan (terburuk); keterangan 4 gas; FAQ diperbarui (interval 5 detik, sensor terputus, bukan diagnosis).

- [ ] Test: `/rekomendasi` menampilkan keempat teks Tabel 3.3 dan tautan EPA; `/perangkat` menampilkan `AIR-001`, "Terhubung" bila data 10 dtk lalu, "Terputus" bila 2 menit lalu, dan galat penolakan terakhir; Panduan menampilkan tabel ambang dengan angka 50/100/150 dan sitasi DOI, tanpa kata "sub-indeks"; navbar memuat 6 menu.
- [ ] Jalankan, gagal; implementasi; merge `origin/v2`; hapus berkas usang; `grep -rn "PreventiveActions\|sub-indeks\|Indeks KU" app resources tests` kosong; suite hijau; Pint; build.
- [ ] Commit: `feat(ui): recommendation and device status pages; guide aligned with Solihat thresholds`.

### Task 8: Dokumen pengujian, revisi naskah, README, wireframe

**Files:**
- Rewrite: `docs/pengujian-black-box.md` — struktur Bab 3.12: Tabel 3.5 (rencana), 3.6 (fungsional per halaman), 3.7 (validasi data), 3.8 (klasifikasi), 3.9 (rekomendasi), 3.10 (lembar uji manual 4 kondisi ruangan dengan kolom isian), status Valid hanya untuk skenario yang dibuktikan test otomatis yang lolos.
- Create: `docs/revisi-naskah.md` — tabel per subbab (Lokasi | Tertulis sekarang | Usulan revisi | Alasan) mencakup seluruh butir spec §9.
- Modify: `README.md` (fitur, rute, payload API baru, `ueq:report`, seeder), wireframe kanvas (hapus artboard admin, tambah Rekomendasi & Perangkat, perbarui Beranda/Detail/Panduan/Kuesioner).

- [ ] Jalankan `php artisan test --compact` untuk angka aktual; tulis dokumen.
- [ ] Commit: `docs: black-box tests per Bab 3.12, manuscript revision list, README`.

{{-- Halaman latihan sidang: tidak ditautkan dari menu SIMKU dan tidak diindeks mesin pencari. --}}
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
@verbatim
<title>Latihan Sidang SIMKU</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,400;0,500;0,600;1,400&family=IBM+Plex+Mono:wght@400;500&family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700&display=swap">
<style>
/* Layout: satu kolom bacaan (±70 karakter), bilah alat lengket di atas, kartu tanya-jawab per bab. */
:root {
  --bg: #f6f7f5;
  --surface: #ffffff;
  --ink: #18231f;
  --muted: #5a6862;
  --line: #d9dfdb;
  --accent: #1f6f5c;
  --accent-soft: #e2efe9;
  --warn: #8a5a00;
  --warn-soft: #fbf1dc;
  --chip: #eef1ef;
  --font-display: "Bricolage Grotesque", "IBM Plex Sans", system-ui, sans-serif;
  --font-body: "IBM Plex Sans", system-ui, -apple-system, "Segoe UI", sans-serif;
  --font-mono: "IBM Plex Mono", ui-monospace, "SFMono-Regular", Menlo, monospace;
}
@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
    --bg: #121816; --surface: #1a2220; --ink: #e5ece8; --muted: #9db0a8; --line: #2c3834;
    --accent: #6cc4a8; --accent-soft: #1f322c; --warn: #f0c46a; --warn-soft: #33291a; --chip: #232d2a;
    color-scheme: dark;
  }
}
:root[data-theme="dark"] {
  --bg: #121816; --surface: #1a2220; --ink: #e5ece8; --muted: #9db0a8; --line: #2c3834;
  --accent: #6cc4a8; --accent-soft: #1f322c; --warn: #f0c46a; --warn-soft: #33291a; --chip: #232d2a;
  color-scheme: dark;
}
* { box-sizing: border-box; }
body {
  background: var(--bg); color: var(--ink); font-family: var(--font-body); font-size: 16px; line-height: 1.6;
  padding-left: max(16px, env(safe-area-inset-left, 0px)); padding-right: max(16px, env(safe-area-inset-right, 0px));
}
body::before { content: ""; position: fixed; inset: 0 0 auto; height: env(safe-area-inset-top, 0px); z-index: 10; background: var(--bg); }
.wrap { max-width: 760px; margin: 0 auto; padding-block: 40px 64px; display: grid; gap: 28px; }
header { display: grid; gap: 10px; }
.eyebrow { font: 500 12px/1 var(--font-mono); letter-spacing: .08em; text-transform: uppercase; color: var(--accent); }
h1 { font: 700 clamp(30px, 6vw, 44px)/1.08 var(--font-display); margin: 0; text-wrap: balance; letter-spacing: -.01em; }
.lead { color: var(--muted); margin: 0; max-width: 62ch; }
.legend { display: flex; flex-wrap: wrap; gap: 8px 16px; font-size: 13px; color: var(--muted); }
.legend span { display: inline-flex; align-items: center; gap: 6px; }
.toolbar {
  position: sticky; top: env(safe-area-inset-top, 0px); z-index: 5; background: var(--bg);
  padding-block: 12px; border-bottom: 1px solid var(--line); display: grid; gap: 10px;
}
.row { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
input[type="search"] {
  flex: 1 1 220px; min-width: 0; font: inherit; font-size: 15px; padding: 9px 12px; border-radius: 8px;
  border: 1px solid var(--line); background: var(--surface); color: var(--ink);
}
button {
  font: 500 14px/1 var(--font-body); padding: 9px 12px; border-radius: 8px; border: 1px solid var(--line);
  background: var(--surface); color: var(--ink); cursor: pointer;
}
button[aria-pressed="true"] { background: var(--accent); border-color: var(--accent); color: var(--bg); }
button:focus-visible, summary:focus-visible, input:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
.count { font: 13px var(--font-mono); color: var(--muted); font-variant-numeric: tabular-nums; }
section.bab { display: grid; gap: 12px; }
section.bab h2 { font: 600 22px/1.2 var(--font-display); margin: 8px 0 0; display: flex; gap: 10px; align-items: baseline; }
section.bab h2 small { font: 500 12px var(--font-mono); color: var(--muted); letter-spacing: .04em; }
details.qa { background: var(--surface); border: 1px solid var(--line); border-radius: 10px; }
details.qa[open] { border-color: var(--accent); }
summary { list-style: none; cursor: pointer; padding: 14px 16px; display: grid; grid-template-columns: auto 1fr auto; gap: 12px; align-items: start; }
summary::-webkit-details-marker { display: none; }
.num { font: 500 13px/1.6 var(--font-mono); color: var(--muted); font-variant-numeric: tabular-nums; min-width: 2.2em; }
.q { font-weight: 600; min-width: 0; }
.q .who { display: block; font: 400 12px/1.4 var(--font-mono); color: var(--accent); margin-top: 4px; letter-spacing: .02em; }
.chev { color: var(--muted); transition: transform .15s; font-size: 14px; line-height: 1.6; }
details[open] .chev { transform: rotate(90deg); }
.ans { padding: 0 16px 16px calc(16px + 2.2em + 12px); display: grid; gap: 10px; min-width: 0; }
.ans p { margin: 0; max-width: 66ch; }
.ans ul { margin: 0; padding-left: 20px; display: grid; gap: 4px; max-width: 64ch; }
.refs { display: flex; flex-wrap: wrap; gap: 6px; }
.ref { font: 500 12px/1 var(--font-mono); padding: 6px 8px; border-radius: 6px; background: var(--chip); color: var(--ink); }
.note { font-size: 14px; background: var(--warn-soft); color: var(--ink); border-left: 3px solid var(--warn); padding: 8px 12px; border-radius: 0 6px 6px 0; max-width: 66ch; }
.note b { color: var(--warn); }
.done-row { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--muted); }
.done-row input { width: 16px; height: 16px; accent-color: var(--accent); }
details.qa.mastered summary .q { color: var(--muted); }
.dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
.empty { color: var(--muted); text-align: center; padding: 24px 0; }
footer { color: var(--muted); font-size: 13px; border-top: 1px solid var(--line); padding-top: 16px; }
@media (max-width: 520px) {
  .ans { padding-left: 16px; }
  .toolbar { position: static; }
  #filter { flex-wrap: nowrap; overflow-x: auto; padding-bottom: 4px; }
  #filter button { flex: none; }
}
@media (prefers-reduced-motion: reduce) { .chev { transition: none; } }
</style>
</head>
<body>

<div class="wrap">
  <header>
    <div class="eyebrow">Sidang skripsi · SIMKU · naskah v10</div>
    <h1>Latihan Sidang SIMKU</h1>
    <p class="lead">Pertanyaan yang mungkin diajukan penguji, dengan jawaban yang bersandar pada isi naskah v10 dan nomor subbab tempat jawabannya tertulis. Tutup semua jawaban, coba jawab sendiri, lalu buka untuk mencocokkan.</p>
    <div class="legend">
      <span><span class="dot" style="background:var(--accent)"></span>Label hijau: penguji yang pernah menyinggung topik ini di STI-9</span>
      <span><span class="dot" style="background:var(--warn)"></span>Kotak kuning: hal yang belum tertulis di naskah, perlu disiapkan</span>
    </div>
  </header>

  <div class="toolbar">
    <div class="row">
      <input type="search" id="cari" placeholder="Cari kata, misalnya ppm, UEQ, Lab. DSK" aria-label="Cari pertanyaan">
      <button type="button" id="buka">Buka semua</button>
      <button type="button" id="tutup">Tutup semua</button>
    </div>
    <div class="row" id="filter" role="group" aria-label="Saring per bab"></div>
    <div class="count" id="hitung"></div>
  </div>

  <main id="daftar"></main>

  <footer>Jawaban dalam bentuk lisan orang pertama. Angka hasil pengujian ruangan (subbab 4.3) dan UEQ (subbab 4.4) masih menunggu data asli; lengkapi jawaban terkait setelah isian [ISI] di naskah terisi. Centang "sudah dikuasai" hanya tersimpan di peramban ini.</footer>
</div>

<script>
const BAB = [
  { id: "lingkup", judul: "Masalah dan lingkup", sub: "Bab I–II" },
  { id: "data", judul: "Sensor, gas, dan ruangan uji", sub: "Bab I–III" },
  { id: "rancang", judul: "Metode dan perancangan sistem", sub: "Bab II–III" },
  { id: "olah", judul: "Klasifikasi dan rekomendasi", sub: "Bab II–IV" },
  { id: "uji", judul: "Pengujian dan hasil", sub: "Bab III–V" }
];

const QA = [
  // ---------- lingkup ----------
  { bab: "lingkup", q: "Apa masalah yang ingin diselesaikan penelitian ini?",
    a: ["Sistem monitoring kualitas udara berbasis IoT umumnya berhenti pada angka ppm dan grafik. Pengguna awam sulit menilai apakah angka itu berbahaya dan apa yang perlu dilakukan.",
        "Penelitian ini membangun aplikasi web SIMKU yang menerjemahkan data perangkat IoT menjadi kategori kualitas udara per gas dan keseluruhan, gas penyebab utama, dan saran tindakan preventif sesuai kondisi saat itu."],
    refs: ["1.1 Latar Belakang", "1.2 Rumusan Masalah", "2.1 (paragraf setelah Tabel 2.1)"] },
  { bab: "lingkup", q: "Apa bedanya penelitian ini dengan penelitian terdahulu? Di mana gap-nya?", who: "Penguji 2 (Bu Dian) no. 2",
    a: ["Penelitian terdahulu umumnya menyajikan nilai ppm dan label kategori. Penelitian-penelitian itu belum menunjukkan gas penyebab kondisi tersebut dan belum memberi saran tindakan preventif. Pengujiannya juga lebih banyak pada keakuratan sensor, belum pada fungsionalitas aplikasi di beberapa kondisi ruangan nyata dan pengalaman pengguna.",
        "Penelitian ini mengisi celah itu dengan tiga hal: penyebab utama, rekomendasi berbasis aturan, serta pengujian black-box di tiga kondisi ruangan ditambah evaluasi UEQ."],
    refs: ["2.1 Penelitian Terdahulu", "Tabel 2.1"] },
  { bab: "lingkup", q: "Kenapa referensi penelitian terdahulu yang dipilih itu? Apa kaitannya dengan metode Anda?", who: "Penguji 2 (Bu Dian) no. 2",
    a: ["Referensi dipilih berdasarkan metode dan teknik yang saya pakai:",
        "• Penelitian nomor 1, 2, 4 sampai 8, dan 10 pada Tabel 2.1 menjadi acuan alur pengiriman, penyimpanan, dan penyajian data real-time.",
        "• Wibowo et al. (2024) menjadi acuan pemakaian sensor MQ-135 dan MQ-2 sebagai indikator relatif.",
        "• Haryanto et al. (2024) menjadi acuan klasifikasi berbasis aturan, dengan ambang dari Solihat et al. (2026).",
        "• Rivangga et al. (2024) menjadi pembanding, karena memakai data historis untuk prediksi, sedangkan penelitian saya tidak memprediksi."],
    refs: ["2.1 (paragraf sintesis setelah Tabel 2.1)"] },
  { bab: "lingkup", q: "Ruang lingkup penelitiannya di mana saja?", who: "Penguji 3 (Pak Nanda) no. 1",
    a: ["Penelitian ini fokus pada perangkat lunak (aplikasi web). Perangkat IoT hanya penyedia data dan dikerjakan rekan satu proyek.",
        "Lingkup lainnya: empat gas (CO₂, NH₃, CO, H₂), satu ruangan uji (Lab. DSK) dengan satu perangkat (AIR-001) dan tiga skenario kondisi, responden UEQ mahasiswa Teknik Elektro yang belajar di Lab. DSK, tanpa login, serta tanpa analisis tren atau prediksi."],
    refs: ["1.4 Batasan Masalah", "3.2 Objek Penelitian", "Tabel 3.1"] },
  { bab: "lingkup", q: "Di proposal ada prediksi kualitas udara 10 menit ke depan. Sekarang prediksinya di mana?", who: "Penguji 1 (Pak Salhazan) no. 2 · Penguji 3 (Pak Nanda) no. 1–2 · Penguji 2 (Bu Dian) no. 3",
    a: ["Prediksi dan analisis tren dikeluarkan dari lingkup penelitian sesuai masukan seminar proposal, agar penelitian fokus pada ranah RPL dan tidak overclaim. Sistem hanya menyajikan kondisi terkini secara real-time. Grafik dan riwayat hanya ditampilkan, tidak dianalisis.",
        "Hal ini tertulis tegas di batasan masalah dan di dasar pengambilan keputusan rekomendasi."],
    refs: ["1.4 butir 8", "Tabel 3.1 (Batasan objek)", "3.10.1 Dasar Pengambilan Keputusan"],
    note: "Siapkan jawaban singkat bila ditanya kenapa prediksi dibuang: tunjukkan bahwa dasar ilmiah prediksi 10 menit tidak dapat dipertanggungjawabkan dalam lingkup S1 RPL, lalu arahkan ke apa yang sekarang diuji." },
  { bab: "lingkup", q: "Bagaimana dengan durasi paparan (exposure duration)?", who: "Penguji 2 (Bu Dian) no. 3",
    a: ["Durasi paparan juga tidak dipakai. Satu-satunya masukan rekomendasi adalah kategori keseluruhan dari data terbaru. Fitur ini tidak memakai analisis tren, prediksi, maupun riwayat paparan."],
    refs: ["3.10.1 Dasar Pengambilan Keputusan", "3.8.5 Wireframe Halaman Rekomendasi Tindakan"] },
  { bab: "lingkup", q: "Apakah rekomendasi aplikasi ini termasuk health advisory?", who: "Penguji 1 (Pak Salhazan) no. 4",
    a: ["Bukan. Rekomendasi ini hanya tindakan preventif sederhana yang bersifat umum, seperti meningkatkan ventilasi atau mengurangi sumber polutan. Rekomendasi ini bukan saran medis maupun diagnosis.",
        "Halaman Rekomendasi Tindakan dan Panduan Sistem juga menyatakan hal ini kepada pengguna."],
    refs: ["3.10.1", "Tabel 3.15", "4.1.3 (Halaman Rekomendasi Tindakan)"] },

  // ---------- data ----------
  { bab: "data", q: "Kenapa gas yang dipantau CO₂, NH₃, CO, dan H₂? Apa hubungannya dengan studi kasus?", who: "Penguji 2 (Bu Dian) no. 1",
    a: ["Keempat gas dapat dibaca sensor MQ-135 dan MQ-2 yang murah dan umum dipakai, dan dipilih sesuai kondisi Lab. DSK:",
        "• CO₂: ruangan kadang terisi penuh mahasiswa dan belum memiliki AC, sehingga ventilasinya bergantung pada pintu dan jendela. CO₂ dihasilkan penghuni dan lazim dipakai untuk menilai kecukupan ventilasi.",
        "• CO dan H₂: sebagai laboratorium, ruangan dapat dipakai untuk praktikum yang menimbulkan asap, misalnya menyolder. MQ-2 dirancang untuk asap dan gas mudah terbakar.",
        "• NH₃: dapat berasal dari bahan pembersih saat lantai, yang dimasuki dengan alas kaki, dibersihkan."],
    refs: ["1.1 (paragraf parameter kualitas udara)", "3.3 Sumber Data", "3.2 Objek Penelitian"] },
  { bab: "data", q: "Kenapa ruang uji di Lab. DSK?",
    a: ["Ada tiga alasan:",
        "• Lab. DSK dipakai untuk kegiatan belajar mengajar mahasiswa Jurusan Teknik Elektro UNRI, jurusan yang menaungi Prodi S1 Teknik Informatika. Jadi ada pengguna yang merasakan langsung kondisi udaranya dan dapat menjadi responden.",
        "• Di dalamnya terdapat beberapa alat laboratorium, sehingga ruangan ini mewakili ruang praktikum, bukan hanya ruang kelas biasa.",
        "• Ruangannya belum memiliki AC, sehingga kondisi udara tidak dikendalikan sistem pendingin dan efek membuka atau menutup pintu dan jendela dapat diamati."],
    refs: ["3.2 Objek Penelitian", "Tabel 3.1"] },
  { bab: "data", q: "Ruangan itu dimasuki pakai sepatu dan bisa berdebu. Kenapa debu tidak diukur?",
    a: ["Perangkat hanya memiliki sensor gas MQ-135 dan MQ-2, tanpa sensor partikulat. Pengukuran PM2.5 dan PM10 berada di luar lingkup penelitian, dan hal ini disebut terang-terangan di naskah agar tidak overclaim.",
        "Penambahan parameter seperti PM2.5 menjadi saran penelitian berikutnya."],
    refs: ["1.1", "1.4 butir 5", "3.3 Sumber Data", "5.2 butir 4"] },
  { bab: "data", q: "Seberapa akurat nilai ppm yang ditampilkan?",
    a: ["Nilai ppm bukan konsentrasi absolut. Perangkat menghitungnya relatif terhadap kondisi udara saat perangkat dinyalakan (baseline), sehingga nilai ini dibaca sebagai indikator perubahan kondisi udara.",
        "Kalibrasi sensor, akurasi ppm, dan rumus konversi adalah tanggung jawab penelitian IoT rekan satu proyek. Aplikasi hanya memvalidasi format data yang diterima."],
    refs: ["2.5 Data Indikator Gas dari Perangkat IoT", "1.4 butir 4 dan 9", "3.3"] },
  { bab: "data", q: "Sensor MQ tidak selektif terhadap satu gas. Bagaimana Anda menyikapinya?",
    a: ["Betul, sensor MQ bersifat cross-sensitive, sehingga satu sensor dapat merespons beberapa gas sekaligus. Karena itu nilai berlabel CO₂, NH₃, CO, atau H₂ dibaca sebagai indikator perubahan kondisi udara di sekitar sensor, bukan hasil pengukuran polutan yang terkalibrasi."],
    refs: ["2.5", "3.3 Sumber Data"] },
  { bab: "data", q: "Apa yang terjadi kalau perangkat menyala ulang di tengah pengujian?",
    a: ["Baseline diukur ulang saat perangkat menyala, sehingga acuannya berubah. Karena itu perangkat dinyalakan pada kondisi ruangan normal dan tidak dinyalakan ulang di antara skenario. Bila terlanjur menyala ulang, pengujian diulang dari skenario pertama."],
    refs: ["3.12.5 Pengujian pada Beberapa Kondisi Ruangan", "4.3"] },
  { bab: "data", q: "Ambang batas kategori diambil dari mana? Apakah itu standar resmi?",
    a: ["Ambang diambil dari satu rujukan, yaitu Solihat et al. (2026), untuk keempat gas (Tabel 2.2). Ambang ini dipakai sebagai acuan kategori aplikasi, bukan baku mutu udara resmi. Karena itu kategori Tidak Sehat atau Berbahaya tidak dapat dibaca sebagai tingkat bahaya menurut standar kesehatan."],
    refs: ["2.10 Klasifikasi Berbasis Ambang Batas", "Tabel 2.2", "1.4 butir 9", "4.5 (paragraf batasan)"] },

  // ---------- rancang ----------
  { bab: "rancang", q: "Metode penelitian apa yang dipakai?",
    a: ["Research and Development (R&D) dengan pendekatan pengembangan sistem. Produknya aplikasi web. Validasi produk dilakukan dengan pengujian black-box untuk kesesuaian fungsi dan UEQ untuk pengalaman pengguna."],
    refs: ["3.1 Metode Penelitian"] },
  { bab: "rancang", q: "Kenapa memakai Waterfall, bukan Agile?",
    a: ["Kebutuhan aplikasi sudah ditentukan sejak awal, sehingga tahapan dapat dijalankan berurutan dan sistematis: analisis kebutuhan, perancangan, implementasi, pengujian, dan pemeliharaan. Alurnya juga jelas untuk didokumentasikan."],
    refs: ["2.14 Metode Waterfall", "3.1", "3.5 Metode Pengembangan Sistem"] },
  { bab: "rancang", q: "Bagaimana arsitektur sistemnya?",
    a: ["Ada tiga lapisan:",
        "• IoT Layer: perangkat mengirim data.",
        "• Processing Layer: aplikasi menerima, memvalidasi, mengklasifikasikan, dan menyimpan data.",
        "• Presentation Layer: halaman web untuk pengguna.",
        "Pemisahan ini membuat pengembangan, pemeliharaan, dan pengujian lebih mudah."],
    refs: ["3.6 Arsitektur Sistem (3.6.1–3.6.4)"] },
  { bab: "rancang", q: "Sesuai kaidah RPL, mana analisis kebutuhan, use case, dan desainnya?", who: "Penguji 2 (Bu Dian) no. 5",
    a: ["Semuanya ada di Bab III:",
        "• kebutuhan fungsional dan nonfungsional di 3.5.1;",
        "• flowchart di 3.7.1;",
        "• use case diagram dan skenario use case di 3.7.2–3.7.3;",
        "• activity diagram di 3.7.4;",
        "• ERD di 3.7.5;",
        "• wireframe ketujuh halaman di 3.8."],
    refs: ["3.5.1", "3.7.1–3.7.5", "3.8.1–3.8.7"] },
  { bab: "rancang", q: "Siapa aktornya? Kenapa tidak ada login atau admin?",
    a: ["Aktornya hanya Pengguna dan Perangkat IoT. Informasi kualitas udara ruangan bersifat publik bagi penghuni, sehingga aplikasi dapat diakses tanpa login dan tidak memiliki panel administrator."],
    refs: ["1.4 butir 10", "3.7.2 Use Case Diagram"],
    note: "Bila ditanya keamanan endpoint data: siapkan penjelasan bahwa data yang masuk divalidasi dan perangkat harus terdaftar (4.1.1). Naskah tidak membahas autentikasi perangkat secara khusus." },
  { bab: "rancang", q: "Bagaimana tampilan bisa real-time tanpa memuat ulang halaman?",
    a: ["Perangkat mengirim data setiap 5 detik. Halaman memakai polling Livewire setiap 5 detik, sehingga tampilan mengikuti data terbaru. Status perangkat Terhubung bila data terakhir diterima paling lama 30 detik yang lalu, dan Terputus bila lebih lama.",
        "Hasil pengamatan di peramban menunjukkan Beranda berubah sekitar 2,4 detik setelah data baru dikirim, tanpa memuat ulang halaman."],
    refs: ["2.8.4 Livewire", "3.7.3 Skenario Use Case", "4.1.3", "4.2.1"] },
  { bab: "rancang", q: "Teknologi apa yang dipakai, dan bagaimana struktur basis datanya?",
    a: ["Laravel 13 dengan PHP 8.3, Livewire, Tailwind CSS, Chart.js untuk grafik, dan MySQL.",
        "Basis datanya punya lima tabel utama: rooms, nodes, sensor_logs, telemetry_rejections untuk data yang ditolak, dan ueq_responses."],
    refs: ["2.8 Teknologi Aplikasi Web", "3.11.5 / Tabel 3.16", "3.7.5 Perancangan Basis Data", "4.1.2 Implementasi Basis Data"] },
  { bab: "rancang", q: "Apa yang terjadi bila data dari perangkat rusak atau tidak lengkap?",
    a: ["Setiap data diperiksa: identitas perangkat harus terdaftar, keempat nilai gas harus angka tidak negatif, dan format waktu harus benar.",
        "Data yang tidak lolos ditolak, tidak disimpan sebagai data pembacaan, dan tidak ditampilkan. Data itu dicatat terpisah beserta alasannya agar dapat ditelusuri."],
    refs: ["4.1.1 Implementasi Penerimaan dan Pengolahan Data", "3.7.3 (skenario use case penerimaan data)", "Tabel 4.2"] },
  { bab: "rancang", q: "Bagaimana kebutuhan pengguna dikumpulkan?",
    a: ["Ada lima teknik: observasi data sensor, wawancara dengan calon pengguna, studi literatur, dokumentasi data pengujian, dan kuesioner UEQ."],
    refs: ["3.4 Teknik Pengumpulan Data"],
    note: "Naskah menyebut wawancara dengan calon pengguna, tetapi tidak mencantumkan siapa narasumbernya dan apa hasilnya. Siapkan jawaban lisan atau lampiran bila ditanya." },

  // ---------- olah ----------
  { bab: "olah", q: "Bagaimana cara menentukan kategori kualitas udara keseluruhan?",
    a: ["Setiap gas diklasifikasikan dengan ambang Tabel 2.2, lalu kategori keseluruhan diambil dari kategori terburuk: KU = max(KCO2, KNH3, KCO, KH2). Pendekatannya konservatif, karena satu gas yang buruk sudah menandakan penurunan kualitas udara.",
        "Contoh: CO₂ 70 (Sedang), NH₃ 30 (Baik), CO 55 (Tidak Sehat), H₂ 10 (Baik). Kategori keseluruhannya Tidak Sehat dengan penyebab utama CO."],
    refs: ["3.9.2 Penentuan Kategori Keseluruhan", "2.10", "4.1.1"] },
  { bab: "olah", q: "Bagaimana kalau dua gas sama-sama paling buruk? Dan bagaimana nilai tepat di batas ambang?",
    a: ["Bila lebih dari satu gas berada pada kategori terburuk, semuanya ditampilkan sebagai penyebab utama. Bila kategorinya Baik, penyebab utama tidak ditampilkan.",
        "Batas kategori bersifat eksklusif: CO₂ = 50 ppm sudah Sedang dan CO₂ = 150 ppm sudah Berbahaya, sedangkan nilai sedikit di bawahnya tetap di kategori sebelumnya. Hal ini diuji dengan analisis nilai batas."],
    refs: ["3.9.2", "2.10", "2.15 (boundary value analysis)", "Tabel 4.4"] },
  { bab: "olah", q: "Dari mana sumber isi rekomendasinya?", who: "Penguji 2 (Bu Dian) no. 4",
    a: ["Jenis tindakan mengacu pada tiga strategi perbaikan kualitas udara dalam ruangan dari U.S. EPA: kendali sumber, peningkatan ventilasi, dan pembersihan udara. Tingkat urgensinya (Normal, Waspada, Perlu Tindakan, Segera Bertindak) mengikuti prinsip penyampaian AQI EPA, yaitu anjuran makin tegas seiring kategori memburuk.",
        "Pemetaannya ke empat kategori adalah rancangan saya yang bersifat umum dan bukan saran medis."],
    refs: ["2.11 Rekomendasi Tindakan Preventif", "3.10.1", "3.10.2 / Tabel 3.15"] },
  { bab: "olah", q: "Kenapa memakai rule-based, bukan machine learning atau fuzzy?",
    a: ["Ada empat alasan:",
        "• mudah diterapkan secara real-time;",
        "• tidak butuh komputasi kompleks;",
        "• transparan dan mudah dipahami pengguna;",
        "• mudah diuji, karena setiap kategori menghasilkan satu rekomendasi yang pasti."],
    refs: ["3.10.4 Alasan Pemilihan Pendekatan Rule-Based", "2.10"],
    note: "Bila ditekan soal ML: tambahkan secara lisan bahwa tidak ada data berlabel untuk melatih model, dan bahwa prediksi berada di luar lingkup. Pernyataan ini tidak tertulis di naskah, jadi sampaikan sebagai pendapat." },
  { bab: "olah", q: "Kenapa rekomendasi hanya berdasarkan kategori keseluruhan, bukan per gas?",
    a: ["Satu kategori keseluruhan menghasilkan satu saran yang pasti, sehingga lebih mudah dipahami pengguna awam dan mudah diuji. Informasi per gas tetap tersedia: aplikasi menampilkan kategori setiap gas dan gas penyebab utama di samping rekomendasi."],
    refs: ["3.10.1", "3.10.4", "4.1.3 (Beranda dan Rekomendasi)"] },

  // ---------- uji ----------
  { bab: "uji", q: "Kenapa memakai pengujian black-box? Teknik apa yang dipakai?",
    a: ["Black-box menguji kesesuaian fungsi terhadap kebutuhan tanpa melihat struktur kode, sesuai tujuan memastikan aplikasi berfungsi bagi pengguna.",
        "Kasus uji disusun dengan equivalence partitioning, misalnya empat kelas nilai CO₂, dan boundary value analysis, yaitu nilai tepat di batas ambang."],
    refs: ["2.15 Pengujian Black-Box", "3.12.1–3.12.4"] },
  { bab: "uji", q: "Bagaimana hasil pengujian black-box?",
    a: ["Seluruh 29 skenario berstatus Valid:",
        "• 9 fungsional aplikasi;",
        "• 5 validasi data;",
        "• 7 klasifikasi;",
        "• 4 nilai batas ambang;",
        "• 4 rekomendasi.",
        "Empat bagian tampilan diamati langsung di peramban: pembaruan Beranda tanpa memuat ulang, pergantian grafik, perpindahan halaman Riwayat, dan waktu data terakhir di Status Perangkat."],
    refs: ["4.2 Hasil Pengujian Black-Box", "Tabel 4.6"] },
  { bab: "uji", q: "Bagaimana skenario pengujian pada kondisi ruangan nyata?", who: "Penguji 1 (Pak Salhazan) no. 3",
    a: ["Ada tiga skenario di Lab. DSK, dijalankan berurutan dengan perangkat AIR-001:",
        "• ruangan normal ± 10 menit;",
        "• ruangan tertutup (pintu dan jendela ditutup) ± 30 menit;",
        "• ruangan berasap dengan sumber asap terkontrol di dekat sensor (misalnya dupa atau asap menyolder) ± 5 menit.",
        "Yang diamati: data diterima setiap ± 5 detik, kategori berubah sesuai kondisi, dan rekomendasi sesuai kategori. Datanya diambil dari CSV halaman Riwayat."],
    refs: ["3.12.5 / Tabel 3.22", "4.3 Hasil Pengujian pada Beberapa Kondisi Ruangan", "Tabel 4.7–4.8"],
    note: "Hasil angka masih [ISI] di naskah. Setelah pengujian, hafalkan jam perubahan kategori dan nilai puncak tiap skenario." },
  { bab: "uji", q: "Kenapa hanya tiga skenario? Penguji pernah menyebut ventilasi berbeda.",
    a: ["Saran penguji menyebut ruangan normal, tertutup, berasap, atau ventilasi berbeda sebagai contoh. Penelitian ini memakai tiga kondisi nyata pertama, karena ketiganya sudah menguji apakah kategori dan rekomendasi mengikuti kondisi ruangan saat memburuk dari normal."],
    refs: ["1.4 butir 6", "3.12.5 / Tabel 3.22"],
    note: "Alasan pengurangan menjadi tiga skenario belum tertulis di naskah. Pastikan alasan lisan ini sama dengan keputusan yang disepakati bersama pembimbing." },
  { bab: "uji", q: "Apakah pengujian ruangan menilai ketepatan angka ppm?",
    a: ["Tidak. Yang dinilai adalah kesesuaian perubahan kategori dan rekomendasi dengan perubahan kondisi ruangan, bukan ketepatan angka ppm. Angka ppm sendiri merupakan indikator relatif terhadap baseline."],
    refs: ["4.3 (paragraf kesimpulan)", "2.5"] },
  { bab: "uji", q: "Kenapa evaluasi pengguna memakai UEQ, dan bagaimana mengolahnya?",
    a: ["UEQ mengukur aspek kegunaan klasik (efisiensi, kejelasan, ketepatan) sekaligus aspek pengalaman (kebaruan, stimulasi) dengan cepat. Versi Bahasa Indonesianya sudah tersedia.",
        "Pengolahannya:",
        "• 26 butir skala 1–7 diubah ke −3 sampai +3;",
        "• 13 butir dibalik;",
        "• butir dikelompokkan ke enam skala;",
        "• tiap skala dihitung rata-rata, simpangan baku, dan Cronbach's alpha;",
        "• rata-rata skala dibandingkan dengan benchmark Schrepp et al. (2017)."],
    refs: ["2.16 User Experience Questionnaire", "3.12.6 Evaluasi Pengguna dengan UEQ", "Tabel 3.23–3.24"] },
  { bab: "uji", q: "Siapa respondennya, berapa orang, dan bagaimana memilihnya?",
    a: ["Responden adalah mahasiswa Jurusan Teknik Elektro UNRI yang mengikuti kegiatan belajar mengajar di Lab. DSK, dipilih secara purposive sampling. Merekalah yang merasakan langsung kondisi udara ruangan yang dipantau aplikasi. Sebelum mengisi kuesioner, responden mencoba aplikasi terlebih dahulu."],
    refs: ["1.4 butir 7", "3.4 Teknik Pengumpulan Data", "4.4 Hasil Evaluasi Pengalaman Pengguna"],
    note: "Jumlah responden dan hasil UEQ masih [ISI]. Siapkan juga alasan jumlah responden (kisi-kisi menyarankan minimal 20 orang)." },
  { bab: "uji", q: "Apa kelemahan atau batasan penelitian ini?",
    a: ["Ada tiga batasan:",
        "• Nilai ppm merupakan indikator relatif terhadap baseline, bukan konsentrasi terkalibrasi.",
        "• Ambang Tabel 2.2 adalah acuan kategori aplikasi, bukan baku mutu resmi.",
        "• Pengujian dilakukan di satu ruangan dengan satu perangkat, dan responden UEQ terbatas pada mahasiswa yang belajar di ruangan itu. Karena itu hasilnya belum dapat digeneralisasi ke ruangan lain."],
    refs: ["4.5 Pembahasan (paragraf batasan)", "1.4"] },
  { bab: "uji", q: "Apakah ketiga tujuan penelitian tercapai?",
    a: ["Tujuan 1 tercapai: aplikasi menerima, memvalidasi, menyimpan, dan menampilkan data pada tujuh halaman yang diperbarui otomatis.",
        "Tujuan 2 tercapai: keempat aturan menghasilkan rekomendasi yang tepat untuk setiap kategori (Tabel 4.5).",
        "Tujuan 3: bagian black-box sudah selesai dengan 29 skenario Valid. Pengujian kondisi ruangan dan UEQ menunggu hasil lapangan."],
    refs: ["4.5 Pembahasan", "5.1 Kesimpulan"],
    note: "Lengkapi jawaban tujuan 3 dengan hasil 4.3 dan 4.4 setelah data terkumpul, dan pastikan angkanya sama di Bab IV dan Bab V." },
  { bab: "uji", q: "Apa saran untuk pengembangan berikutnya?",
    a: ["Saran pengembangannya:",
        "• kalibrasi sensor terhadap alat ukur acuan bersama pengembang perangkat;",
        "• pengujian di beberapa ruangan dengan ukuran, penghuni, dan sumber polutan berbeda;",
        "• fitur pemberitahuan saat kualitas udara memburuk;",
        "• penambahan parameter seperti suhu, kelembapan, atau PM2.5 dengan ambang dari rujukan resmi;",
        "• perbaikan tampilan berdasarkan butir UEQ terendah."],
    refs: ["5.2 Saran"] }
];

const daftar = document.getElementById("daftar");
const cari = document.getElementById("cari");
const filter = document.getElementById("filter");
const hitung = document.getElementById("hitung");
let aktif = "semua";

const KEY = "latihan-sidang-simku-dikuasai";
let dikuasai = new Set();
try { dikuasai = new Set(JSON.parse(localStorage.getItem(KEY) || "[]")); } catch (e) { dikuasai = new Set(); }
function simpan() { try { localStorage.setItem(KEY, JSON.stringify([...dikuasai])); } catch (e) {} }

function el(tag, cls, text) { const n = document.createElement(tag); if (cls) n.className = cls; if (text != null) n.textContent = text; return n; }

function render() {
  daftar.replaceChildren();
  const kata = cari.value.trim().toLowerCase();
  let total = 0, nomor = 0;
  for (const b of BAB) {
    const items = QA.map((x, i) => ({ ...x, i })).filter(x => x.bab === b.id);
    const cocok = items.filter(x => (aktif === "semua" || aktif === b.id) &&
      (!kata || [x.q, x.who || "", ...x.a, ...x.refs, x.note || ""].join(" ").toLowerCase().includes(kata)));
    nomor += 0;
    if (!cocok.length) continue;
    const sec = el("section", "bab");
    const h = el("h2"); h.append(b.judul, " "); h.append(el("small", null, b.sub)); sec.append(h);
    for (const x of cocok) {
      total++;
      const d = el("details", "qa" + (dikuasai.has(x.i) ? " mastered" : ""));
      const s = el("summary");
      s.append(el("span", "num", String(x.i + 1).padStart(2, "0")));
      const q = el("span", "q", x.q);
      if (x.who) q.append(el("span", "who", x.who));
      s.append(q, el("span", "chev", "▸"));
      d.append(s);
      const ans = el("div", "ans");
      let ul = null;
      for (const p of x.a) {
        if (p.startsWith("• ")) { if (!ul) { ul = el("ul"); ans.append(ul); } ul.append(el("li", null, p.slice(2))); }
        else { ul = null; ans.append(el("p", null, p)); }
      }
      const refs = el("div", "refs");
      for (const r of x.refs) refs.append(el("span", "ref", r));
      ans.append(refs);
      if (x.note) { const n = el("div", "note"); n.append(el("b", null, "Perlu disiapkan: ")); n.append(x.note); ans.append(n); }
      const lab = el("label", "done-row");
      const cb = el("input"); cb.type = "checkbox"; cb.id = "dikuasai-" + x.i; cb.checked = dikuasai.has(x.i);
      cb.addEventListener("change", () => { cb.checked ? dikuasai.add(x.i) : dikuasai.delete(x.i); simpan(); d.classList.toggle("mastered", cb.checked); tulisHitung(total); });
      lab.append(cb, "Sudah dikuasai");
      ans.append(lab);
      d.append(ans);
      sec.append(d);
    }
    daftar.append(sec);
  }
  if (!total) daftar.append(el("p", "empty", "Tidak ada pertanyaan yang cocok. Coba kata lain atau pilih Semua."));
  tulisHitung(total);
}
function tulisHitung(total) { hitung.textContent = `${total} dari ${QA.length} pertanyaan tampil · ${dikuasai.size} sudah dikuasai`; }

function tombolFilter() {
  const semua = [{ id: "semua", judul: "Semua" }, ...BAB];
  for (const b of semua) {
    const t = el("button", null, b.judul); t.type = "button";
    t.setAttribute("aria-pressed", String(b.id === aktif));
    t.addEventListener("click", () => { aktif = b.id; for (const x of filter.children) x.setAttribute("aria-pressed", "false"); t.setAttribute("aria-pressed", "true"); render(); });
    filter.append(t);
  }
}
document.getElementById("buka").addEventListener("click", () => document.querySelectorAll("details.qa").forEach(d => d.open = true));
document.getElementById("tutup").addEventListener("click", () => document.querySelectorAll("details.qa").forEach(d => d.open = false));
cari.addEventListener("input", render);
tombolFilter();
render();
</script>
@endverbatim
</body>
</html>

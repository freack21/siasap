# Kisi-kisi Pengisian Bab IV dan Bab V

Berkas ini menjelaskan cara mengisi setiap tanda `[ISI: ...]` pada naskah. Setiap tanda sudah memuat
petunjuk singkat dan contoh kalimat, dan bagian yang lebih panjang diberi komentar Word. Angka pada
contoh kalimat hanya ilustrasi: ganti dengan hasil pengujianmu, lalu hapus tanda kurung siku, kata
"Contoh", dan komentarnya.

Urutan kerjanya:

1. Pengujian pada empat kondisi ruangan, lalu isi subbab 4.3.
2. Evaluasi UEQ, lalu isi subbab 4.4.
3. Isi pembahasan (subbab 4.5) dan Bab V dari hasil langkah 1 dan 2.

---

## 1. Pengujian pada beberapa kondisi ruangan (subbab 4.3)

### Yang perlu disiapkan

- Perangkat IoT AIR-001 (`arduino/simku_monitor`) dan aplikasi SIMKU yang berjalan di laptop.
  Perangkat dan laptop terhubung ke WiFi yang sama.
- Ruangan uji dengan pintu dan jendela yang bisa dibuka-tutup.
- Sumber asap yang aman dan terkontrol, misalnya satu batang obat nyamuk bakar atau dupa.
- Jam atau ponsel untuk mencatat jam mulai dan selesai setiap skenario.
- Lembar catatan berisi kolom: skenario, jam mulai, jam selesai, dan kejadian penting
  (misalnya "14.32 pintu dibuka").

### Langkah pengujian

1. Nyalakan perangkat saat ruangan dalam kondisi normal (ventilasi biasa). Jangan nyalakan ulang
   perangkat di antara skenario, karena nilai ppm dihitung relatif terhadap kondisi saat perangkat
   dinyalakan. Bila perangkat terlanjur menyala ulang, ulangi dari skenario 1.
2. Buka halaman **Perangkat** dan pastikan statusnya **Terhubung**.
3. Jalankan keempat skenario berurutan dan catat jam mulai dan selesainya:

   | No | Skenario | Yang dilakukan | Durasi |
   |---|---|---|---|
   | 1 | Ruangan normal | Biarkan ruangan seperti biasa | ± 10 menit |
   | 2 | Ruangan tertutup | Tutup pintu dan jendela | ± 30 menit |
   | 3 | Ruangan berasap | Nyalakan sumber asap di dekat sensor (catat jaraknya) | ± 5 menit |
   | 4 | Ventilasi berbeda | Matikan sumber asap, buka pintu dan jendela | ± 15 menit |

4. Selama pengujian, ambil tangkapan layar Beranda pada setiap skenario bila ingin menambahkan
   gambar (opsional).

### Mengambil angka untuk naskah

Setelah satu skenario selesai, jalankan perintah berikut di folder aplikasi. Ganti jamnya dengan jam
mulai dan selesai skenario tersebut (waktu WIB, format `tahun-bulan-tanggal jam:menit`):

```bash
php artisan telemetry:ringkasan --dari="2026-11-07 14:05" --sampai="2026-11-07 14:15"
```

Perintah ini hanya membaca data pada rentang waktu itu, jadi data lain di basis data tidak
mengganggu. Contoh keluarannya:

```
Jumlah data diterima : 120
Selang antardata     : rata-rata 5,0 detik, terlama 6 detik
Kategori dominan     : Sedang (85 dari 120 data)
Sebaran kategori     : Baik 35, Sedang 85
+-----+-------+----------+-----------+-------+
| Gas | Awal  | Terendah | Tertinggi | Akhir |
| CO₂ | 20,10 | 20,10    | 64,50     | 64,50 |
...
Rentang nilai ppm    : CO₂ 20,10–64,50; NH₃ 3,10–4,00; CO 2,00–2,60; H₂ 1,00–1,40
Rekomendasi tampil   : Normal → Waspada
Perubahan kategori keseluruhan:
| 14:05:00 | Baik   | -   | Normal  |
| 14:32:15 | Sedang | CO₂ | Waspada |
```

### Dari keluaran ke naskah

| Bagian naskah | Diisi dengan |
|---|---|
| Paragraf pembuka 4.3 | Nama dan ukuran ruangan (panjang × lebar meter, jumlah pintu dan jendela), tanggal pengujian, perangkat yang menjalankan aplikasi, nama dan versi peramban |
| Sumber asap | Jenis sumber asap dan jaraknya dari sensor |
| Tabel 4.7 Waktu Mulai / Selesai | Jam dari lembar catatan, misalnya 14.05 WIB |
| Tabel 4.7 Jumlah Data Diterima | Baris **Jumlah data diterima**. Patokan: 5 detik sekali berarti ± 12 data per menit |
| Tabel 4.8 Kategori Dominan | Nama kategori pada baris **Kategori dominan** |
| Tabel 4.8 Rentang Nilai ppm | Baris **Rentang nilai ppm** |
| Tabel 4.8 Rekomendasi yang Tampil | Baris **Rekomendasi tampil** |
| Tabel 4.8 Kesesuaian | **Sesuai** bila perubahan kategori sama dengan hasil yang diharapkan pada Tabel 3.22, selain itu **Tidak sesuai** |
| Paragraf hasil tiap skenario | Kolom **Awal** dan **Tertinggi**, dan jam pada tabel **Perubahan kategori keseluruhan** |
| Paragraf kesimpulan 4.3 | Berapa dari 4 skenario yang **Sesuai**, dan penyebab bila ada yang tidak |

Yang dinilai adalah apakah kategori dan rekomendasi berubah mengikuti kondisi ruangan, bukan apakah
angka ppm-nya tepat.

---

## 2. Evaluasi UEQ (subbab 4.4)

1. Tentukan responden, disarankan paling sedikit 20 orang yang sesuai target pengguna (remaja sampai
   dewasa, misalnya mahasiswa). Catat cara memilihnya, misalnya *purposive sampling*.
2. Minta setiap responden mencoba aplikasi: melihat Beranda, membuka grafik di Detail Ruangan,
   memfilter dan mengunduh data di Riwayat, membaca Rekomendasi Tindakan, dan membaca Panduan Sistem.
   Bila tugasnya berbeda, sesuaikan kalimat di naskah.
3. Responden mengisi 26 butir di halaman **Kuesioner**. Data identitas untuk Tabel 4.9 (jenis
   kelamin, usia, status) dikumpulkan terpisah, misalnya lewat formulir singkat.
4. Setelah semua mengisi, jalankan:

   ```bash
   php artisan ueq:report
   ```

   - Kolom **Rerata**, **SD**, **Alpha**, dan **Benchmark** disalin ke Tabel 4.10 (pakai koma desimal).
   - Berkas `storage/app/ueq-export.csv` berisi jawaban mentah. Salin kolom `i1` sampai `i26` ke
     lembar Data pada UEQ Data Analysis Tool (unduh dari ueq-online.org), lalu salin rata-rata setiap
     butir ke Tabel 4.11.
5. Isi keempat paragraf `[ISI]` di bawah Tabel 4.11:
   - jumlah jawaban yang diolah (dan yang dikeluarkan, bila ada);
   - skala tertinggi dan terendah, penilaiannya (> 0,8 positif, −0,8 sampai 0,8 netral, < −0,8 negatif),
     dan kategori benchmark-nya;
   - nilai Cronbach's alpha (> 0,7 berarti konsisten);
   - butir dengan rata-rata terendah dan kaitannya dengan halaman atau fitur aplikasi.

---

## 3. Pembahasan (subbab 4.5) dan Bab V

| Lokasi | Diisi dengan |
|---|---|
| 4.5 Tujuan pertama | Ringkasan Tabel 4.7: apakah data diterima ± setiap 5 detik dan status tidak pernah Terputus |
| 4.5 Tujuan kedua | Ringkasan kolom Rekomendasi yang Tampil dan Kesesuaian Tabel 4.8 |
| 4.5 Tujuan ketiga | Ringkasan Tabel 4.8 dan Tabel 4.10, lalu nyatakan apakah tujuan ketiga tercapai |
| 4.5 Paragraf terakhir | Kaitan hasil UEQ dengan dua informasi tambahan aplikasi (penyebab utama dan saran) |
| 5.1 Kesimpulan 1–3 | Satu kalimat hasil yang sama dengan Bab IV; jangan memakai angka yang berbeda |
| 5.2 Saran 5 | Saran dari butir UEQ dengan rata-rata terendah |

---

## Daftar periksa sebelum dikirim ke pembimbing

- [ ] Tidak ada lagi teks `[ISI` di naskah (cari dengan Ctrl+F).
- [ ] Kata "Contoh" dan tanda kurung siku dari petunjuk sudah dihapus.
- [ ] Komentar petunjuk sudah dihapus (Tinjau > Hapus > Hapus Semua Komentar dalam Dokumen).
- [ ] Angka yang sama ditulis sama di Tabel 4.7, 4.8, 4.10, subbab 4.5, dan Bab V.
- [ ] Daftar Isi, Daftar Tabel, dan Daftar Gambar diperbarui (klik kanan > Perbarui Bidang > Perbarui seluruh tabel).

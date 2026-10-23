<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Indeks Kualitas Udara (KU)
|--------------------------------------------------------------------------
|
| KU dihitung dengan pendekatan SUB-INDEKS per parameter (mirip ISPU /
| US EPA AQI): setiap konsentrasi gas (ppm) dipetakan ke indeks 0–500
| melalui interpolasi linier sepotong-sepotong (piecewise-linear) di antara
| titik batas (breakpoint). Nilai KU = sub-indeks tertinggi, dan gas yang
| menghasilkannya disebut "parameter dominan". Konsentrasi di atas
| breakpoint terakhir dibatasi (clamp) pada indeks 500.
|
| PENTING: Nilai ambang batas di bawah ini merupakan usulan awal yang
| disusun dari berbagai sumber regulasi/pedoman. Seluruh ambang batas WAJIB
| dikonfirmasi dan disitasi bersama dosen pembimbing sebelum digunakan
| sebagai dasar kesimpulan penelitian.
|
| Format breakpoint: [konsentrasi_ppm, indeks], terurut naik.
|
*/

return [

    /*
    | Klasifikasi ambang per gas (naskah, Bab 3). Batas bersifat eksklusif (`<`):
    | nilai di bawah batas pertama = Baik, di bawah kedua = Sedang, di bawah
    | ketiga = Tidak Sehat, selebihnya Berbahaya. Satuan ppm.
    */
    'source' => [
        'label' => 'Solihat, S. M., Salsabila, S., & Susilawati, S. (2026). Sistem Pemantauan Kualitas Udara Berbasis ESP32 Menggunakan Sensor Gas MQ-135 dan MQ-2. JITET, 14(1), 1592–1599.',
        'doi' => '10.23960/jitet.v14i1.5568',
    ],

    'category_order' => ['Baik', 'Sedang', 'Tidak Sehat', 'Berbahaya'],

    'thresholds' => [
        'co2' => [50, 100, 150],
        'nh3' => [40, 80, 120],
        'co' => [20, 50, 80],
        'h2' => [15, 40, 60],
    ],

    'breakpoints' => [

        // Karbon monoksida (ppm, rata-rata 8 jam).
        // Sumber: US EPA AQI breakpoint CO 8-jam, Technical Assistance
        // Document for the Reporting of Daily Air Quality (EPA-454/B-18-007).
        'co' => [
            [0, 0],
            [4.4, 50],
            [9.4, 100],
            [12.4, 150],
            [15.4, 200],
            [30.4, 300],
            [50.4, 500],
        ],

        // Karbon dioksida (ppm, kualitas udara dalam ruang).
        // Sumber: 1000 ppm = Permenkes RI No. 1077/2011 & pedoman ASHRAE
        // (udara dalam ruang); 5000 ppm = OSHA PEL TWA;
        // 40000 ppm = NIOSH IDLH.
        'co2' => [
            [0, 0],
            [1000, 50],
            [2000, 150],
            [5000, 300],
            [40000, 500],
        ],

        // Amonia (ppm).
        // Sumber: 25 ppm = NIOSH REL TWA; 35 ppm = NIOSH STEL;
        // 50 ppm = OSHA PEL; 300 ppm = NIOSH IDLH.
        'nh3' => [
            [0, 0],
            [25, 50],
            [35, 150],
            [50, 300],
            [300, 500],
        ],

        // Hidrogen (ppm) — berbasis risiko mudah terbakar, BUKAN toksisitas.
        // Sumber: LEL (Lower Explosive Limit) H2 = 4% vol = 40000 ppm;
        // 4000 ppm = 10% LEL; 8000 ppm = 20% LEL; 40000 ppm = 100% LEL.
        'h2' => [
            [0, 0],
            [1000, 50],
            [4000, 150],
            [8000, 300],
            [40000, 500],
        ],
    ],

    // Batas atas indeks untuk setiap kategori status (inklusif).
    // Indeks di atas batas kategori terakhir dikategorikan "Berbahaya".
    'categories' => [
        'Baik' => 50,
        'Sedang' => 150,
        'Tidak Sehat' => 300,
    ],

    'fallback_category' => 'Berbahaya',

    // Indeks maksimum (clamp) untuk konsentrasi di atas breakpoint terakhir.
    'max_index' => 500,

    // Interval kirim firmware (detik); dipakai estimasi paket pada perhitungan QoS.
    'send_interval_seconds' => 5,

    // Perangkat dianggap daring bila data terakhir masuk dalam rentang ini (detik).
    'online_threshold_seconds' => 30,

];

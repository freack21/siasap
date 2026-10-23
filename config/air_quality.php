<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Klasifikasi Kualitas Udara
|--------------------------------------------------------------------------
|
| Kategori ditentukan per gas dari ambang di bawah ini (naskah Bab 3);
| kategori keseluruhan = kategori terburuk di antara keempat gas.
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

    // Interval kirim firmware (detik).
    'send_interval_seconds' => 5,

    // Perangkat dianggap daring bila data terakhir masuk dalam rentang ini (detik).
    'online_threshold_seconds' => 30,

];

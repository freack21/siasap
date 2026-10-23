<?php

/*
|--------------------------------------------------------------------------
| Rekomendasi Tindakan Preventif (rule-based)
|--------------------------------------------------------------------------
|
| Satu aturan per kategori keseluruhan kualitas udara. Teks mengikuti
| Tabel 3.3 naskah. `strategies` merujuk tiga strategi U.S. EPA Improving
| Indoor Air Quality: kendali_sumber, ventilasi, pembersihan_udara.
|
*/

return [

    'rules' => [
        'Baik' => [
            'level' => 'Normal',
            'text' => 'Kondisi udara relatif baik. Pertahankan ventilasi ruangan.',
            'strategies' => ['ventilasi'],
        ],
        'Sedang' => [
            'level' => 'Waspada',
            'text' => 'Tingkatkan sirkulasi udara, misalnya dengan membuka jendela atau pintu.',
            'strategies' => ['ventilasi'],
        ],
        'Tidak Sehat' => [
            'level' => 'Perlu Tindakan',
            'text' => 'Tingkatkan ventilasi, periksa dan kurangi sumber polutan seperti asap atau gas, serta kurangi aktivitas di dalam ruangan.',
            'strategies' => ['ventilasi', 'kendali_sumber'],
        ],
        'Berbahaya' => [
            'level' => 'Segera Bertindak',
            'text' => 'Segera tingkatkan ventilasi, hentikan sumber polutan, dan pertimbangkan meninggalkan ruangan sementara sampai kondisi udara membaik.',
            'strategies' => ['ventilasi', 'kendali_sumber'],
        ],
    ],

    'sources' => [
        'epa_iaq' => [
            'label' => 'U.S. EPA. Improving Indoor Air Quality.',
            'url' => 'https://www.epa.gov/indoor-air-quality-iaq/improving-indoor-air-quality',
        ],
        'epa_aqi' => [
            'label' => 'U.S. EPA. Technical Assistance Document for the Reporting of Daily Air Quality (EPA-403/B-26-003).',
            'url' => 'https://document.airnow.gov/technical-assistance-document-for-the-reporting-of-daily-air-quailty.pdf',
        ],
    ],

    'basis' => [
        'Tindakan mengacu pada tiga strategi U.S. EPA Improving Indoor Air Quality, yaitu kendali sumber, peningkatan ventilasi, dan pembersihan udara.',
        'Tingkat urgensi bertingkat mengikuti prinsip penyampaian AQI EPA (EPA-403/B-26-003, Tabel 5) bahwa anjuran makin tegas seiring kategori memburuk; hanya prinsipnya yang diadopsi, bukan angka AQI.',
    ],

    'disclaimer' => 'Rekomendasi bersifat informatif dan bukan diagnosis medis.',

];

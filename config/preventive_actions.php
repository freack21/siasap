<?php

/*
|--------------------------------------------------------------------------
| Rekomendasi Tindakan Preventif
|--------------------------------------------------------------------------
|
| Rekomendasi disusun per gas berdasarkan sub-indeks pembacaan terakhir.
|
| - CO: memakai 6 level AQI EPA beserta cautionary statement resmi (Tabel 5,
|   EPA-403/B-26-003). Breakpoint sub-indeks CO aplikasi identik dengan
|   breakpoint AQI EPA, sehingga levelnya dapat dibaca langsung. Pernyataan
|   EPA ditulis untuk udara luar ("hindari sumber CO seperti lalu lintas
|   padat"); di sini diadaptasi ke ruangan, dan langkah pengendalian sumber
|   diambil dari halaman EPA Indoor Air Quality tentang CO.
| - CO₂, NH₃, H₂: tidak termasuk AQI EPA. Langkah mengikuti strategi EPA
|   Indoor Air Quality (kendali sumber, ventilasi) per kategori KU aplikasi.
|   Langkah keselamatan H₂ didasarkan pada sifat mudah terbakarnya (LEL).
|
| Teks wajib dikonfirmasi bersama dosen pembimbing; ini bukan diagnosis medis.
|
*/

return [

    'sources' => [
        'epa_aqi' => [
            'label' => 'U.S. EPA (2026). Technical Assistance Document for the Reporting of Daily Air Quality – the AQI (EPA-403/B-26-003), Tabel 5.',
            'url' => 'https://document.airnow.gov/technical-assistance-document-for-the-reporting-of-daily-air-quailty.pdf',
        ],
        'epa_iaq_co' => [
            'label' => 'U.S. EPA. Carbon Monoxide\'s Impact on Indoor Air Quality.',
            'url' => 'https://www.epa.gov/indoor-air-quality-iaq/carbon-monoxides-impact-indoor-air-quality',
        ],
        'epa_iaq_improving' => [
            'label' => 'U.S. EPA. Improving Indoor Air Quality (kendali sumber, ventilasi, pembersih udara).',
            'url' => 'https://www.epa.gov/indoor-air-quality-iaq/improving-indoor-air-quality',
        ],
        'niosh_nh3' => [
            'label' => 'NIOSH Pocket Guide to Chemical Hazards: Ammonia.',
            'url' => 'https://www.cdc.gov/niosh/npg/npgd0028.html',
        ],
        'pubchem_h2' => [
            'label' => 'PubChem: Hydrogen (sifat mudah terbakar, LEL 4% vol).',
            'url' => 'https://pubchem.ncbi.nlm.nih.gov/compound/Hydrogen',
        ],
    ],

    /*
    | Sumber yang dikutip untuk setiap gas.
    */
    'gas_sources' => [
        'co' => ['epa_aqi', 'epa_iaq_co'],
        'co2' => ['epa_iaq_improving'],
        'nh3' => ['epa_iaq_improving', 'niosh_nh3'],
        'h2' => ['epa_iaq_improving', 'pubchem_h2'],
    ],

    /*
    | Level AQI EPA untuk CO (batas atas sub-indeks, inklusif; null = tanpa batas).
    | 'cautionary' adalah terjemahan cautionary statement CO pada Tabel 5.
    */
    'co_aqi_levels' => [
        [
            'key' => 'good',
            'max' => 50,
            'label' => 'Baik',
            'epa_label' => 'Good',
            'cautionary' => 'Kondisi baik untuk beraktivitas.',
        ],
        [
            'key' => 'moderate',
            'max' => 100,
            'label' => 'Sedang',
            'epa_label' => 'Moderate',
            'cautionary' => 'Kondisi masih baik untuk beraktivitas.',
        ],
        [
            'key' => 'usg',
            'max' => 150,
            'label' => 'Tidak Sehat bagi Kelompok Sensitif',
            'epa_label' => 'Unhealthy for Sensitive Groups',
            'cautionary' => 'Kelompok sensitif (penderita penyakit jantung): batasi aktivitas fisik berat dan hindari sumber CO.',
        ],
        [
            'key' => 'unhealthy',
            'max' => 200,
            'label' => 'Tidak Sehat',
            'epa_label' => 'Unhealthy',
            'cautionary' => 'Kelompok sensitif (penderita penyakit jantung): batasi aktivitas fisik sedang dan hindari sumber CO.',
        ],
        [
            'key' => 'very_unhealthy',
            'max' => 300,
            'label' => 'Sangat Tidak Sehat',
            'epa_label' => 'Very Unhealthy',
            'cautionary' => 'Kelompok sensitif (penderita penyakit jantung): hindari aktivitas fisik dan sumber CO.',
        ],
        [
            'key' => 'hazardous',
            'max' => null,
            'label' => 'Berbahaya',
            'epa_label' => 'Hazardous',
            'cautionary' => 'Kelompok sensitif (penderita penyakit jantung): hindari aktivitas fisik dan sumber CO. Orang lainnya: batasi aktivitas fisik berat.',
        ],
    ],

    /*
    | Langkah preventif. CO dikunci dengan level AQI EPA; gas lain dengan kategori KU.
    */
    'actions' => [
        'co' => [
            'moderate' => [
                'Periksa kompor gas dan peralatan pembakaran lain; pastikan terawat dan tersetel dengan benar.',
                'Nyalakan kipas penghisap (exhaust) yang dibuang ke luar ruangan saat memasak dengan kompor gas.',
            ],
            'usg' => [
                'Penderita penyakit jantung sebaiknya menghindari aktivitas fisik berat di ruangan ini.',
                'Matikan sumber CO yang tidak diperlukan, seperti pemanas tanpa saluran buang dan asap rokok.',
                'Buka jendela atau pintu sebagai ventilasi tambahan sementara.',
                'Pasang alarm CO di dekat ruang tidur bila belum ada.',
            ],
            'unhealthy' => [
                'Penderita penyakit jantung sebaiknya membatasi aktivitas fisik, termasuk aktivitas sedang.',
                'Matikan kompor, pemanas, atau alat pembakaran lain sampai kadar CO turun.',
                'Buka jendela dan pintu serta nyalakan exhaust untuk menambah pertukaran udara.',
                'Jangan menyalakan kendaraan atau genset di dalam atau di dekat ruangan.',
            ],
            'very_unhealthy' => [
                'Penderita penyakit jantung sebaiknya keluar dari ruangan dan menghindari aktivitas fisik.',
                'Matikan seluruh alat pembakaran dan maksimalkan ventilasi.',
                'Minta teknisi memeriksa kebocoran pada kompor, pemanas, cerobong, atau saluran buang.',
            ],
            'hazardous' => [
                'Bawa seluruh penghuni keluar ke udara segar sampai kadar CO turun.',
                'Matikan seluruh alat pembakaran dan buka ventilasi bila dapat dilakukan dengan aman.',
                'Segera cari pertolongan medis bila ada gejala pusing, sakit kepala, mual, atau linglung.',
                'Jangan gunakan ruangan sebelum sumber CO diperbaiki oleh teknisi.',
            ],
        ],
        'co2' => [
            'Sedang' => [
                'Tambah pertukaran udara dengan membuka jendela atau pintu secara berkala.',
                'Kurangi jumlah orang di ruangan bila memungkinkan.',
            ],
            'Tidak Sehat' => [
                'Buka jendela atau pintu dan nyalakan exhaust untuk memasukkan udara luar.',
                'Batasi jumlah orang dan lama aktivitas di ruangan tertutup.',
                'Periksa sistem ventilasi atau AC, termasuk filter dan saluran udara segar.',
            ],
            'Berbahaya' => [
                'Keluar dari ruangan dan maksimalkan ventilasi sebelum ruangan digunakan kembali.',
                'Periksa sumber CO₂ selain pernapasan, misalnya alat pembakaran atau tabung CO₂.',
            ],
        ],
        'nh3' => [
            'Sedang' => [
                'Cari dan singkirkan sumber amonia: pembersih berbahan amonia, sampah organik, atau kotoran hewan.',
                'Gunakan produk pembersih sesuai petunjuk label dan buka ventilasi saat membersihkan.',
            ],
            'Tidak Sehat' => [
                'Simpan wadah pembersih dalam keadaan tertutup dan jauh dari ruangan yang ditempati.',
                'Nyalakan exhaust atau buka jendela untuk membuang udara ke luar ruangan.',
                'Kurangi waktu berada di ruangan sampai bau amonia hilang.',
            ],
            'Berbahaya' => [
                'Tinggalkan ruangan dan buka seluruh akses udara.',
                'Jangan kembali sebelum sumber amonia disingkirkan dan kadar turun.',
                'Cari pertolongan medis bila mata, hidung, atau tenggorokan terasa perih atau sesak.',
            ],
        ],
        'h2' => [
            'Sedang' => [
                'Pastikan ruang pengisian aki atau baterai memiliki ventilasi yang baik.',
                'Periksa kemungkinan kebocoran gas di sekitar ruangan.',
            ],
            'Tidak Sehat' => [
                'Hentikan sementara pengisian aki atau baterai.',
                'Jauhkan api terbuka dan sumber percikan listrik.',
                'Buka ventilasi untuk mengencerkan gas.',
            ],
            'Berbahaya' => [
                'Jangan menyalakan saklar listrik, korek, atau api apa pun.',
                'Tinggalkan ruangan dan buka ventilasi dari luar bila aman.',
                'Hubungi petugas pemadam kebakaran atau teknisi.',
            ],
        ],
    ],

    /*
    | Anjuran pemeliharaan bila seluruh gas berada pada kategori Baik.
    */
    'maintenance' => [
        'Pertahankan ventilasi dan kebiasaan membuka jendela secara berkala.',
        'Rawat kompor dan peralatan pembakaran secara rutin agar tidak menjadi sumber CO.',
    ],

    'maintenance_sources' => ['epa_iaq_improving', 'epa_iaq_co'],

];

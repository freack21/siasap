<?php

/**
 * Konfigurasi User Experience Questionnaire (UEQ).
 *
 * Butir (26 pasangan atribut) diambil apa adanya dari berkas resmi
 * UEQ_Indonesian.pdf (ueq-online.org, Material/UEQ_All_Languages.zip;
 * terjemahan Santoso dkk.). Skala respons 1..7: 1 = istilah kiri, 7 = istilah kanan.
 */
return [

    'items' => [
        1 => ['menyusahkan', 'menyenangkan'],
        2 => ['tak dapat dipahami', 'dapat dipahami'],
        3 => ['kreatif', 'monoton'],
        4 => ['mudah dipelajari', 'sulit dipelajari'],
        5 => ['bermanfaat', 'kurang bermanfaat'],
        6 => ['membosankan', 'mengasyikkan'],
        7 => ['tidak menarik', 'menarik'],
        8 => ['tak dapat diprediksi', 'dapat diprediksi'],
        9 => ['cepat', 'lambat'],
        10 => ['berdaya cipta', 'konvensional'],
        11 => ['menghalangi', 'mendukung'],
        12 => ['baik', 'buruk'],
        13 => ['rumit', 'sederhana'],
        14 => ['tidak disukai', 'menggembirakan'],
        15 => ['lazim', 'terdepan'],
        16 => ['tidak nyaman', 'nyaman'],
        17 => ['aman', 'tidak aman'],
        18 => ['memotivasi', 'tidak memotivasi'],
        19 => ['memenuhi ekspektasi', 'tidak memenuhi ekspektasi'],
        20 => ['tidak efisien', 'efisien'],
        21 => ['jelas', 'membingungkan'],
        22 => ['tidak praktis', 'praktis'],
        23 => ['terorganisasi', 'berantakan'],
        24 => ['atraktif', 'tidak atraktif'],
        25 => ['ramah pengguna', 'tidak ramah pengguna'],
        26 => ['konservatif', 'inovatif'],
    ],

    /** Butir yang istilah positifnya berada di sisi kiri (nilai dibalik saat transformasi). */
    'reversed' => [3, 4, 5, 9, 10, 12, 17, 18, 19, 21, 23, 24, 25],

    'scales' => [
        'Attractiveness' => [1, 12, 14, 16, 24, 25],
        'Perspicuity' => [2, 4, 13, 21],
        'Efficiency' => [9, 20, 22, 23],
        'Dependability' => [8, 11, 17, 19],
        'Stimulation' => [5, 6, 7, 18],
        'Novelty' => [3, 10, 15, 26],
    ],

    /**
     * Benchmark UEQ (Schrepp, Hinderks & Thomaschewski, 2017).
     * Batas bawah rata-rata untuk: Excellent, Good, Above Average, Below Average.
     * Di bawah batas keempat = Bad.
     * Wajib dicocokkan dengan UEQ Handbook sebelum dikutip.
     */
    'benchmark' => [
        'Attractiveness' => [1.75, 1.52, 1.17, 0.70],
        'Perspicuity' => [1.90, 1.56, 1.08, 0.64],
        'Efficiency' => [1.78, 1.47, 0.98, 0.54],
        'Dependability' => [1.65, 1.48, 1.14, 0.78],
        'Stimulation' => [1.55, 1.31, 0.99, 0.50],
        'Novelty' => [1.40, 1.05, 0.71, 0.30],
    ],

    'references' => [
        'Laugwitz, B., Held, T., & Schrepp, M. (2008). Construction and evaluation of a user experience questionnaire.',
        'Schrepp, M., Hinderks, A., & Thomaschewski, J. (2017). Construction of a benchmark for the User Experience Questionnaire (UEQ).',
        'Santoso, H. B., Schrepp, M., Isal, R. Y. K., Utomo, A. Y., & Priyogi, B. (2016). Measuring user experience of the student-centered e-learning environment.',
    ],
];

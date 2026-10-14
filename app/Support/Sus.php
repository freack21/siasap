<?php

namespace App\Support;

/**
 * System Usability Scale (SUS) — Brooke (1996).
 *
 * Satu-satunya tempat untuk butir pernyataan, skala Likert, kelompok responden,
 * rumus skor, dan interpretasi (grade/adjective: Bangor dkk. 2009 & Sauro;
 * acceptability: Bangor dkk. 2008/2009). Semua method bersifat murni (pure).
 */
class Sus
{
    public const ITEM_COUNT = 10;

    public const MIN = 1;

    public const MAX = 5;

    /** Rata-rata skor SUS global sebagai acuan (Sauro). */
    public const AVERAGE_BENCHMARK = 68.0;

    /**
     * Butir SUS adaptasi bahasa Indonesia (Sharfina & Santoso, 2016),
     * disesuaikan menjadi "website ini". Butir ganjil positif, butir genap negatif.
     */
    public const ITEMS = [
        1 => 'Saya berpikir akan sering menggunakan website ini.',
        2 => 'Saya merasa website ini rumit untuk digunakan.',
        3 => 'Saya merasa website ini mudah digunakan.',
        4 => 'Saya membutuhkan bantuan dari orang lain atau teknisi dalam menggunakan website ini.',
        5 => 'Saya merasa fitur-fitur website ini berjalan dengan semestinya.',
        6 => 'Saya merasa ada banyak hal yang tidak konsisten (tidak serasi) pada website ini.',
        7 => 'Saya merasa orang lain akan memahami cara menggunakan website ini dengan cepat.',
        8 => 'Saya merasa website ini membingungkan.',
        9 => 'Saya merasa tidak ada hambatan dalam menggunakan website ini.',
        10 => 'Saya perlu membiasakan diri terlebih dahulu sebelum menggunakan website ini.',
    ];

    /** Skala Likert 5 poin. */
    public const SCALE = [
        1 => ['short' => 'STS', 'label' => 'Sangat Tidak Setuju'],
        2 => ['short' => 'TS', 'label' => 'Tidak Setuju'],
        3 => ['short' => 'N', 'label' => 'Netral'],
        4 => ['short' => 'S', 'label' => 'Setuju'],
        5 => ['short' => 'SS', 'label' => 'Sangat Setuju'],
    ];

    /** Kelompok responden uji coba produk. */
    public const GROUPS = [
        'pengguna' => 'Penghuni / Pengguna Ruangan',
        'ahli_media' => 'Ahli Media / Tampilan',
        'ahli_materi' => 'Ahli Materi / Kualitas Udara',
        'lainnya' => 'Lainnya',
    ];

    /**
     * Pita grade (urut dari batas bawah tertinggi). Bangor dkk. (2009), Sauro.
     * Skor >= min masuk ke pita tersebut.
     */
    public const GRADES = [
        ['grade' => 'A', 'min' => 80.3, 'adjective' => 'Excellent', 'label' => 'Sangat Baik', 'range' => '≥ 80,3'],
        ['grade' => 'B/C', 'min' => 68.0, 'adjective' => 'Good', 'label' => 'Baik', 'range' => '68 – 80,3'],
        ['grade' => 'D', 'min' => 51.0, 'adjective' => 'OK', 'label' => 'Cukup (Marginal)', 'range' => '51 – 68'],
        ['grade' => 'F', 'min' => 0.0, 'adjective' => 'Poor', 'label' => 'Buruk', 'range' => '< 51'],
    ];

    /** Rentang penerimaan (acceptability ranges), Bangor dkk. */
    public const ACCEPTABILITY = [
        ['key' => 'acceptable', 'min' => 70.0, 'label' => 'Acceptable', 'label_id' => 'Dapat Diterima', 'range' => '≥ 70'],
        ['key' => 'marginal', 'min' => 50.0, 'label' => 'Marginal', 'label_id' => 'Marginal', 'range' => '50 – 70'],
        ['key' => 'not_acceptable', 'min' => 0.0, 'label' => 'Not Acceptable', 'label_id' => 'Tidak Dapat Diterima', 'range' => '< 50'],
    ];

    public static function isPositive(int $item): bool
    {
        return $item % 2 === 1;
    }

    /**
     * Kontribusi satu butir (0–4): ganjil = skor − 1, genap = 5 − skor.
     * Menerima rata-rata (float) agar dapat dipakai untuk analisis per butir.
     */
    public static function contribution(int $item, float $value): float
    {
        return self::isPositive($item) ? $value - 1 : 5 - $value;
    }

    /**
     * Skor SUS 0–100 dari 10 jawaban (key 1..10 atau list 0..9).
     *
     * @param  array<int, int|string>  $answers
     */
    public static function score(array $answers): float
    {
        $answers = array_values($answers);

        if (count($answers) !== self::ITEM_COUNT) {
            throw new \InvalidArgumentException('SUS membutuhkan tepat 10 jawaban.');
        }

        $sum = 0.0;
        foreach ($answers as $index => $value) {
            $value = (int) $value;
            if ($value < self::MIN || $value > self::MAX) {
                throw new \InvalidArgumentException('Jawaban SUS harus bernilai 1 sampai 5.');
            }
            $sum += self::contribution($index + 1, $value);
        }

        return round($sum * 2.5, 2);
    }

    /**
     * @return array{grade: string, min: float, adjective: string, label: string, range: string}
     */
    public static function grade(float $score): array
    {
        foreach (self::GRADES as $band) {
            if ($score >= $band['min']) {
                return $band;
            }
        }

        return self::GRADES[array_key_last(self::GRADES)];
    }

    /**
     * @return array{key: string, min: float, label: string, label_id: string, range: string}
     */
    public static function acceptability(float $score): array
    {
        foreach (self::ACCEPTABILITY as $range) {
            if ($score >= $range['min']) {
                return $range;
            }
        }

        return self::ACCEPTABILITY[array_key_last(self::ACCEPTABILITY)];
    }

    public static function groupLabel(?string $group): string
    {
        return self::GROUPS[$group] ?? 'Lainnya';
    }

    /**
     * @param  array<int, float|int|string>  $values
     */
    public static function mean(array $values): ?float
    {
        if ($values === []) {
            return null;
        }

        return array_sum(array_map('floatval', $values)) / count($values);
    }

    /**
     * Simpangan baku sampel (n − 1). Null bila data kurang dari 2.
     *
     * @param  array<int, float|int|string>  $values
     */
    public static function standardDeviation(array $values): ?float
    {
        $n = count($values);
        if ($n < 2) {
            return null;
        }

        $mean = self::mean($values);
        $squares = array_sum(array_map(fn ($v) => ((float) $v - $mean) ** 2, $values));

        return sqrt($squares / ($n - 1));
    }
}

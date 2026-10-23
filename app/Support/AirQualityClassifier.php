<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Klasifikasi kategori kualitas udara berbasis ambang per gas.
 *
 * Ambang dan urutan kategori dibaca dari config/air_quality.php.
 */
class AirQualityClassifier
{
    /**
     * Kategori satu gas; batas ambang bersifat eksklusif (`< batas`).
     */
    public static function categoryFor(string $gas, float $ppm): string
    {
        $limits = config("air_quality.thresholds.$gas");

        if (! is_array($limits)) {
            throw new InvalidArgumentException("Gas tidak dikenal: $gas");
        }

        $order = config('air_quality.category_order');

        foreach ($limits as $index => $limit) {
            if ($ppm < $limit) {
                return $order[$index];
            }
        }

        return $order[count($limits)];
    }

    /**
     * @return array{categories: array{co2: string, nh3: string, co: string, h2: string}, overall: string, dominant: list<string>}
     */
    public static function classify(float $co2, float $nh3, float $co, float $h2): array
    {
        $categories = [
            'co2' => self::categoryFor('co2', $co2),
            'nh3' => self::categoryFor('nh3', $nh3),
            'co' => self::categoryFor('co', $co),
            'h2' => self::categoryFor('h2', $h2),
        ];

        $overall = config('air_quality.category_order')[max(array_map(self::rank(...), $categories))];

        return [
            'categories' => $categories,
            'overall' => $overall,
            'dominant' => $overall === 'Baik' ? [] : array_keys($categories, $overall, true),
        ];
    }

    /**
     * Peringkat kategori, 0 (Baik) sampai 3 (Berbahaya).
     */
    public static function rank(string $category): int
    {
        $rank = array_search($category, config('air_quality.category_order'), true);

        if ($rank === false) {
            throw new InvalidArgumentException("Kategori tidak dikenal: $category");
        }

        return $rank;
    }
}

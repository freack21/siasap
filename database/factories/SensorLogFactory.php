<?php

namespace Database\Factories;

use App\Models\SensorLog;
use App\Support\AirQualityClassifier;
use App\Support\Recommendation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory untuk pengujian: kategori selalu diturunkan dari nilai ppm lewat
 * AirQualityClassifier, sama seperti jalur API.
 *
 * @extends Factory<SensorLog>
 */
class SensorLogFactory extends Factory
{
    /**
     * Pembacaan default: seluruh gas pada kategori Baik.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return self::attributesFor(30, 20, 10, 5);
    }

    /**
     * Pembacaan dengan kadar gas tertentu (ppm); kategori diturunkan otomatis.
     */
    public function withGases(float $co2, float $nh3, float $co, float $h2): static
    {
        return $this->state(fn () => self::attributesFor($co2, $nh3, $co, $h2));
    }

    /**
     * @return array<string, mixed>
     */
    public static function attributesFor(float $co2, float $nh3, float $co, float $h2): array
    {
        $result = AirQualityClassifier::classify($co2, $nh3, $co, $h2);

        return [
            'co2' => $co2,
            'nh3' => $nh3,
            'co' => $co,
            'h2' => $h2,
            'recorded_at' => now(),
            'co2_category' => $result['categories']['co2'],
            'nh3_category' => $result['categories']['nh3'],
            'co_category' => $result['categories']['co'],
            'h2_category' => $result['categories']['h2'],
            'overall_category' => $result['overall'],
            'dominant_gases' => $result['dominant'] === [] ? null : implode(',', $result['dominant']),
            'recommendation_level' => Recommendation::forCategory($result['overall'])['level'],
        ];
    }
}

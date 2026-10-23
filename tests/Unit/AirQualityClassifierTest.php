<?php

namespace Tests\Unit;

use App\Support\AirQualityClassifier;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AirQualityClassifierTest extends TestCase
{
    /**
     * Tabel 3.8 naskah: [co2, nh3, co, h2, kategori keseluruhan].
     *
     * @return array<string, array{float, float, float, float, string}>
     */
    public static function tabel38Provider(): array
    {
        return [
            'baris 1' => [30, 20, 10, 5, 'Baik'],
            'baris 2' => [70, 30, 10, 5, 'Sedang'],
            'baris 3' => [70, 30, 55, 10, 'Tidak Sehat'],
            'baris 4' => [30, 30, 10, 45, 'Tidak Sehat'],
            'baris 5' => [40, 130, 10, 5, 'Berbahaya'],
            'baris 6' => [50, 20, 10, 5, 'Sedang'],
            'baris 7' => [150, 20, 10, 5, 'Berbahaya'],
        ];
    }

    #[DataProvider('tabel38Provider')]
    public function test_overall_category_follows_table_3_8(float $co2, float $nh3, float $co, float $h2, string $expected): void
    {
        $this->assertSame($expected, AirQualityClassifier::classify($co2, $nh3, $co, $h2)['overall']);
    }

    /**
     * @return array<string, array{string, float, string}>
     */
    public static function boundaryProvider(): array
    {
        $cases = [];
        $thresholds = [
            'co2' => [50, 100, 150],
            'nh3' => [40, 80, 120],
            'co' => [20, 50, 80],
            'h2' => [15, 40, 60],
        ];

        foreach ($thresholds as $gas => [$first, $second, $third]) {
            $cases["$gas di bawah $first"] = [$gas, $first - 0.01, 'Baik'];
            $cases["$gas tepat $first"] = [$gas, (float) $first, 'Sedang'];
            $cases["$gas di bawah $second"] = [$gas, $second - 0.01, 'Sedang'];
            $cases["$gas tepat $second"] = [$gas, (float) $second, 'Tidak Sehat'];
            $cases["$gas di bawah $third"] = [$gas, $third - 0.01, 'Tidak Sehat'];
            $cases["$gas tepat $third"] = [$gas, (float) $third, 'Berbahaya'];
        }

        return $cases;
    }

    #[DataProvider('boundaryProvider')]
    public function test_category_boundaries_are_exclusive_upper_limits(string $gas, float $ppm, string $expected): void
    {
        $this->assertSame($expected, AirQualityClassifier::categoryFor($gas, $ppm));
    }

    public function test_dominant_lists_all_worst_gases(): void
    {
        $result = AirQualityClassifier::classify(70, 30, 55, 45);

        $this->assertSame('Tidak Sehat', $result['overall']);
        $this->assertSame(['co', 'h2'], $result['dominant']);
        $this->assertSame(
            ['co2' => 'Sedang', 'nh3' => 'Baik', 'co' => 'Tidak Sehat', 'h2' => 'Tidak Sehat'],
            $result['categories'],
        );
    }

    public function test_dominant_empty_when_all_good(): void
    {
        $result = AirQualityClassifier::classify(30, 20, 10, 5);

        $this->assertSame('Baik', $result['overall']);
        $this->assertSame([], $result['dominant']);
    }

    public function test_rank_orders_categories(): void
    {
        $this->assertSame(0, AirQualityClassifier::rank('Baik'));
        $this->assertSame(3, AirQualityClassifier::rank('Berbahaya'));
    }

    public function test_unknown_gas_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AirQualityClassifier::categoryFor('o2', 10);
    }
}

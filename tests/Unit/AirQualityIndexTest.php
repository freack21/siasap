<?php

namespace Tests\Unit;

use App\Models\SensorLog;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AirQualityIndexTest extends TestCase
{
    public static function subIndexProvider(): array
    {
        return [
            // Nol
            'co nol' => ['co', 0, 0.0],
            'co2 nol' => ['co2', 0, 0.0],
            'nh3 nol' => ['nh3', 0, 0.0],
            'h2 nol' => ['h2', 0, 0.0],

            // Tepat pada breakpoint
            'co 4.4' => ['co', 4.4, 50.0],
            'co 9.4' => ['co', 9.4, 100.0],
            'co 30.4' => ['co', 30.4, 300.0],
            'co 50.4' => ['co', 50.4, 500.0],
            'co2 1000' => ['co2', 1000, 50.0],
            'co2 5000' => ['co2', 5000, 300.0],
            'nh3 25' => ['nh3', 25, 50.0],
            'nh3 35' => ['nh3', 35, 150.0],
            'h2 4000' => ['h2', 4000, 150.0],
            'h2 40000' => ['h2', 40000, 500.0],

            // Titik tengah antar breakpoint
            'co 6.9' => ['co', 6.9, 75.0],
            'co 40.4' => ['co', 40.4, 400.0],
            'co2 1500' => ['co2', 1500, 100.0],
            'co2 500' => ['co2', 500, 25.0],
            'nh3 30' => ['nh3', 30, 100.0],
            'h2 6000' => ['h2', 6000, 225.0],

            // Di atas breakpoint terakhir -> clamp 500
            'co clamp' => ['co', 100, 500.0],
            'co2 clamp' => ['co2', 100000, 500.0],
            'nh3 clamp' => ['nh3', 1000, 500.0],
            'h2 clamp' => ['h2', 50000, 500.0],
        ];
    }

    #[DataProvider('subIndexProvider')]
    public function test_sub_index_interpolation(string $gas, float $ppm, float $expected)
    {
        $this->assertEqualsWithDelta($expected, SensorLog::computeSubIndex($gas, $ppm), 0.0001);
    }

    public function test_unknown_gas_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);

        SensorLog::computeSubIndex('o3', 1);
    }

    public function test_compute_ku_returns_max_sub_index_and_dominant_gas()
    {
        $ku = SensorLog::computeKu(co2: 420, nh3: 30, co: 0.5, h2: 5);

        $this->assertSame(100.0, $ku['value']);
        $this->assertSame('Sedang', $ku['status']);
        $this->assertSame('nh3', $ku['dominant']);
        $this->assertSame(['co2', 'nh3', 'co', 'h2'], array_keys($ku['sub_indices']));
        $this->assertSame(21.0, $ku['sub_indices']['co2']);
    }

    public function test_compute_ku_value_is_backward_compatible()
    {
        $this->assertSame(500.0, SensorLog::computeKuValue(420, 1, 100, 5));
        $this->assertSame('Berbahaya', SensorLog::computeKuStatus(500));
    }

    public function test_status_thresholds_on_index()
    {
        $this->assertSame('Baik', SensorLog::computeKuStatus(50));
        $this->assertSame('Sedang', SensorLog::computeKuStatus(50.1));
        $this->assertSame('Sedang', SensorLog::computeKuStatus(150));
        $this->assertSame('Tidak Sehat', SensorLog::computeKuStatus(300));
        $this->assertSame('Berbahaya', SensorLog::computeKuStatus(300.1));
    }
}

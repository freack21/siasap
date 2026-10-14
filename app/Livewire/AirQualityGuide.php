<?php

namespace App\Livewire;

use App\Models\SensorLog;
use App\Support\AirQuality;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::public')]
#[Title('Panduan Kualitas Udara — SIASAP')]
class AirQualityGuide extends Component
{
    /** Contoh nilai pengukuran (ppm) untuk ilustrasi perhitungan Indeks KU. */
    public const EXAMPLE = ['co2' => 850.0, 'nh3' => 2.0, 'co' => 6.0, 'h2' => 120.0];

    /**
     * Konsentrasi (ppm) pada batas atas setiap kategori untuk satu gas,
     * dihitung dengan interpolasi balik dari breakpoint konfigurasi.
     *
     * @return array<string, float|null> [kategori => ppm batas atas]
     */
    public static function thresholds(string $gas): array
    {
        $breakpoints = config("air_quality.breakpoints.{$gas}", []);
        $result = [];

        foreach (config('air_quality.categories', []) as $status => $index) {
            $result[$status] = self::concentrationAt($breakpoints, (float) $index);
        }

        return $result;
    }

    /**
     * Konsentrasi (ppm) yang menghasilkan sub-indeks tertentu.
     *
     * @param  array<int, array{0: float|int, 1: float|int}>  $breakpoints
     */
    public static function concentrationAt(array $breakpoints, float $index): ?float
    {
        for ($i = 1; $i < count($breakpoints); $i++) {
            [$cLow, $iLow] = $breakpoints[$i - 1];
            [$cHigh, $iHigh] = $breakpoints[$i];

            if ($index >= $iLow && $index <= $iHigh) {
                if ($iHigh == $iLow) {
                    return (float) $cHigh;
                }

                return $cLow + ($index - $iLow) * ($cHigh - $cLow) / ($iHigh - $iLow);
            }
        }

        return null;
    }

    public function render()
    {
        $example = self::EXAMPLE;
        $result = SensorLog::computeKu($example['co2'], $example['nh3'], $example['co'], $example['h2']);

        $gases = AirQuality::gases();
        foreach ($gases as $key => $gas) {
            $gases[$key]['thresholds'] = self::thresholds($key);
        }

        return view('livewire.air-quality-guide', [
            'categories' => AirQuality::categories(),
            'gases' => $gases,
            'example' => $example,
            'result' => $result,
            'onlineSeconds' => AirQuality::onlineThresholdSeconds(),
            'sendInterval' => (int) config('air_quality.send_interval_seconds', 5),
        ]);
    }
}

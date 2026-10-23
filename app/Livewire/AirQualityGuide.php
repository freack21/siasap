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

    /**
     * Level AQI EPA untuk CO beserta rentang indeks dan kategori KU aplikasi
     * pada rentang yang sama (dipakai untuk warna penanda).
     *
     * @return list<array{key: string, label: string, epa_label: string, cautionary: string, range: string, ku_status: string}>
     */
    public static function coAqiLevels(): array
    {
        $levels = [];
        $previousMax = null;

        foreach (config('preventive_actions.co_aqi_levels', []) as $level) {
            $min = $previousMax === null ? 0 : $previousMax + 1;

            $levels[] = [
                'key' => $level['key'],
                'label' => $level['label'],
                'epa_label' => $level['epa_label'],
                'cautionary' => $level['cautionary'],
                'range' => $level['max'] === null ? '≥ '.$min : $min.' – '.$level['max'],
                'ku_status' => SensorLog::computeKuStatus((float) $min),
            ];

            $previousMax = $level['max'];
        }

        return $levels;
    }

    /**
     * Contoh langkah preventif untuk tingkat paling serius setiap gas.
     *
     * @return array<string, list<string>> [gas => langkah]
     */
    public static function preventiveExamples(int $limit = 3): array
    {
        $examples = [];

        foreach (array_keys(AirQuality::gases()) as $gas) {
            $key = $gas === 'co' ? 'hazardous' : 'Berbahaya';
            $examples[$gas] = array_slice(config("preventive_actions.actions.{$gas}.{$key}", []), 0, $limit);
        }

        return $examples;
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
            'coAqiLevels' => self::coAqiLevels(),
            'preventiveExamples' => self::preventiveExamples(),
            'preventiveSources' => config('preventive_actions.sources', []),
        ]);
    }
}

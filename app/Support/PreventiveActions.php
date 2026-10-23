<?php

namespace App\Support;

use App\Models\SensorLog;

/**
 * Menyusun rekomendasi tindakan preventif dari pembacaan gas terakhir.
 *
 * CO memakai level AQI EPA (sub-indeks CO aplikasi identik dengan AQI EPA);
 * CO₂, NH₃, dan H₂ memakai kategori KU aplikasi dengan langkah dari panduan
 * EPA Indoor Air Quality. Teks dan sumber ada di config/preventive_actions.php.
 */
class PreventiveActions
{
    /**
     * Level AQI EPA untuk sub-indeks CO.
     *
     * @return array{key: string, max: int|null, label: string, epa_label: string, cautionary: string}
     */
    public static function coAqiLevel(float $subIndex): array
    {
        $levels = config('preventive_actions.co_aqi_levels');

        foreach ($levels as $level) {
            if ($level['max'] === null || $subIndex <= $level['max']) {
                return $level;
            }
        }

        return end($levels);
    }

    /**
     * @return array{
     *     all_good: bool,
     *     items: list<array{gas: string, sub_index: float, category: string, level: string, level_label: string, cautionary: string|null, actions: list<string>, sources: list<array{label: string, url: string}>}>,
     *     maintenance: list<string>,
     *     maintenance_sources: list<array{label: string, url: string}>
     * }
     */
    public static function forReading(float $co2, float $nh3, float $co, float $h2): array
    {
        $readings = ['co2' => $co2, 'nh3' => $nh3, 'co' => $co, 'h2' => $h2];
        $items = [];

        foreach ($readings as $gas => $ppm) {
            $subIndex = SensorLog::computeSubIndex($gas, $ppm);
            $category = SensorLog::computeKuStatus($subIndex);

            if ($category === 'Baik') {
                continue;
            }

            $items[] = $gas === 'co'
                ? self::coItem($subIndex, $category)
                : self::categoryItem($gas, $subIndex, $category);
        }

        usort($items, fn (array $a, array $b): int => $b['sub_index'] <=> $a['sub_index']);

        return [
            'all_good' => $items === [],
            'items' => $items,
            'maintenance' => config('preventive_actions.maintenance'),
            'maintenance_sources' => self::sources(config('preventive_actions.maintenance_sources')),
        ];
    }

    /**
     * @return array{all_good: bool, items: list<array<string, mixed>>, maintenance: list<string>, maintenance_sources: list<array{label: string, url: string}>}
     */
    public static function forLog(SensorLog $log): array
    {
        return self::forReading($log->co2, $log->nh3, $log->co, $log->h2);
    }

    /**
     * @return array{gas: string, sub_index: float, category: string, level: string, level_label: string, cautionary: string|null, actions: list<string>, sources: list<array{label: string, url: string}>}
     */
    private static function coItem(float $subIndex, string $category): array
    {
        $level = self::coAqiLevel($subIndex);

        return [
            'gas' => 'co',
            'sub_index' => round($subIndex, 1),
            'category' => $category,
            'level' => $level['key'],
            'level_label' => $level['label'].' ('.$level['epa_label'].')',
            'cautionary' => $level['cautionary'],
            'actions' => config("preventive_actions.actions.co.{$level['key']}", []),
            'sources' => self::sources(config('preventive_actions.gas_sources.co')),
        ];
    }

    /**
     * @return array{gas: string, sub_index: float, category: string, level: string, level_label: string, cautionary: string|null, actions: list<string>, sources: list<array{label: string, url: string}>}
     */
    private static function categoryItem(string $gas, float $subIndex, string $category): array
    {
        return [
            'gas' => $gas,
            'sub_index' => round($subIndex, 1),
            'category' => $category,
            'level' => $category,
            'level_label' => $category,
            'cautionary' => null,
            'actions' => config("preventive_actions.actions.{$gas}.{$category}", []),
            'sources' => self::sources(config("preventive_actions.gas_sources.{$gas}")),
        ];
    }

    /**
     * @param  list<string>  $keys
     * @return list<array{label: string, url: string}>
     */
    private static function sources(array $keys): array
    {
        return array_map(fn (string $key): array => config("preventive_actions.sources.{$key}"), $keys);
    }
}

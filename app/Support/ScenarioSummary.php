<?php

namespace App\Support;

use App\Models\SensorLog;
use Illuminate\Support\Collection;

/**
 * Ringkasan data sensor satu skenario pengujian ruangan: jumlah data, selang antardata,
 * kategori dominan dan sebarannya, nilai ppm setiap gas, serta perubahan kategori keseluruhan.
 */
class ScenarioSummary
{
    /**
     * Ringkas data satu skenario (urut waktu terima server).
     *
     * Kategori dominan adalah kategori keseluruhan yang paling sering muncul; bila seri, dipilih
     * kategori yang lebih buruk. Perubahan kategori memuat data pertama dan setiap data yang
     * kategori keseluruhannya berbeda dari data sebelumnya.
     *
     * @param  Collection<int, SensorLog>  $logs
     * @return array{count: int, interval_avg: ?float, interval_max: ?int, dominant: ?array{category: string, count: int}, spread: array<string, int>, gases: array<string, array{symbol: string, first: float, min: float, max: float, last: float}>, changes: list<array{time: string, category: string, cause: string, level: string}>, levels: list<string>}
     */
    public static function summarize(Collection $logs): array
    {
        $logs = $logs->values();

        if ($logs->isEmpty()) {
            return ['count' => 0, 'interval_avg' => null, 'interval_max' => null, 'dominant' => null, 'spread' => [], 'gases' => [], 'changes' => [], 'levels' => []];
        }

        $gaps = $logs->count() < 2
            ? collect()
            : $logs->sliding(2)->map(fn (Collection $pair) => (int) $pair->first()->created_at->diffInSeconds($pair->last()->created_at));

        $counts = $logs->countBy('overall_category');
        $dominant = $counts->keys()
            ->sortByDesc(fn (string $category) => [$counts[$category], AirQualityClassifier::rank($category)])
            ->first();

        $changes = self::categoryChanges($logs);

        return [
            'count' => $logs->count(),
            'interval_avg' => $gaps->isEmpty() ? null : (float) $gaps->avg(),
            'interval_max' => $gaps->isEmpty() ? null : (int) $gaps->max(),
            'dominant' => ['category' => $dominant, 'count' => $counts[$dominant]],
            'spread' => collect(config('air_quality.category_order'))
                ->filter(fn (string $category) => $counts->has($category))
                ->mapWithKeys(fn (string $category) => [$category => $counts[$category]])
                ->all(),
            'gases' => collect(AirQuality::gases())
                ->map(fn (array $gas, string $key) => [
                    'symbol' => $gas['symbol'],
                    'first' => (float) $logs->first()->{$key},
                    'min' => (float) $logs->min($key),
                    'max' => (float) $logs->max($key),
                    'last' => (float) $logs->last()->{$key},
                ])->all(),
            'changes' => $changes,
            'levels' => collect($changes)->pluck('level')->unique()->values()->all(),
        ];
    }

    /**
     * Simbol gas penyebab utama satu data, atau "-" bila seluruh gas Baik.
     */
    public static function causeLabel(SensorLog $log): string
    {
        return collect($log->dominantGases())
            ->map(fn (string $gas) => AirQuality::gases()[$gas]['symbol'] ?? $gas)
            ->implode(', ') ?: '-';
    }

    /**
     * @param  Collection<int, SensorLog>  $logs
     * @return list<array{time: string, category: string, cause: string, level: string}>
     */
    private static function categoryChanges(Collection $logs): array
    {
        $previous = null;

        return $logs->filter(function (SensorLog $log) use (&$previous) {
            $changed = $log->overall_category !== $previous;
            $previous = $log->overall_category;

            return $changed;
        })->map(fn (SensorLog $log) => [
            'time' => $log->created_at->format('H:i:s'),
            'category' => $log->overall_category,
            'cause' => self::causeLabel($log),
            'level' => $log->recommendation_level,
        ])->values()->all();
    }
}

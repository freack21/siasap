<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Perhitungan User Experience Questionnaire (UEQ): transformasi −3..+3,
 * rata-rata skala, ringkasan (rerata, SD, Cronbach's alpha), dan kategori benchmark.
 */
class Ueq
{
    public const ITEM_COUNT = 26;

    public const MIN = 1;

    public const MAX = 7;

    /**
     * Ubah 26 jawaban (1..7, key 1..26 atau list 0..25) menjadi nilai −3..+3
     * dengan membalik butir pada `ueq.reversed`.
     *
     * @param  array<int, int|string>  $answers
     * @return array<int, int>
     */
    public static function transform(array $answers): array
    {
        $answers = array_values($answers);

        if (count($answers) !== self::ITEM_COUNT) {
            throw new InvalidArgumentException('UEQ membutuhkan tepat '.self::ITEM_COUNT.' jawaban.');
        }

        $reversed = config('ueq.reversed');
        $result = [];

        foreach ($answers as $index => $value) {
            if (! is_numeric($value) || (int) $value != $value || $value < self::MIN || $value > self::MAX) {
                throw new InvalidArgumentException('Jawaban UEQ harus bernilai 1 sampai 7.');
            }

            $item = $index + 1;
            $centered = (int) $value - 4;
            $result[$item] = in_array($item, $reversed, true) ? -$centered : $centered;
        }

        return $result;
    }

    /**
     * @param  array<int, int|string>  $answers
     * @return array<string, float>
     */
    public static function scaleMeans(array $answers): array
    {
        $transformed = self::transform($answers);
        $means = [];

        foreach (config('ueq.scales') as $scale => $items) {
            $means[$scale] = array_sum(array_map(fn (int $i) => $transformed[$i], $items)) / count($items);
        }

        return $means;
    }

    /**
     * @param  iterable<array<int, int|string>|object{answers: array<int, int|string>}>  $responses
     * @return array{count: int, scales: array<string, array{mean: float|null, sd: float|null, alpha: float|null, benchmark: string|null}>}
     */
    public static function summary(iterable $responses): array
    {
        $transformed = [];
        foreach ($responses as $response) {
            $answers = is_array($response) ? $response : $response->answers;
            $transformed[] = self::transform($answers);
        }

        $count = count($transformed);
        $scales = [];

        foreach (config('ueq.scales') as $scale => $items) {
            if ($count === 0) {
                $scales[$scale] = ['mean' => null, 'sd' => null, 'alpha' => null, 'benchmark' => null];

                continue;
            }

            $respondentMeans = array_map(
                fn (array $t) => array_sum(array_map(fn (int $i) => $t[$i], $items)) / count($items),
                $transformed,
            );
            $mean = array_sum($respondentMeans) / $count;

            $scales[$scale] = [
                'mean' => $mean,
                'sd' => self::standardDeviation($respondentMeans),
                'alpha' => self::cronbachAlpha($transformed, $items),
                'benchmark' => self::benchmarkCategory($scale, $mean),
            ];
        }

        return ['count' => $count, 'scales' => $scales];
    }

    public static function benchmarkCategory(string $scale, float $mean): string
    {
        $bounds = config("ueq.benchmark.{$scale}");

        if ($bounds === null) {
            throw new InvalidArgumentException("Skala UEQ tidak dikenal: {$scale}.");
        }

        $categories = ['Excellent', 'Good', 'Above Average', 'Below Average'];

        foreach ($categories as $index => $category) {
            if (round($mean, 2) >= $bounds[$index]) {
                return $category;
            }
        }

        return 'Bad';
    }

    /**
     * @param  array<int, float|int>  $values
     */
    private static function standardDeviation(array $values): ?float
    {
        $n = count($values);
        if ($n < 2) {
            return null;
        }

        $mean = array_sum($values) / $n;
        $squares = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values));

        return sqrt($squares / ($n - 1));
    }

    /**
     * Cronbach's alpha untuk butir-butir satu skala (nilai sudah ditransformasi).
     * Null bila responden < 2, butir < 2, atau varians total nol.
     *
     * @param  array<int, array<int, int>>  $transformed
     * @param  array<int, int>  $items
     */
    private static function cronbachAlpha(array $transformed, array $items): ?float
    {
        $n = count($transformed);
        $k = count($items);

        if ($n < 2 || $k < 2) {
            return null;
        }

        $itemVariances = 0.0;
        foreach ($items as $item) {
            $itemVariances += self::standardDeviation(array_column($transformed, $item)) ** 2;
        }

        $totals = array_map(fn (array $t) => array_sum(array_map(fn (int $i) => $t[$i], $items)), $transformed);
        $totalVariance = self::standardDeviation($totals) ** 2;

        if ($totalVariance == 0.0) {
            return null;
        }

        return ($k / ($k - 1)) * (1 - $itemVariances / $totalVariance);
    }
}

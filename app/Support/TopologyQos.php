<?php

namespace App\Support;

use App\Models\TopologyTestLog;
use Illuminate\Support\Collection;

/**
 * Ringkasan kualitas layanan jaringan (QoS) data uji topologi per perangkat dan topologi.
 */
class TopologyQos
{
    /**
     * Penurunan seq_number sampai sebesar ini dianggap paket datang tidak berurutan (mis. salinan
     * lewat relay tiba belakangan), bukan perangkat dinyalakan ulang.
     */
    public const REORDER_TOLERANCE = 5;

    /**
     * Ringkas data satu perangkat pada satu topologi (data sudah urut waktu terima).
     *
     * PDR dihitung dari seq_number: paket yang diharapkan = rentang seq setiap sesi, dengan
     * sesi baru dimulai saat seq turun lebih dari REORDER_TOLERANCE (perangkat dinyalakan
     * ulang). Paket ber-seq sama dalam satu sesi dihitung sekali sebagai paket unik; sisanya
     * duplikat.
     *
     * @param  Collection<int, TopologyTestLog>  $logs
     * @return array{received: int, unique: int, expected: ?int, duplicates: int, pdr: ?float, loss: ?float, sessions: int, rssi_avg: ?float, rssi_min: ?int, hop_avg: ?float, interval_avg: ?float, latency_avg: ?float}
     */
    public static function summarize(Collection $logs): array
    {
        $logs = $logs->values();
        [$unique, $expected, $sessions] = self::sequenceStats($logs->pluck('seq_number')->filter(fn ($seq) => $seq !== null)->values());

        $rssi = $logs->pluck('rssi')->filter(fn ($value) => $value !== null);
        $hops = $logs->pluck('hop_count')->filter(fn ($value) => $value !== null);

        $gaps = $logs->sliding(2)->map(fn (Collection $pair) => $pair->first()->created_at->diffInSeconds($pair->last()->created_at));
        $latencies = $logs->map(fn (TopologyTestLog $log) => $log->recorded_at->diffInSeconds($log->created_at, false));

        $pdr = $expected > 0 ? round($unique / $expected * 100, 1) : null;

        return [
            'received' => $logs->count(),
            'unique' => $unique,
            'expected' => $expected > 0 ? $expected : null,
            'duplicates' => $logs->pluck('seq_number')->filter(fn ($seq) => $seq !== null)->count() - $unique,
            'pdr' => $pdr,
            'loss' => $pdr === null ? null : round(100 - $pdr, 1),
            'sessions' => $sessions,
            'rssi_avg' => $rssi->isEmpty() ? null : round($rssi->avg(), 1),
            'rssi_min' => $rssi->isEmpty() ? null : (int) $rssi->min(),
            'hop_avg' => $hops->isEmpty() ? null : round($hops->avg(), 2),
            'interval_avg' => $gaps->isEmpty() ? null : round($gaps->avg(), 1),
            'latency_avg' => $latencies->isEmpty() ? null : round($latencies->avg(), 1),
        ];
    }

    /**
     * Jumlah seq unik, jumlah paket yang diharapkan, dan jumlah sesi dari deret seq_number.
     *
     * @param  Collection<int, int>  $sequence
     * @return array{0: int, 1: int, 2: int}
     */
    private static function sequenceStats(Collection $sequence): array
    {
        $unique = 0;
        $expected = 0;
        $sessions = 0;
        $seen = [];
        $min = null;
        $max = null;
        $previous = null;

        $closeSession = function () use (&$unique, &$expected, &$seen, &$min, &$max): void {
            if ($min !== null) {
                $unique += count($seen);
                $expected += $max - $min + 1;
            }
            $seen = [];
            $min = null;
            $max = null;
        };

        foreach ($sequence as $seq) {
            if ($previous === null || $seq < $previous - self::REORDER_TOLERANCE) {
                $closeSession();
                $sessions++;
                $previous = $seq;
            }
            $seen[$seq] = true;
            $min = $min === null ? $seq : min($min, $seq);
            $max = $max === null ? $seq : max($max, $seq);
            $previous = max($previous, $seq);
        }
        $closeSession();

        return [$unique, $expected, $sessions];
    }
}

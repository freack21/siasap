<?php

namespace App\Support;

use App\Models\Node;
use App\Models\TestingSession;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Perhitungan metrik QoS jaringan (PDR, packet loss, RSSI, hop count)
 * per topologi, berbasis sesi pengujian.
 *
 * Metodologi:
 * - Hanya log yang terikat pada sesi pengujian (testing_session_id NOT NULL)
 *   yang dihitung. Log di luar sesi bukan bagian dari eksperimen.
 * - Perhitungan dilakukan per pasangan (sesi, node), lalu received/expected
 *   DIJUMLAHKAN per topologi, sehingga rentang seq dari sesi berbeda tidak
 *   pernah tercampur.
 * - Paket duplikat (seq sama yang diterima lebih dari sekali dalam jendela
 *   waktu pendek, mis. langsung + via relay) dihitung satu kali.
 * - Reboot node (seq kembali ke angka kecil) memulai segmen baru:
 *   expected = Σ (max - min + 1) per segmen, received = Σ seq unik per segmen.
 * - Node yang tidak mengirim satu paket pun dalam sesi tetap dihitung dengan
 *   expected yang diestimasi dari durasi sesi / interval kirim (PDR 0%).
 * - RSSI/hop dihitung per paket unik (penerimaan pertama); RSSI NULL
 *   (tidak terukur) dikecualikan dari rata-rata & simpangan baku.
 */
class QosCalculator
{
    /**
     * Penurunan seq yang belum pernah terlihat dan selisihnya <= nilai ini
     * dianggap paket yang tiba tidak berurutan (out-of-order), bukan reboot.
     */
    public const REORDER_WINDOW = 3;

    public static function sendIntervalSeconds(): int
    {
        return max(1, (int) config('air_quality.send_interval_seconds', 5));
    }

    /**
     * Hitung metrik QoS untuk satu topologi (opsional dibatasi satu sesi).
     */
    public static function forTopology(string $topology, ?int $sessionId = null, ?CarbonInterface $now = null): array
    {
        $now ??= Carbon::now();
        $interval = self::sendIntervalSeconds();

        // Sesi yang relevan: sesi bertopologi ini, atau sesi yang memiliki log bertopologi ini.
        $sessionIdsFromLogs = DB::table('sensor_logs')
            ->where('topology_type', $topology)
            ->whereNotNull('testing_session_id')
            ->when($sessionId, fn ($q) => $q->where('testing_session_id', $sessionId))
            ->distinct()
            ->pluck('testing_session_id');

        $sessions = TestingSession::query()
            ->when($sessionId, fn ($q) => $q->whereKey($sessionId))
            ->where(fn ($q) => $q->where('topology_type', $topology)->orWhereIn('id', $sessionIdsFromLogs))
            ->orderBy('id')
            ->get();

        $result = self::emptyResult();
        $result['sessions_count'] = $sessions->count();

        if ($sessions->isEmpty()) {
            return $result;
        }

        $logsBySessionNode = DB::table('sensor_logs')
            ->select(['id', 'testing_session_id', 'node_id', 'seq_number', 'rssi', 'hop_count', 'created_at'])
            ->where('topology_type', $topology)
            ->whereIn('testing_session_id', $sessions->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($row) => $row->testing_session_id.':'.$row->node_id);

        $nodes = Node::query()->get(['id', 'created_at']);

        $perNode = [];
        $allRssi = [];
        $allHops = [];

        foreach ($sessions as $session) {
            $sessionEnd = $session->ended_at ?? $now;

            // Node kandidat: semua node terdaftar (yang sudah ada saat sesi berjalan) + node yang muncul di log.
            $nodeIds = $nodes
                ->filter(fn ($n) => $n->created_at === null || $n->created_at->lte($sessionEnd))
                ->pluck('id')
                ->all();
            foreach ($logsBySessionNode->keys() as $key) {
                [$sid, $nid] = explode(':', $key);
                if ((int) $sid === $session->id) {
                    $nodeIds[] = (int) $nid;
                }
            }
            $nodeIds = array_values(array_unique(array_map('intval', $nodeIds)));
            sort($nodeIds);

            foreach ($nodeIds as $nodeId) {
                $rows = $logsBySessionNode->get($session->id.':'.$nodeId);

                $acc = $perNode[$nodeId] ?? [
                    'received' => 0,
                    'expected' => 0,
                    'expected_estimated' => false,
                    'rssi' => [],
                    'hops' => [],
                ];

                if ($rows === null || $rows->isEmpty()) {
                    // Node tidak mengirim satu paket pun → estimasi expected dari durasi sesi.
                    $acc['expected'] += self::estimateExpected($session->started_at, $sessionEnd, $interval);
                    $acc['expected_estimated'] = true;
                } else {
                    $stats = self::analyzeNodeRows($rows->all(), $interval);
                    $acc['received'] += $stats['received'];
                    $acc['expected'] += $stats['expected'];
                    array_push($acc['rssi'], ...$stats['rssi']);
                    array_push($acc['hops'], ...$stats['hops']);
                }

                $perNode[$nodeId] = $acc;
            }
        }

        ksort($perNode);

        $totalReceived = 0;
        $totalExpected = 0;
        $anyEstimated = false;

        foreach ($perNode as $nodeId => $acc) {
            $totalReceived += $acc['received'];
            $totalExpected += $acc['expected'];
            $anyEstimated = $anyEstimated || $acc['expected_estimated'];
            array_push($allRssi, ...$acc['rssi']);
            array_push($allHops, ...$acc['hops']);

            [$pdr, $loss] = self::ratios($acc['received'], $acc['expected']);

            $result['per_node'][$nodeId] = [
                'received' => $acc['received'],
                'expected' => $acc['expected'],
                'pdr' => $pdr,
                'loss_rate' => $loss,
                'avg_rssi' => self::round(self::mean($acc['rssi']), 1),
                'std_rssi' => self::round(self::sampleStdDev($acc['rssi']), 2),
                'rssi_samples' => count($acc['rssi']),
                'avg_hop' => self::round(self::mean($acc['hops']), 2),
                'expected_estimated' => $acc['expected_estimated'],
            ];
        }

        [$pdr, $loss] = self::ratios($totalReceived, $totalExpected);

        $result['total_received'] = $totalReceived;
        $result['expected_packets'] = $totalExpected;
        $result['pdr'] = $pdr;
        $result['loss_rate'] = $loss;
        $result['avg_rssi'] = self::round(self::mean($allRssi), 1);
        $result['std_rssi'] = self::round(self::sampleStdDev($allRssi), 2);
        $result['rssi_samples'] = count($allRssi);
        $result['avg_hop'] = self::round(self::mean($allHops), 2);
        $result['has_estimated'] = $anyEstimated;

        return $result;
    }

    /**
     * Analisis log satu (sesi, node), terurut berdasarkan id (urutan kedatangan).
     *
     * @param  array<int, object>  $rows
     * @return array{received:int, expected:int, rssi:array<int,float>, hops:array<int,float>}
     */
    public static function analyzeNodeRows(array $rows, int $dupWindowSeconds): array
    {
        $segments = [];
        $current = null; // ['seen' => [seq => timestamp|null], 'min' => int, 'max' => int]
        $prevSeq = null;
        $rssi = [];
        $hops = [];

        foreach ($rows as $row) {
            $seq = (int) $row->seq_number;
            $ts = $row->created_at !== null ? Carbon::parse($row->created_at)->getTimestamp() : null;

            if ($current !== null && array_key_exists($seq, $current['seen'])) {
                $firstTs = $current['seen'][$seq];
                $isDuplicate = $ts === null || $firstTs === null || abs($ts - $firstTs) <= $dupWindowSeconds;
                if ($isDuplicate) {
                    // Paket yang sama diterima lagi (mis. langsung + via relay) → abaikan.
                    continue;
                }
                // Seq yang sama muncul jauh setelahnya → node reboot, segmen baru.
                $segments[] = $current;
                $current = null;
            } elseif ($current !== null && $prevSeq !== null && $seq < $prevSeq && ($prevSeq - $seq) > self::REORDER_WINDOW) {
                // Seq turun tajam → node reboot (counter direset), segmen baru.
                $segments[] = $current;
                $current = null;
            }

            if ($current === null) {
                $current = ['seen' => [], 'min' => $seq, 'max' => $seq];
            }

            $current['seen'][$seq] = $ts;
            $current['min'] = min($current['min'], $seq);
            $current['max'] = max($current['max'], $seq);
            $prevSeq = $seq;

            if ($row->rssi !== null) {
                $rssi[] = (float) $row->rssi;
            }
            if ($row->hop_count !== null) {
                $hops[] = (float) $row->hop_count;
            }
        }

        if ($current !== null) {
            $segments[] = $current;
        }

        $received = 0;
        $expected = 0;
        foreach ($segments as $segment) {
            $received += count($segment['seen']);
            $expected += $segment['max'] - $segment['min'] + 1;
        }

        return [
            'received' => $received,
            'expected' => max($expected, $received),
            'rssi' => $rssi,
            'hops' => $hops,
        ];
    }

    public static function estimateExpected(?CarbonInterface $startedAt, CarbonInterface $endedAt, int $interval): int
    {
        if ($startedAt === null) {
            return 0;
        }

        $duration = $endedAt->getTimestamp() - $startedAt->getTimestamp();

        return $duration > 0 ? intdiv($duration, max(1, $interval)) : 0;
    }

    /**
     * @param  array<int, float>  $values
     */
    public static function mean(array $values): ?float
    {
        return $values === [] ? null : array_sum($values) / count($values);
    }

    /**
     * Simpangan baku sampel (pembagi n - 1). Null bila n < 2.
     *
     * @param  array<int, float>  $values
     */
    public static function sampleStdDev(array $values): ?float
    {
        $n = count($values);
        if ($n < 2) {
            return null;
        }

        $mean = array_sum($values) / $n;
        $sumSq = 0.0;
        foreach ($values as $v) {
            $sumSq += ($v - $mean) ** 2;
        }

        return sqrt($sumSq / ($n - 1));
    }

    private static function ratios(int $received, int $expected): array
    {
        if ($expected <= 0) {
            return [null, null];
        }

        $pdr = round($received / $expected * 100, 1);

        return [$pdr, round(100 - $pdr, 1)];
    }

    private static function round(?float $value, int $precision): ?float
    {
        return $value === null ? null : round($value, $precision);
    }

    private static function emptyResult(): array
    {
        return [
            'total_received' => 0,
            'expected_packets' => 0,
            'pdr' => null,
            'loss_rate' => null,
            'avg_rssi' => null,
            'std_rssi' => null,
            'rssi_samples' => 0,
            'avg_hop' => null,
            'has_estimated' => false,
            'sessions_count' => 0,
            'per_node' => [],
        ];
    }
}

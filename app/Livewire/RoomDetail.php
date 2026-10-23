<?php

namespace App\Livewire;

use App\Models\Room;
use App\Models\SensorLog;
use App\Support\AirQuality;
use App\Support\PreventiveActions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Detail satu ruangan: nilai terkini, tindakan preventif, grafik tren,
 * ringkasan statistik, dan pembacaan terakhir.
 */
#[Layout('layouts::public')]
#[Title('Detail Ruangan — SIASAP')]
class RoomDetail extends Component
{
    /**
     * Rentang waktu grafik: durasi (detik), ukuran bucket rata-rata (detik),
     * dan format label. Bucket dipilih agar titik grafik ≤ ~120.
     */
    public const RANGES = [
        '1h' => ['label' => '1 Jam', 'seconds' => 3600, 'bucket' => 60, 'format' => 'H:i'],
        '24h' => ['label' => '24 Jam', 'seconds' => 86400, 'bucket' => 900, 'format' => 'H:i'],
        '7d' => ['label' => '7 Hari', 'seconds' => 604800, 'bucket' => 7200, 'format' => 'd M H:i'],
    ];

    public Room $room;

    #[Url]
    public string $range = '24h';

    public function setRange(string $range): void
    {
        $this->range = array_key_exists($range, self::RANGES) ? $range : '24h';
    }

    public function render()
    {
        if (! array_key_exists($this->range, self::RANGES)) {
            $this->range = '24h';
        }

        $nodes = $this->room->nodes()->where('is_active', true)->with('latestLog')->orderBy('name')->get();
        $nodeIds = $nodes->pluck('id');
        $threshold = now()->subSeconds(AirQuality::onlineThresholdSeconds());

        $latestLogs = $nodes->pluck('latestLog')->filter();
        $current = $latestLogs->sortByDesc('ku_value')->first();

        $devices = $nodes->map(fn ($node) => [
            'name' => $node->name,
            'online' => $node->latestLog && $node->latestLog->created_at->gte($threshold),
            'last_seen' => $node->latestLog?->created_at,
        ]);

        $recent = $nodeIds->isEmpty() ? collect() : SensorLog::query()
            ->with('node:id,name')
            ->whereIn('node_id', $nodeIds)
            ->latest('created_at')
            ->latest('id')
            ->limit(10)
            ->get();

        [$kuChart, $gasChart, $stats] = $this->buildTrend($nodeIds);

        return view('livewire.room-detail', [
            'current' => $current,
            'currentCategory' => AirQuality::category($current?->ku_status),
            'dominant' => $current
                ? ($current->ku_dominant ?? SensorLog::computeKu($current->co2, $current->nh3, $current->co, $current->h2)['dominant'])
                : null,
            'preventive' => $current ? PreventiveActions::forLog($current) : null,
            'devices' => $devices,
            'lastUpdate' => $latestLogs->max('created_at'),
            'recent' => $recent,
            'kuChart' => $kuChart,
            'gasChart' => $gasChart,
            'stats' => $stats,
            'gases' => AirQuality::gases(),
            'categories' => AirQuality::categories(),
            'ranges' => self::RANGES,
            'multiNode' => $nodes->count() > 1,
        ])->title($this->room->name.' — SIASAP');
    }

    /**
     * Ambil pembacaan dalam rentang terpilih lalu rata-ratakan per bucket waktu
     * (dikelompokkan di PHP agar sama di SQLite dan MySQL).
     *
     * @param  Collection<int, int>  $nodeIds
     * @return array{0: array, 1: array, 2: array}
     */
    protected function buildTrend(Collection $nodeIds): array
    {
        $config = self::RANGES[$this->range];
        $size = $config['bucket'];
        $now = now();
        $from = $now->copy()->subSeconds($config['seconds']);

        $columns = ['ku_value', 'co2', 'nh3', 'co', 'h2'];
        $buckets = [];
        $count = 0;
        $sum = 0.0;
        $min = null;
        $max = null;
        $perCategory = array_fill_keys(array_keys(AirQuality::categories()), 0);

        if ($nodeIds->isNotEmpty()) {
            $rows = SensorLog::query()
                ->toBase()
                ->select(array_merge(['created_at', 'ku_status'], $columns))
                ->whereIn('node_id', $nodeIds)
                ->where('created_at', '>=', $from)
                ->orderBy('created_at')
                ->cursor();

            foreach ($rows as $row) {
                $ts = strtotime((string) $row->created_at);
                $key = intdiv($ts, $size) * $size;

                foreach ($columns as $column) {
                    $buckets[$key][$column] = ($buckets[$key][$column] ?? 0) + (float) $row->{$column};
                }
                $buckets[$key]['n'] = ($buckets[$key]['n'] ?? 0) + 1;

                $ku = (float) $row->ku_value;
                $count++;
                $sum += $ku;
                $min = $min === null ? $ku : min($min, $ku);
                $max = $max === null ? $ku : max($max, $ku);

                $status = array_key_exists($row->ku_status, $perCategory)
                    ? $row->ku_status
                    : SensorLog::computeKuStatus($ku);
                $perCategory[$status]++;
            }
        }

        // Deret waktu lengkap; bucket tanpa data bernilai null agar celah terlihat jujur
        $labels = [];
        $series = array_fill_keys($columns, []);
        $timezone = config('app.timezone');

        for ($key = intdiv($from->getTimestamp(), $size) * $size; $key <= $now->getTimestamp(); $key += $size) {
            $labels[] = Carbon::createFromTimestamp($key, $timezone)->locale('id')->translatedFormat($config['format']);

            foreach ($columns as $column) {
                $series[$column][] = isset($buckets[$key])
                    ? round($buckets[$key][$column] / $buckets[$key]['n'], $column === 'ku_value' ? 1 : 2)
                    : null;
            }
        }

        $kuChart = [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Indeks KU',
                'data' => $series['ku_value'],
                'color' => '#0d9488',
            ]],
            'yTitle' => 'Indeks KU',
        ];

        $gasChart = [
            'labels' => $labels,
            'datasets' => collect(AirQuality::gases())->map(fn (array $gas, string $key) => [
                'label' => $gas['symbol'],
                'data' => $series[$key],
                'color' => $gas['color'],
            ])->values()->all(),
            'yTitle' => 'ppm',
        ];

        $stats = [
            'count' => $count,
            'avg' => $count ? $sum / $count : null,
            'min' => $min,
            'max' => $max,
            'distribution' => collect($perCategory)->map(fn (int $n) => $count ? round($n / $count * 100, 1) : 0.0)->all(),
        ];

        return [$kuChart, $gasChart, $stats];
    }
}

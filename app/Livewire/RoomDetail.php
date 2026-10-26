<?php

namespace App\Livewire;

use App\Models\Room;
use App\Models\SensorLog;
use App\Support\AirQuality;
use App\Support\AirQualityClassifier;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Detail satu ruangan (Grafik Sensor): nilai terkini, grafik satu gas terpilih
 * dengan garis ambang kategori, ringkasan rentang, dan 10 pembacaan terakhir.
 */
#[Layout('layouts::public')]
#[Title('Detail Ruangan — SIMKU')]
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

    public const DEFAULT_GAS = 'co2';

    public const DEFAULT_RANGE = '24h';

    public Room $room;

    /** Gas yang ditampilkan pada grafik: co2|nh3|co|h2. */
    #[Url]
    public string $gas = self::DEFAULT_GAS;

    #[Url]
    public string $range = self::DEFAULT_RANGE;

    public function setGas(string $gas): void
    {
        $this->gas = array_key_exists($gas, AirQuality::gases()) ? $gas : self::DEFAULT_GAS;
    }

    public function setRange(string $range): void
    {
        $this->range = array_key_exists($range, self::RANGES) ? $range : self::DEFAULT_RANGE;
    }

    public function render(): View
    {
        // Nilai dari URL bisa sembarang; normalisasi sebelum dipakai
        $this->setGas($this->gas);
        $this->setRange($this->range);

        $nodes = $this->room->nodes()->where('is_active', true)->with('latestLog')->orderBy('name')->get();
        $nodeIds = $nodes->pluck('id');
        $threshold = now()->subSeconds(AirQuality::onlineThresholdSeconds());

        $latestLogs = $nodes->pluck('latestLog')->filter();
        $current = $latestLogs->sortByDesc(fn (SensorLog $log) => AirQualityClassifier::rank($log->overall_category))->first();

        $devices = $nodes->map(fn ($node) => [
            'name' => $node->name,
            'online' => $node->latestLog && $node->latestLog->created_at->gte($threshold),
        ]);

        $recent = $nodeIds->isEmpty() ? collect() : SensorLog::query()
            ->with('node:id,name')
            ->whereIn('node_id', $nodeIds)
            ->latest('created_at')
            ->latest('id')
            ->limit(10)
            ->get();

        [$gasChart, $stats] = $this->buildTrend($nodeIds);

        return view('livewire.room-detail', [
            'current' => $current,
            'currentCategory' => AirQuality::category($current?->overall_category),
            'dominant' => $current?->dominantGases() ?? [],
            'recommendation' => $current?->recommendation(),
            'devices' => $devices,
            'lastUpdate' => $latestLogs->max('created_at'),
            'recent' => $recent,
            'gasChart' => $gasChart,
            'stats' => $stats,
            'gases' => AirQuality::gases(),
            'selectedGas' => AirQuality::gases()[$this->gas],
            'categories' => AirQuality::categories(),
            'ranges' => self::RANGES,
            'multiNode' => $nodes->count() > 1,
        ])->title($this->room->name.' — SIMKU');
    }

    /**
     * Ambil pembacaan dalam rentang terpilih lalu rata-ratakan gas terpilih per
     * bucket waktu (dikelompokkan di PHP agar sama di SQLite dan MySQL).
     *
     * @param  Collection<int, int>  $nodeIds
     * @return array{0: array{labels: list<string>, datasets: list<array{label: string, data: list<float|int|null>, color: string, dashed?: bool}>, yTitle: string}, 1: array{count: int, avg: ?float, min: ?float, max: ?float, distribution: array<string, float>}}
     */
    protected function buildTrend(Collection $nodeIds): array
    {
        $config = self::RANGES[$this->range];
        $size = $config['bucket'];
        $now = now();
        $from = $now->copy()->subSeconds($config['seconds']);
        $column = $this->gas;

        $buckets = [];
        $count = 0;
        $sum = 0.0;
        $min = null;
        $max = null;
        $perCategory = array_fill_keys(array_keys(AirQuality::categories()), 0);

        if ($nodeIds->isNotEmpty()) {
            $rows = SensorLog::query()
                ->toBase()
                ->select(['created_at', 'overall_category', $column])
                ->whereIn('node_id', $nodeIds)
                ->where('created_at', '>=', $from)
                ->orderBy('created_at')
                ->cursor();

            foreach ($rows as $row) {
                $value = (float) $row->{$column};
                $key = intdiv(strtotime((string) $row->created_at), $size) * $size;

                $buckets[$key]['sum'] = ($buckets[$key]['sum'] ?? 0) + $value;
                $buckets[$key]['n'] = ($buckets[$key]['n'] ?? 0) + 1;

                $count++;
                $sum += $value;
                $min = $min === null ? $value : min($min, $value);
                $max = $max === null ? $value : max($max, $value);
                $perCategory[$row->overall_category]++;
            }
        }

        // Deret waktu lengkap; bucket tanpa data bernilai null agar celah terlihat jujur
        $labels = [];
        $series = [];
        $timezone = config('app.timezone');

        for ($key = intdiv($from->getTimestamp(), $size) * $size; $key <= $now->getTimestamp(); $key += $size) {
            $labels[] = Carbon::createFromTimestamp($key, $timezone)->locale('id')->translatedFormat($config['format']);
            $series[] = isset($buckets[$key]) ? round($buckets[$key]['sum'] / $buckets[$key]['n'], 2) : null;
        }

        $gas = AirQuality::gases()[$column];
        $datasets = [['label' => $gas['symbol'], 'data' => $series, 'color' => $gas['color']]];

        // Garis ambang (putus-putus): batas atas kategori Baik, Sedang, Tidak Sehat
        $order = config('air_quality.category_order');
        foreach (config("air_quality.thresholds.$column") as $index => $limit) {
            $category = AirQuality::category($order[$index]);
            $datasets[] = [
                'label' => 'Batas '.$category['label'].' ('.$limit.' ppm)',
                'data' => array_fill(0, count($labels), $limit),
                'color' => $category['color'],
                'dashed' => true,
            ];
        }

        $gasChart = [
            'labels' => $labels,
            'datasets' => $datasets,
            'yTitle' => 'ppm (indikator relatif)',
        ];

        $stats = [
            'count' => $count,
            'avg' => $count ? round($sum / $count, 2) : null,
            'min' => $min,
            'max' => $max,
            'distribution' => collect($perCategory)->map(fn (int $n) => $count ? round($n / $count * 100, 1) : 0.0)->all(),
        ];

        return [$gasChart, $stats];
    }
}

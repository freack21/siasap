<?php

namespace App\Livewire;

use App\Models\Node;
use App\Models\Room;
use App\Models\SensorLog;
use App\Models\TestingSession;
use App\Support\AirQuality;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Ringkasan — SIASAP')]
class AdminOverview extends Component
{
    /**
     * Palet tetap untuk membedakan ruangan pada grafik (bukan warna kategori KU).
     */
    private const ROOM_PALETTE = ['#0d9488', '#6366f1', '#0ea5e9', '#a855f7', '#64748b', '#db2777'];

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('telemetry.view'), 403, 'Anda tidak memiliki hak akses ke halaman ini.');
    }

    public function render()
    {
        $now = now();
        $threshold = AirQuality::onlineThresholdSeconds();

        $nodes = Node::query()
            ->with(['room', 'latestLog'])
            ->orderBy('room_id')
            ->orderBy('name')
            ->get();

        $devices = $nodes->map(function (Node $node) use ($now, $threshold) {
            $log = $node->latestLog;

            return [
                'node' => $node,
                'log' => $log,
                'online' => $log && $log->created_at->diffInSeconds($now, true) <= $threshold,
            ];
        });

        $activeNodes = $nodes->where('is_active', true);
        $since = $now->copy()->subHours(24);

        // Bucket per jam dihitung di PHP agar portabel (SQLite & MySQL).
        $recentLogs = SensorLog::query()
            ->where('created_at', '>=', $since)
            ->with('node:id,room_id')
            ->get(['id', 'node_id', 'ku_value', 'created_at']);

        return view('livewire.admin-overview', [
            'devices' => $devices,
            'stats' => [
                'today' => SensorLog::where('created_at', '>=', $now->copy()->startOfDay())->count(),
                'online' => $devices->filter(fn ($d) => $d['online'] && $d['node']->is_active)->count(),
                'activeTotal' => $activeNodes->count(),
                'rooms' => $activeNodes->pluck('room_id')->filter()->unique()->count(),
                'avgKu' => $recentLogs->isEmpty() ? null : $recentLogs->avg('ku_value'),
            ],
            'activeSession' => TestingSession::active()->latest('started_at')->first(),
            'alerts' => SensorLog::query()
                ->whereIn('ku_status', ['Tidak Sehat', 'Berbahaya'])
                ->with('node.room')
                ->latest()
                ->latest('id')
                ->take(8)
                ->get(),
            'countChart' => $this->countChart($recentLogs, $now),
            'kuChart' => $this->kuChart($recentLogs, $now),
        ]);
    }

    /**
     * @return array<string, Carbon> Kunci jam ('Y-m-d H') => awal jam, 24 jam terakhir.
     */
    private function hourBuckets(Carbon $now): array
    {
        $buckets = [];
        for ($i = 23; $i >= 0; $i--) {
            $hour = $now->copy()->startOfHour()->subHours($i);
            $buckets[$hour->format('Y-m-d H')] = $hour;
        }

        return $buckets;
    }

    private function countChart(Collection $logs, Carbon $now): array
    {
        $buckets = $this->hourBuckets($now);
        $counts = $logs->countBy(fn (SensorLog $log) => $log->created_at->format('Y-m-d H'));

        return [
            'labels' => array_map(fn (Carbon $hour) => $hour->format('H:00'), array_values($buckets)),
            'datasets' => [[
                'label' => 'Data masuk',
                'data' => array_map(fn ($key) => $counts[$key] ?? 0, array_keys($buckets)),
                'color' => '#0d9488',
            ]],
            'yTitle' => 'Jumlah data',
        ];
    }

    private function kuChart(Collection $logs, Carbon $now): array
    {
        $buckets = $this->hourBuckets($now);
        $byRoom = $logs->groupBy(fn (SensorLog $log) => $log->node?->room_id);
        $rooms = Room::whereIn('id', $byRoom->keys()->filter())->orderBy('name')->get(['id', 'name']);

        $datasets = $rooms->values()->map(function (Room $room, int $index) use ($byRoom, $buckets) {
            $averages = $byRoom[$room->id]
                ->groupBy(fn (SensorLog $log) => $log->created_at->format('Y-m-d H'))
                ->map(fn (Collection $group) => round($group->avg('ku_value'), 1));

            return [
                'label' => $room->name,
                'data' => array_map(fn ($key) => $averages[$key] ?? null, array_keys($buckets)),
                'color' => self::ROOM_PALETTE[$index % count(self::ROOM_PALETTE)],
            ];
        })->all();

        return [
            'labels' => array_map(fn (Carbon $hour) => $hour->format('H:00'), array_values($buckets)),
            'datasets' => $datasets,
            'yTitle' => 'Indeks KU',
        ];
    }
}

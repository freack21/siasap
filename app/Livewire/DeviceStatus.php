<?php

namespace App\Livewire;

use App\Models\Node;
use App\Models\SensorLog;
use App\Models\TelemetryRejection;
use App\Support\AirQuality;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Halaman Status Perangkat (IoT System): status koneksi tiap perangkat,
 * interval kirim terukur, alur data, dan 10 penolakan data terakhir.
 */
#[Layout('layouts::public')]
#[Title('Status Perangkat — SIMKU')]
class DeviceStatus extends Component
{
    /**
     * Jumlah data terakhir yang dipakai untuk menghitung interval terukur.
     */
    public const INTERVAL_SAMPLE_SIZE = 10;

    public const REJECTION_LIMIT = 10;

    public function render(): View
    {
        return view('livewire.device-status', [
            'devices' => $this->devices(),
            'rejections' => TelemetryRejection::query()
                ->latest('created_at')
                ->latest('id')
                ->limit(self::REJECTION_LIMIT)
                ->get(),
            'gases' => AirQuality::gases(),
            'onlineSeconds' => AirQuality::onlineThresholdSeconds(),
            'sendInterval' => (int) config('air_quality.send_interval_seconds', 5),
        ]);
    }

    /**
     * @return Collection<int, array{node: Node, latest: ?SensorLog, last_seen: ?Carbon, connected: bool, interval: ?float}>
     */
    protected function devices(): Collection
    {
        $threshold = now()->subSeconds(AirQuality::onlineThresholdSeconds());

        return Node::query()
            ->with('room')
            ->orderBy('code')
            ->get()
            ->map(function (Node $node) use ($threshold): array {
                $recentLogs = $node->sensorLogs()
                    ->latest('created_at')
                    ->latest('id')
                    ->limit(self::INTERVAL_SAMPLE_SIZE)
                    ->get();

                $latest = $recentLogs->first();

                return [
                    'node' => $node,
                    'latest' => $latest,
                    'last_seen' => $latest?->created_at,
                    'connected' => $latest !== null && $latest->created_at->gte($threshold),
                    'interval' => $this->measuredInterval($recentLogs),
                ];
            });
    }

    /**
     * Rata-rata selisih waktu (detik) antar data berurutan, dari data terbaru
     * ke terlama; null bila data kurang dari dua.
     *
     * @param  Collection<int, SensorLog>  $recentLogs
     */
    protected function measuredInterval(Collection $recentLogs): ?float
    {
        if ($recentLogs->count() < 2) {
            return null;
        }

        $span = $recentLogs->last()->created_at->diffInSeconds($recentLogs->first()->created_at);

        return $span / ($recentLogs->count() - 1);
    }
}

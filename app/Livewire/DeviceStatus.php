<?php

namespace App\Livewire;

use App\Models\Node;
use App\Models\SensorLog;
use App\Support\AirQuality;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Halaman Status Perangkat: apakah setiap perangkat sedang mengirim data,
 * kapan data terakhir diterima, dan gas yang dipantau. Penjelasan cara
 * kerja sistem ada di halaman Panduan.
 */
#[Layout('layouts::public')]
#[Title('Status Perangkat — SIMKU')]
class DeviceStatus extends Component
{
    public function render(): View
    {
        return view('livewire.device-status', [
            'devices' => $this->devices(),
            'gases' => AirQuality::gases(),
            'onlineSeconds' => AirQuality::onlineThresholdSeconds(),
        ]);
    }

    /**
     * @return Collection<int, array{node: Node, latest: ?SensorLog, last_seen: ?Carbon, connected: bool}>
     */
    protected function devices(): Collection
    {
        $threshold = now()->subSeconds(AirQuality::onlineThresholdSeconds());

        return Node::query()
            ->with('room')
            ->orderBy('code')
            ->get()
            ->map(function (Node $node) use ($threshold): array {
                $latest = $node->sensorLogs()
                    ->latest('created_at')
                    ->latest('id')
                    ->first();

                return [
                    'node' => $node,
                    'latest' => $latest,
                    'last_seen' => $latest?->created_at,
                    'connected' => $latest !== null && $latest->created_at->gte($threshold),
                ];
            });
    }
}

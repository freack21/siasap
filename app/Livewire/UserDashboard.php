<?php

namespace App\Livewire;

use App\Models\Room;
use App\Models\SensorLog;
use App\Support\AirQuality;
use App\Support\AirQualityClassifier;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Beranda publik: ringkasan kualitas udara seluruh ruangan yang dipantau.
 */
#[Layout('layouts::public')]
#[Title('Beranda — SIASAP')]
class UserDashboard extends Component
{
    public function render()
    {
        $threshold = now()->subSeconds(AirQuality::onlineThresholdSeconds());

        $rooms = Room::query()
            ->whereHas('nodes', fn ($query) => $query->where('is_active', true))
            ->with(['nodes' => fn ($query) => $query->where('is_active', true)->with('latestLog')])
            ->orderBy('name')
            ->get();

        $cards = $rooms->map(fn (Room $room) => $this->summarizeRoom($room, $threshold));

        $devicesTotal = $cards->sum('devices_total');
        $devicesOnline = $cards->sum('devices_online');

        // Status keseluruhan = kondisi terburuk dari pembacaan terakhir tiap ruangan
        $worstCard = $cards->filter(fn (array $card) => $card['log'])
            ->sortByDesc(fn (array $card) => AirQualityClassifier::rank($card['log']->overall_category))
            ->first();
        $worst = $worstCard['log'] ?? null;
        $lastUpdate = $cards->pluck('updated_at')->filter()->max();

        return view('livewire.user-dashboard', [
            'cards' => $cards,
            'overallStatus' => $worst?->overall_category,
            'overallCategory' => AirQuality::category($worst?->overall_category),
            'worstRoom' => $worstCard['room'] ?? null,
            'worstRecommendation' => $worst?->recommendation(),
            'lastUpdate' => $lastUpdate,
            'devicesTotal' => $devicesTotal,
            'devicesOnline' => $devicesOnline,
            'categories' => AirQuality::categories(),
            'gases' => AirQuality::gases(),
        ]);
    }

    /**
     * Ringkas satu ruangan: pembacaan terburuk dari node aktif, status perangkat,
     * dan penyebab utama.
     *
     * @return array{room: Room, log: ?SensorLog, dominant: list<string>, devices_total: int, devices_online: int, online: bool, updated_at: mixed}
     */
    protected function summarizeRoom(Room $room, $threshold): array
    {
        /** @var Collection<int, SensorLog> $logs */
        $logs = $room->nodes->pluck('latestLog')->filter();

        $worst = $logs->sortByDesc(fn (SensorLog $log) => AirQualityClassifier::rank($log->overall_category))->first();
        $online = $logs->filter(fn (SensorLog $log) => $log->created_at->gte($threshold))->count();

        return [
            'room' => $room,
            'log' => $worst,
            'dominant' => $worst?->dominantGases() ?? [],
            'devices_total' => $room->nodes->count(),
            'devices_online' => $online,
            'online' => $online > 0,
            'updated_at' => $logs->max('created_at'),
        ];
    }
}

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
use Livewire\Component;

/**
 * Beranda publik: kategori keseluruhan, penyebab utama, kategori per gas, dan
 * rekomendasi tindakan. Bila hanya satu ruangan dipantau, ruangan itu ditampilkan
 * langsung; bila lebih, setiap ruangan tampil sebagai kartu ringkas.
 */
#[Layout('layouts::public')]
#[Title('Beranda — SIASAP')]
class UserDashboard extends Component
{
    public function render(): View
    {
        $threshold = now()->subSeconds(AirQuality::onlineThresholdSeconds());

        $rooms = Room::query()
            ->whereHas('nodes', fn ($query) => $query->where('is_active', true))
            ->with(['nodes' => fn ($query) => $query->where('is_active', true)->with('latestLog')])
            ->orderBy('name')
            ->get();

        $cards = $rooms->map(fn (Room $room) => $this->summarizeRoom($room, $threshold));

        // Kondisi terburuk dari pembacaan terakhir tiap ruangan (untuk ringkasan multi-ruangan)
        $worstCard = $cards->filter(fn (array $card) => $card['log'])
            ->sortByDesc(fn (array $card) => AirQualityClassifier::rank($card['log']->overall_category))
            ->first();

        return view('livewire.user-dashboard', [
            'cards' => $cards,
            // Tepat satu ruangan dipantau → beranda langsung menampilkan ruangan itu
            'focus' => $cards->count() === 1 ? $cards->first() : null,
            'worstCard' => $worstCard,
            'lastUpdate' => $cards->pluck('updated_at')->filter()->max(),
            'devicesTotal' => $cards->sum('devices_total'),
            'devicesOnline' => $cards->sum('devices_online'),
            'categories' => AirQuality::categories(),
            'gases' => AirQuality::gases(),
        ]);
    }

    /**
     * Ringkas satu ruangan: pembacaan terburuk dari node aktif, kategori per gas,
     * penyebab utama, rekomendasi, dan status perangkat.
     *
     * @return array{room: Room, log: ?SensorLog, categories: array<string, string>, dominant: list<string>, recommendation: ?array{category: string, level: string, text: string, strategies: list<string>}, devices_total: int, devices_online: int, online: bool, updated_at: mixed}
     */
    protected function summarizeRoom(Room $room, Carbon $threshold): array
    {
        /** @var Collection<int, SensorLog> $logs */
        $logs = $room->nodes->pluck('latestLog')->filter();

        $worst = $logs->sortByDesc(fn (SensorLog $log) => AirQualityClassifier::rank($log->overall_category))->first();
        $online = $logs->filter(fn (SensorLog $log) => $log->created_at->gte($threshold))->count();

        return [
            'room' => $room,
            'log' => $worst,
            'categories' => $worst?->categories() ?? [],
            'dominant' => $worst?->dominantGases() ?? [],
            'recommendation' => $worst?->recommendation(),
            'devices_total' => $room->nodes->count(),
            'devices_online' => $online,
            'online' => $online > 0,
            'updated_at' => $logs->max('created_at'),
        ];
    }
}

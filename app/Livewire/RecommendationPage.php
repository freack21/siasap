<?php

namespace App\Livewire;

use App\Models\Room;
use App\Models\SensorLog;
use App\Support\AirQualityClassifier;
use App\Support\Recommendation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Halaman Rekomendasi Tindakan: kondisi terkini per ruangan, tabel 4 aturan
 * (Tabel 3.3 naskah), dasar rekomendasi, dan disclaimer.
 */
#[Layout('layouts::public')]
#[Title('Rekomendasi Tindakan — SIASAP')]
class RecommendationPage extends Component
{
    public function render(): View
    {
        return view('livewire.recommendation-page', [
            'conditions' => $this->currentConditions(),
            'rules' => Recommendation::all(),
            'strategyLabels' => config('recommendations.strategy_labels'),
            'basis' => config('recommendations.basis'),
            'sources' => Recommendation::sources(),
            'disclaimer' => config('recommendations.disclaimer'),
        ]);
    }

    /**
     * Kondisi terkini tiap ruangan: pembacaan terakhir terburuk di antara node aktif.
     *
     * @return Collection<int, array{room: Room, log: ?SensorLog, recommendation: ?array{category: string, level: string, text: string, strategies: list<string>}}>
     */
    protected function currentConditions(): Collection
    {
        return Room::query()
            ->whereHas('nodes', fn ($query) => $query->where('is_active', true))
            ->with(['nodes' => fn ($query) => $query->where('is_active', true)->with('latestLog')])
            ->orderBy('name')
            ->get()
            ->map(function (Room $room): array {
                $log = $room->nodes->pluck('latestLog')->filter()
                    ->sortByDesc(fn (SensorLog $log) => AirQualityClassifier::rank($log->overall_category))
                    ->first();

                return [
                    'room' => $room,
                    'log' => $log,
                    'recommendation' => $log?->recommendation(),
                ];
            });
    }
}

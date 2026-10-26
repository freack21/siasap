<?php

namespace App\Livewire;

use App\Support\AirQuality;
use App\Support\Recommendation;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Halaman Panduan: cara membaca kategori, tabel ambang Solihat et al. (2026),
 * keterangan gas, FAQ, dan batasan.
 */
#[Layout('layouts::public')]
#[Title('Panduan Kualitas Udara — SIMKU')]
class AirQualityGuide extends Component
{
    public function render(): View
    {
        return view('livewire.air-quality-guide', [
            'categories' => AirQuality::categories(),
            'gases' => AirQuality::gases(),
            'recommendations' => collect(Recommendation::all())->keyBy('category')->all(),
            'source' => config('air_quality.source'),
            'onlineSeconds' => AirQuality::onlineThresholdSeconds(),
            'sendInterval' => (int) config('air_quality.send_interval_seconds', 5),
        ]);
    }
}

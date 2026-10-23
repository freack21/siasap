<?php

namespace App\Livewire;

use App\Support\AirQuality;
use App\Support\Recommendation;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::public')]
#[Title('Panduan Kualitas Udara — SIASAP')]
class AirQualityGuide extends Component
{
    public function render()
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

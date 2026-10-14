<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::public')]
#[Title('Panduan Kualitas Udara — SIASAP')]
class AirQualityGuide extends Component
{
    public function render()
    {
        return view('livewire.air-quality-guide');
    }
}

<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Hasil Kuesioner — SIASAP')]
class SurveyResults extends Component
{
    public function render()
    {
        return view('livewire.survey-results');
    }
}

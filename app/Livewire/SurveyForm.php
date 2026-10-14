<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::public')]
#[Title('Kuesioner — SIASAP')]
class SurveyForm extends Component
{
    public function render()
    {
        return view('livewire.survey-form');
    }
}

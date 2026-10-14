<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Ringkasan — SIASAP')]
class AdminOverview extends Component
{
    public function render()
    {
        return view('livewire.admin-overview');
    }
}

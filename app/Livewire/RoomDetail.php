<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::public')]
#[Title('Detail Ruangan — SIASAP')]
class RoomDetail extends Component
{
    public function render()
    {
        return view('livewire.room-detail');
    }
}

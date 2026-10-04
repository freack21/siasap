<?php

namespace App\Livewire;

use App\Models\SystemNavigation;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Navigasi Sistem — SIASAP')]
class NavigationManagement extends Component
{
    public function render()
    {
        $navigations = SystemNavigation::orderBy('section')->orderBy('order')->get();

        return view('livewire.navigation-management', [
            'navigations' => $navigations,
        ]);
    }
}

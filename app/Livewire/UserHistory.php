<?php

namespace App\Livewire;

use App\Models\SensorLog;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Riwayat Kualitas Udara — SIASAP')]
class UserHistory extends Component
{
    use WithPagination;

    public string $selectedArea = 'all';
    public string $selectedStatus = 'all';
    public string $searchDate = '';

    public function updatingSelectedArea()
    {
        $this->resetPage();
    }

    public function updatingSelectedStatus()
    {
        $this->resetPage();
    }

    public function updatingSearchDate()
    {
        $this->resetPage();
    }

    public function render()
    {
        $areaNames = [
            1 => 'Area Ruang Utama & Lobby',
            2 => 'Area Halaman Terbuka',
            3 => 'Area Laboratorium / Produksi',
        ];

        $query = SensorLog::query()->latest();

        if ($this->selectedArea !== 'all') {
            $query->where('node_id', (int) $this->selectedArea);
        }

        if ($this->selectedStatus !== 'all') {
            $query->where('ku_status', $this->selectedStatus);
        }

        if (!empty($this->searchDate)) {
            $query->whereDate('created_at', $this->searchDate);
        }

        $logs = $query->paginate(15);

        return view('livewire.user-history', [
            'logs' => $logs,
            'areaNames' => $areaNames,
        ]);
    }
}

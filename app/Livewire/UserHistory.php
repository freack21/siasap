<?php

namespace App\Livewire;

use App\Models\SensorLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::public')]
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

    /**
     * Poll history data every 3 seconds for real-time updates
     */
    public function refreshHistory()
    {
        // Auto refreshed by Livewire poll
    }

    public function render()
    {
        $nodes = \App\Models\Node::with('room')->get();

        $query = SensorLog::with(['node.room'])->latest();

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
            'nodes' => $nodes,
        ]);
    }
}

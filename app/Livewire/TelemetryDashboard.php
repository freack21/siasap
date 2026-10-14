<?php

namespace App\Livewire;

use App\Models\SensorLog;
use App\Models\TestingSession;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Monitoring Live Telemetry — SIASAP')]
class TelemetryDashboard extends Component
{
    public string $selectedTopology = 'all'; // 'all', 'star', 'mesh'
    public string $selectedNode = 'all';     // 'all' atau id node

    public function mount()
    {
        if (!auth()->user() || !auth()->user()->hasPermission('telemetry.view')) {
            abort(403, 'Anda tidak memiliki hak akses ke panel pengelola ini.');
        }
    }

    /**
     * Poll telemetry every 3 seconds
     */
    public function refreshTelemetry()
    {
        // Auto refreshed by Livewire poll
    }

    /**
     * Set topology filter
     */
    public function setTopologyFilter(string $type)
    {
        $this->selectedTopology = $type;
    }

    /**
     * Set node filter
     */
    public function setNodeFilter(string $nodeId)
    {
        $this->selectedNode = $nodeId;
    }

    public function render()
    {
        $activeSession = TestingSession::active()->latest()->first();

        // Dynamic Nodes Summary Cards
        $nodesList = \App\Models\Node::with('room')->get();
        $nodesData = [];
        foreach ($nodesList as $node) {
            $latest = SensorLog::where('node_id', $node->id)->latest()->first();
            $isOnline = $latest && $latest->created_at->diffInSeconds(now()) <= 30;

            $nodesData[$node->id] = [
                'node_id' => $node->id,
                'name' => $node->name,
                'room_name' => $node->room?->name ?? 'Tanpa Ruangan',
                'latest' => $latest,
                'is_online' => $isOnline,
            ];
        }

        // Query Logs with Filters
        $logsQuery = SensorLog::with(['node.room'])->latest();

        if ($this->selectedTopology !== 'all') {
            $logsQuery->where('topology_type', $this->selectedTopology);
        }

        if ($this->selectedNode !== 'all') {
            $logsQuery->where('node_id', (int) $this->selectedNode);
        }

        $logs = $logsQuery->take(15)->get();

        // Aggregate Metrics
        $totalPacketsToday = SensorLog::whereDate('created_at', now()->today())->count();
        $avgKuToday = SensorLog::whereDate('created_at', now()->today())->avg('ku_value') ?? 0;

        return view('livewire.telemetry-dashboard', [
            'activeSession' => $activeSession,
            'nodesData' => $nodesData,
            'logs' => $logs,
            'totalPacketsToday' => $totalPacketsToday,
            'avgKuToday' => round($avgKuToday, 1),
        ]);
    }
}

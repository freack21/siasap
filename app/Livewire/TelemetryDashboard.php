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
    public string $selectedNode = 'all';     // 'all', '1', '2', '3'

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

    /**
     * Helper to generate synthetic telemetry data for quick demonstration
     */
    public function generateSimulatedData()
    {
        $activeSession = TestingSession::active()->latest()->first();
        $topology = $activeSession ? $activeSession->topology_type : 'star';

        foreach ([1, 2, 3] as $nodeId) {
            // Generate realistic values with occasional high gas spikes
            $co2 = rand(15, 60) + (rand(0, 100) > 85 ? rand(100, 250) : 0);
            $nh3 = rand(5, 35);
            $co  = rand(2, 25);
            $h2  = rand(1, 15);

            $kuValue = SensorLog::computeKuValue($co2, $nh3, $co, $h2);
            $kuStatus = SensorLog::computeKuStatus($kuValue);

            $lastLog = SensorLog::where('node_id', $nodeId)->latest()->first();
            $seqNumber = $lastLog ? ($lastLog->seq_number + 1) : 1;

            $hopCount = match ($nodeId) {
                1 => 1,
                2 => 1,
                3 => ($topology === 'mesh' ? 2 : 1),
            };

            $rssi = match ($nodeId) {
                1 => rand(-55, -45),
                2 => rand(-70, -58),
                3 => ($topology === 'mesh' ? rand(-72, -62) : rand(-89, -78)),
            };

            SensorLog::create([
                'testing_session_id' => $activeSession?->id,
                'node_id' => $nodeId,
                'co2' => $co2,
                'nh3' => $nh3,
                'co' => $co,
                'h2' => $h2,
                'ku_value' => $kuValue,
                'ku_status' => $kuStatus,
                'rssi' => $rssi,
                'seq_number' => $seqNumber,
                'hop_count' => $hopCount,
                'topology_type' => $topology,
            ]);
        }
    }

    public function render()
    {
        $activeSession = TestingSession::active()->latest()->first();

        // 3 Nodes Summary Cards
        $nodesData = [];
        for ($i = 1; $i <= 3; $i++) {
            $latest = SensorLog::where('node_id', $i)->latest()->first();
            $isOnline = $latest && $latest->created_at->diffInSeconds(now()) <= 30;

            $nodesData[$i] = [
                'node_id' => $i,
                'name' => "Node Sensor #{$i}",
                'latest' => $latest,
                'is_online' => $isOnline,
            ];
        }

        // Query Logs with Filters
        $logsQuery = SensorLog::query()->latest();

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

<?php

namespace App\Livewire;

use App\Models\SensorLog;
use App\Models\TestingSession;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Benchmark QoS Topologi (Star vs Mesh) — SIASAP')]
class QosAnalytics extends Component
{
    // Form Inputs for New Testing Session
    public string $sessionName = '';
    public string $sessionTopology = 'star';
    public string $sessionDescription = '';

    public ?int $filterSessionId = null;

    public function mount()
    {
        if (!auth()->user() || !auth()->user()->hasPermission('qos.view')) {
            abort(403, 'Anda tidak memiliki hak akses ke modul analisis ini.');
        }
    }

    /**
     * Start a new testing session
     */
    public function startSession()
    {
        $this->validate([
            'sessionName' => 'required|string|max:100',
            'sessionTopology' => 'required|in:star,mesh',
            'sessionDescription' => 'nullable|string|max:255',
        ]);

        // Complete any existing active session
        TestingSession::where('status', 'active')->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);

        // Create new active session
        $session = TestingSession::create([
            'name' => $this->sessionName,
            'topology_type' => $this->sessionTopology,
            'status' => 'active',
            'description' => $this->sessionDescription,
            'started_at' => now(),
        ]);

        $this->reset(['sessionName', 'sessionDescription']);
        $this->filterSessionId = $session->id;

        session()->flash('message', "Sesi pengujian baru '{$session->name}' ({$session->topology_type}) berhasil dimulai!");
    }

    /**
     * Stop active session
     */
    public function stopActiveSession()
    {
        $activeSession = TestingSession::active()->first();
        if ($activeSession) {
            $activeSession->update([
                'status' => 'completed',
                'ended_at' => now(),
            ]);
            session()->flash('message', "Sesi pengujian '{$activeSession->name}' telah diselesaikan.");
        }
    }

    /**
     * Populate synthetic research dataset for both Star and Mesh topologies
     */
    public function populateResearchBenchmarkData()
    {
        // Session 1: Star Topology Benchmark
        $starSession = TestingSession::create([
            'name' => 'Uji Komparasi Star (Jarak & Halangan Dinding)',
            'topology_type' => 'star',
            'status' => 'completed',
            'description' => 'Skenario Node 1 & 2 jarak dekat, Node 3 terhalang dinding/blind spot.',
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHour(),
        ]);

        // Node 1 & 2 in Star: high PDR (98%), RSSI -55dBm, 50 packets
        for ($i = 1; $i <= 50; $i++) {
            foreach ([1, 2] as $nodeId) {
                if (rand(1, 100) <= 98) { // 98% PDR
                    SensorLog::create([
                        'testing_session_id' => $starSession->id,
                        'node_id' => $nodeId,
                        'co2' => rand(25, 45),
                        'nh3' => rand(10, 20),
                        'co' => rand(5, 15),
                        'h2' => rand(2, 10),
                        'ku_value' => rand(25, 45),
                        'ku_status' => 'Baik',
                        'rssi' => rand(-60, -50),
                        'seq_number' => $i,
                        'hop_count' => 1,
                        'topology_type' => 'star',
                        'created_at' => now()->subHours(2)->addSeconds($i * 5),
                    ]);
                }
            }
            // Node 3 in Star (Blind Spot): low PDR (42%) due to wall interference
            if (rand(1, 100) <= 42) {
                SensorLog::create([
                    'testing_session_id' => $starSession->id,
                    'node_id' => 3,
                    'co2' => rand(30, 50),
                    'nh3' => rand(12, 22),
                    'co' => rand(8, 18),
                    'h2' => rand(5, 12),
                    'ku_value' => rand(30, 50),
                    'ku_status' => 'Baik',
                    'rssi' => rand(-92, -84),
                    'seq_number' => $i,
                    'hop_count' => 1,
                    'topology_type' => 'star',
                    'created_at' => now()->subHours(2)->addSeconds($i * 5),
                ]);
            }
        }

        // Session 2: Mesh Topology Benchmark
        $meshSession = TestingSession::create([
            'name' => 'Uji Komparasi Mesh Multi-Hop Relay',
            'topology_type' => 'mesh',
            'status' => 'completed',
            'description' => 'Skenario Node 3 merelay sinyal melalui Node 2 untuk mengatasi blind spot.',
            'started_at' => now()->subHour(),
            'ended_at' => now(),
        ]);

        // Mesh nodes: Node 1 & 2 direct (hop=1, PDR 96%), Node 3 multi-hop (hop=2, PDR 94%)
        for ($i = 1; $i <= 50; $i++) {
            foreach ([1, 2, 3] as $nodeId) {
                $pdrThreshold = ($nodeId === 3) ? 94 : 96;
                if (rand(1, 100) <= $pdrThreshold) {
                    SensorLog::create([
                        'testing_session_id' => $meshSession->id,
                        'node_id' => $nodeId,
                        'co2' => rand(20, 50),
                        'nh3' => rand(8, 25),
                        'co' => rand(4, 18),
                        'h2' => rand(2, 12),
                        'ku_value' => rand(20, 50),
                        'ku_status' => 'Baik',
                        'rssi' => ($nodeId === 3) ? rand(-72, -62) : rand(-62, -52),
                        'seq_number' => $i,
                        'hop_count' => ($nodeId === 3) ? 2 : 1,
                        'topology_type' => 'mesh',
                        'created_at' => now()->subHour()->addSeconds($i * 5),
                    ]);
                }
            }
        }

        session()->flash('message', 'Dataset empiris riset berhasil di-generate untuk Topologi Star & Mesh!');
    }

    /**
     * Compute QoS metrics helper
     */
    private function computeQosMetrics(string $topology, ?int $sessionId = null): array
    {
        $query = SensorLog::where('topology_type', $topology);

        if ($sessionId) {
            $query->where('testing_session_id', $sessionId);
        }

        $receivedCount = $query->count();
        if ($receivedCount === 0) {
            return [
                'total_received' => 0,
                'expected_packets' => 0,
                'pdr' => 0,
                'loss_rate' => 0,
                'avg_rssi' => 0,
                'avg_hop' => 0,
                'per_node' => [],
            ];
        }

        $avgRssi = round($query->avg('rssi') ?? 0, 1);
        $avgHop  = round($query->avg('hop_count') ?? 1, 2);

        // Calculate expected packets based on sequence numbers per node
        $totalExpected = 0;
        $perNodeMetrics = [];

        for ($n = 1; $n <= 3; $n++) {
            $nodeQuery = (clone $query)->where('node_id', $n);
            $nodeReceived = $nodeQuery->count();
            $maxSeq = $nodeQuery->max('seq_number');
            $minSeq = $nodeQuery->min('seq_number');

            $expected = ($maxSeq && $minSeq) ? ($maxSeq - $minSeq + 1) : $nodeReceived;
            if ($expected < $nodeReceived) {
                $expected = $nodeReceived;
            }

            $totalExpected += $expected;
            $nodePdr = $expected > 0 ? round(($nodeReceived / $expected) * 100, 1) : 0;
            $nodeLoss = round(100 - $nodePdr, 1);

            $perNodeMetrics[$n] = [
                'received' => $nodeReceived,
                'expected' => $expected,
                'pdr' => $nodePdr,
                'loss_rate' => $nodeLoss,
                'avg_rssi' => round($nodeQuery->avg('rssi') ?? 0, 1),
                'avg_hop' => round($nodeQuery->avg('hop_count') ?? 1, 1),
            ];
        }

        $overallPdr = $totalExpected > 0 ? round(($receivedCount / $totalExpected) * 100, 1) : 0;
        $overallLoss = round(100 - $overallPdr, 1);

        return [
            'total_received' => $receivedCount,
            'expected_packets' => $totalExpected,
            'pdr' => $overallPdr,
            'loss_rate' => $overallLoss,
            'avg_rssi' => $avgRssi,
            'avg_hop' => $avgHop,
            'per_node' => $perNodeMetrics,
        ];
    }

    public function render()
    {
        $activeSession = TestingSession::active()->first();
        $sessionsList = TestingSession::latest()->take(10)->get();

        // Calculate metrics for Star vs Mesh
        $starQos = $this->computeQosMetrics('star', $this->filterSessionId);
        $meshQos = $this->computeQosMetrics('mesh', $this->filterSessionId);

        return view('livewire.qos-analytics', [
            'activeSession' => $activeSession,
            'sessionsList' => $sessionsList,
            'starQos' => $starQos,
            'meshQos' => $meshQos,
        ]);
    }
}

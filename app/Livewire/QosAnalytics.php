<?php

namespace App\Livewire;

use App\Models\Node;
use App\Models\TestingSession;
use App\Support\QosCalculator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts::admin')]
#[Title('Benchmark QoS Topologi (Star vs Mesh) — SIASAP')]
class QosAnalytics extends Component
{
    // Form Inputs for New Testing Session
    public string $sessionName = '';
    public string $sessionTopology = 'star';
    public string $sessionDescription = '';

    public ?int $filterSessionId = null;

    public const CSV_COLUMNS = [
        'session_id', 'session_name', 'topology_type', 'node_id', 'seq_number', 'rssi', 'hop_count',
        'co2', 'nh3', 'co', 'h2', 'ku_value', 'ku_status', 'created_at',
    ];

    public function mount()
    {
        $this->authorizePermission('qos.view', 'Anda tidak memiliki hak akses ke modul analisis ini.');
    }

    private function authorizePermission(string $permission, string $message): void
    {
        if (! auth()->user() || ! auth()->user()->hasPermission($permission)) {
            abort(403, $message);
        }
    }

    /**
     * Poll QoS analytics data every 3 seconds for real-time benchmark updates
     */
    public function refreshAnalytics()
    {
        // Auto refreshed by Livewire poll
    }

    /**
     * Start a new testing session
     */
    public function startSession()
    {
        $this->authorizePermission('session.manage', 'Anda tidak memiliki hak akses untuk mengelola sesi pengujian.');

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
        $this->authorizePermission('session.manage', 'Anda tidak memiliki hak akses untuk mengelola sesi pengujian.');

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
     * Export raw sensor_logs (only logs bound to a testing session) as CSV
     * for the thesis appendix / statistical analysis.
     */
    public function exportCsv(): StreamedResponse
    {
        $this->authorizePermission('qos.view', 'Anda tidak memiliki hak akses ke modul analisis ini.');

        $sessionId = $this->filterSessionId;
        $filename = 'siasap-qos-sensor-logs-'.($sessionId ? 'sesi-'.$sessionId : 'semua-sesi').'-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($sessionId) {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::CSV_COLUMNS);

            DB::table('sensor_logs')
                ->join('testing_sessions', 'testing_sessions.id', '=', 'sensor_logs.testing_session_id')
                ->when($sessionId, fn ($q) => $q->where('sensor_logs.testing_session_id', $sessionId))
                ->select([
                    'sensor_logs.id',
                    'sensor_logs.testing_session_id as session_id',
                    'testing_sessions.name as session_name',
                    'sensor_logs.topology_type',
                    'sensor_logs.node_id',
                    'sensor_logs.seq_number',
                    'sensor_logs.rssi',
                    'sensor_logs.hop_count',
                    'sensor_logs.co2',
                    'sensor_logs.nh3',
                    'sensor_logs.co',
                    'sensor_logs.h2',
                    'sensor_logs.ku_value',
                    'sensor_logs.ku_status',
                    'sensor_logs.created_at',
                ])
                ->orderBy('sensor_logs.id')
                ->chunk(1000, function ($rows) use ($out) {
                    foreach ($rows as $row) {
                        fputcsv($out, array_map(fn ($col) => $row->{$col}, self::CSV_COLUMNS));
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        $activeSession = TestingSession::active()->first();
        $sessionsList = TestingSession::latest()->take(10)->get();

        // Calculate metrics for Star vs Mesh
        $starQos = QosCalculator::forTopology('star', $this->filterSessionId);
        $meshQos = QosCalculator::forTopology('mesh', $this->filterSessionId);

        return view('livewire.qos-analytics', [
            'activeSession' => $activeSession,
            'sessionsList' => $sessionsList,
            'starQos' => $starQos,
            'meshQos' => $meshQos,
            'nodeNames' => Node::query()->pluck('name', 'id'),
            'canManageSessions' => (bool) auth()->user()?->hasPermission('session.manage'),
            'sendInterval' => QosCalculator::sendIntervalSeconds(),
        ]);
    }
}

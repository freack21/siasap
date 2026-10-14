<?php

namespace App\Livewire;

use App\Models\Node;
use App\Models\SensorLog;
use App\Models\TestingSession;
use App\Support\AirQuality;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts::admin')]
#[Title('Data Sensor — SIASAP')]
class TelemetryDashboard extends Component
{
    use WithPagination;

    /** Nilai filter sesi untuk data yang masuk di luar sesi pengujian. */
    public const OUTSIDE_SESSION = 'none';

    #[Url(as: 'node', except: '')]
    public string $node = '';

    #[Url(as: 'kategori', except: '')]
    public string $category = '';

    #[Url(as: 'sesi', except: '')]
    public string $session = '';

    #[Url(as: 'dari', except: '')]
    public string $dateFrom = '';

    #[Url(as: 'sampai', except: '')]
    public string $dateTo = '';

    public function mount(): void
    {
        $this->authorizeView();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['node', 'category', 'session', 'dateFrom', 'dateTo'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('node', 'category', 'session', 'dateFrom', 'dateTo');
        $this->resetPage();
    }

    public function export(): StreamedResponse
    {
        $this->authorizeView();

        $query = $this->filteredQuery()->with(['node.room', 'testingSession']);
        $filename = 'data-sensor-siasap-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'id', 'waktu', 'node_id', 'node', 'ruangan', 'co2_ppm', 'nh3_ppm', 'co_ppm', 'h2_ppm',
                'ku_value', 'ku_status', 'ku_dominant', 'rssi_dbm', 'hop_count', 'seq_number',
                'topology_type', 'testing_session_id', 'sesi',
            ]);

            foreach ($query->lazyById(500) as $log) {
                fputcsv($out, [
                    $log->id,
                    $log->created_at?->format('Y-m-d H:i:s'),
                    $log->node_id,
                    $log->node?->name,
                    $log->node?->room?->name,
                    $log->co2,
                    $log->nh3,
                    $log->co,
                    $log->h2,
                    $log->ku_value,
                    $log->ku_status,
                    $log->ku_dominant,
                    $log->rssi,
                    $log->hop_count,
                    $log->seq_number,
                    $log->topology_type,
                    $log->testing_session_id,
                    $log->testingSession?->name,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        $base = $this->filteredQuery();

        $summary = (clone $base)->toBase()
            ->selectRaw('COUNT(*) as total, MIN(created_at) as first_at, MAX(created_at) as last_at')
            ->first();

        return view('livewire.telemetry-dashboard', [
            'logs' => (clone $base)
                ->with(['node.room', 'testingSession'])
                ->latest()
                ->latest('id')
                ->paginate(25),
            'total' => (int) ($summary->total ?? 0),
            'firstAt' => $summary?->first_at ? Carbon::parse($summary->first_at) : null,
            'lastAt' => $summary?->last_at ? Carbon::parse($summary->last_at) : null,
            'nodes' => Node::with('room')->orderBy('room_id')->orderBy('name')->get(),
            'sessions' => TestingSession::latest('started_at')->get(['id', 'name', 'topology_type', 'status']),
            'categories' => AirQuality::categories(),
            'gases' => AirQuality::gases(),
            'hasFilters' => $this->node !== '' || $this->category !== '' || $this->session !== '' || $this->dateFrom !== '' || $this->dateTo !== '',
        ]);
    }

    private function filteredQuery(): Builder
    {
        return SensorLog::query()
            ->when($this->node !== '', fn (Builder $q) => $q->where('node_id', (int) $this->node))
            ->when(
                array_key_exists($this->category, AirQuality::categories()),
                fn (Builder $q) => $q->where('ku_status', $this->category)
            )
            ->when($this->session === self::OUTSIDE_SESSION, fn (Builder $q) => $q->whereNull('testing_session_id'))
            ->when(
                $this->session !== '' && $this->session !== self::OUTSIDE_SESSION,
                fn (Builder $q) => $q->where('testing_session_id', (int) $this->session)
            )
            ->when($this->validDate($this->dateFrom), fn (Builder $q) => $q->where('created_at', '>=', Carbon::parse($this->dateFrom)->startOfDay()))
            ->when($this->validDate($this->dateTo), fn (Builder $q) => $q->where('created_at', '<=', Carbon::parse($this->dateTo)->endOfDay()));
    }

    private function validDate(string $value): bool
    {
        return $value !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 && strtotime($value) !== false;
    }

    private function authorizeView(): void
    {
        abort_unless(auth()->user()?->hasPermission('telemetry.view'), 403, 'Anda tidak memiliki hak akses ke halaman ini.');
    }
}

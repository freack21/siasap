<?php

namespace App\Livewire;

use App\Models\Node;
use App\Models\Room;
use App\Models\SensorLog;
use App\Support\AirQuality;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts::public')]
#[Title('Riwayat Kualitas Udara — SIASAP')]
class UserHistory extends Component
{
    use WithPagination;

    /** ID ruangan ('' = semua ruangan). */
    #[Url(as: 'ruangan', except: '')]
    public string $room = '';

    /** Kategori KU ('' = semua kategori). */
    #[Url(as: 'kategori', except: '')]
    public string $category = '';

    /** Tanggal awal (Y-m-d). */
    #[Url(as: 'dari', except: '')]
    public string $from = '';

    /** Tanggal akhir (Y-m-d). */
    #[Url(as: 'sampai', except: '')]
    public string $to = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['room', 'category', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('room', 'category', 'from', 'to');
        $this->resetPage();
    }

    /**
     * Unduh data riwayat (sesuai filter aktif) sebagai CSV.
     */
    public function downloadCsv(): StreamedResponse
    {
        $query = $this->filteredQuery()
            ->with('node.room')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $filename = 'riwayat-kualitas-udara-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar terbaca rapi di Excel

            fputcsv($out, ['waktu', 'ruangan', 'indeks_ku', 'kategori', 'parameter_dominan', 'co2_ppm', 'nh3_ppm', 'co_ppm', 'h2_ppm']);

            foreach ($query->lazy(1000) as $log) {
                fputcsv($out, [
                    $log->created_at?->format('Y-m-d H:i:s'),
                    $this->roomName($log),
                    $log->ku_value,
                    $log->ku_status,
                    $log->ku_dominant ?? '',
                    $log->co2,
                    $log->nh3,
                    $log->co,
                    $log->h2,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function hasFilters(): bool
    {
        return $this->room !== '' || $this->category !== '' || $this->from !== '' || $this->to !== '';
    }

    /**
     * Query dasar SensorLog dengan seluruh filter aktif diterapkan.
     */
    protected function filteredQuery(): Builder
    {
        $query = SensorLog::query();

        if ($this->room !== '' && ctype_digit($this->room)) {
            $query->whereIn('node_id', Node::where('room_id', (int) $this->room)->select('id'));
        }

        if ($this->category !== '' && array_key_exists($this->category, AirQuality::categories())) {
            $query->where('ku_status', $this->category);
        }

        if ($from = $this->parseDate($this->from)) {
            $query->where('created_at', '>=', $from->startOfDay());
        }

        if ($to = $this->parseDate($this->to)) {
            $query->where('created_at', '<=', $to->endOfDay());
        }

        return $query;
    }

    protected function parseDate(string $value): ?Carbon
    {
        if ($value === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function roomName(SensorLog $log): string
    {
        return $log->node?->room?->name ?? $log->node?->name ?? 'Perangkat #'.$log->node_id;
    }

    /**
     * Ringkasan data terfilter: jumlah, rata-rata indeks, kategori terburuk, sebaran.
     *
     * @return array{count: int, average: ?float, worst: ?string, distribution: array<string, array{count: int, percent: float}>}
     */
    protected function summary(): array
    {
        $base = $this->filteredQuery();

        $count = (clone $base)->count();
        $average = $count > 0 ? (float) (clone $base)->avg('ku_value') : null;

        $counts = (clone $base)
            ->selectRaw('ku_status, COUNT(*) as total')
            ->groupBy('ku_status')
            ->pluck('total', 'ku_status');

        $distribution = [];
        $worst = null;
        foreach (array_keys(AirQuality::categories()) as $status) {
            $n = (int) ($counts[$status] ?? 0);
            $distribution[$status] = [
                'count' => $n,
                'percent' => $count > 0 ? round($n / $count * 100, 1) : 0.0,
            ];
            if ($n > 0) {
                $worst = $status; // kategori diurutkan dari terbaik ke terburuk
            }
        }

        return compact('count', 'average', 'worst', 'distribution');
    }

    protected function periodLabel(): string
    {
        $from = $this->parseDate($this->from);
        $to = $this->parseDate($this->to);

        return match (true) {
            $from && $to && $from->isSameDay($to) => $from->translatedFormat('d M Y'),
            $from && $to => $from->translatedFormat('d M Y').' – '.$to->translatedFormat('d M Y'),
            (bool) $from => 'Sejak '.$from->translatedFormat('d M Y'),
            (bool) $to => 'Hingga '.$to->translatedFormat('d M Y'),
            default => 'Seluruh waktu',
        };
    }

    public function render()
    {
        $logs = $this->filteredQuery()
            ->with('node.room')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('livewire.user-history', [
            'logs' => $logs,
            'rooms' => Room::orderBy('name')->get(['id', 'name']),
            'categories' => AirQuality::categories(),
            'gases' => AirQuality::gases(),
            'summary' => $this->summary(),
            'period' => $this->periodLabel(),
        ]);
    }
}

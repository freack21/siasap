<?php

namespace App\Livewire;

use App\Models\TopologyTestDevice;
use App\Models\TopologyTestLog;
use App\Support\AirQuality;
use App\Support\TopologyQos;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Halaman tersembunyi uji topologi star dan mesh untuk tiga perangkat IoT: status setiap
 * perangkat, ringkasan QoS per perangkat dan topologi, serta daftar paket yang diterima.
 * Tidak ditautkan dari menu SIMKU; datanya terpisah dari halaman SIMKU.
 */
#[Layout('layouts::uji')]
#[Title('Uji Topologi Star & Mesh — SIMKU')]
class TopologyTest extends Component
{
    use WithPagination;

    /** Topologi ('' = semua). */
    #[Url(as: 'topologi', except: '')]
    public string $topology = '';

    /** Awal rentang waktu terima (Y-m-d\TH:i). */
    #[Url(as: 'dari', except: '')]
    public string $from = '';

    /** Akhir rentang waktu terima (Y-m-d\TH:i). */
    #[Url(as: 'sampai', except: '')]
    public string $to = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['topology', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('topology', 'from', 'to');
        $this->resetPage();
    }

    /**
     * Unduh data sesuai filter aktif sebagai CSV.
     */
    public function downloadCsv(): StreamedResponse
    {
        $query = $this->filteredQuery()->orderBy('created_at')->orderBy('id');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar terbaca rapi di Excel

            fputcsv($out, [
                'waktu_terima', 'waktu_perangkat', 'device_id', 'topologi', 'seq_number', 'hop_count', 'rssi', 'delay_ms', 'payload_bytes',
                'co2_ppm', 'nh3_ppm', 'co_ppm', 'h2_ppm', 'kategori_keseluruhan', 'penyebab_utama', 'rekomendasi',
            ], escape: '');

            foreach ($query->lazy(1000) as $log) {
                fputcsv($out, [
                    $log->created_at?->format('Y-m-d H:i:s'),
                    $log->recorded_at?->format('Y-m-d H:i:s'),
                    $log->device_id,
                    $log->topology,
                    $log->seq_number,
                    $log->hop_count,
                    $log->rssi,
                    $log->delay_ms,
                    $log->payload_bytes,
                    $log->co2,
                    $log->nh3,
                    $log->co,
                    $log->h2,
                    $log->overall_category,
                    $log->dominant_gases,
                    $log->recommendation_level,
                ], escape: '');
            }

            fclose($out);
        }, 'uji-topologi-'.now()->format('Ymd_His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render(): View
    {
        return view('livewire.topology-test', [
            'devices' => $this->devices(),
            'qos' => $this->qos(),
            'packets' => $this->filteredQuery()->orderByDesc('created_at')->orderByDesc('id')->paginate(20),
            'topologies' => config('topology_test.topologies'),
            'endpoint' => url('api/'.config('topology_test.path')),
            'onlineSeconds' => AirQuality::onlineThresholdSeconds(),
        ]);
    }

    /**
     * Data terbaru setiap perangkat uji (sesuai filter topologi, tanpa filter waktu).
     *
     * @return Collection<int, array{id: string, name: string, latest: ?TopologyTestLog, connected: bool, handshake: ?TopologyTestDevice}>
     */
    protected function devices(): Collection
    {
        $threshold = now()->subSeconds(AirQuality::onlineThresholdSeconds());
        $handshakes = TopologyTestDevice::all()->keyBy('device_id');

        return collect(config('topology_test.devices'))->map(function (string $name, string $id) use ($threshold, $handshakes): array {
            $latest = TopologyTestLog::query()
                ->where('device_id', $id)
                ->when($this->topology !== '', fn (Builder $query) => $query->where('topology', $this->topology))
                ->latest('created_at')
                ->latest('id')
                ->first();

            return [
                'id' => $id,
                'name' => $name,
                'latest' => $latest,
                'connected' => $latest !== null && $latest->created_at->gte($threshold),
                'handshake' => $handshakes->get($id),
            ];
        })->values();
    }

    /**
     * Ringkasan QoS setiap pasangan perangkat dan topologi yang memiliki data.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function qos(): Collection
    {
        return $this->filteredQuery()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['device_id', 'topology', 'recorded_at', 'rssi', 'seq_number', 'hop_count', 'delay_ms', 'payload_bytes', 'created_at'])
            ->groupBy(fn (TopologyTestLog $log) => $log->device_id.'|'.($log->topology ?? '-'))
            ->sortKeys()
            ->map(function (Collection $logs, string $key): array {
                [$device, $topology] = explode('|', $key);

                return ['device_id' => $device, 'topology' => $topology, ...TopologyQos::summarize($logs)];
            })
            ->values();
    }

    /**
     * @return Builder<TopologyTestLog>
     */
    protected function filteredQuery(): Builder
    {
        return TopologyTestLog::query()
            ->when(in_array($this->topology, config('topology_test.topologies'), true),
                fn (Builder $query) => $query->where('topology', $this->topology))
            ->when($this->parseTime($this->from), fn (Builder $query, Carbon $from) => $query->where('created_at', '>=', $from))
            ->when($this->parseTime($this->to), fn (Builder $query, Carbon $to) => $query->where('created_at', '<=', $to->endOfMinute()));
    }

    private function parseTime(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d\TH:i', $value) ?: null;
        } catch (InvalidFormatException) {
            return null;
        }
    }
}

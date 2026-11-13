<?php

namespace App\Console\Commands;

use App\Models\Node;
use App\Models\SensorLog;
use App\Support\ScenarioSummary;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ExportRoomTestCommand extends Command
{
    /**
     * Folder ekspor pada disk local (storage/app/private).
     */
    public const FOLDER = 'uji-ruangan';

    /**
     * Berkas yang ditulis untuk setiap tanggal pengujian.
     *
     * @var list<string>
     */
    public const FILES = ['ringkasan.md', 'ringkasan.json', 'data-pengujian.csv'];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telemetry:ekspor-uji-ruangan
        {--tanggal= : Tanggal pengujian ("Y-m-d"), mis. 2026-11-13}
        {--skenario=* : Skenario "Nama=HH:MM-HH:MM" menurut waktu terima server; boleh diulang}
        {--perangkat=AIR-001 : Kode perangkat}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ekspor data dan ringkasan pengujian kondisi ruangan ke storage untuk bahan naskah';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = (string) $this->option('tanggal');
        $day = $this->parseDate($date);
        $scenarios = $this->parseScenarios($day);

        if ($day === null || $scenarios === null) {
            return self::INVALID;
        }

        $code = (string) $this->option('perangkat');
        $node = Node::where('code', $code)->first();

        if (! $node) {
            $this->error("Perangkat {$code} tidak terdaftar.");

            return self::INVALID;
        }

        $results = collect($scenarios)->map(function (array $scenario) use ($node) {
            $logs = SensorLog::where('node_id', $node->id)
                ->where('created_at', '>=', $scenario['from'])
                ->where('created_at', '<', $scenario['to'])
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            return [...$scenario, 'logs' => $logs, 'summary' => ScenarioSummary::summarize($logs)];
        });

        $folder = self::FOLDER.'/'.$date;
        $disk = Storage::disk('local');
        $disk->put("{$folder}/data-pengujian.csv", $this->csv($results));
        $disk->put("{$folder}/ringkasan.json", json_encode($this->json($results, $date, $code), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $disk->put("{$folder}/ringkasan.md", $this->markdown($results, $date, $code));

        foreach ($results as $result) {
            $this->line(sprintf('%-20s %s–%s  %d data', $result['name'], $result['from']->format('H:i'), $result['to']->format('H:i'), $result['summary']['count']));
        }

        $this->newLine();
        $this->info('Tersimpan di storage/app/private/'.$folder.'/ ('.implode(', ', self::FILES).').');
        $this->line('Unduh lewat: '.route('uji-ruangan.index'));

        return self::SUCCESS;
    }

    private function parseDate(string $date): ?Carbon
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 && checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4))) {
            return Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        }

        $this->error('--tanggal wajib diisi dengan format "Y-m-d", mis. "2026-11-13".');

        return null;
    }

    /**
     * Skenario dalam urutan masukan. Rentang setengah terbuka [mulai, selesai) agar data tepat
     * pada pergantian skenario hanya masuk ke skenario berikutnya.
     *
     * @return list<array{name: string, from: Carbon, to: Carbon}>|null
     */
    private function parseScenarios(?Carbon $day): ?array
    {
        $values = (array) $this->option('skenario');

        if ($values === []) {
            $this->error('--skenario wajib diisi, mis. --skenario="Ruangan normal=17:45-17:50".');

            return null;
        }

        $scenarios = [];

        foreach ($values as $value) {
            if (preg_match('/^(.+)=([01]\d|2[0-3]):([0-5]\d)-([01]\d|2[0-3]):([0-5]\d)$/', trim($value), $match) !== 1) {
                $this->error("Format skenario salah: \"{$value}\". Contoh: \"Ruangan normal=17:45-17:50\".");

                return null;
            }

            if ($day === null) {
                continue;
            }

            $from = $day->copy()->setTime((int) $match[2], (int) $match[3]);
            $to = $day->copy()->setTime((int) $match[4], (int) $match[5]);

            if ($to->lessThanOrEqualTo($from)) {
                $this->error("Jam selesai skenario \"{$match[1]}\" harus setelah jam mulai.");

                return null;
            }

            $scenarios[] = ['name' => trim($match[1]), 'from' => $from, 'to' => $to];
        }

        return $scenarios;
    }

    /**
     * Seluruh data pengujian, satu baris per pembacaan, dengan kolom skenario.
     *
     * @param  Collection<int, array{name: string, from: Carbon, to: Carbon, logs: Collection<int, SensorLog>, summary: array<string, mixed>}>  $results
     */
    private function csv(Collection $results): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['skenario', 'waktu', 'co2_ppm', 'nh3_ppm', 'co_ppm', 'h2_ppm', 'kategori_co2', 'kategori_nh3', 'kategori_co', 'kategori_h2', 'kategori_keseluruhan', 'penyebab_utama', 'rekomendasi'], escape: '');

        foreach ($results as $result) {
            foreach ($result['logs'] as $log) {
                fputcsv($handle, [
                    $result['name'],
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->co2, $log->nh3, $log->co, $log->h2,
                    $log->co2_category, $log->nh3_category, $log->co_category, $log->h2_category,
                    $log->overall_category,
                    ScenarioSummary::causeLabel($log),
                    $log->recommendation_level,
                ], escape: '');
            }
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * @param  Collection<int, array{name: string, from: Carbon, to: Carbon, logs: Collection<int, SensorLog>, summary: array<string, mixed>}>  $results
     * @return array<string, mixed>
     */
    private function json(Collection $results, string $date, string $code): array
    {
        return [
            'tanggal' => $date,
            'perangkat' => $code,
            'catatan' => 'Waktu adalah waktu terima server (WIB). Rentang skenario setengah terbuka: [mulai, selesai).',
            'skenario' => $results->map(fn (array $result) => [
                'nama' => $result['name'],
                'mulai' => $result['from']->format('Y-m-d H:i:s'),
                'selesai' => $result['to']->format('Y-m-d H:i:s'),
                ...$result['summary'],
            ])->values()->all(),
        ];
    }

    /**
     * Ringkasan yang mudah dibaca untuk mengisi Tabel 4.7 dan Tabel 4.8 naskah.
     *
     * @param  Collection<int, array{name: string, from: Carbon, to: Carbon, logs: Collection<int, SensorLog>, summary: array<string, mixed>}>  $results
     */
    private function markdown(Collection $results, string $date, string $code): string
    {
        $lines = [
            "# Pengujian kondisi ruangan {$date}",
            '',
            "Perangkat {$code}. Waktu adalah waktu terima server (WIB). Data tepat pada jam selesai masuk ke skenario berikutnya.",
            '',
            '| Skenario | Mulai | Selesai | Jumlah data | Selang rata-rata | Kategori dominan | Rekomendasi tampil |',
            '|---|---|---|---|---|---|---|',
        ];

        foreach ($results as $result) {
            $summary = $result['summary'];
            $lines[] = sprintf('| %s | %s | %s | %d | %s | %s | %s |',
                $result['name'],
                $result['from']->format('H.i'),
                $result['to']->format('H.i'),
                $summary['count'],
                $summary['interval_avg'] === null ? '-' : $this->number($summary['interval_avg'], 1).' detik',
                $summary['dominant'] === null ? '-' : "{$summary['dominant']['category']} ({$summary['dominant']['count']} dari {$summary['count']})",
                implode(' → ', $summary['levels']) ?: '-',
            );
        }

        foreach ($results as $result) {
            $summary = $result['summary'];
            $lines[] = '';
            $lines[] = "## {$result['name']} ({$result['from']->format('H.i')}–{$result['to']->format('H.i')} WIB)";
            $lines[] = '';

            if ($summary['count'] === 0) {
                $lines[] = 'Tidak ada data pada rentang ini.';

                continue;
            }

            $lines[] = "- Jumlah data: {$summary['count']}";
            $lines[] = '- Selang antardata: '.($summary['interval_avg'] === null ? '- (hanya satu data)' : 'rata-rata '.$this->number($summary['interval_avg'], 1).' detik, terlama '.$summary['interval_max'].' detik');
            $lines[] = "- Kategori dominan: {$summary['dominant']['category']} ({$summary['dominant']['count']} dari {$summary['count']} data)";
            $lines[] = '- Sebaran kategori: '.collect($summary['spread'])->map(fn (int $count, string $category) => "{$category} {$count}")->implode(', ');
            $lines[] = '- Rentang nilai ppm: '.collect($summary['gases'])->map(fn (array $gas) => "{$gas['symbol']} {$this->number($gas['min'])}–{$this->number($gas['max'])}")->implode('; ');
            $lines[] = '';
            $lines[] = '| Gas | Awal | Terendah | Tertinggi | Akhir |';
            $lines[] = '|---|---|---|---|---|';

            foreach ($summary['gases'] as $gas) {
                $lines[] = "| {$gas['symbol']} | {$this->number($gas['first'])} | {$this->number($gas['min'])} | {$this->number($gas['max'])} | {$this->number($gas['last'])} |";
            }

            $lines[] = '';
            $lines[] = '| Waktu | Kategori keseluruhan | Penyebab utama | Rekomendasi |';
            $lines[] = '|---|---|---|---|';

            foreach ($summary['changes'] as $change) {
                $lines[] = "| {$change['time']} | {$change['category']} | {$change['cause']} | {$change['level']} |";
            }
        }

        return implode("\n", $lines)."\n";
    }

    private function number(float $value, int $decimals = 2): string
    {
        return number_format($value, $decimals, ',', '.');
    }
}

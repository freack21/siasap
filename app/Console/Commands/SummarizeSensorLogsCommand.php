<?php

namespace App\Console\Commands;

use App\Models\Node;
use App\Models\SensorLog;
use App\Support\ScenarioSummary;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SummarizeSensorLogsCommand extends Command
{
    /**
     * Format waktu yang diterima opsi --dari dan --sampai.
     *
     * @var list<string>
     */
    private const TIME_FORMATS = ['Y-m-d H:i:s', 'Y-m-d H:i'];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telemetry:ringkasan
        {--dari= : Awal skenario menurut waktu terima server ("Y-m-d H:i" atau "Y-m-d H:i:s")}
        {--sampai= : Akhir skenario menurut waktu terima server ("Y-m-d H:i" atau "Y-m-d H:i:s")}
        {--perangkat=AIR-001 : Kode perangkat}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ringkas data sensor satu skenario pengujian ruangan (jumlah data, kategori, rentang ppm, rekomendasi)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $from = $this->parseTime('dari');
        $to = $this->parseTime('sampai');

        if ($from === null || $to === null) {
            return self::INVALID;
        }

        if ($from->greaterThan($to)) {
            $this->error('--dari tidak boleh setelah --sampai.');

            return self::INVALID;
        }

        $code = (string) $this->option('perangkat');
        $node = Node::where('code', $code)->first();

        if (! $node) {
            $this->error("Perangkat {$code} tidak terdaftar.");

            return self::INVALID;
        }

        $logs = SensorLog::where('node_id', $node->id)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $this->line("Rentang   : {$from->format('Y-m-d H:i:s')} s.d. {$to->format('Y-m-d H:i:s')} (waktu terima server)");
        $this->line("Perangkat : {$code}");

        if ($logs->isEmpty()) {
            $this->warn('Tidak ada data pada rentang tersebut.');

            return self::SUCCESS;
        }

        $summary = ScenarioSummary::summarize($logs);

        $this->newLine();
        $this->line("Jumlah data diterima : <comment>{$summary['count']}</comment>");
        $this->line('Selang antardata     : '.($summary['interval_avg'] === null
            ? '- (hanya satu data)'
            : 'rata-rata '.number_format($summary['interval_avg'], 1, ',', '.').' detik, terlama '.number_format($summary['interval_max'], 0, ',', '.').' detik'));
        $this->line("Kategori dominan     : {$summary['dominant']['category']} ({$summary['dominant']['count']} dari {$summary['count']} data)");
        $this->line('Sebaran kategori     : '.collect($summary['spread'])->map(fn (int $count, string $category) => "{$category} {$count}")->implode(', '));

        $this->newLine();
        $this->table(['Gas', 'Awal', 'Terendah', 'Tertinggi', 'Akhir'], collect($summary['gases'])
            ->map(fn (array $gas) => [$gas['symbol'], $this->ppm($gas['first']), $this->ppm($gas['min']), $this->ppm($gas['max']), $this->ppm($gas['last'])])
            ->values()->all());

        $this->line('Rentang nilai ppm    : '.collect($summary['gases'])
            ->map(fn (array $gas) => "{$gas['symbol']} {$this->ppm($gas['min'])}–{$this->ppm($gas['max'])}")
            ->implode('; '));

        $this->line('Rekomendasi tampil   : '.implode(' → ', $summary['levels']));

        $this->newLine();
        $this->line('Perubahan kategori keseluruhan:');
        $this->table(['Waktu', 'Kategori', 'Penyebab utama', 'Rekomendasi'], collect($summary['changes'])->map(fn (array $change) => [
            $change['time'],
            $change['category'],
            $change['cause'],
            $change['level'],
        ])->all());

        return self::SUCCESS;
    }

    /**
     * Baca opsi waktu wajib; null (dengan pesan galat) bila kosong atau formatnya salah.
     */
    private function parseTime(string $option): ?Carbon
    {
        $value = trim((string) $this->option($option));

        foreach (self::TIME_FORMATS as $format) {
            try {
                $time = Carbon::createFromFormat($format, $value);
            } catch (InvalidFormatException) {
                continue;
            }

            if ($time->format($format) === $value) {
                return $format === 'Y-m-d H:i' && $option === 'sampai' ? $time->endOfMinute() : $time;
            }
        }

        $this->error("--{$option} wajib diisi dengan format \"Y-m-d H:i\" atau \"Y-m-d H:i:s\", mis. \"2026-11-07 14:05\".");

        return null;
    }

    private function ppm(float $value): string
    {
        return number_format($value, 2, ',', '.');
    }
}

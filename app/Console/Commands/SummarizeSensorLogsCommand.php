<?php

namespace App\Console\Commands;

use App\Models\Node;
use App\Models\SensorLog;
use App\Support\AirQuality;
use App\Support\AirQualityClassifier;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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

        $this->newLine();
        $this->line("Jumlah data diterima : <comment>{$logs->count()}</comment>");
        $this->line('Selang antardata     : '.$this->intervalSummary($logs));
        $this->line('Kategori dominan     : '.$this->dominantCategory($logs));
        $this->line('Sebaran kategori     : '.$this->categorySpread($logs));

        $this->newLine();
        $this->table(['Gas', 'Awal', 'Terendah', 'Tertinggi', 'Akhir'], collect(AirQuality::gases())
            ->map(fn (array $gas, string $key) => [
                $gas['symbol'],
                $this->ppm($logs->first()->{$key}),
                $this->ppm($logs->min($key)),
                $this->ppm($logs->max($key)),
                $this->ppm($logs->last()->{$key}),
            ])->values()->all());

        $this->line('Rentang nilai ppm    : '.collect(AirQuality::gases())
            ->map(fn (array $gas, string $key) => "{$gas['symbol']} {$this->ppm($logs->min($key))}–{$this->ppm($logs->max($key))}")
            ->implode('; '));

        $changes = $this->categoryChanges($logs);
        $this->line('Rekomendasi tampil   : '.$changes->pluck('level')->unique()->implode(' → '));

        $this->newLine();
        $this->line('Perubahan kategori keseluruhan:');
        $this->table(['Waktu', 'Kategori', 'Penyebab utama', 'Rekomendasi'], $changes->map(fn (array $change) => [
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

    /**
     * Rata-rata dan selang terlama antara dua data berurutan.
     *
     * @param  Collection<int, SensorLog>  $logs
     */
    private function intervalSummary(Collection $logs): string
    {
        if ($logs->count() < 2) {
            return '- (hanya satu data)';
        }

        $gaps = $logs->sliding(2)->map(fn (Collection $pair) => $pair->first()->created_at->diffInSeconds($pair->last()->created_at));

        return 'rata-rata '.number_format($gaps->avg(), 1, ',', '.').' detik, terlama '.number_format($gaps->max(), 0, ',', '.').' detik';
    }

    /**
     * Kategori keseluruhan yang paling sering muncul; bila seri, dipilih kategori yang lebih buruk.
     *
     * @param  Collection<int, SensorLog>  $logs
     */
    private function dominantCategory(Collection $logs): string
    {
        $counts = $logs->countBy('overall_category');
        $category = $counts->keys()
            ->sortByDesc(fn (string $category) => [$counts[$category], AirQualityClassifier::rank($category)])
            ->first();

        return "{$category} ({$counts[$category]} dari {$logs->count()} data)";
    }

    /**
     * @param  Collection<int, SensorLog>  $logs
     */
    private function categorySpread(Collection $logs): string
    {
        $counts = $logs->countBy('overall_category');

        return collect(config('air_quality.category_order'))
            ->filter(fn (string $category) => $counts->has($category))
            ->map(fn (string $category) => "{$category} {$counts[$category]}")
            ->implode(', ');
    }

    /**
     * Data pertama dan setiap data yang kategori keseluruhannya berbeda dari data sebelumnya.
     *
     * @param  Collection<int, SensorLog>  $logs
     * @return Collection<int, array{time: string, category: string, cause: string, level: string}>
     */
    private function categoryChanges(Collection $logs): Collection
    {
        $previous = null;

        return $logs->filter(function (SensorLog $log) use (&$previous) {
            $changed = $log->overall_category !== $previous;
            $previous = $log->overall_category;

            return $changed;
        })->map(fn (SensorLog $log) => [
            'time' => $log->created_at->format('H:i:s'),
            'category' => $log->overall_category,
            'cause' => collect($log->dominantGases())->map(fn (string $gas) => AirQuality::gases()[$gas]['symbol'] ?? $gas)->implode(', ') ?: '-',
            'level' => $log->recommendation_level,
        ])->values();
    }

    private function ppm(float $value): string
    {
        return number_format($value, 2, ',', '.');
    }
}

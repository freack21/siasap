<?php

namespace App\Console\Commands;

use App\Models\UeqResponse;
use App\Support\Ueq;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UeqReportCommand extends Command
{
    protected $signature = 'ueq:report {--csv=storage/app/ueq-export.csv : Lokasi berkas CSV data mentah}';

    protected $description = 'Ringkasan hasil kuesioner UEQ per skala dan ekspor data mentah ke CSV';

    public function handle(): int
    {
        $responses = UeqResponse::orderBy('id')->get();
        $summary = Ueq::summary($responses);

        $this->info("Jumlah responden: {$summary['count']}");

        $format = fn (?float $value): string => $value === null ? '-' : number_format($value, 2, '.', '');

        $this->table(
            ['Skala', 'Rerata', 'SD', 'Alpha', 'Benchmark'],
            collect($summary['scales'])->map(fn (array $row, string $scale) => [
                $scale,
                $format($row['mean']),
                $format($row['sd']),
                $format($row['alpha']),
                $row['benchmark'] ?? '-',
            ])->values()->all(),
        );

        $path = $this->csvPath((string) $this->option('csv'));
        File::ensureDirectoryExists(dirname($path));

        $handle = fopen($path, 'w');
        fputcsv($handle, ['id', 'waktu', 'nama', ...array_map(fn (int $i) => "i{$i}", range(1, Ueq::ITEM_COUNT))], escape: '');

        foreach ($responses as $response) {
            fputcsv($handle, [
                $response->id,
                $response->created_at?->format('Y-m-d H:i:s'),
                $this->csvSafe($response->respondent_name),
                ...array_values($response->answers),
            ], escape: '');
        }

        fclose($handle);

        $this->info("CSV ditulis ke {$path}");

        return self::SUCCESS;
    }

    /**
     * Cegah formula injection: sel teks dari formulir publik yang diawali
     * karakter formula diberi awalan petik tunggal agar dibaca sebagai teks.
     */
    private function csvSafe(?string $value): ?string
    {
        if ($value !== null && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }

    private function csvPath(string $path): string
    {
        return str_starts_with($path, '/') ? $path : base_path($path);
    }
}

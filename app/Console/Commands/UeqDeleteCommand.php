<?php

namespace App\Console\Commands;

use App\Models\UeqResponse;
use App\Support\Ueq;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class UeqDeleteCommand extends Command
{
    /**
     * Folder cadangan pada disk local (storage/app/private).
     */
    public const BACKUP_FOLDER = 'ueq-cadangan';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ueq:hapus
        {id* : Id jawaban yang dihapus; boleh rentang, mis. 1 2 9-47}
        {--force : Hapus tanpa konfirmasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus jawaban UEQ berdasarkan id, setelah menyimpan cadangannya ke CSV';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $ids = $this->parseIds((array) $this->argument('id'));

        if ($ids === null) {
            return self::INVALID;
        }

        $responses = UeqResponse::whereIn('id', $ids)->orderBy('id')->get();
        $missing = $ids->diff($responses->pluck('id'));

        if ($missing->isNotEmpty()) {
            $this->warn('Id tidak ditemukan dan dilewati: '.$missing->implode(', '));
        }

        if ($responses->isEmpty()) {
            $this->info('Tidak ada jawaban yang dihapus.');

            return self::SUCCESS;
        }

        $this->table(['Id', 'Waktu', 'Nama'], $responses->map(fn (UeqResponse $response) => [
            $response->id,
            $response->created_at?->format('Y-m-d H:i:s'),
            $response->respondent_name ?? '-',
        ])->all());

        if (! $this->option('force') && ! $this->confirm("Hapus {$responses->count()} jawaban UEQ di atas?")) {
            $this->info('Dibatalkan, tidak ada jawaban yang dihapus.');

            return self::SUCCESS;
        }

        $backup = self::BACKUP_FOLDER.'/ueq-hapus-'.now()->format('Ymd_His').'.csv';
        Storage::disk('local')->put($backup, $this->csv($responses));

        UeqResponse::whereIn('id', $responses->pluck('id'))->delete();

        $this->info("{$responses->count()} jawaban dihapus. Cadangan: storage/app/private/{$backup}");
        $this->line('Sisa jawaban UEQ: '.UeqResponse::count());

        return self::SUCCESS;
    }

    /**
     * Id tunggal dan rentang "a-b" menjadi daftar id unik; null (dengan pesan galat) bila formatnya salah.
     *
     * @param  list<string>  $values
     * @return Collection<int, int>|null
     */
    private function parseIds(array $values): ?Collection
    {
        $ids = collect();

        foreach ($values as $value) {
            if (preg_match('/^(\d+)(?:-(\d+))?$/', trim($value), $match) !== 1) {
                $this->error("Id tidak valid: \"{$value}\". Contoh: 1 2 9-47.");

                return null;
            }

            $from = (int) $match[1];
            $to = isset($match[2]) ? (int) $match[2] : $from;

            if ($to < $from) {
                $this->error("Rentang tidak valid: \"{$value}\".");

                return null;
            }

            $ids = $ids->merge(range($from, $to));
        }

        return $ids->unique()->sort()->values();
    }

    /**
     * Cadangan dengan kolom yang sama seperti ekspor ueq:report.
     *
     * @param  Collection<int, UeqResponse>  $responses
     */
    private function csv(Collection $responses): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['id', 'waktu', 'nama', ...array_map(fn (int $i) => "i{$i}", range(1, Ueq::ITEM_COUNT))], escape: '');

        foreach ($responses as $response) {
            fputcsv($handle, [
                $response->id,
                $response->created_at?->format('Y-m-d H:i:s'),
                $response->respondent_name,
                ...array_values($response->answers),
            ], escape: '');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}

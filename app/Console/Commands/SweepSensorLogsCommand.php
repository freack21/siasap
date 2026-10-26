<?php

namespace App\Console\Commands;

use App\Models\Node;
use App\Models\SensorLog;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class SweepSensorLogsCommand extends Command
{
    /**
     * Format waktu yang diterima opsi --dari dan --sampai.
     */
    private const DATE_FORMAT = 'Y-m-d';

    private const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telemetry:sweep
        {--dari= : Awal rentang waktu terima server (Y-m-d atau "Y-m-d H:i:s")}
        {--sampai= : Akhir rentang waktu terima server (Y-m-d atau "Y-m-d H:i:s")}
        {--perangkat= : Hanya data dari kode perangkat ini, mis. AIR-001}
        {--pratinjau : Tampilkan data yang akan dihapus tanpa menghapusnya}
        {--force : Lewati konfirmasi interaktif}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus data historis sensor pada rentang waktu tertentu (data lain tetap utuh)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->option('dari') && ! $this->option('sampai')) {
            $this->error('Isi minimal --dari atau --sampai. Untuk mengosongkan seluruh data, pakai php artisan telemetry:clear.');

            return self::INVALID;
        }

        $from = $this->parseTime('dari', fn (Carbon $date) => $date->startOfDay());
        $to = $this->parseTime('sampai', fn (Carbon $date) => $date->endOfDay());

        if ($from === false || $to === false) {
            return self::INVALID;
        }

        if ($from && $to && $from->greaterThan($to)) {
            $this->error('--dari tidak boleh setelah --sampai.');

            return self::INVALID;
        }

        $query = SensorLog::query()
            ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->where('created_at', '<=', $to));

        if ($code = $this->option('perangkat')) {
            $node = Node::where('code', $code)->first();

            if (! $node) {
                $this->error("Perangkat {$code} tidak terdaftar.");

                return self::INVALID;
            }

            $query->where('node_id', $node->id);
        }

        $count = (clone $query)->count();
        $this->line('Rentang : '.($from?->format(self::DATETIME_FORMAT) ?? 'awal data').' s.d. '.($to?->format(self::DATETIME_FORMAT) ?? 'akhir data'));
        $this->line('Perangkat : '.($code ?: 'semua'));
        $this->line("Data sensor yang cocok : <comment>{$count}</comment> dari ".SensorLog::count().' baris');

        if ($count === 0) {
            $this->info('Tidak ada data pada rentang tersebut.');

            return self::SUCCESS;
        }

        $this->line('Data pertama : '.(clone $query)->min('created_at').' | data terakhir : '.(clone $query)->max('created_at'));

        if ($this->option('pratinjau')) {
            $this->info('Mode pratinjau: tidak ada data yang dihapus.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Hapus {$count} baris data sensor di atas?", false)) {
            $this->warn('Penghapusan dibatalkan.');

            return self::SUCCESS;
        }

        $deleted = $query->delete();
        $this->info("{$deleted} baris data sensor dihapus. Tersisa ".SensorLog::count().' baris.');

        return self::SUCCESS;
    }

    /**
     * Baca opsi waktu: tanggal saja (dilebarkan dengan $widenDate) atau tanggal dan jam.
     * Mengembalikan null bila opsi kosong dan false bila formatnya salah.
     *
     * @param  callable(Carbon): Carbon  $widenDate
     */
    private function parseTime(string $option, callable $widenDate): Carbon|false|null
    {
        $value = trim((string) $this->option($option));

        if ($value === '') {
            return null;
        }

        foreach ([self::DATETIME_FORMAT => false, self::DATE_FORMAT => true] as $format => $dateOnly) {
            try {
                $time = Carbon::createFromFormat($format, $value);
            } catch (InvalidFormatException) {
                continue;
            }

            if ($time->format($format) === $value) {
                return $dateOnly ? $widenDate($time) : $time;
            }
        }

        $this->error("--{$option} harus berformat Y-m-d atau \"Y-m-d H:i:s\", mis. 2026-10-26 atau \"2026-10-26 22:15:00\".");

        return false;
    }
}

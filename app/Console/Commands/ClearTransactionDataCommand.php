<?php

namespace App\Console\Commands;

use App\Models\Node;
use App\Models\Room;
use App\Models\SensorLog;
use App\Models\TelemetryRejection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ClearTransactionDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telemetry:clear {--force : Lewati konfirmasi interaktif}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kosongkan seluruh data transaksi (log sensor & penolakan data) dan sisakan data master';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->newLine();
        $this->info('====================================================================');
        $this->info('       SIASAP - PEMBERSIHAN DATA TRANSAKSI SISTEM PEMANTAUAN        ');
        $this->info('====================================================================');

        $logCount = SensorLog::count();
        $rejectionCount = TelemetryRejection::count();

        $this->line('Ditemukan data transaksi saat ini:');
        $this->line(" - Log Data Sensor Telemetri : <comment>{$logCount}</comment> baris");
        $this->line(" - Catatan Data Ditolak      : <comment>{$rejectionCount}</comment> baris");
        $this->newLine();

        if ($logCount === 0 && $rejectionCount === 0) {
            $this->info('[INFO] Data transaksi sudah dalam keadaan kosong.');
            $this->displayMasterDataSummary();

            return Command::SUCCESS;
        }

        if (! $this->option('force')) {
            if (! $this->confirm('Apakah Anda yakin ingin mengosongkan seluruh data transaksi di atas?', false)) {
                $this->warn('Operasi pembersihan dibatalkan oleh pengguna.');

                return Command::SUCCESS;
            }
        }

        $this->line('Sedang mengosongkan data transaksi...');

        // Nonaktifkan foreign key checks sementara untuk menghindari constraint error
        Schema::disableForeignKeyConstraints();
        SensorLog::truncate();
        TelemetryRejection::truncate();
        Schema::enableForeignKeyConstraints();

        $this->newLine();
        $this->info(' [SUKSES] Seluruh data transaksi berhasil dikosongkan!');
        $this->newLine();

        $this->displayMasterDataSummary();

        $this->info('Sistem kini 100% bersih dan siap menerima data transmisi riil dari ESP32!');
        $this->newLine();

        return Command::SUCCESS;
    }

    /**
     * Tampilkan rekapitulasi data master yang tetap aman di database
     */
    private function displayMasterDataSummary(): void
    {
        $this->line('<fg=cyan>Status Data Master yang Dipertahankan (Tetap Utuh):</>');

        $this->table(
            ['Komponen Master Data', 'Jumlah Data Terdaftar', 'Status'],
            [
                ['Master Ruangan (rooms)', Room::count().' Ruangan', 'AMAN (Utuh)'],
                ['Master Node Perangkat (nodes)', Node::count().' Perangkat Node', 'AMAN (Utuh)'],
            ]
        );
        $this->newLine();
    }
}

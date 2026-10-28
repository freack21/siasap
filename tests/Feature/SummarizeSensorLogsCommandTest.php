<?php

namespace Tests\Feature;

use App\Models\Node;
use App\Models\Room;
use App\Models\SensorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SummarizeSensorLogsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected Node $device;

    protected function setUp(): void
    {
        parent::setUp();

        $room = Room::create(['code' => 'ROOM-01', 'name' => 'Ruang Uji']);
        $this->device = Node::create(['room_id' => $room->id, 'code' => 'AIR-001', 'name' => 'Perangkat 1']);
    }

    private function log(string $at, float $co2, float $co = 10, ?Node $node = null): void
    {
        SensorLog::factory()->withGases($co2, 20, $co, 5)->create([
            'node_id' => ($node ?? $this->device)->id,
            'created_at' => Carbon::parse($at),
            'updated_at' => Carbon::parse($at),
        ]);
    }

    public function test_summarizes_a_closed_room_scenario(): void
    {
        $this->log('2026-11-07 14:04:59', 99);
        $this->log('2026-11-07 14:05:00', 30);
        $this->log('2026-11-07 14:05:05', 45);
        $this->log('2026-11-07 14:05:10', 55);
        $this->log('2026-11-07 14:05:20', 62);
        $this->log('2026-11-07 14:05:25', 58);
        $this->log('2026-11-07 14:06:01', 140);

        $this->artisan('telemetry:ringkasan', ['--dari' => '2026-11-07 14:05', '--sampai' => '2026-11-07 14:05'])
            ->expectsOutputToContain('Jumlah data diterima : 5')
            ->expectsOutputToContain('rata-rata 6,3 detik, terlama 10 detik')
            ->expectsOutputToContain('Kategori dominan     : Sedang (3 dari 5 data)')
            ->expectsOutputToContain('Sebaran kategori     : Baik 2, Sedang 3')
            ->expectsOutputToContain('CO₂ 30,00–62,00; NH₃ 20,00–20,00; CO 10,00–10,00; H₂ 5,00–5,00')
            ->expectsOutputToContain('Rekomendasi tampil   : Normal → Waspada')
            ->expectsTable(['Waktu', 'Kategori', 'Penyebab utama', 'Rekomendasi'], [
                ['14:05:00', 'Baik', '-', 'Normal'],
                ['14:05:10', 'Sedang', 'CO₂', 'Waspada'],
            ])
            ->assertSuccessful();
    }

    public function test_tie_picks_the_worse_category(): void
    {
        $this->log('2026-11-07 14:05:00', 30);
        $this->log('2026-11-07 14:05:05', 30, 55);

        $this->artisan('telemetry:ringkasan', ['--dari' => '2026-11-07 14:05:00', '--sampai' => '2026-11-07 14:05:05'])
            ->expectsOutputToContain('Kategori dominan     : Tidak Sehat (1 dari 2 data)')
            ->assertSuccessful();
    }

    public function test_only_counts_the_chosen_device(): void
    {
        $other = Node::create(['room_id' => $this->device->room_id, 'code' => 'AIR-002', 'name' => 'Perangkat 2']);
        $this->log('2026-11-07 14:05:00', 30);
        $this->log('2026-11-07 14:05:01', 30, node: $other);

        $this->artisan('telemetry:ringkasan', ['--dari' => '2026-11-07 14:05', '--sampai' => '2026-11-07 14:06'])
            ->expectsOutputToContain('Jumlah data diterima : 1')
            ->expectsOutputToContain('hanya satu data')
            ->assertSuccessful();
    }

    public function test_empty_range_reports_no_data(): void
    {
        $this->artisan('telemetry:ringkasan', ['--dari' => '2026-11-07 14:05', '--sampai' => '2026-11-07 14:15'])
            ->expectsOutputToContain('Tidak ada data pada rentang tersebut.')
            ->assertSuccessful();
    }

    public function test_rejects_missing_or_invalid_options(): void
    {
        $this->artisan('telemetry:ringkasan', ['--sampai' => '2026-11-07 14:15'])
            ->expectsOutputToContain('--dari wajib diisi')
            ->assertExitCode(2);

        $this->artisan('telemetry:ringkasan', ['--dari' => '2026-11-07 14:15', '--sampai' => '2026-11-07 14:05'])
            ->expectsOutputToContain('--dari tidak boleh setelah --sampai.')
            ->assertExitCode(2);

        $this->artisan('telemetry:ringkasan', ['--dari' => '2026-11-07 14:05', '--sampai' => '2026-11-07 14:15', '--perangkat' => 'AIR-999'])
            ->expectsOutputToContain('Perangkat AIR-999 tidak terdaftar.')
            ->assertExitCode(2);
    }
}

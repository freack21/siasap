<?php

namespace Tests\Feature;

use App\Models\Node;
use App\Models\Room;
use App\Models\SensorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportRoomTestCommandTest extends TestCase
{
    use RefreshDatabase;

    protected Node $device;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $room = Room::create(['code' => 'ROOM-01', 'name' => 'Lab. DSK']);
        $this->device = Node::create(['room_id' => $room->id, 'code' => 'AIR-001', 'name' => 'Perangkat IoT 1']);
    }

    private function log(string $at, float $co2, float $co = 10): void
    {
        SensorLog::factory()->withGases($co2, 20, $co, 5)->create([
            'node_id' => $this->device->id,
            'created_at' => Carbon::parse($at),
            'updated_at' => Carbon::parse($at),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function runExport(): array
    {
        $this->artisan('telemetry:ekspor-uji-ruangan', [
            '--tanggal' => '2026-11-13',
            '--skenario' => ['Ruangan normal=17:45-17:50', 'Ruangan tertutup=17:50-17:55', 'Ruangan berasap=17:55-18:00'],
        ])->assertSuccessful();

        return json_decode(Storage::disk('local')->get('uji-ruangan/2026-11-13/ringkasan.json'), true);
    }

    public function test_exports_each_scenario_with_half_open_ranges(): void
    {
        $this->log('2026-11-13 17:44:59', 99);
        $this->log('2026-11-13 17:45:00', 20);
        $this->log('2026-11-13 17:49:55', 22);
        $this->log('2026-11-13 17:50:00', 40);
        $this->log('2026-11-13 17:54:55', 60);
        $this->log('2026-11-13 17:55:00', 30, 55);
        $this->log('2026-11-13 18:00:00', 30, 90);

        $scenarios = collect($this->runExport()['skenario'])->keyBy('nama');

        $this->assertSame(2, $scenarios['Ruangan normal']['count']);
        $this->assertSame(2, $scenarios['Ruangan tertutup']['count']);
        $this->assertSame(['Normal', 'Waspada'], $scenarios['Ruangan tertutup']['levels']);
        $this->assertSame(1, $scenarios['Ruangan berasap']['count']);
        $this->assertSame('Tidak Sehat', $scenarios['Ruangan berasap']['dominant']['category']);
        $this->assertEquals(60, $scenarios['Ruangan tertutup']['gases']['co2']['max']);

        $csv = Storage::disk('local')->get('uji-ruangan/2026-11-13/data-pengujian.csv');
        $this->assertCount(6, array_filter(explode("\n", $csv)));
        $this->assertStringContainsString('"Ruangan berasap","2026-11-13 17:55:00"', $csv);
        $this->assertStringContainsString('| Ruangan tertutup | 17.50 | 17.55 | 2 |', Storage::disk('local')->get('uji-ruangan/2026-11-13/ringkasan.md'));
    }

    public function test_rejects_a_malformed_scenario(): void
    {
        $this->artisan('telemetry:ekspor-uji-ruangan', ['--tanggal' => '2026-11-13', '--skenario' => ['Ruangan normal 17:45-17:50']])
            ->expectsOutputToContain('Format skenario salah')
            ->assertFailed();

        Storage::disk('local')->assertMissing('uji-ruangan/2026-11-13/ringkasan.json');
    }

    public function test_hidden_page_lists_and_downloads_the_export(): void
    {
        $this->log('2026-11-13 17:46:00', 20);
        $this->runExport();
        $path = '/'.config('uji_ruangan.path');

        $this->get($path)
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Ruangan normal')
            ->assertSee(route('uji-ruangan.download', ['date' => '2026-11-13', 'file' => 'data-pengujian.csv']), false);

        $this->get("{$path}/2026-11-13/data-pengujian.csv")->assertOk()->assertDownload('uji-ruangan-2026-11-13-data-pengujian.csv');
        $this->get("{$path}/2026-11-13/.env")->assertNotFound();
        $this->get("{$path}/2026-11-14/ringkasan.md")->assertNotFound();
        $this->get('/uji-ruangan')->assertNotFound();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Node;
use App\Models\Room;
use App\Models\SensorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SweepSensorLogsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected Node $device;

    protected Node $otherDevice;

    protected function setUp(): void
    {
        parent::setUp();

        $room = Room::create(['code' => 'ROOM-01', 'name' => 'Ruang Uji']);
        $this->device = Node::create(['room_id' => $room->id, 'code' => 'AIR-001', 'name' => 'Perangkat 1']);
        $this->otherDevice = Node::create(['room_id' => $room->id, 'code' => 'AIR-002', 'name' => 'Perangkat 2']);

        $this->log($this->device, '2026-10-25 23:59:59');
        $this->log($this->device, '2026-10-26 00:00:00');
        $this->log($this->device, '2026-10-26 22:15:00');
        $this->log($this->otherDevice, '2026-10-26 22:15:00');
        $this->log($this->device, '2026-10-27 00:00:00');
    }

    private function log(Node $node, string $at): void
    {
        SensorLog::factory()->create([
            'node_id' => $node->id,
            'created_at' => Carbon::parse($at),
            'updated_at' => Carbon::parse($at),
        ]);
    }

    /**
     * @return list<string>
     */
    private function remainingTimes(): array
    {
        return SensorLog::orderBy('created_at')->pluck('created_at')->map->format('Y-m-d H:i:s')->all();
    }

    public function test_date_range_deletes_whole_days_only(): void
    {
        $this->artisan('telemetry:sweep', ['--dari' => '2026-10-26', '--sampai' => '2026-10-26', '--force' => true])
            ->expectsOutputToContain('3 baris data sensor dihapus')
            ->assertSuccessful();

        $this->assertSame(['2026-10-25 23:59:59', '2026-10-27 00:00:00'], $this->remainingTimes());
    }

    public function test_datetime_and_device_narrow_the_sweep(): void
    {
        $this->artisan('telemetry:sweep', ['--dari' => '2026-10-26 22:00:00', '--perangkat' => 'AIR-001', '--force' => true])
            ->assertSuccessful();

        $this->assertSame(3, SensorLog::count());
        $this->assertSame(1, SensorLog::where('node_id', $this->otherDevice->id)->count());
        $this->assertSame(0, SensorLog::where('node_id', $this->device->id)->where('created_at', '>=', '2026-10-26 22:00:00')->count());
    }

    public function test_preview_and_declined_confirmation_keep_data(): void
    {
        $this->artisan('telemetry:sweep', ['--sampai' => '2026-10-26', '--pratinjau' => true])
            ->expectsOutputToContain('Mode pratinjau')
            ->assertSuccessful();

        $this->artisan('telemetry:sweep', ['--sampai' => '2026-10-26'])
            ->expectsConfirmation('Hapus 4 baris data sensor di atas?', 'no')
            ->assertSuccessful();

        $this->assertSame(5, SensorLog::count());
    }

    public function test_invalid_input_is_rejected_without_deleting(): void
    {
        $this->artisan('telemetry:sweep', ['--force' => true])->assertExitCode(2);
        $this->artisan('telemetry:sweep', ['--dari' => '26-10-2026', '--force' => true])->assertExitCode(2);
        $this->artisan('telemetry:sweep', ['--dari' => '2026-10-27', '--sampai' => '2026-10-26', '--force' => true])->assertExitCode(2);
        $this->artisan('telemetry:sweep', ['--dari' => '2026-10-26', '--perangkat' => 'AIR-999', '--force' => true])->assertExitCode(2);

        $this->assertSame(5, SensorLog::count());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Node;
use App\Models\Room;
use App\Models\SensorLog;
use App\Models\TelemetryRejection;
use Database\Seeders\RoomDeviceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomDeviceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_one_room_with_one_active_device_and_is_idempotent(): void
    {
        $this->seed(RoomDeviceSeeder::class);
        $this->seed(RoomDeviceSeeder::class);

        $this->assertSame(1, Room::count());
        $this->assertSame(1, Node::count());

        $room = Room::firstOrFail();
        $this->assertSame('Ruang Uji', $room->name);
        $this->assertSame('ROOM-01', $room->code);

        $node = Node::firstOrFail();
        $this->assertSame('Perangkat IoT 1', $node->name);
        $this->assertSame('AIR-001', $node->code);
        $this->assertTrue($node->is_active);
        $this->assertSame($room->id, $node->room_id);
    }

    public function test_telemetry_clear_empties_logs_and_rejections_but_keeps_master_data(): void
    {
        $this->seed(RoomDeviceSeeder::class);
        SensorLog::factory()->create(['node_id' => Node::firstOrFail()->id]);
        TelemetryRejection::create(['device_id' => null, 'payload' => [], 'errors' => ['device_id' => ['x']]]);

        $this->artisan('telemetry:clear', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, SensorLog::count());
        $this->assertSame(0, TelemetryRejection::count());
        $this->assertSame(1, Room::count());
        $this->assertSame(1, Node::count());
    }
}

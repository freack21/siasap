<?php

namespace Tests\Feature;

use App\Models\Node;
use App\Models\Room;
use App\Models\SensorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyDemoRoomsMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function runMigration(): void
    {
        (require database_path('migrations/2026_10_26_212423_remove_legacy_demo_rooms.php'))->up();
    }

    private function seedLegacyRooms(): void
    {
        foreach (['TAMU' => 'Ruang Tamu', 'KAMAR' => 'Kamar', 'DAPUR' => 'Dapur'] as $code => $name) {
            $room = Room::create(['code' => "ROOM-{$code}", 'name' => $name]);
            Node::create(['room_id' => $room->id, 'code' => 'NODE-0'.(Node::count() + 1), 'name' => "Node {$name}"]);
        }
    }

    public function test_legacy_demo_rooms_without_data_are_replaced_by_the_test_room(): void
    {
        $this->seedLegacyRooms();

        $this->runMigration();

        $this->assertSame(['ROOM-01'], Room::pluck('code')->all());
        $this->assertSame(['AIR-001'], Node::pluck('code')->all());
    }

    public function test_legacy_device_with_readings_is_kept(): void
    {
        $this->seedLegacyRooms();
        SensorLog::factory()->create(['node_id' => Node::where('code', 'NODE-01')->value('id')]);

        $this->runMigration();

        $this->assertEqualsCanonicalizing(['ROOM-TAMU', 'ROOM-01'], Room::pluck('code')->all());
        $this->assertEqualsCanonicalizing(['NODE-01', 'AIR-001'], Node::pluck('code')->all());
        $this->assertSame(1, SensorLog::count());
    }

    public function test_database_without_legacy_rooms_is_unchanged(): void
    {
        $this->runMigration();

        $this->assertSame(0, Room::count());
        $this->assertSame(0, Node::count());
    }
}

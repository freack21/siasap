<?php

namespace Tests\Feature;

use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenameTestRoomMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function runMigration(): void
    {
        (require database_path('migrations/2026_11_05_190809_rename_test_room_to_rc1ti.php'))->up();
    }

    public function test_default_test_room_is_renamed_to_rc1ti(): void
    {
        Room::create(['code' => 'ROOM-01', 'name' => 'Ruang Uji']);

        $this->runMigration();

        $this->assertSame('RC1TI', Room::where('code', 'ROOM-01')->value('name'));
    }

    public function test_rooms_with_custom_names_or_other_codes_are_unchanged(): void
    {
        Room::create(['code' => 'ROOM-01', 'name' => 'Lab Komputer']);
        Room::create(['code' => 'ROOM-02', 'name' => 'Ruang Uji']);

        $this->runMigration();

        $this->assertSame(['Lab Komputer', 'Ruang Uji'], Room::orderBy('code')->pluck('name')->all());
    }
}

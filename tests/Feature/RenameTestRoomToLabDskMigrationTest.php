<?php

namespace Tests\Feature;

use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RenameTestRoomToLabDskMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function runMigration(): void
    {
        (require database_path('migrations/2026_11_10_203343_rename_test_room_to_lab_dsk.php'))->up();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function defaultNames(): array
    {
        return ['nama RC1TI' => ['RC1TI'], 'nama awal' => ['Ruang Uji']];
    }

    #[DataProvider('defaultNames')]
    public function test_default_test_room_becomes_lab_dsk(string $name): void
    {
        Room::create(['code' => 'ROOM-01', 'name' => $name, 'description' => 'Ruangan tempat perangkat pemantau kualitas udara dipasang.']);

        $this->runMigration();

        $room = Room::where('code', 'ROOM-01')->sole();
        $this->assertSame('Lab. DSK', $room->name);
        $this->assertSame('Laboratorium Dasar Sistem Kendali, Jurusan Teknik Elektro Universitas Riau.', $room->description);
    }

    public function test_custom_description_is_kept(): void
    {
        Room::create(['code' => 'ROOM-01', 'name' => 'RC1TI', 'description' => 'Catatan sendiri.']);

        $this->runMigration();

        $room = Room::where('code', 'ROOM-01')->sole();
        $this->assertSame('Lab. DSK', $room->name);
        $this->assertSame('Catatan sendiri.', $room->description);
    }

    public function test_rooms_with_custom_names_or_other_codes_are_unchanged(): void
    {
        Room::create(['code' => 'ROOM-01', 'name' => 'Lab Komputer']);
        Room::create(['code' => 'ROOM-02', 'name' => 'RC1TI']);

        $this->runMigration();

        $this->assertSame(['Lab Komputer', 'RC1TI'], Room::orderBy('code')->pluck('name')->all());
    }
}

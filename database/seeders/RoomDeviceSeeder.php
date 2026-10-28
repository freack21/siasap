<?php

namespace Database\Seeders;

use App\Models\Node;
use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomDeviceSeeder extends Seeder
{
    /**
     * Seed satu ruangan uji dan satu perangkat IoT (code = device_id firmware).
     */
    public function run(): void
    {
        $room = Room::updateOrCreate(
            ['code' => 'ROOM-01'],
            ['name' => 'Ruang Uji', 'description' => 'Ruangan tempat perangkat pemantau kualitas udara dipasang.'],
        );

        Node::updateOrCreate(
            ['code' => 'AIR-001'],
            [
                'room_id' => $room->id,
                'name' => 'Perangkat IoT 1',
                'description' => 'ESP32 dengan sensor MQ-135 dan MQ-2.',
                'is_active' => true,
            ],
        );
    }
}

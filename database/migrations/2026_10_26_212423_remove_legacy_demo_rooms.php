<?php

use Database\Seeders\RoomDeviceSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Kode ruangan dan perangkat contoh dari seeder versi awal (tiga ruangan).
     *
     * @var array{rooms: list<string>, nodes: list<string>}
     */
    private const LEGACY = [
        'rooms' => ['ROOM-TAMU', 'ROOM-KAMAR', 'ROOM-DAPUR'],
        'nodes' => ['NODE-01', 'NODE-02', 'NODE-03'],
    ];

    /**
     * Penelitian memakai satu ruangan uji. Hapus ruangan dan perangkat contoh versi awal
     * yang belum pernah menerima data, lalu pastikan ruangan uji (ROOM-01, AIR-001) tersedia.
     * Perangkat contoh yang sudah memiliki pembacaan dibiarkan agar data tidak hilang.
     */
    public function up(): void
    {
        $removedNodes = DB::table('nodes')
            ->whereIn('code', self::LEGACY['nodes'])
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))->from('sensor_logs')->whereColumn('sensor_logs.node_id', 'nodes.id'))
            ->delete();

        $removedRooms = DB::table('rooms')
            ->whereIn('code', self::LEGACY['rooms'])
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))->from('nodes')->whereColumn('nodes.room_id', 'rooms.id'))
            ->delete();

        if ($removedNodes + $removedRooms > 0) {
            (new RoomDeviceSeeder)->run();
        }
    }

    /**
     * Data contoh versi awal tidak dibuat ulang.
     */
    public function down(): void
    {
        //
    }
};

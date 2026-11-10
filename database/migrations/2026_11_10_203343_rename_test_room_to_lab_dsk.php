<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Deskripsi bawaan ruangan sebelum ruangan uji dipindah ke Lab. DSK.
     */
    private const OLD_DESCRIPTION = 'Ruangan tempat perangkat pemantau kualitas udara dipasang.';

    private const NEW_DESCRIPTION = 'Laboratorium Dasar Sistem Kendali, Jurusan Teknik Elektro Universitas Riau.';

    /**
     * Ruangan uji penelitian dipindah ke Lab. DSK (Laboratorium Dasar Sistem Kendali). Ganti nama
     * ROOM-01 yang masih bernama bawaan ("Ruang Uji" atau "RC1TI"), dan deskripsinya bila masih
     * deskripsi bawaan. Ruangan yang sudah diberi nama lain tidak diubah.
     */
    public function up(): void
    {
        $room = DB::table('rooms')->where('code', 'ROOM-01')->whereIn('name', ['Ruang Uji', 'RC1TI'])->first();

        if ($room === null) {
            return;
        }

        DB::table('rooms')->where('id', $room->id)->update([
            'name' => 'Lab. DSK',
            'description' => $room->description === self::OLD_DESCRIPTION || $room->description === null
                ? self::NEW_DESCRIPTION
                : $room->description,
        ]);
    }

    /**
     * Kembalikan nama dan deskripsi sebelumnya.
     */
    public function down(): void
    {
        DB::table('rooms')->where('code', 'ROOM-01')->where('name', 'Lab. DSK')->update(['name' => 'RC1TI']);
        DB::table('rooms')->where('code', 'ROOM-01')->where('description', self::NEW_DESCRIPTION)->update(['description' => self::OLD_DESCRIPTION]);
    }
};

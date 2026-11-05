<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ruangan uji penelitian adalah ruang kelas RC1TI. Ganti nama ruangan ROOM-01 yang masih
     * bernama bawaan "Ruang Uji"; ruangan yang sudah diberi nama lain tidak diubah.
     */
    public function up(): void
    {
        DB::table('rooms')->where('code', 'ROOM-01')->where('name', 'Ruang Uji')->update(['name' => 'RC1TI']);
    }

    /**
     * Kembalikan nama bawaan sebelumnya.
     */
    public function down(): void
    {
        DB::table('rooms')->where('code', 'ROOM-01')->where('name', 'RC1TI')->update(['name' => 'Ruang Uji']);
    }
};

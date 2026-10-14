<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * - Tambah kolom ku_dominant (parameter gas dominan penentu nilai KU).
     * - rssi menjadi nullable tanpa default: RSSI yang tidak dikirim node
     *   disimpan sebagai NULL, bukan dipalsukan menjadi -60.
     * - Hapus default seq_number dan hop_count (wajib dikirim oleh node).
     */
    public function up(): void
    {
        Schema::table('sensor_logs', function (Blueprint $table) {
            $table->string('ku_dominant')->nullable()->after('ku_status');
            $table->integer('rssi')->nullable()->change();
            $table->integer('seq_number')->change();
            $table->integer('hop_count')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kolom rssi kembali NOT NULL, sehingga nilai NULL harus diisi terlebih dahulu
        DB::table('sensor_logs')->whereNull('rssi')->update(['rssi' => -60]);

        Schema::table('sensor_logs', function (Blueprint $table) {
            $table->dropColumn('ku_dominant');
        });

        Schema::table('sensor_logs', function (Blueprint $table) {
            $table->integer('rssi')->default(-60)->change();
            $table->integer('seq_number')->default(1)->change();
            $table->integer('hop_count')->default(1)->change();
        });
    }
};

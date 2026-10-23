<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jadikan sensor_logs.node_id foreign key ke nodes.id agar relasi
     * perangkat–pembacaan dijaga oleh basis data, bukan hanya aplikasi.
     * Pembacaan tanpa perangkat yang valid dihapus terlebih dahulu.
     */
    public function up(): void
    {
        DB::table('sensor_logs')->whereNotIn('node_id', DB::table('nodes')->select('id'))->delete();

        Schema::table('sensor_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('node_id')->change();
            $table->foreign('node_id')->references('id')->on('nodes')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sensor_logs', function (Blueprint $table) {
            $table->dropForeign(['node_id']);
            $table->integer('node_id')->change();
        });
    }
};

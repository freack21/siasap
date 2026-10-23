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
     * Menyelaraskan sensor_logs dengan naskah: kategori per gas, kategori
     * keseluruhan, penyebab utama, dan level rekomendasi menggantikan Indeks KU
     * serta metrik QoS. Data lama dihapus karena kategori lama tidak kompatibel
     * dan sesi pengujian tidak lagi dipakai.
     */
    public function up(): void
    {
        DB::table('sensor_logs')->delete();

        Schema::table('sensor_logs', function (Blueprint $table) {
            $table->dropForeign(['testing_session_id']);
            $table->dropIndex(['testing_session_id', 'node_id', 'created_at']);
        });

        Schema::table('sensor_logs', function (Blueprint $table) {
            $table->dropColumn([
                'testing_session_id',
                'ku_value',
                'ku_status',
                'ku_dominant',
                'rssi',
                'seq_number',
                'hop_count',
                'topology_type',
            ]);
        });

        Schema::table('sensor_logs', function (Blueprint $table) {
            $table->timestamp('recorded_at')->nullable()->after('h2');
            $table->string('co2_category')->after('recorded_at');
            $table->string('nh3_category')->after('co2_category');
            $table->string('co_category')->after('nh3_category');
            $table->string('h2_category')->after('co_category');
            $table->string('overall_category')->after('h2_category');
            $table->string('dominant_gases')->nullable()->after('overall_category');
            $table->string('recommendation_level')->after('dominant_gases');
        });

        Schema::dropIfExists('testing_sessions');
    }

    /**
     * Reverse the migrations.
     *
     * Skema lama tidak dipulihkan: data pengukuran lama sudah dihapus pada up().
     */
    public function down(): void
    {
        throw new RuntimeException('Migrasi ini tidak dapat dibatalkan: skema dan data lama (Indeks KU/QoS) sudah dihapus.');
    }
};

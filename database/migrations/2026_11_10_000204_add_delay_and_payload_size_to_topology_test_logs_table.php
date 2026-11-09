<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Delay (milidetik) dan ukuran payload (byte) setiap paket uji untuk menghitung delay,
     * jitter, dan throughput.
     */
    public function up(): void
    {
        Schema::table('topology_test_logs', function (Blueprint $table) {
            $table->integer('delay_ms')->nullable()->after('hop_count');
            $table->unsignedInteger('payload_bytes')->nullable()->after('delay_ms');
        });
    }

    public function down(): void
    {
        Schema::table('topology_test_logs', function (Blueprint $table) {
            $table->dropColumn(['delay_ms', 'payload_bytes']);
        });
    }
};

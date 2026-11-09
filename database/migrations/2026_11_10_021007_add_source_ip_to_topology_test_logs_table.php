<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * IP pengirim setiap paket data uji (node star atau gateway mesh), sebagai sumber IP node
     * bila perangkat belum melakukan handshake.
     */
    public function up(): void
    {
        Schema::table('topology_test_logs', function (Blueprint $table) {
            $table->string('source_ip', 45)->nullable()->after('payload_bytes');
        });
    }

    public function down(): void
    {
        Schema::table('topology_test_logs', function (Blueprint $table) {
            $table->dropColumn('source_ip');
        });
    }
};

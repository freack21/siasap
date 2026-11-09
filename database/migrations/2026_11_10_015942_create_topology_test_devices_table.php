<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Handshake terakhir setiap perangkat uji topologi: IP yang dilaporkan perangkat dan IP
     * yang terlihat oleh server.
     */
    public function up(): void
    {
        Schema::create('topology_test_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 20)->unique();
            $table->string('ip', 45)->nullable();
            $table->string('remote_ip', 45)->nullable();
            $table->string('mac', 17)->nullable();
            $table->string('topology', 10)->nullable();
            $table->smallInteger('rssi')->nullable();
            $table->unsignedInteger('handshake_count')->default(0);
            $table->dateTime('last_handshake_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topology_test_devices');
    }
};

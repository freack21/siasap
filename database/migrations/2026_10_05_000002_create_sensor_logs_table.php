<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sensor_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('testing_session_id')->nullable()->constrained('testing_sessions')->nullOnDelete();
            $table->integer('node_id');
            $table->decimal('co2', 8, 2);
            $table->decimal('nh3', 8, 2);
            $table->decimal('co', 8, 2);
            $table->decimal('h2', 8, 2);
            $table->decimal('ku_value', 8, 2);
            $table->string('ku_status');
            $table->integer('rssi')->default(-60);
            $table->integer('seq_number')->default(1);
            $table->integer('hop_count')->default(1);
            $table->enum('topology_type', ['star', 'mesh'])->default('star');
            $table->timestamps();

            $table->index(['testing_session_id', 'node_id', 'created_at']);
            $table->index(['node_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_logs');
    }
};

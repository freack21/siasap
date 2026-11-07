<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data uji topologi star dan mesh (tiga perangkat), terpisah dari sensor_logs agar tidak
     * tercampur dengan data penelitian di halaman SIMKU.
     */
    public function up(): void
    {
        Schema::create('topology_test_logs', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 20);
            $table->string('topology', 10)->nullable();
            $table->dateTime('recorded_at');
            $table->decimal('co2', 8, 2);
            $table->decimal('nh3', 8, 2);
            $table->decimal('co', 8, 2);
            $table->decimal('h2', 8, 2);
            $table->string('co2_category', 20);
            $table->string('nh3_category', 20);
            $table->string('co_category', 20);
            $table->string('h2_category', 20);
            $table->string('overall_category', 20);
            $table->string('dominant_gases', 30)->nullable();
            $table->string('recommendation_level', 30);
            $table->smallInteger('rssi')->nullable();
            $table->unsignedInteger('seq_number')->nullable();
            $table->unsignedTinyInteger('hop_count')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'topology', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topology_test_logs');
    }
};

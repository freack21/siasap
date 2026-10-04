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
        Schema::create('testing_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('topology_type', ['star', 'mesh'])->default('star');
            $table->enum('status', ['active', 'completed'])->default('active');
            $table->text('description')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testing_sessions');
    }
};

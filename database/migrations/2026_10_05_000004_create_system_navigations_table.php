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
        Schema::create('system_navigations', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('route_name')->nullable();
            $table->string('url')->nullable();
            $table->string('icon')->default('heroicon-o-rectangle-group');
            $table->string('permission_name')->nullable();
            $table->string('section')->default('Utama');
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_navigations');
    }
};

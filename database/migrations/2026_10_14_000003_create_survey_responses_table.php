<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->string('respondent_name')->nullable();
            $table->string('respondent_group')->default('pengguna')->index();
            for ($i = 1; $i <= 10; $i++) {
                $table->unsignedTinyInteger("q{$i}");
            }
            $table->decimal('sus_score', 5, 2);
            $table->text('comment')->nullable();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};

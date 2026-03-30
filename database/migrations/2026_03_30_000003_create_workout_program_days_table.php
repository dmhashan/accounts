<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_program_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('workout_programs')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_number');
            $table->string('title');
            $table->timestamps();

            $table->unique(['program_id', 'day_number'], 'workout_days_program_day_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_program_days');
    }
};

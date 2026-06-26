<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_day_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('day_id')->constrained('workout_program_days')->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained('exercises')->restrictOnDelete();
            $table->string('display_name')->nullable();
            $table->string('w1_w3_exercise');
            $table->string('w2_w4_exercise');
            $table->unsignedTinyInteger('sets');
            $table->string('reps');
            $table->string('tempo', 20);
            $table->unsignedInteger('rest_seconds')->default(0);
            $table->unsignedInteger('exercise_order')->default(1);
            $table->timestamps();

            $table->index(['day_id', 'exercise_order'], 'workout_day_exercises_day_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_day_exercises');
    }
};

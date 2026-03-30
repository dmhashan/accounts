<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_program_extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('workout_programs')->cascadeOnDelete();
            $table->enum('type', ['core', 'cardio']);

            $table->string('exercise_name')->nullable();
            $table->unsignedTinyInteger('sets')->nullable();
            $table->string('reps_or_time')->nullable();
            $table->string('rest')->nullable();
            $table->text('notes')->nullable();

            $table->unsignedTinyInteger('frequency_per_week')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('cardio_type')->nullable();

            $table->timestamps();

            $table->index(['program_id', 'type'], 'workout_program_extras_program_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_program_extras');
    }
};

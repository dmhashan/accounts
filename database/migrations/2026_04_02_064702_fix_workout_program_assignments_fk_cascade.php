<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workout_program_assignments', function (Blueprint $table) {
            $table->dropForeign(['source_program_id']);
            $table->dropForeign(['assigned_program_id']);

            $table->foreign('source_program_id')
                ->references('id')->on('workout_programs')
                ->cascadeOnDelete();

            $table->foreign('assigned_program_id')
                ->references('id')->on('workout_programs')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('workout_program_assignments', function (Blueprint $table) {
            $table->dropForeign(['source_program_id']);
            $table->dropForeign(['assigned_program_id']);

            $table->foreign('source_program_id')
                ->references('id')->on('workout_programs')
                ->restrictOnDelete();

            $table->foreign('assigned_program_id')
                ->references('id')->on('workout_programs')
                ->restrictOnDelete();
        });
    }
};


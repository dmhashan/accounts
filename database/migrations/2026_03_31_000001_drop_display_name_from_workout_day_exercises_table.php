<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('workout_day_exercises')) {
            return;
        }

        if (Schema::hasColumn('workout_day_exercises', 'display_name')) {
            Schema::table('workout_day_exercises', function (Blueprint $table) {
                $table->dropColumn('display_name');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('workout_day_exercises')) {
            return;
        }

        if (!Schema::hasColumn('workout_day_exercises', 'display_name')) {
            Schema::table('workout_day_exercises', function (Blueprint $table) {
                $table->string('display_name')->nullable()->after('exercise_id');
            });
        }
    }
};

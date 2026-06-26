<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workout_programs', function (Blueprint $table) {
            $table->index('tenant_id', 'workout_programs_tenant_id_idx');
            $table->dropIndex('workout_programs_tenant_status_idx');
            $table->dropColumn(['days_per_week', 'level', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('workout_programs', function (Blueprint $table) {
            $table->unsignedTinyInteger('days_per_week')->after('duration_weeks');
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->after('days_per_week');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('level');
            $table->index(['tenant_id', 'status'], 'workout_programs_tenant_status_idx');
            $table->dropIndex('workout_programs_tenant_id_idx');
        });
    }
};

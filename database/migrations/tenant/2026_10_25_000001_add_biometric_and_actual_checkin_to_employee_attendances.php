<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_attendances')) {
            return;
        }

        Schema::table('employee_attendances', function (Blueprint $table): void {
            if (!Schema::hasColumn('employee_attendances', 'biometric_checkin_at')) {
                $table->time('biometric_checkin_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('employee_attendances', 'actual_checkin_at')) {
                $table->time('actual_checkin_at')->nullable()->after('biometric_checkin_at');
            }

            if (!Schema::hasColumn('employee_attendances', 'attendance_source')) {
                $table->string('attendance_source', 30)->nullable()->after('actual_checkin_at');
            }
        });

        DB::table('employee_attendances')
            ->whereNull('actual_checkin_at')
            ->whereNotNull('check_in_at')
            ->update(['actual_checkin_at' => DB::raw('check_in_at')]);

        DB::table('employee_attendances')
            ->whereNull('attendance_source')
            ->update(['attendance_source' => 'manual']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('employee_attendances')) {
            return;
        }

        Schema::table('employee_attendances', function (Blueprint $table): void {
            foreach (['biometric_checkin_at', 'actual_checkin_at', 'attendance_source'] as $column) {
                if (Schema::hasColumn('employee_attendances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

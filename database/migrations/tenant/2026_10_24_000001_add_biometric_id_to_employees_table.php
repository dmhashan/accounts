<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'biometric_id')) {
                $table->string('biometric_id', 50)->nullable()->unique()->after('employee_code');
            }

            if (!Schema::hasColumn('employees', 'biometric_last_synced_at')) {
                $table->timestamp('biometric_last_synced_at')->nullable()->after('biometric_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'biometric_id')) {
                $table->dropUnique(['biometric_id']);
                $table->dropColumn('biometric_id');
            }

            if (Schema::hasColumn('employees', 'biometric_last_synced_at')) {
                $table->dropColumn('biometric_last_synced_at');
            }
        });
    }
};

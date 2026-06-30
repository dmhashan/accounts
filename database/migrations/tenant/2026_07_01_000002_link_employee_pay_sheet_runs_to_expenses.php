<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_pay_sheet_runs')) {
            return;
        }

        Schema::table('employee_pay_sheet_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_pay_sheet_runs', 'expense_id')) {
                $table->foreignId('expense_id')
                    ->nullable()
                    ->after('company_account_id')
                    ->constrained('expenses')
                    ->restrictOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('employee_pay_sheet_runs')) {
            return;
        }

        Schema::table('employee_pay_sheet_runs', function (Blueprint $table) {
            if (Schema::hasColumn('employee_pay_sheet_runs', 'expense_id')) {
                $table->dropConstrainedForeignId('expense_id');
            }
        });
    }
};

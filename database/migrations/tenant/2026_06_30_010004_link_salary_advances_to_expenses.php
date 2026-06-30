<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_pay_sheet_adjustments')) {
            return;
        }

        Schema::table('employee_pay_sheet_adjustments', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_pay_sheet_adjustments', 'company_account_id')) {
                $table->foreignId('company_account_id')
                    ->nullable()
                    ->after('employee_id')
                    ->constrained('company_accounts')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('employee_pay_sheet_adjustments', 'expense_id')) {
                $table->foreignId('expense_id')
                    ->nullable()
                    ->after('company_account_id')
                    ->constrained('expenses')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('employee_pay_sheet_adjustments')) {
            return;
        }

        Schema::table('employee_pay_sheet_adjustments', function (Blueprint $table) {
            if (Schema::hasColumn('employee_pay_sheet_adjustments', 'expense_id')) {
                $table->dropConstrainedForeignId('expense_id');
            }

            if (Schema::hasColumn('employee_pay_sheet_adjustments', 'company_account_id')) {
                $table->dropConstrainedForeignId('company_account_id');
            }
        });
    }
};

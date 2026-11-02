<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_pay_sheet_items')) {
            return;
        }

        Schema::table('employee_pay_sheet_items', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_pay_sheet_items', 'expense_id')) {
                $table->foreignId('expense_id')
                    ->nullable()
                    ->after('net_pay')
                    ->constrained('expenses')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('employee_pay_sheet_items', 'status')) {
                $table->string('status', 20)->default('draft')->after('expense_id');
            }

            if (!Schema::hasColumn('employee_pay_sheet_items', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('employee_pay_sheet_items', 'paid_by')) {
                $table->foreignId('paid_by')
                    ->nullable()
                    ->after('paid_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('employee_pay_sheet_items')) {
            return;
        }

        Schema::table('employee_pay_sheet_items', function (Blueprint $table) {
            if (Schema::hasColumn('employee_pay_sheet_items', 'paid_by')) {
                $table->dropConstrainedForeignId('paid_by');
            }

            if (Schema::hasColumn('employee_pay_sheet_items', 'paid_at')) {
                $table->dropColumn('paid_at');
            }

            if (Schema::hasColumn('employee_pay_sheet_items', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('employee_pay_sheet_items', 'expense_id')) {
                $table->dropConstrainedForeignId('expense_id');
            }
        });
    }
};

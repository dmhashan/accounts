<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_pay_sheet_adjustments')) {
            Schema::create('employee_pay_sheet_adjustments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->date('period_start');
                $table->date('period_end');
                $table->string('type', 30);
                $table->string('category', 60);
                $table->string('description');
                $table->decimal('amount', 14, 2);
                $table->date('adjustment_date')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['employee_id', 'period_start', 'period_end'], 'employee_pay_sheet_adjustments_period_index');
                $table->index(['type', 'category']);
            });
        }

        if (Schema::hasTable('employee_pay_sheet_items')) {
            Schema::table('employee_pay_sheet_items', function (Blueprint $table) {
                if (!Schema::hasColumn('employee_pay_sheet_items', 'earning_lines')) {
                    $table->json('earning_lines')->nullable()->after('net_pay');
                }

                if (!Schema::hasColumn('employee_pay_sheet_items', 'deduction_lines')) {
                    $table->json('deduction_lines')->nullable()->after('earning_lines');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employee_pay_sheet_items')) {
            Schema::table('employee_pay_sheet_items', function (Blueprint $table) {
                if (Schema::hasColumn('employee_pay_sheet_items', 'deduction_lines')) {
                    $table->dropColumn('deduction_lines');
                }

                if (Schema::hasColumn('employee_pay_sheet_items', 'earning_lines')) {
                    $table->dropColumn('earning_lines');
                }
            });
        }

        Schema::dropIfExists('employee_pay_sheet_adjustments');
    }
};

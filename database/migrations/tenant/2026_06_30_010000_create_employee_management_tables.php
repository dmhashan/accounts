<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const EMPLOYEE_PAY_SHEET_ITEM_EMPLOYEE_RUN_INDEX = 'eps_items_employee_run_idx';

    public function up(): void
    {
        if (!Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->string('employee_code')->nullable()->unique();
                $table->string('first_name');
                $table->string('last_name')->nullable();
                $table->string('name');
                $table->string('email')->nullable()->unique();
                $table->string('phone')->nullable();
                $table->string('nic')->nullable();
                $table->string('gender', 20)->nullable();
                $table->date('date_of_birth')->nullable();
                $table->text('address')->nullable();
                $table->string('emergency_contact_name')->nullable();
                $table->string('emergency_contact_phone')->nullable();
                $table->string('job_title')->nullable();
                $table->string('department')->nullable();
                $table->string('employment_type', 30)->default('full_time');
                $table->string('status', 30)->default('active');
                $table->date('joined_date');
                $table->date('left_date')->nullable();
                $table->string('pay_method', 30)->default('daily');
                $table->decimal('daily_rate', 14, 2)->default(0);
                $table->decimal('paid_leave_days_per_month', 6, 2)->default(0);
                $table->decimal('half_paid_leave_days_per_month', 6, 2)->default(0);
                $table->text('pay_sheet_notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['status', 'joined_date']);
                $table->index(['department', 'job_title']);
            });
        }

        if (!Schema::hasTable('employee_documents')) {
            Schema::create('employee_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name');
                $table->string('category', 100)->default('other');
                $table->string('path', 1000);
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('file_size')->default(0);
                $table->string('original_filename', 500)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['employee_id', 'category']);
            });
        }

        if (!Schema::hasTable('employee_attendances')) {
            Schema::create('employee_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->date('attendance_date');
                $table->string('status', 40);
                $table->time('check_in_at')->nullable();
                $table->time('check_out_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['employee_id', 'attendance_date'], 'employee_attendance_date_unique');
                $table->index(['attendance_date', 'status']);
            });
        }

        if (!Schema::hasTable('employee_pay_sheet_runs')) {
            Schema::create('employee_pay_sheet_runs', function (Blueprint $table) {
                $table->id();
                $table->date('period_start');
                $table->date('period_end');
                $table->string('status', 30)->default('draft');
                $table->foreignId('company_account_id')->nullable()->constrained('company_accounts')->nullOnDelete();
                $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('generated_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->decimal('total_gross', 14, 2)->default(0);
                $table->decimal('total_deductions', 14, 2)->default(0);
                $table->decimal('total_net', 14, 2)->default(0);
                $table->string('reference_number')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['period_start', 'period_end'], 'employee_pay_sheet_runs_period_unique');
                $table->index(['status', 'period_end']);
            });
        }

        if (!Schema::hasTable('employee_pay_sheet_items')) {
            Schema::create('employee_pay_sheet_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_pay_sheet_run_id')->constrained('employee_pay_sheet_runs')->cascadeOnDelete();
                $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->string('employee_code')->nullable();
                $table->string('employee_name');
                $table->string('job_title')->nullable();
                $table->string('department')->nullable();
                $table->string('pay_method', 30)->default('daily');
                $table->decimal('daily_rate', 14, 2)->default(0);
                $table->decimal('present_days', 8, 2)->default(0);
                $table->decimal('half_day_days', 8, 2)->default(0);
                $table->decimal('absent_days', 8, 2)->default(0);
                $table->decimal('full_paid_leave_days', 8, 2)->default(0);
                $table->decimal('half_paid_leave_days', 8, 2)->default(0);
                $table->decimal('no_pay_leave_days', 8, 2)->default(0);
                $table->decimal('payable_days', 8, 2)->default(0);
                $table->decimal('gross_pay', 14, 2)->default(0);
                $table->decimal('deductions', 14, 2)->default(0);
                $table->decimal('net_pay', 14, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['employee_pay_sheet_run_id', 'employee_id'], 'employee_pay_sheet_employee_unique');
                $table->index(['employee_id', 'employee_pay_sheet_run_id'], self::EMPLOYEE_PAY_SHEET_ITEM_EMPLOYEE_RUN_INDEX);
            });

            return;
        }

        Schema::table('employee_pay_sheet_items', function (Blueprint $table) {
            if (!Schema::hasIndex('employee_pay_sheet_items', ['employee_pay_sheet_run_id', 'employee_id'], 'unique')) {
                $table->unique(['employee_pay_sheet_run_id', 'employee_id'], 'employee_pay_sheet_employee_unique');
            }

            if (!Schema::hasIndex('employee_pay_sheet_items', ['employee_id', 'employee_pay_sheet_run_id'])) {
                $table->index(['employee_id', 'employee_pay_sheet_run_id'], self::EMPLOYEE_PAY_SHEET_ITEM_EMPLOYEE_RUN_INDEX);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_pay_sheet_items');
        Schema::dropIfExists('employee_pay_sheet_runs');
        Schema::dropIfExists('employee_attendances');
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('employees');
    }
};

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePaySheetItem extends Model
{
    protected $fillable = [
        'employee_pay_sheet_run_id',
        'employee_id',
        'employee_code',
        'employee_name',
        'job_title',
        'department',
        'pay_method',
        'daily_rate',
        'present_days',
        'half_day_days',
        'absent_days',
        'full_paid_leave_days',
        'half_paid_leave_days',
        'no_pay_leave_days',
        'payable_days',
        'gross_pay',
        'deductions',
        'net_pay',
        'earning_lines',
        'deduction_lines',
        'notes',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'present_days' => 'decimal:2',
        'half_day_days' => 'decimal:2',
        'absent_days' => 'decimal:2',
        'full_paid_leave_days' => 'decimal:2',
        'half_paid_leave_days' => 'decimal:2',
        'no_pay_leave_days' => 'decimal:2',
        'payable_days' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'earning_lines' => 'array',
        'deduction_lines' => 'array',
    ];

    public function paySheetRun(): BelongsTo
    {
        return $this->belongsTo(EmployeePaySheetRun::class, 'employee_pay_sheet_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

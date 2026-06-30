<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_code',
        'first_name',
        'last_name',
        'name',
        'email',
        'phone',
        'nic',
        'gender',
        'date_of_birth',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'job_title',
        'department',
        'employment_type',
        'status',
        'joined_date',
        'left_date',
        'pay_method',
        'daily_rate',
        'annual_leave_days',
        'paid_leave_days_per_month',
        'half_paid_leave_days_per_month',
        'pay_sheet_notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joined_date' => 'date',
        'left_date' => 'date',
        'daily_rate' => 'decimal:2',
        'annual_leave_days' => 'decimal:2',
        'paid_leave_days_per_month' => 'decimal:2',
        'half_paid_leave_days_per_month' => 'decimal:2',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class);
    }

    public function paySheetItems(): HasMany
    {
        return $this->hasMany(EmployeePaySheetItem::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeePaySheetRun extends Model
{
    protected $fillable = [
        'period_start',
        'period_end',
        'status',
        'company_account_id',
        'generated_by',
        'paid_by',
        'generated_at',
        'paid_at',
        'total_gross',
        'total_deductions',
        'total_net',
        'reference_number',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'generated_at' => 'datetime',
        'paid_at' => 'datetime',
        'total_gross' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(EmployeePaySheetItem::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(CompanyAccount::class, 'company_account_id');
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}

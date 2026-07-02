<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    public const DEDUCTION_NONE = 'none';

    public const DEDUCTION_FIXED = 'fixed';

    public const DEDUCTION_PERCENTAGE = 'percentage';

    protected $fillable = [
        'company_account_id',
        'name',
        'deduction_type',
        'deduction_value',
        'record_deduction_as_expense',
        'requires_reconciliation',
        'is_active',
    ];

    protected $casts = [
        'deduction_value' => 'decimal:4',
        'record_deduction_as_expense' => 'boolean',
        'requires_reconciliation' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(CompanyAccount::class, 'company_account_id');
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(PaymentSettlement::class);
    }
}

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

    public const PREDEFINED_COLORS = [
        'emerald', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose',
        'red', 'orange', 'amber', 'yellow', 'lime', 'teal', 'cyan', 'sky',
        'slate', 'zinc', 'stone', 'neutral',
    ];

    public const PREDEFINED_ICONS = [
        'CreditCard', 'Wallet', 'Banknote', 'Coins', 'Building2', 'ArrowRightLeft',
        'Smartphone', 'QrCode', 'Globe', 'Receipt', 'CheckSquare', 'ShieldCheck',
        'Sparkles', 'Gift', 'Percent', 'HandCoins', 'Heart', 'User',
        'Store', 'Calendar',
    ];

    protected $fillable = [
        'company_account_id',
        'name',
        'deduction_type',
        'deduction_value',
        'record_deduction_as_expense',
        'requires_reconciliation',
        'is_active',
        'color',
        'icon',
        'order',
    ];

    protected $casts = [
        'deduction_value' => 'decimal:4',
        'record_deduction_as_expense' => 'boolean',
        'requires_reconciliation' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
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

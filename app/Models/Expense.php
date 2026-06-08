<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Expense extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_account_id',
        'category',
        'amount',
        'expense_date',
        'reference_number',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(CompanyAccount::class, 'company_account_id');
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(CompanyAccountTransaction::class, 'expense_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ExpenseDocument::class);
    }
}

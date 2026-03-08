<?php

namespace App\Models;

use App\Models\Concerns\GuardsBalanceUpdates;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyAccount extends Model
{
    use GuardsBalanceUpdates;

    protected $fillable = [
        'tenant_id',
        'account_name',
        'description',
        'current_balance',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'reference_id')
            ->where('transaction_reference_type', Transaction::REFERENCE_COMPANY_ACCOUNT);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

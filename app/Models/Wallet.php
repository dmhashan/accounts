<?php

namespace App\Models;

use App\Models\Concerns\GuardsBalanceUpdates;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use GuardsBalanceUpdates;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'tenant_id',
        'member_id',
        'current_balance',
        'status',
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

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'reference_id')
            ->where('transaction_reference_type', Transaction::REFERENCE_WALLET);
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

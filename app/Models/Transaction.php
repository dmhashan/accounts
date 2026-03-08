<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    public const REFERENCE_COMPANY_ACCOUNT = 'company_account';
    public const REFERENCE_WALLET = 'wallet';
    public const REFERENCE_SALE = 'sale';

    public const TYPE_CREDIT = 'credit';
    public const TYPE_DEBIT = 'debit';

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id',
        'transaction_reference_type',
        'reference_id',
        'amount',
        'transaction_type',
        'balance_before',
        'balance_after',
        'description',
        'status',
        'transaction_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
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

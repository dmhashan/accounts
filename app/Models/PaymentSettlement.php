<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSettlement extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'payment_method_id',
        'company_account_id',
        'source_type',
        'source_id',
        'payment_method_name',
        'gross_amount',
        'deduction_amount',
        'net_amount',
        'record_deduction_as_expense',
        'status',
        'payment_date',
        'confirmed_transaction_date',
        'confirmed_at',
        'confirmed_by',
        'reference_number',
        'confirmation_reference',
        'notes',
        'confirmation_notes',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'record_deduction_as_expense' => 'boolean',
        'payment_date' => 'date',
        'confirmed_transaction_date' => 'date',
        'confirmed_at' => 'datetime',
    ];

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(CompanyAccount::class, 'company_account_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}

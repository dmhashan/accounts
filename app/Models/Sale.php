<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_name',
        'customer_member_id',
        'account_id',
        'payment_method_id',
        'customer_type',
        'payment_method',
        'reference_number',
        'total_amount',
        'paid_amount',
        'balance',
        'is_paid',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_paid' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(CompanyAccount::class, 'account_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function accountTransaction(): HasOne
    {
        return $this->hasOne(CompanyAccountTransaction::class);
    }

    public function settlement(): HasOne
    {
        return $this->hasOne(PaymentSettlement::class, 'source_id')
            ->where('source_type', 'sale');
    }
}

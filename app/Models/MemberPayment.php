<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MemberPayment extends Model
{
    protected $fillable = [
        'member_id',
        'company_account_id',
        'payment_method_id',
        'payment_method',
        'amount',
        'payment_date',
        'reference_number',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(CompanyAccount::class, 'company_account_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function membership(): HasOne
    {
        return $this->hasOne(PaymentMembership::class);
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(CompanyAccountTransaction::class, 'reference_id')
            ->where('model_name', 'payment');
    }

    public function settlement(): HasOne
    {
        return $this->hasOne(PaymentSettlement::class, 'source_id')
            ->where('source_type', 'payment');
    }
}

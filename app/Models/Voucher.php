<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Voucher extends Model
{
    protected $fillable = [
        'name',
        'uuid',
        'amount',
        'status',
        'valid_from',
        'valid_until',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function redemption(): HasOne
    {
        return $this->hasOne(VoucherRedemption::class);
    }

    public function isRedeemed(): bool
    {
        return $this->status === 'redeemed';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isValidNow(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $today = now()->startOfDay();

        if ($this->valid_from && $today->lt($this->valid_from->startOfDay())) {
            return false;
        }

        return !($this->valid_until && $today->gt($this->valid_until->endOfDay()));
    }
}

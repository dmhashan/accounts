<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyAccount extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'opening_balance',
        'description',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(CompanyAccountTransfer::class, 'destination_account_id');
    }

    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(CompanyAccountTransfer::class, 'source_account_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'domain',
        'use_custom_landing_page',
        'wallet_credit_limit',
    ];

    protected $casts = [
        'use_custom_landing_page' => 'boolean',
        'wallet_credit_limit' => 'decimal:2',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function companyAccounts(): HasMany
    {
        return $this->hasMany(CompanyAccount::class);
    }
}

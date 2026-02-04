<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'tenant_id',
        'customer_name',
        'customer_type',
        'total_amount',
        'paid_amount',
        'balance',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}

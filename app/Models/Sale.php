<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_name',
        'customer_member_id',
        'customer_type',
        'payment_method',
        'reference_number',
        'total_amount',
        'paid_amount',
        'balance',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'product_variation_id',
        'quantity',
        'unit_price',
        'unit_cost',
        'subtotal',
        'cost_total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'subtotal' => 'decimal:2',
        'cost_total' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyAccountTransfer extends Model
{
    protected $fillable = [
        'source_account_id',
        'destination_account_id',
        'amount',
        'transfer_date',
        'reference_number',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transfer_date' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyAccount::class, 'source_account_id');
    }

    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyAccount::class, 'destination_account_id');
    }
}

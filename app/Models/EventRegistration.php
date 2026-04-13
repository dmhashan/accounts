<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventRegistration extends Model
{
    protected $fillable = [
        'event_id',
        'tenant_id',
        'member_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'notes',
        'total_fee',
        'is_paid',
        'paid_at',
        'company_account_id',
    ];

    protected $casts = [
        'total_fee' => 'decimal:2',
        'is_paid'   => 'boolean',
        'paid_at'   => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(EventRegistrationGuest::class);
    }
}

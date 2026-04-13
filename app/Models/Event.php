<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'start_datetime',
        'end_datetime',
        'venue',
        'venue_url',
        'agenda',
        'registration_process',
        'ticket_fee',
        'additional_ticket_fee',
        'is_active',
    ];

    protected $casts = [
        'start_datetime'        => 'datetime',
        'end_datetime'          => 'datetime',
        'ticket_fee'            => 'decimal:2',
        'additional_ticket_fee' => 'decimal:2',
        'is_active'             => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }
}

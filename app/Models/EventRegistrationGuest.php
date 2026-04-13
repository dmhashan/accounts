<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistrationGuest extends Model
{
    protected $fillable = [
        'event_registration_id',
        'first_name',
        'last_name',
        'fee',
        'notes',
    ];

    protected $casts = [
        'fee' => 'decimal:2',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'event_registration_id');
    }
}

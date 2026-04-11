<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationEntry extends Model
{
    protected $fillable = [
        'session_id',
        'type',
        'reference_id',
        'stage',
        'entered_value',
    ];

    protected $casts = [
        'entered_value' => 'decimal:2',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ReconciliationSession::class, 'session_id');
    }
}

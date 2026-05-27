<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiometricSyncLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'biometric_member_id',
        'direction',
        'action',
        'status',
        'device_maker',
        'device_model',
        'payload',
        'response',
        'error_message',
        'synced_at',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
        'synced_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'biometric_member_id');
    }
}

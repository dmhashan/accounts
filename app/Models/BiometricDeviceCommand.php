<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiometricDeviceCommand extends Model
{
    protected $fillable = [
        'device_sn',
        'command_type',
        'command_string',
        'status',
        'member_id',
        'biometric_member_id',
        'action',
        'return_code',
        'executed_at',
    ];

    protected $casts = [
        'return_code' => 'integer',
        'executed_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}

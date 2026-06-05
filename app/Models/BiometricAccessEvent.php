<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiometricAccessEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'member_id',
        'biometric_member_id',
        'employee_no',
        'person_name',
        'auth_method',
        'result',
        'minor_code',
        'picture_path',
        'event_time',
        'raw',
        'created_at',
    ];

    protected $casts = [
        'raw' => 'array',
        'event_time' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}

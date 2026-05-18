<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberAttendance extends Model
{
    protected $fillable = [
        'tenant_id',
        'member_id',
        'legacy_uuid',
        'legacy_member_id',
        'username',
        'attended_date',
    ];

    protected $casts = [
        'attended_date' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}

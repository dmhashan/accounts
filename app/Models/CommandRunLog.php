<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommandRunLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'command',
        'params',
        'exit_code',
        'output',
        'success',
    ];

    protected $casts = [
        'params' => 'array',
        'success' => 'boolean', // nullable — null means still running
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

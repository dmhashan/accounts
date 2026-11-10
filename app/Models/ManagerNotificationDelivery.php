<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagerNotificationDelivery extends Model
{
    protected $fillable = [
        'tenant_id',
        'event_uuid',
        'event_key',
        'channel',
        'destination',
        'destination_hash',
        'status',
        'summary',
        'attempts',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }
}

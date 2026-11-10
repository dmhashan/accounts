<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagerNotificationSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'email_enabled',
        'sms_enabled',
        'email_events',
        'sms_events',
    ];

    protected function casts(): array
    {
        return [
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'email_events' => 'array',
            'sms_events' => 'array',
        ];
    }
}

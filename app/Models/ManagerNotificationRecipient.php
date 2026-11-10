<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagerNotificationRecipient extends Model
{
    protected $fillable = [
        'tenant_id',
        'channel',
        'label',
        'destination',
        'normalized_destination',
    ];
}

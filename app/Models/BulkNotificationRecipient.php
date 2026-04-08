<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkNotificationRecipient extends Model
{
    protected $fillable = [
        'bulk_notification_id',
        'member_id',
        'phone_number',
    ];

    public function bulkNotification(): BelongsTo
    {
        return $this->belongsTo(BulkNotification::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}

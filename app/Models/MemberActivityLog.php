<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberActivityLog extends Model
{
    protected $fillable = [
        'member_id',
        'session_id',
        'event_type',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'os',
        'screen_width',
        'screen_height',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Parse a User-Agent string into device_type, browser, and os components.
     */
    public static function parseUserAgent(string $ua): array
    {
        // Device type
        if (preg_match('/Mobile|Android(?!.*Tablet)|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i', $ua)) {
            $deviceType = 'mobile';
        } elseif (preg_match('/Tablet|iPad|Kindle|PlayBook|Silk|Android.*Tablet/i', $ua)) {
            $deviceType = 'tablet';
        } else {
            $deviceType = 'desktop';
        }

        // Browser (order matters — check specific ones before generic)
        if (preg_match('/Edg\//i', $ua)) {
            $browser = 'Edge';
        } elseif (preg_match('/OPR\//i', $ua)) {
            $browser = 'Opera';
        } elseif (preg_match('/SamsungBrowser\//i', $ua)) {
            $browser = 'Samsung';
        } elseif (preg_match('/Chrome\//i', $ua)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\//i', $ua)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\//i', $ua)) {
            $browser = 'Safari';
        } elseif (preg_match('/MSIE|Trident\//i', $ua)) {
            $browser = 'IE';
        } else {
            $browser = 'Other';
        }

        // OS
        if (preg_match('/iPhone OS/i', $ua)) {
            $os = 'iOS';
        } elseif (preg_match('/iPad/i', $ua)) {
            $os = 'iPadOS';
        } elseif (preg_match('/Android/i', $ua)) {
            $os = 'Android';
        } elseif (preg_match('/Windows NT/i', $ua)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X/i', $ua)) {
            $os = 'macOS';
        } elseif (preg_match('/CrOS/i', $ua)) {
            $os = 'ChromeOS';
        } elseif (preg_match('/Linux/i', $ua)) {
            $os = 'Linux';
        } else {
            $os = 'Other';
        }

        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'os' => $os,
        ];
    }
}

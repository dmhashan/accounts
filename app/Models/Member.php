<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Member extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'member_id',
        'name',
        'gender',
        'email',
        'phone_number',
        'nic',
        'date_of_birth',
        'comment',
        'is_active',
        'is_verified',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'date_of_birth' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateMemberId(): string
    {
        // Default format: MEM-YYYY-XXXX (e.g., MEM-2025-0001)
        // This can be configured in settings later
        $year = date('Y');
        $prefix = 'MEM';
        
        $lastMember = self::where('member_id', 'like', "{$prefix}-{$year}-%")
            ->orderBy('member_id', 'desc')
            ->first();
        
        if ($lastMember) {
            $lastNumber = (int) substr($lastMember->member_id, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return sprintf("%s-%s-%04d", $prefix, $year, $newNumber);
    }
}

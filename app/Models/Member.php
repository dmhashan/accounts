<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    protected $fillable = [
        'user_id',
        'biometric_member_id',
        'first_name',
        'last_name',
        'username',
        'profile_photo_path',
        'name',
        'gender',
        'email',
        'phone_number',
        'allow_sms',
        'allow_whatsapp',
        'whatsapp_number',
        'nic',
        'date_of_birth',
        'address',
        'admission_fee',
        'payment_plan_id',
        'price',
        'current_balance',
        'joined_date',
        'comment',
        'is_active',
        'is_verified',
        'is_temp',
        'biometric_last_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'allow_sms' => 'boolean',
        'allow_whatsapp' => 'boolean',
        'is_temp' => 'boolean',
        'date_of_birth' => 'date',
        'joined_date' => 'date',
        'admission_fee' => 'decimal:2',
        'price' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bodyMeasurements(): HasMany
    {
        return $this->hasMany(MemberBodyMeasurement::class);
    }

    /**
     * Generate the next numeric biometric_member_id for a tenant.
     * Finds the highest existing purely-numeric ID for the tenant and returns max+1.
     */
    public static function generateBiometricMemberId(int $tenantId): string
    {
        $driver = \DB::getDriverName();
        $query = self::whereNotNull('biometric_member_id');

        if ($driver === 'mysql') {
            $max = (int) $query
                ->whereRaw("biometric_member_id REGEXP '^[0-9]+$'")
                ->selectRaw('MAX(CAST(biometric_member_id AS UNSIGNED)) as max_id')
                ->value('max_id');
        } else {
            // SQLite/Postgres-portable: filter numeric in PHP
            $ids = $query->pluck('biometric_member_id')->all();
            $max = 0;

            foreach ($ids as $id) {
                if (ctype_digit((string) $id)) {
                    $max = max($max, (int) $id);
                }
            }
        }

        return (string) ($max + 1);
    }
}

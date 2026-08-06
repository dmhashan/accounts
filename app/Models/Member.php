<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    protected $fillable = [
        'user_id',
        'campaign_id',
        'registration_source',
        'biometric_member_id',
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

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function paymentPlan(): BelongsTo
    {
        return $this->belongsTo(PaymentPlan::class);
    }

    public function bodyMeasurements(): HasMany
    {
        return $this->hasMany(MemberBodyMeasurement::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MemberDocument::class);
    }

    /**
     * Generate the next member ID and biometric ID based on tenant configuration settings.
     *
     * @return array{next_member_id: string, next_biometric_id: string}
     */
    public static function generateNextIds(int $tenantId, bool $advance = false): array
    {
        /** @var \App\Services\TenantConfigurationService $configService */
        $configService = app(\App\Services\TenantConfigurationService::class);
        $config = $tenantId > 0 ? $configService->all($tenantId) : [];

        $prefix = (string) ($config['member.id_prefix'] ?? '');
        $startNum = max(1, (int) ($config['member.id_next_number'] ?? 1));
        $padding = max(0, min(10, (int) ($config['member.id_padding'] ?? 4)));

        $sameAsMember = ($config['biometric.id_same_as_member_id'] ?? '1') === '1';
        $bioPrefix = (string) ($config['biometric.id_prefix'] ?? '');
        $bioStartNum = max(1, (int) ($config['biometric.id_next_number'] ?? 1));
        $bioPadding = max(0, min(10, (int) ($config['biometric.id_padding'] ?? 4)));

        // Determine max existing sequence in DB
        $ids = self::whereNotNull('biometric_member_id')->pluck('biometric_member_id')->all();

        $maxMemberSeq = 0;
        $maxBioSeq = 0;

        foreach ($ids as $id) {
            $strId = trim((string) $id);

            if ($strId === '') {
                continue;
            }

            // Member ID sequence extraction
            if ($prefix !== '' && str_starts_with($strId, $prefix)) {
                $remainder = substr($strId, strlen($prefix));

                if (ctype_digit($remainder)) {
                    $maxMemberSeq = max($maxMemberSeq, (int) $remainder);
                } elseif (preg_match('/([0-9]+)$/', $remainder, $m)) {
                    $maxMemberSeq = max($maxMemberSeq, (int) $m[1]);
                }
            } else {
                if (preg_match('/([0-9]+)$/', $strId, $m)) {
                    if ($prefix === '' || !ctype_digit($strId)) {
                        $maxMemberSeq = max($maxMemberSeq, (int) $m[1]);
                    }
                }
            }

            // Biometric ID sequence extraction (if non-linked mode)
            if (!$sameAsMember) {
                if ($bioPrefix !== '' && str_starts_with($strId, $bioPrefix)) {
                    $remainder = substr($strId, strlen($bioPrefix));

                    if (ctype_digit($remainder)) {
                        $maxBioSeq = max($maxBioSeq, (int) $remainder);
                    } elseif (preg_match('/([0-9]+)$/', $remainder, $m)) {
                        $maxBioSeq = max($maxBioSeq, (int) $m[1]);
                    }
                } else {
                    if (preg_match('/([0-9]+)$/', $strId, $m)) {
                        if ($bioPrefix === '' || !ctype_digit($strId)) {
                            $maxBioSeq = max($maxBioSeq, (int) $m[1]);
                        }
                    }
                }
            }
        }

        $nextMemberSeq = max($startNum, $maxMemberSeq + 1);
        $paddedMemberSeq = $padding > 0 ? str_pad((string) $nextMemberSeq, $padding, '0', STR_PAD_LEFT) : (string) $nextMemberSeq;
        $nextMemberId = $prefix . $paddedMemberSeq;

        if ($sameAsMember) {
            $nextBiometricId = $nextMemberId;
            $nextBioSeq = $nextMemberSeq;
        } else {
            $nextBioSeq = max($bioStartNum, $maxBioSeq + 1);
            $paddedBioSeq = $bioPadding > 0 ? str_pad((string) $nextBioSeq, $bioPadding, '0', STR_PAD_LEFT) : (string) $nextBioSeq;
            $nextBiometricId = $bioPrefix . $paddedBioSeq;
        }

        if ($advance && $tenantId > 0) {
            try {
                $updateData = [
                    'member.id_next_number' => (string) ($nextMemberSeq + 1),
                ];

                if (!$sameAsMember) {
                    $updateData['biometric.id_next_number'] = (string) ($nextBioSeq + 1);
                }

                $configService->updateBatch($tenantId, $updateData);
            } catch (\Throwable) {
                // Ignore exception if tenant_configurations table or tenant context is not ready
            }
        }

        return [
            'next_member_id' => $nextMemberId,
            'next_biometric_id' => $nextBiometricId,
        ];
    }

    /**
     * Generate the next numeric/formatted biometric_member_id for a tenant and advance sequence counter.
     */
    public static function generateBiometricMemberId(int $tenantId): string
    {
        $next = self::generateNextIds($tenantId, true);

        return $next['next_member_id'];
    }
}

<?php

namespace App\Support;

use App\Models\Member;
use App\Models\Tenant;
use App\Services\MediaStorageService;
use App\Services\MemberPortalUrlService;

class TenantEmailBranding
{
    /**
     * @return array<string, string|null>
     */
    public static function forTenant(?Tenant $tenant): array
    {
        $name = trim((string) ($tenant?->name ?? 'Your Organisation')) ?: 'Your Organisation';
        $logoUrl = null;

        if ($tenant?->logo_path) {
            try {
                $logoUrl = app(MediaStorageService::class)->url($tenant->logo_path);
            } catch (\Throwable) {
                $logoUrl = null;
            }
        }

        return [
            'name' => $name,
            'initial' => strtoupper(substr($name, 0, 1)),
            'logo_url' => $logoUrl,
            'address' => self::clean($tenant?->address),
            'email' => self::clean($tenant?->email),
            'phone' => self::clean($tenant?->phone),
            'profile_url' => $tenant ? app(MemberPortalUrlService::class)->urlForTenant($tenant) : null,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public static function forTenantId(int $tenantId): array
    {
        return self::forTenant(Tenant::find($tenantId));
    }

    public static function initials(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $parts = array_values(array_filter($parts));

        if (empty($parts)) {
            return 'M';
        }

        $first = substr($parts[0], 0, 1);
        $last = count($parts) > 1 ? substr($parts[count($parts) - 1], 0, 1) : '';

        return strtoupper($first . $last);
    }

    public static function memberAvatarUrl(?Member $member): ?string
    {
        if (!$member?->profile_photo_path) {
            return null;
        }

        try {
            return app(MediaStorageService::class)->url($member->profile_photo_path);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

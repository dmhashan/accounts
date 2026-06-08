<?php

namespace App\Services;

use App\Models\Tenant;

class MemberPortalUrlService
{
    public function __construct(
        private readonly TenantConfigurationService $tenantConfig,
    ) {}

    public function urlForTenant(Tenant $tenant, string $path = ''): string
    {
        $baseUrl = $this->configuredBaseUrl($tenant)
            ?: $this->fallbackBaseUrl($tenant);

        return $this->appendPath($baseUrl, $path);
    }

    private function configuredBaseUrl(Tenant $tenant): ?string
    {
        $raw = $this->tenantConfig->all($tenant->id)[AutomatedMemberNotificationService::CONFIG_KEY] ?? '{}';
        $decoded = json_decode((string) $raw, true);

        if (!is_array($decoded)) {
            return null;
        }

        $url = trim((string) ($decoded['member_login_url'] ?? ''));

        return $url === '' ? null : $url;
    }

    private function fallbackBaseUrl(Tenant $tenant): string
    {
        if (config('app.multitenancy_enabled', true)) {
            $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?? 'https';

            return "{$scheme}://{$tenant->domain}." . config('app.domain') . '/profile';
        }

        return rtrim(config('app.url'), '/') . '/profile';
    }

    private function appendPath(string $baseUrl, string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return $baseUrl;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

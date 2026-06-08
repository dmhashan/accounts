<?php

namespace Tests\Feature\Api;

use App\Services\TenantConfigurationService;

class ContextApiTest extends ApiRouteTestCase
{
    public function testAppContextRouteReturnsUserTenantAndPermissions(): void
    {
        $user = $this->actingAsUser([
            'dashboard.view',
            'users.view',
            'inventory.manage',
            'accounts.manage',
            'sales.process',
            'sales.create',
            'sales.edit',
            'sales.delete',
        ]);

        $response = $this->getJson('/api/app/context');

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('tenant.id', $this->tenant->id)
            ->assertJsonPath('permissions.dashboard', true)
            ->assertJsonPath('permissions.users', true)
            ->assertJsonPath('permissions.inventory', true)
            ->assertJsonPath('permissions.accounts', true)
            ->assertJsonPath('permissions.sales', true)
            ->assertJsonPath('permissions.salesCreate', true)
            ->assertJsonPath('permissions.salesEdit', true)
            ->assertJsonPath('permissions.salesDelete', true)
            ->assertJsonPath('permissions.stats', true);
    }

    public function testAppContextUsesConfiguredMemberPortalUrl(): void
    {
        $this->actingAsUser();

        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'general.member_notifications' => json_encode([
                'member_login_url' => 'https://members.test/profile',
            ]),
        ]);

        $this->getJson('/api/app/context')
            ->assertOk()
            ->assertJsonPath('tenant.member_portal_url', 'https://members.test/profile');
    }

    public function testAppContextIncludesTenantAppearanceSettings(): void
    {
        $this->actingAsUser();

        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'general.color_theme' => 'ocean',
            'general.color_mode' => 'dark',
        ]);

        $this->getJson('/api/app/context')
            ->assertOk()
            ->assertJsonPath('settings.colorTheme', 'ocean')
            ->assertJsonPath('settings.colorMode', 'dark');
    }
}

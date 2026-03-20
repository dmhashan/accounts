<?php

namespace Tests\Feature\Api;

class ContextApiTest extends ApiRouteTestCase
{
    public function test_app_context_route_returns_user_tenant_and_permissions(): void
    {
        $user = $this->actingAsUser([
            'dashboard.view',
            'users.view',
            'inventory.manage',
            'accounts.manage',
            'sales.process',
            'member.profile.view',
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
            ->assertJsonPath('permissions.stats', true)
            ->assertJsonPath('permissions.profile', true);
    }
}

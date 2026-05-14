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
}

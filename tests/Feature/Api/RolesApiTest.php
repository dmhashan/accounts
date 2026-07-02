<?php

namespace Tests\Feature\Api;

class RolesApiTest extends ApiRouteTestCase
{
    public function testRolesIndexRouteReturnsPaginatedRoles(): void
    {
        $this->actingAsUser(['roles.view']);
        $this->createRole('manager');

        $response = $this->getJson('/api/roles?per_page=10');

        $response
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'permissions']);
    }

    public function testRolesStoreRouteCreatesRole(): void
    {
        $this->actingAsUser(['roles.permissions']);

        $response = $this->postJson('/api/roles', [
            'name' => 'Nutrition Coach',
            'slug' => 'nutrition-coach',
            'description' => 'Handles nutrition plans',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Role created successfully.');

        $this->assertDatabaseHas('roles', [
            'slug' => 'nutrition-coach',
            'name' => 'Nutrition Coach',
        ]);
    }

    public function testRolesShowRouteReturnsRoleAndPermissions(): void
    {
        $this->actingAsUser(['roles.view']);
        $role = $this->createRole('support-staff');
        $permission = $this->createPermission('users.view', 'users');
        $role->permissions()->sync([$permission->id]);

        $response = $this->getJson('/api/roles/' . $role->id);

        $response
            ->assertOk()
            ->assertJsonPath('role.id', $role->id)
            ->assertJsonStructure(['role', 'permissions']);

        $permissionGroups = $response->json('permissions');

        $this->assertArrayHasKey('Accounting', $permissionGroups);
        $this->assertArrayHasKey('Settings', $permissionGroups);
        $this->assertArrayNotHasKey('Accounts', $permissionGroups);
        $this->assertArrayNotHasKey('Payments', $permissionGroups);
        $this->assertArrayNotHasKey('Notifications', $permissionGroups);
        $this->assertSame(
            ['payments.manage', 'expenses.manage', 'accounts.transfers', 'accounts.transactions'],
            collect($permissionGroups['Accounting'])->pluck('slug')->all(),
        );
        $this->assertContains('accounts.manage', collect($permissionGroups['Settings'])->pluck('slug')->all());
        $this->assertContains('payment_plans.manage', collect($permissionGroups['Settings'])->pluck('slug')->all());
        $this->assertContains('payment_methods.manage', collect($permissionGroups['Settings'])->pluck('slug')->all());
        $this->assertContains('notifications.send', collect($permissionGroups['Settings'])->pluck('slug')->all());
        $this->assertContains('events.manage', collect($permissionGroups['Settings'])->pluck('slug')->all());
        $this->assertContains('vouchers.manage', collect($permissionGroups['Settings'])->pluck('slug')->all());
        $this->assertContains('forms.manage', collect($permissionGroups['Settings'])->pluck('slug')->all());
    }

    public function testRolesUpdateRouteUpdatesRole(): void
    {
        $this->actingAsUser(['roles.permissions']);
        $role = $this->createRole('front-desk', [], true);

        $response = $this->putJson('/api/roles/' . $role->id, [
            'name' => 'Front Desk Updated',
            'slug' => 'front-desk-updated',
            'description' => 'Updated role details',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Role updated successfully.');

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'slug' => 'front-desk-updated',
            'name' => 'Front Desk Updated',
        ]);
    }

    public function testRolesUpdatePermissionsRouteSyncsPermissions(): void
    {
        $this->actingAsUser(['roles.permissions']);
        $role = $this->createRole('operations', [], true);
        $permissionA = $this->createPermission('inventory.manage', 'inventory');
        $permissionB = $this->createPermission('sales.process', 'sales');

        $response = $this->patchJson('/api/roles/' . $role->id . '/permissions', [
            'permissions' => [$permissionA->id, $permissionB->id],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Permissions updated successfully.');

        $this->assertDatabaseHas('role_permission', [
            'role_id' => $role->id,
            'permission_id' => $permissionA->id,
        ]);

        $this->assertDatabaseHas('role_permission', [
            'role_id' => $role->id,
            'permission_id' => $permissionB->id,
        ]);
    }
}

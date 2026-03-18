<?php

namespace Tests\Feature\Api;

class RolesApiTest extends ApiRouteTestCase
{
    public function test_roles_index_route_returns_paginated_roles(): void
    {
        $this->actingAsUser(['roles.view']);
        $this->createRole('manager');

        $response = $this->getJson('/api/roles?per_page=10');

        $response
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'permissions']);
    }

    public function test_roles_store_route_creates_role(): void
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

    public function test_roles_show_route_returns_role_and_permissions(): void
    {
        $this->actingAsUser(['roles.view']);
        $role = $this->createRole('support-staff');
        $permission = $this->createPermission('users.view', 'users');
        $role->permissions()->sync([$permission->id]);

        $response = $this->getJson('/api/roles/'.$role->id);

        $response
            ->assertOk()
            ->assertJsonPath('role.id', $role->id)
            ->assertJsonStructure(['role', 'permissions']);
    }

    public function test_roles_update_route_updates_role(): void
    {
        $this->actingAsUser(['roles.permissions']);
        $role = $this->createRole('front-desk', [], true);

        $response = $this->putJson('/api/roles/'.$role->id, [
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

    public function test_roles_update_permissions_route_syncs_permissions(): void
    {
        $this->actingAsUser(['roles.permissions']);
        $role = $this->createRole('operations', [], true);
        $permissionA = $this->createPermission('inventory.manage', 'inventory');
        $permissionB = $this->createPermission('sales.process', 'sales');

        $response = $this->patchJson('/api/roles/'.$role->id.'/permissions', [
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

<?php

namespace Tests\Feature\Api;

class UsersApiTest extends ApiRouteTestCase
{
    public function test_users_meta_route_returns_roles_payload(): void
    {
        $this->createRole('member');
        $this->createRole('coach');
        $this->actingAsUser(['users.view']);

        $response = $this->getJson('/api/users/meta');

        $response
            ->assertOk()
            ->assertJsonStructure(['roles']);
    }

    public function test_users_index_route_returns_paginated_users(): void
    {
        $this->actingAsUser(['users.view']);
        $targetUser = $this->createUser();

        $response = $this->getJson('/api/users?per_page=10');

        $response
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'permissions'])
            ->assertJsonFragment(['id' => $targetUser->id]);
    }

    public function test_users_show_route_returns_single_user(): void
    {
        $this->actingAsUser(['users.view']);
        $targetUser = $this->createUser();

        $response = $this->getJson('/api/users/'.$targetUser->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $targetUser->id)
            ->assertJsonPath('data.email', $targetUser->email);
    }

    public function test_users_store_route_creates_user(): void
    {
        $this->actingAsUser(['users.view', 'users.create']);
        $role = $this->createRole('trainer');

        $response = $this->postJson('/api/users', [
            'name' => 'Created User',
            'email' => 'created-user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $role->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'User created successfully.');

        $this->assertDatabaseHas('users', [
            'tenant_id' => $this->tenant->id,
            'email' => 'created-user@example.com',
            'role_id' => $role->id,
        ]);
    }

    public function test_users_update_route_updates_user(): void
    {
        $this->actingAsUser(['users.view', 'users.edit']);
        $targetUser = $this->createUser([], [
            'name' => 'Before Update',
            'email' => 'before-update@example.com',
        ]);
        $newRole = $this->createRole('updated-role');

        $response = $this->putJson('/api/users/'.$targetUser->id, [
            'name' => 'After Update',
            'email' => 'after-update@example.com',
            'role_id' => $newRole->id,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'User updated successfully.');

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'After Update',
            'email' => 'after-update@example.com',
            'role_id' => $newRole->id,
        ]);
    }

    public function test_users_destroy_route_deletes_user(): void
    {
        $this->actingAsUser(['users.view', 'users.delete']);
        $targetUser = $this->createUser([], [
            'email' => 'delete-me@example.com',
        ]);

        $response = $this->deleteJson('/api/users/'.$targetUser->id);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'User deleted successfully.');

        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id,
        ]);
    }
}

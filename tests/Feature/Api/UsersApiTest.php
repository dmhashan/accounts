<?php

namespace Tests\Feature\Api;

class UsersApiTest extends ApiRouteTestCase
{
    public function testUsersMetaRouteReturnsRolesPayload(): void
    {
        $this->createRole('member');
        $this->createRole('coach');
        $this->actingAsUser(['users.view']);

        $response = $this->getJson('/api/users/meta');

        $response
            ->assertOk()
            ->assertJsonStructure(['roles']);
    }

    public function testUsersIndexRouteReturnsPaginatedUsers(): void
    {
        $this->actingAsUser(['users.view']);
        $targetUser = $this->createUser();

        $response = $this->getJson('/api/users?per_page=10');

        $response
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'permissions'])
            ->assertJsonFragment(['id' => $targetUser->id]);
    }

    public function testUsersShowRouteReturnsSingleUser(): void
    {
        $this->actingAsUser(['users.view']);
        $targetUser = $this->createUser();
        $targetMember = $this->createMember($targetUser);

        $response = $this->getJson('/api/users/' . $targetUser->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $targetUser->id)
            ->assertJsonPath('data.email', $targetUser->email)
            ->assertJsonPath('data.role.id', $targetUser->role_id)
            ->assertJsonPath('data.member.id', $targetMember->id)
            ->assertJsonPath('data.member.member_id', $targetMember->biometric_member_id);
    }

    public function testUsersStoreRouteCreatesUser(): void
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

    public function testUsersUpdateRouteUpdatesUser(): void
    {
        $this->actingAsUser(['users.view', 'users.edit']);
        $targetUser = $this->createUser([], [
            'name' => 'Before Update',
            'email' => 'before-update@example.com',
        ]);
        $newRole = $this->createRole('updated-role');

        $response = $this->putJson('/api/users/' . $targetUser->id, [
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

    public function testUsersDestroyRouteDeletesUser(): void
    {
        $this->actingAsUser(['users.view', 'users.delete']);
        $targetUser = $this->createUser([], [
            'email' => 'delete-me@example.com',
        ]);

        $response = $this->deleteJson('/api/users/' . $targetUser->id);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'User deleted successfully.');

        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id,
        ]);
    }
}

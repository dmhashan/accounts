<?php

namespace Tests\Feature\Api;

use App\Mail\PasswordResetLinkMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

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
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.role.id', $targetUser->role_id)
            ->assertJsonPath('data.member.id', $targetMember->id)
            ->assertJsonPath('data.member.member_id', $targetMember->biometric_member_id)
            ->assertJsonPath('data.canDelete', true)
            ->assertJsonPath('data.canDeactivate', true);
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
        $originalPassword = $targetUser->password;
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

        $targetUser->refresh();
        $this->assertSame($originalPassword, $targetUser->password);
        $this->assertFalse(Hash::check('newpassword123', $targetUser->password));
    }

    public function testUsersPasswordResetRouteSendsResetEmail(): void
    {
        Mail::fake();
        $this->actingAsUser(['users.view', 'users.edit']);
        $targetUser = $this->createUser([], [
            'name' => 'Reset Recipient',
            'email' => 'reset-recipient@example.com',
        ]);

        $response = $this->postJson('/api/users/' . $targetUser->id . '/password-reset');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Password reset link has been sent to reset-recipient@example.com.');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'reset-recipient@example.com',
        ]);

        Mail::assertSent(
            PasswordResetLinkMail::class,
            fn (PasswordResetLinkMail $mail) => $mail->hasTo('reset-recipient@example.com')
                && $mail->tenantName === $this->tenant->name
                && $mail->recipientName === 'Reset Recipient'
                && str_contains($mail->resetUrl, '/reset-password/')
                && str_contains($mail->resetUrl, 'email=reset-recipient%40example.com'),
        );
    }

    public function testUsersStatusRouteDeactivatesAndReactivatesUser(): void
    {
        $this->actingAsUser(['users.view', 'users.edit']);
        $targetUser = $this->createUser();

        $deactivateResponse = $this->patchJson('/api/users/' . $targetUser->id . '/status', [
            'is_active' => false,
        ]);

        $deactivateResponse
            ->assertOk()
            ->assertJsonPath('message', 'User deactivated successfully.')
            ->assertJsonPath('data.is_active', false);

        $this->assertFalse($targetUser->fresh()->is_active);

        $activateResponse = $this->patchJson('/api/users/' . $targetUser->id . '/status', [
            'is_active' => true,
        ]);

        $activateResponse
            ->assertOk()
            ->assertJsonPath('message', 'User activated successfully.')
            ->assertJsonPath('data.is_active', true);

        $this->assertTrue($targetUser->fresh()->is_active);
    }

    public function testUsersStatusRouteCannotDeactivateCurrentUser(): void
    {
        $currentUser = $this->actingAsUser(['users.view', 'users.edit']);

        $response = $this->patchJson('/api/users/' . $currentUser->id . '/status', [
            'is_active' => false,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'You cannot deactivate yourself.');

        $this->assertTrue($currentUser->fresh()->is_active);
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

<?php

namespace Tests\Feature\Api;

use App\Services\BiometricSyncService;
use Illuminate\Support\Str;

class MembersApiTest extends ApiRouteTestCase
{
    public function testMembersMetaRouteReturnsGeneratedMemberId(): void
    {
        $this->actingAsUser(['users.view']);

        $response = $this->getJson('/api/members/meta');

        $response
            ->assertOk()
            ->assertJsonStructure(['generated_member_id']);
    }

    public function testMembersIndexRouteReturnsPaginatedMembers(): void
    {
        $this->actingAsUser(['users.view']);
        $member = $this->createMember();

        $response = $this->getJson('/api/members?per_page=10');

        $response
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'permissions'])
            ->assertJsonFragment(['id' => $member->id]);
    }

    public function testMembersExportRouteReturnsCsvStream(): void
    {
        $this->actingAsUser(['users.view']);
        $this->createMember();

        $response = $this->get('/api/members/export/google-contacts');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
    }

    public function testMembersShowRouteReturnsSingleMember(): void
    {
        $this->actingAsUser(['users.view', 'users.edit', 'users.delete']);
        $member = $this->createMember(null, [
            'is_active' => false,
            'is_verified' => true,
            'current_balance' => 1250,
        ]);

        $response = $this->getJson('/api/members/' . $member->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $member->id)
            ->assertJsonPath('data.email', $member->email)
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.is_verified', true)
            ->assertJsonPath('permissions.edit', true)
            ->assertJsonPath('permissions.delete', true);
    }

    public function testMembersShowRouteTriesBiometricFacePhotoSyncWhenMissingPhoto(): void
    {
        $this->actingAsUser(['users.view', 'users.edit', 'users.delete']);
        $member = $this->createMember(null, [
            'biometric_member_id' => 'BIO-1001',
            'profile_photo_path' => null,
        ]);

        $biometric = \Mockery::mock(BiometricSyncService::class);
        $biometric->shouldReceive('getMemberDeviceInfo')
            ->once()
            ->withArgs(fn ($arg) => $arg->id === $member->id)
            ->andReturn([
                'connection_failed' => false,
                'not_assigned' => false,
                'not_found' => false,
                'face' => ['enrolled' => true],
            ]);

        $biometric->shouldReceive('uploadFaceAsAvatar')
            ->once()
            ->withArgs(fn ($arg) => $arg->id === $member->id)
            ->andReturn([
                'success' => true,
                'profile_photo_url' => 'https://example.test/photo.jpg',
            ]);

        $this->app->instance(BiometricSyncService::class, $biometric);

        $response = $this->getJson('/api/members/' . $member->id);

        $response->assertOk();
    }

    public function testMembersShowRouteSkipsUploadWhenFaceNotEnrolled(): void
    {
        $this->actingAsUser(['users.view', 'users.edit', 'users.delete']);
        $member = $this->createMember(null, [
            'biometric_member_id' => 'BIO-1002',
            'profile_photo_path' => null,
        ]);

        $biometric = \Mockery::mock(BiometricSyncService::class);
        $biometric->shouldReceive('getMemberDeviceInfo')
            ->once()
            ->withArgs(fn ($arg) => $arg->id === $member->id)
            ->andReturn([
                'connection_failed' => false,
                'not_assigned' => false,
                'not_found' => false,
                'face' => ['enrolled' => false],
            ]);

        $biometric->shouldReceive('uploadFaceAsAvatar')->never();

        $this->app->instance(BiometricSyncService::class, $biometric);

        $response = $this->getJson('/api/members/' . $member->id);

        $response->assertOk();
    }

    public function testMembersStoreRouteCreatesMember(): void
    {
        $this->createRole('member');
        $this->actingAsUser(['users.view', 'users.create']);

        $payload = $this->memberPayload([
            'email' => 'member-store@example.com',
            'username' => 'member-store',
        ]);

        $response = $this->postJson('/api/members', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Member created successfully.');

        $this->assertDatabaseHas('members', [
            'tenant_id' => $this->tenant->id,
            'email' => 'member-store@example.com',
            'username' => 'member-store',
        ]);
    }

    public function testMembersUpdateRouteUpdatesMember(): void
    {
        $this->actingAsUser(['users.view', 'users.edit']);
        $linkedUser = $this->createUser();
        $member = $this->createMember($linkedUser, [
            'username' => 'member-update',
            'email' => 'member-update@example.com',
        ]);

        $payload = $this->memberPayload([
            'first_name' => 'Updated',
            'last_name' => 'Member',
            'username' => 'member-update',
            'email' => 'member-update@example.com',
        ]);

        $response = $this->putJson('/api/members/' . $member->id, $payload);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Member updated successfully.');

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'first_name' => 'Updated',
            'last_name' => 'Member',
        ]);
    }

    public function testMembersToggleStatusRouteTogglesState(): void
    {
        $this->actingAsUser(['users.view', 'users.edit']);
        $member = $this->createMember(null, ['is_active' => true]);

        $response = $this->patchJson('/api/members/' . $member->id . '/toggle-status');

        $response
            ->assertOk()
            ->assertJsonPath('is_active', false);
    }

    public function testMembersToggleVerificationRouteTogglesState(): void
    {
        $this->actingAsUser(['users.view', 'users.edit']);
        $member = $this->createMember(null, ['is_verified' => true]);

        $response = $this->patchJson('/api/members/' . $member->id . '/toggle-verification');

        $response
            ->assertOk()
            ->assertJsonPath('is_verified', false);
    }

    public function testMembersDestroyRouteDeletesMemberAndLinkedUser(): void
    {
        $this->actingAsUser(['users.view', 'users.delete']);
        $linkedUser = $this->createUser([], ['email' => 'linked-user@example.com']);
        $member = $this->createMember($linkedUser);

        $response = $this->deleteJson('/api/members/' . $member->id);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Member deleted successfully.');

        $this->assertDatabaseMissing('members', ['id' => $member->id]);
        $this->assertDatabaseMissing('users', ['id' => $linkedUser->id]);
    }

    private function memberPayload(array $overrides = []): array
    {
        $plan = $this->createPaymentPlan();

        return array_merge([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'username' => 'member-' . Str::lower(Str::random(6)),
            'gender' => 'male',
            'email' => 'member-' . Str::lower(Str::random(6)) . '@example.com',
            'phone_number' => '0712345678',
            'nic' => '993456789V',
            'date_of_birth' => now()->subYears(25)->toDateString(),
            'address' => 'No 1, Main Street',
            'admission_fee' => 500,
            'payment_plan_id' => $plan->id,
            'price' => 2500,
            'joined_date' => now()->toDateString(),
            'comment' => 'Test member payload',
        ], $overrides);
    }
}

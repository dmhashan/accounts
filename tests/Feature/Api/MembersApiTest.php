<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Str;

class MembersApiTest extends ApiRouteTestCase
{
    public function test_members_meta_route_returns_generated_member_id(): void
    {
        $this->actingAsUser(['users.view']);

        $response = $this->getJson('/api/members/meta');

        $response
            ->assertOk()
            ->assertJsonStructure(['generated_member_id']);
    }

    public function test_members_index_route_returns_paginated_members(): void
    {
        $this->actingAsUser(['users.view']);
        $member = $this->createMember();

        $response = $this->getJson('/api/members?per_page=10');

        $response
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'permissions'])
            ->assertJsonFragment(['id' => $member->id]);
    }

    public function test_members_export_route_returns_csv_stream(): void
    {
        $this->actingAsUser(['users.view']);
        $this->createMember();

        $response = $this->get('/api/members/export/google-contacts');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
    }

    public function test_members_show_route_returns_single_member(): void
    {
        $this->actingAsUser(['users.view', 'users.edit', 'users.delete']);
        $member = $this->createMember(null, [
            'is_active' => false,
            'is_verified' => true,
            'current_balance' => 1250,
        ]);

        $response = $this->getJson('/api/members/'.$member->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $member->id)
            ->assertJsonPath('data.email', $member->email)
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.is_verified', true)
            ->assertJsonPath('permissions.edit', true)
            ->assertJsonPath('permissions.delete', true);
    }

    public function test_members_store_route_creates_member(): void
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

    public function test_members_update_route_updates_member(): void
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

        $response = $this->putJson('/api/members/'.$member->id, $payload);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Member updated successfully.');

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'first_name' => 'Updated',
            'last_name' => 'Member',
        ]);
    }

    public function test_members_toggle_status_route_toggles_state(): void
    {
        $this->actingAsUser(['users.view', 'users.edit']);
        $member = $this->createMember(null, ['is_active' => true]);

        $response = $this->patchJson('/api/members/'.$member->id.'/toggle-status');

        $response
            ->assertOk()
            ->assertJsonPath('is_active', false);
    }

    public function test_members_toggle_verification_route_toggles_state(): void
    {
        $this->actingAsUser(['users.view', 'users.edit']);
        $member = $this->createMember(null, ['is_verified' => true]);

        $response = $this->patchJson('/api/members/'.$member->id.'/toggle-verification');

        $response
            ->assertOk()
            ->assertJsonPath('is_verified', false);
    }

    public function test_members_destroy_route_deletes_member_and_linked_user(): void
    {
        $this->actingAsUser(['users.view', 'users.delete']);
        $linkedUser = $this->createUser([], ['email' => 'linked-user@example.com']);
        $member = $this->createMember($linkedUser);

        $response = $this->deleteJson('/api/members/'.$member->id);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Member deleted successfully.');

        $this->assertDatabaseMissing('members', ['id' => $member->id]);
        $this->assertDatabaseMissing('users', ['id' => $linkedUser->id]);
    }

    private function memberPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'username' => 'member-'.Str::lower(Str::random(6)),
            'gender' => 'male',
            'email' => 'member-'.Str::lower(Str::random(6)).'@example.com',
            'phone_number' => '0712345678',
            'nic' => '993456789V',
            'date_of_birth' => now()->subYears(25)->toDateString(),
            'age' => 25,
            'address' => 'No 1, Main Street',
            'member_role' => 'premium',
            'admission_fee' => 500,
            'payment_plan' => 'monthly',
            'price' => 2500,
            'joined_date' => now()->toDateString(),
            'comment' => 'Test member payload',
        ], $overrides);
    }
}

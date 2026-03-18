<?php

namespace Tests\Feature\Api;

class ProfileApiTest extends ApiRouteTestCase
{
    public function test_profile_route_returns_profile_data_for_authorized_user(): void
    {
        $user = $this->actingAsUser(['member.profile.view']);
        $member = $this->createMember($user);

        $response = $this->getJson('/api/profile');

        $response
            ->assertOk()
            ->assertJsonPath('data.account.id', $user->id)
            ->assertJsonPath('data.tenant.id', $this->tenant->id)
            ->assertJsonPath('data.member.id', $member->id);
    }
}

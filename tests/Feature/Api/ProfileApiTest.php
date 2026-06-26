<?php

namespace Tests\Feature\Api;

class ProfileApiTest extends ApiRouteTestCase
{
    public function testProfileRouteReturnsProfileDataForAuthorizedUser(): void
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

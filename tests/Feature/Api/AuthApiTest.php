<?php

namespace Tests\Feature\Api;

class AuthApiTest extends ApiRouteTestCase
{
    public function testLoginRouteAuthenticatesUser(): void
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/auth/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Login successful.');

        $this->assertAuthenticatedAs($user);
    }

    public function testLogoutRouteLogsOutAuthenticatedUser(): void
    {
        $user = $this->actingAsUser();

        $response = $this->postJson('/api/auth/logout');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->assertGuest();
    }

    public function testRefreshRouteReturnsUnauthorizedForGuest(): void
    {
        $response = $this->postJson('/api/auth/refresh');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function testRefreshRouteReturnsOkForAuthenticatedUser(): void
    {
        $this->actingAsUser();

        $response = $this->postJson('/api/auth/refresh');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Session refreshed successfully.');
    }
}

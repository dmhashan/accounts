<?php

namespace Tests\Feature\Api;

class AuthApiTest extends ApiRouteTestCase
{
    public function test_login_route_authenticates_user(): void
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

    public function test_logout_route_logs_out_authenticated_user(): void
    {
        $user = $this->actingAsUser();

        $response = $this->postJson('/api/auth/logout');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->assertGuest();
    }

    public function test_refresh_route_returns_unauthorized_for_guest(): void
    {
        $response = $this->postJson('/api/auth/refresh');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_refresh_route_returns_ok_for_authenticated_user(): void
    {
        $this->actingAsUser();

        $response = $this->postJson('/api/auth/refresh');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Session refreshed successfully.');
    }
}

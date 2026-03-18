<?php

namespace Tests\Feature\Api;

class HealthApiTest extends ApiRouteTestCase
{
    public function test_health_route_returns_ok_response(): void
    {
        $response = $this->getJson('/api/health');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['status', 'timestamp']);
    }
}

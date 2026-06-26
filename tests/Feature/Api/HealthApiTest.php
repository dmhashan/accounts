<?php

namespace Tests\Feature\Api;

class HealthApiTest extends ApiRouteTestCase
{
    public function testHealthRouteReturnsOkResponse(): void
    {
        $response = $this->getJson('/api/health');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['status', 'timestamp']);
    }
}

<?php

namespace Tests\Feature\Api;

class ReportsApiTest extends ApiRouteTestCase
{
    public function test_reports_overview_route_returns_coming_soon_payload(): void
    {
        $this->actingAsUser(['reports.view']);

        $response = $this->getJson('/api/reports/overview');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'coming-soon')
            ->assertJsonCount(4, 'features');
    }
}

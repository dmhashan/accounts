<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $tenant = Tenant::create([
            'name' => 'Gym',
            'domain' => 'gym',
            'use_custom_landing_page' => false,
        ]);

        config([
            'app.multitenancy_enabled' => false,
            'app.multitenancy_bypass_domain' => $tenant->domain,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}

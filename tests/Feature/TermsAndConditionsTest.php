<?php

namespace Tests\Feature;

use App\Models\TenantConfiguration;
use Tests\Feature\Api\ApiRouteTestCase;

class TermsAndConditionsTest extends ApiRouteTestCase
{
    public function testPublicWebRouteReturnsTermsWhenEnabled(): void
    {
        TenantConfiguration::create([
            'tenant_id' => $this->tenant->id,
            'key' => 'terms.enabled',
            'title' => 'Enable Member Terms and Conditions',
            'value' => '1',
        ]);
        TenantConfiguration::create([
            'tenant_id' => $this->tenant->id,
            'key' => 'terms.content',
            'title' => 'Member Terms and Conditions Content',
            'value' => '<h2>Gym Policy</h2><p>Always respect equipment.</p>',
        ]);

        $this->get('/members_terms_and_conditions')
            ->assertOk()
            ->assertSee('Gym Policy')
            ->assertSee('Always respect equipment.');
    }

    public function testPublicWebRouteReturns404WhenDisabled(): void
    {
        TenantConfiguration::create([
            'tenant_id' => $this->tenant->id,
            'key' => 'terms.enabled',
            'title' => 'Enable Member Terms and Conditions',
            'value' => '0',
        ]);

        $this->get('/members_terms_and_conditions')
            ->assertStatus(404)
            ->assertSee('Terms &amp; Conditions Unavailable', false);
    }

    public function testPublicApiEndpointReturnsTermsData(): void
    {
        TenantConfiguration::create([
            'tenant_id' => $this->tenant->id,
            'key' => 'terms.enabled',
            'title' => 'Enable Member Terms and Conditions',
            'value' => '1',
        ]);
        TenantConfiguration::create([
            'tenant_id' => $this->tenant->id,
            'key' => 'terms.content',
            'title' => 'Member Terms and Conditions Content',
            'value' => '<p>Terms content here</p>',
        ]);

        $this->getJson('/api/public/terms-and-conditions')
            ->assertOk()
            ->assertJson([
                'enabled' => true,
                'content' => '<p>Terms content here</p>',
                'tenant' => [
                    'name' => $this->tenant->name,
                    'domain' => $this->tenant->domain,
                ],
            ]);
    }
}

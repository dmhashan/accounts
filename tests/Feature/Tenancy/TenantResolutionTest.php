<?php

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function testSubdomainResolvesOnlyTheMatchingTenant(): void
    {
        $expected = $this->createTenant('alpha');
        $this->createTenant('beta');

        config([
            'app.domain' => 'example.test',
            'app.multitenancy_enabled' => true,
        ]);

        $this->getJson('http://alpha.example.test/api/health')->assertOk();
        $this->assertTrue(app('tenant')->is($expected));
    }

    public function testUnknownSubdomainIsRejectedBeforeTheEndpointRuns(): void
    {
        $this->createTenant('alpha');

        config([
            'app.domain' => 'example.test',
            'app.multitenancy_enabled' => true,
        ]);

        $this->getJson('http://unknown.example.test/api/health')
            ->assertRedirect('/');
    }

    public function testBypassModeResolvesOnlyTheConfiguredTenant(): void
    {
        $expected = $this->createTenant('alpha');
        $this->createTenant('beta');

        config([
            'app.multitenancy_enabled' => false,
            'app.multitenancy_bypass_domain' => 'alpha',
        ]);

        $this->getJson('/api/health')->assertOk();

        $this->assertTrue(app('tenant')->is($expected));
    }

    public function testLoginAuthenticatesAUserAfterTenantResolution(): void
    {
        $this->createTenant('alpha');
        $this->createTenant('beta');

        $user = User::create([
            'name' => 'Other Tenant User',
            'email' => 'shared@example.com',
            'username' => 'shared',
            'password' => Hash::make('password'),
        ]);

        config([
            'app.domain' => 'example.test',
            'app.multitenancy_enabled' => true,
        ]);

        $this->postJson('http://alpha.example.test/api/auth/login', [
            'login' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $this->assertAuthenticated();
    }

    private function createTenant(string $domain): Tenant
    {
        return Tenant::create([
            'name' => Str::title($domain),
            'domain' => $domain,
            'tenant_uuid' => Str::uuid()->toString(),
            'use_custom_landing_page' => false,
        ]);
    }
}

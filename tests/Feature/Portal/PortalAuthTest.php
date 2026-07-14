<?php

namespace Tests\Feature\Portal;

use App\Models\PortalUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortalAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reconfigure central database connection to sqlite memory for testing
        config([
            'database.connections.central' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);

        DB::purge('central');

        // Create base central tenants table mimicking production central DB before portal migration
        \Illuminate\Support\Facades\Schema::connection('central')->create('tenants', function ($table) {
            $table->string('subdomain')->primary();
            $table->string('database_name')->unique();
        });

        // Migrate portal-specific tables
        \Illuminate\Support\Facades\Artisan::call('migrate', [
            '--database' => 'central',
            '--path' => 'database/migrations/portal',
            '--force' => true,
        ]);
    }

    public function testCannotRequestOtpForNonExistentUser()
    {
        $response = $this->postJson('/api/portal/auth/request-otp', [
            'identifier' => 'unknown@portal.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function testCanRequestOtpForActiveUser()
    {
        $user = PortalUser::create([
            'name' => 'Test Admin',
            'email' => 'admin@portal.com',
            'mobile_number' => '0771234567',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/portal/auth/request-otp', [
            'identifier' => 'admin@portal.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'otp_debug']);

        $otp = $response->json('otp_debug');
        $this->assertNotNull($otp);
        $this->assertEquals($otp, Cache::get("otp:portal:login:{$user->id}"));
    }

    public function testCannotLoginWithInvalidOtp()
    {
        $user = PortalUser::create([
            'name' => 'Test Admin',
            'email' => 'admin@portal.com',
            'mobile_number' => '0771234567',
            'is_active' => true,
        ]);

        Cache::put("otp:portal:login:{$user->id}", '123456', now()->addMinutes(10));

        $response = $this->postJson('/api/portal/auth/login', [
            'identifier' => 'admin@portal.com',
            'otp' => '111111',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function testCanLoginWithCorrectOtp()
    {
        $user = PortalUser::create([
            'name' => 'Test Admin',
            'email' => 'admin@portal.com',
            'mobile_number' => '0771234567',
            'is_active' => true,
        ]);

        Cache::put("otp:portal:login:{$user->id}", '123456', now()->addMinutes(10));

        $response = $this->postJson('/api/portal/auth/login', [
            'identifier' => 'admin@portal.com',
            'otp' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user', 'message']);

        $this->assertAuthenticatedAs($user, 'portal');
    }

    public function testCanCheckAuthStatusViaMe()
    {
        $user = PortalUser::create([
            'name' => 'Test Admin',
            'email' => 'admin@portal.com',
            'mobile_number' => '0771234567',
            'is_active' => true,
        ]);

        // Unauthenticated check
        $this->getJson('/api/portal/auth/me')
            ->assertStatus(401);

        // Authenticated check
        $this->actingAs($user, 'portal')
            ->getJson('/api/portal/auth/me')
            ->assertStatus(200)
            ->assertJson([
                'authenticated' => true,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ]);
    }

    public function testCanLogout()
    {
        $user = PortalUser::create([
            'name' => 'Test Admin',
            'email' => 'admin@portal.com',
            'mobile_number' => '0771234567',
            'is_active' => true,
        ]);

        $this->actingAs($user, 'portal')
            ->postJson('/api/portal/auth/logout')
            ->assertStatus(200);

        $this->assertGuest('portal');
    }
}

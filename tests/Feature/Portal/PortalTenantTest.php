<?php

namespace Tests\Feature\Portal;

use App\Models\PortalUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortalTenantTest extends TestCase
{
    use RefreshDatabase;

    private PortalUser $user;

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

        // Set isolation disabled for testing
        config(['tenancy.database_isolation_enabled' => false]);

        $this->user = PortalUser::create([
            'name' => 'Portal Admin',
            'email' => 'admin@portal.com',
            'mobile_number' => '0771234567',
            'is_active' => true,
        ]);
    }

    public function testMutatingActionsRequireOtpVerification()
    {
        $response = $this->actingAs($this->user, 'portal')
            ->postJson('/api/portal/tenants', [
                'name' => 'New Gym',
                'domain' => 'newgym',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'otp_required' => true,
            ]);
    }

    public function testCanRequestActionOtp()
    {
        $response = $this->actingAs($this->user, 'portal')
            ->postJson('/api/portal/auth/action-otp');

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'otp_debug']);

        $otp = $response->json('otp_debug');
        $this->assertNotNull($otp);
        $this->assertEquals($otp, Cache::get("otp:portal:action:{$this->user->id}"));
    }

    public function testCanCreateTenantWithOtp()
    {
        $otp = '654321';
        Cache::put("otp:portal:action:{$this->user->id}", $otp, now()->addMinutes(10));

        $response = $this->actingAs($this->user, 'portal')
            ->withHeader('X-Portal-OTP', $otp)
            ->postJson('/api/portal/tenants', [
                'name' => 'Apex Gym',
                'domain' => 'apexgym',
                'email' => 'apex@gym.com',
                'phone' => '0778889990',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('tenant.subdomain', 'apexgym');

        // Check central registry DB insertion
        $this->assertTrue(DB::connection('central')->table('tenants')->where('subdomain', 'apexgym')->exists());

        // Check local tenant DB insertion (bypass database creation logic mock)
        $this->assertDatabaseHas('tenants', [
            'domain' => 'apexgym',
            'name' => 'Apex Gym',
        ]);
    }

    public function testCannotCreateTenantWithReservedSubdomain()
    {
        $otp = '654321';
        Cache::put("otp:portal:action:{$this->user->id}", $otp, now()->addMinutes(10));

        $response = $this->actingAs($this->user, 'portal')
            ->withHeader('X-Portal-OTP', $otp)
            ->postJson('/api/portal/tenants', [
                'name' => 'System Admin',
                'domain' => 'portal', // Reserved!
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['domain']]);
    }

    public function testCanUpdateTenantWithOtp()
    {
        // Setup initial tenant row in central
        DB::connection('central')->table('tenants')->insert([
            'subdomain' => 'testgym',
            'database_name' => 'uuid-12345',
            'name' => 'Test Gym',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otp = '123123';
        Cache::put("otp:portal:action:{$this->user->id}", $otp, now()->addMinutes(10));

        $response = $this->actingAs($this->user, 'portal')
            ->withHeader('X-Portal-OTP', $otp)
            ->putJson('/api/portal/tenants/testgym', [
                'name' => 'Updated Gym Name',
                'email' => 'updated@gym.com',
                'phone' => '0779998887',
            ]);

        $response->assertStatus(200);

        // Check central registry updated
        $centralRow = DB::connection('central')->table('tenants')->where('subdomain', 'testgym')->first();
        $this->assertEquals('Updated Gym Name', $centralRow->name);
        $this->assertEquals('updated@gym.com', $centralRow->email);
        $this->assertEquals('0779998887', $centralRow->phone);
    }

    public function testApiDeleteRouteIsDisabled()
    {
        $response = $this->actingAs($this->user, 'portal')
            ->deleteJson('/api/portal/tenants/deletegym');

        // Since it is excluded from apiResource, it should return 404 or 405
        $this->assertTrue(in_array($response->status(), [404, 405]));
    }

    public function testDeleteCommandFailsForNonExistentTenant()
    {
        $this->artisan('tenants:delete', ['subdomain' => 'nonexistent'])
            ->expectsOutput("Tenant 'nonexistent' not found in the central registry.")
            ->assertFailed();
    }

    public function testDeleteCommandFailsForActiveTenant()
    {
        DB::connection('central')->table('tenants')->insert([
            'subdomain' => 'activegym',
            'database_name' => 'uuid-active',
            'name' => 'Active Gym',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('tenants:delete', ['subdomain' => 'activegym'])
            ->expectsOutput("Cannot delete active tenant 'activegym'. You must suspend/block the tenant first.")
            ->assertFailed();
    }

    public function testDeleteCommandSucceedsForInactiveTenant()
    {
        DB::connection('central')->table('tenants')->insert([
            'subdomain' => 'inactivegym',
            'database_name' => 'uuid-inactive',
            'name' => 'Inactive Gym',
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('tenants:delete', ['subdomain' => 'inactivegym'])
            ->expectsConfirmation("Are you absolutely sure you want to permanently delete tenant 'inactivegym'? This drops its database and cannot be undone.", 'yes')
            ->expectsOutput("Tenant 'inactivegym' has been successfully deleted.")
            ->assertSuccessful();

        $this->assertFalse(DB::connection('central')->table('tenants')->where('subdomain', 'inactivegym')->exists());
    }

    public function testDeleteCommandCancelsOnNegativeConfirmation()
    {
        DB::connection('central')->table('tenants')->insert([
            'subdomain' => 'inactivegym2',
            'database_name' => 'uuid-inactive2',
            'name' => 'Inactive Gym 2',
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('tenants:delete', ['subdomain' => 'inactivegym2'])
            ->expectsConfirmation("Are you absolutely sure you want to permanently delete tenant 'inactivegym2'? This drops its database and cannot be undone.", 'no')
            ->expectsOutput('Deletion cancelled.')
            ->assertSuccessful();

        $this->assertTrue(DB::connection('central')->table('tenants')->where('subdomain', 'inactivegym2')->exists());
    }

    public function testCanGetTenantDetailsShowView()
    {
        // Setup initial tenant row in central
        DB::connection('central')->table('tenants')->insert([
            'subdomain' => 'showgym',
            'database_name' => 'uuid-show',
            'name' => 'Show Gym',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'portal')
            ->getJson('/api/portal/tenants/showgym');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'tenant',
                'members' => ['total_count', 'active_count', 'inactive_count', 'temp_count', 'recent'],
                'users' => ['total_count', 'recent'],
            ]);
    }

    public function testCanToggleTenantActiveStatus()
    {
        // Setup initial tenant row in central
        DB::connection('central')->table('tenants')->insert([
            'subdomain' => 'togglegym',
            'database_name' => 'uuid-toggle',
            'name' => 'Toggle Gym',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otp = '112233';
        Cache::put("otp:portal:action:{$this->user->id}", $otp, now()->addMinutes(10));

        $response = $this->actingAs($this->user, 'portal')
            ->withHeader('X-Portal-OTP', $otp)
            ->putJson('/api/portal/tenants/togglegym', [
                'name' => 'Toggle Gym',
                'is_active' => false,
            ]);

        $response->assertStatus(200);

        // Assert updated in central database
        $centralRow = DB::connection('central')->table('tenants')->where('subdomain', 'togglegym')->first();
        $this->assertEquals(0, $centralRow->is_active);
    }

    public function testInactiveTenantIsBlockedByMiddleware()
    {
        // Setup inactive tenant in central database
        DB::connection('central')->table('tenants')->insert([
            'subdomain' => 'blockedgym',
            'database_name' => 'uuid-blocked',
            'name' => 'Blocked Gym',
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Setup local tenant representation since isolation is disabled in tests
        \App\Models\Tenant::create([
            'name' => 'Blocked Gym',
            'domain' => 'blockedgym',
            'tenant_uuid' => 'uuid-blocked',
        ]);

        // Mock config domain for host resolution
        config([
            'app.domain' => 'localhost',
            'app.multitenancy_enabled' => true,
        ]);

        // 1. JSON Request should return 403
        $this->getJson('http://blockedgym.localhost/api/health')
            ->assertStatus(403)
            ->assertJson([
                'blocked' => true,
            ]);

        // 2. Web Request should render tenant-blocked view
        $response = $this->get('http://blockedgym.localhost/');
        $response->assertStatus(200);
        $response->assertSee('Temporary Blocked');
        $response->assertSee('blockedgym');
        $response->assertSee('beforward.lk');
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoginPageRedirectTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Gym',
            'domain' => 'test-gym',
            'tenant_uuid' => Str::uuid()->toString(),
            'use_custom_landing_page' => false,
        ]);

        config([
            'app.multitenancy_enabled' => false,
            'app.multitenancy_bypass_domain' => $this->tenant->domain,
        ]);
    }

    public function testLoginPageRedirectsAuthenticatedUserOfSameTenant(): void
    {
        $user = $this->createUserForTenant($this->tenant, 'admin');

        $this->actingAs($user);

        $this->get('/login')->assertRedirect(route('dashboard'));
    }

    public function testLoginPostRejectsInactiveUser(): void
    {
        $user = $this->createUserForTenant($this->tenant, 'admin');
        $user->update(['is_active' => false]);

        $response = $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHas('error', 'Invalid username/email or password for this tenant.');
        $this->assertGuest();
    }

    public function testInactiveAuthenticatedUserIsLoggedOutFromProtectedWebRoutes(): void
    {
        $user = $this->createUserForTenant($this->tenant, 'admin');
        $user->update(['is_active' => false]);

        $this->actingAs($user);

        $this->get('/dashboard')
            ->assertRedirect(route('login.form'))
            ->assertSessionHas('error', 'Your account is deactivated. Please contact an administrator.');

        $this->assertGuest();
    }

    private function createUserForTenant(Tenant $tenant, string $roleSlug): User
    {
        $role = Role::create([
            'name' => ucfirst($roleSlug),
            'slug' => $roleSlug,
            'description' => $roleSlug . ' role',
            'is_editable' => true,
        ]);

        return User::create([
            'role_id' => $role->id,
            'name' => ucfirst($roleSlug) . ' User',
            'email' => $roleSlug . '+' . $tenant->domain . '@example.com',
            'username' => $roleSlug . '_' . str_replace('-', '_', $tenant->domain),
            'password' => Hash::make('password'),
        ]);
    }
}

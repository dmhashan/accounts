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

    public function test_login_page_redirects_authenticated_user_of_same_tenant(): void
    {
        $user = $this->createUserForTenant($this->tenant, 'admin');

        $this->actingAs($user);

        $this->get('/login')->assertRedirect(route('dashboard'));
    }

    public function test_login_page_logs_out_authenticated_user_from_another_tenant(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other Gym',
            'domain' => 'other-gym',
            'tenant_uuid' => Str::uuid()->toString(),
            'use_custom_landing_page' => false,
        ]);

        $otherUser = $this->createUserForTenant($otherTenant, 'staff');

        $this->actingAs($otherUser);

        $response = $this->get('/login');

        $response->assertOk()->assertViewIs('auth.login');
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
            'tenant_id' => $tenant->id,
            'role_id' => $role->id,
            'name' => ucfirst($roleSlug) . ' User',
            'email' => $roleSlug . '+' . $tenant->domain . '@example.com',
            'username' => $roleSlug . '_' . str_replace('-', '_', $tenant->domain),
            'password' => Hash::make('password'),
        ]);
    }
}

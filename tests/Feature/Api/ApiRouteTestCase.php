<?php

namespace Tests\Feature\Api;

use App\Models\CompanyAccount;
use App\Models\CompanyAccountTransfer;
use App\Models\Member;
use App\Models\PaymentPlan;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Role;
use App\Models\Sale;
use App\Models\StockEntry;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

abstract class ApiRouteTestCase extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    private int $sequence = 0;

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

    protected function actingAsUser(array $permissions = [], array $attributes = [], ?Role $role = null): User
    {
        $user = $this->createUser($permissions, $attributes, $role);
        $this->actingAs($user);

        return $user;
    }

    protected function createUser(array $permissions = [], array $attributes = [], ?Role $role = null): User
    {
        $sequence = $this->nextSequence();
        $role ??= $this->createRole('role-' . $sequence, $permissions);

        if ($permissions !== []) {
            $this->grantPermissions($role, $permissions);
        }

        return User::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'role_id' => $role->id,
            'name' => 'User ' . $sequence,
            'email' => 'user' . $sequence . '@example.com',
            'username' => 'user' . $sequence,
            'password' => Hash::make('password'),
        ], $attributes));
    }

    protected function createRole(string $slug = 'admin', array $permissions = [], bool $isEditable = true): Role
    {
        $role = Role::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => Str::title(str_replace(['-', '_'], ' ', $slug)),
                'description' => $slug . ' role',
                'is_editable' => $isEditable,
            ],
        );

        if ($permissions !== []) {
            $this->grantPermissions($role, $permissions);
        }

        return $role;
    }

    protected function grantPermissions(Role $role, array $permissionSlugs): void
    {
        $permissionIds = collect($permissionSlugs)->map(function (string $slug) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => Str::title(str_replace('.', ' ', $slug)),
                    'feature' => Str::before($slug, '.') ?: 'general',
                    'description' => $slug . ' permission',
                ],
            );

            return $permission->id;
        })->all();

        $role->permissions()->syncWithoutDetaching($permissionIds);
    }

    protected function createPermission(string $slug, ?string $feature = null): Permission
    {
        return Permission::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => Str::title(str_replace('.', ' ', $slug)),
                'feature' => $feature ?? (Str::before($slug, '.') ?: 'general'),
                'description' => $slug . ' permission',
            ],
        );
    }

    protected function createMember(?User $user = null, array $attributes = []): Member
    {
        $sequence = $this->nextSequence();
        $firstName = 'Member' . $sequence;
        $lastName = 'Tester';
        $plan = $this->createPaymentPlan();

        return Member::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user?->id,
            'biometric_member_id' => Member::generateBiometricMemberId($this->tenant->id),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => 'member' . $sequence,
            'name' => $firstName . ' ' . $lastName,
            'gender' => 'male',
            'email' => 'member' . $sequence . '@example.com',
            'phone_number' => '07000000' . $sequence,
            'nic' => 'NIC' . $sequence,
            'date_of_birth' => now()->subYears(24)->toDateString(),
            'address' => 'No. ' . $sequence . ', Test Street',
            'admission_fee' => 500,
            'payment_plan_id' => $plan->id,
            'price' => 1200,
            'current_balance' => 0,
            'joined_date' => now()->toDateString(),
            'comment' => 'Test member',
            'is_active' => true,
            'is_verified' => true,
        ], $attributes));
    }

    protected function createPaymentPlan(array $attributes = []): PaymentPlan
    {
        $sequence = $this->nextSequence();

        return PaymentPlan::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'name' => 'Plan ' . $sequence,
            'duration_days' => 30,
            'price' => 1200,
            'is_active' => true,
        ], $attributes));
    }

    protected function createProduct(array $attributes = []): Product
    {
        $sequence = $this->nextSequence();

        return Product::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'name' => 'Product ' . $sequence,
        ], $attributes));
    }

    protected function createVariation(Product $product, array $attributes = []): ProductVariation
    {
        $sequence = $this->nextSequence();

        return ProductVariation::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'product_id' => $product->id,
            'name' => 'Variation ' . $sequence,
        ], $attributes));
    }

    protected function createStockEntry(Product $product, ProductVariation $variation, array $attributes = []): StockEntry
    {
        $data = array_merge([
            'tenant_id' => $this->tenant->id,
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'quantity' => 20,
            'manufacturing_date' => now()->subDays(5)->toDateString(),
            'expiry_date' => now()->addDays(30)->toDateString(),
            'purchasing_price' => 100,
            'local_selling_price' => 150,
            'foreign_selling_price' => 200,
        ], $attributes);

        // Default display_quantity to quantity so test sales can operate on stock
        if (!array_key_exists('display_quantity', $data)) {
            $data['display_quantity'] = $data['quantity'];
        }

        return StockEntry::create($data);
    }

    protected function createSale(array $attributes = []): Sale
    {
        return Sale::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'customer_name' => 'Walk In',
            'customer_type' => 'local',
            'payment_method' => 'cash',
            'reference_number' => 'REF-' . Str::upper(Str::random(6)),
            'total_amount' => 300,
            'paid_amount' => 300,
            'balance' => 0,
        ], $attributes));
    }

    protected function createCompanyAccount(array $attributes = []): CompanyAccount
    {
        $sequence = $this->nextSequence();

        return CompanyAccount::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'name' => 'Account ' . $sequence,
            'opening_balance' => 0,
            'description' => 'Test account',
        ], $attributes));
    }

    protected function createCompanyAccountTransfer(CompanyAccount $sourceAccount, CompanyAccount $destinationAccount, array $attributes = []): CompanyAccountTransfer
    {
        return CompanyAccountTransfer::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'source_account_id' => $sourceAccount->id,
            'destination_account_id' => $destinationAccount->id,
            'amount' => 100,
            'transfer_date' => now()->toDateString(),
            'reference_number' => 'TRF-' . Str::upper(Str::random(6)),
            'notes' => 'Test transfer',
        ], $attributes));
    }

    private function nextSequence(): int
    {
        $this->sequence++;

        return $this->sequence;
    }
}

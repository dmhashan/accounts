<?php

namespace Tests\Feature\Api;

use App\Http\Middleware\CheckPermission;
use App\Models\CompanyAccount;
use App\Models\Member;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Role;
use App\Models\Sale;
use App\Models\StockEntry;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiCrudOperationsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Tenant $otherTenant;

    private User $user;

    private Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.multitenancy_enabled' => false,
            'app.multitenancy_bypass_domain' => 'test-tenant',
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test-tenant',
            'use_custom_landing_page' => false,
            'wallet_credit_limit' => 0,
        ]);

        $this->otherTenant = Tenant::create([
            'name' => 'Other Tenant',
            'domain' => 'other-tenant',
            'use_custom_landing_page' => false,
            'wallet_credit_limit' => 0,
        ]);

        $this->adminRole = Role::create([
            'name' => 'Administrator',
            'slug' => 'admin',
            'description' => 'Admin role for tests',
            'is_editable' => true,
        ]);

        Role::create([
            'name' => 'Member',
            'slug' => 'member',
            'description' => 'Member role for member provisioning',
            'is_editable' => false,
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role_id' => $this->adminRole->id,
            'email' => 'api-admin@example.com',
            'username' => 'api-admin',
        ]);

        $this->actingAs($this->user);
        $this->withoutMiddleware(CheckPermission::class);
    }

    public function test_users_crud_success_and_failure_paths(): void
    {
        $managerRole = Role::create([
            'name' => 'Manager',
            'slug' => 'manager',
            'description' => 'Manager role',
            'is_editable' => true,
        ]);

        $createResponse = $this->postJson('/api/users', [
            'name' => 'Normal User',
            'email' => 'normal.user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $managerRole->id,
        ]);

        $createResponse->assertCreated();
        $createdUserId = (int) $createResponse->json('data.id');

        $this->assertDatabaseHas('users', [
            'id' => $createdUserId,
            'tenant_id' => $this->tenant->id,
            'email' => 'normal.user@example.com',
            'role_id' => $managerRole->id,
        ]);

        $this->postJson('/api/users', [
            'email' => 'invalid.user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $managerRole->id,
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonFragment(['id' => $createdUserId]);

        $this->getJson('/api/users/'.$createdUserId)
            ->assertOk()
            ->assertJsonPath('data.id', $createdUserId);

        $otherTenantUser = User::factory()->create([
            'tenant_id' => $this->otherTenant->id,
            'role_id' => $managerRole->id,
            'email' => 'foreign.user@example.com',
            'username' => 'foreign-user',
        ]);

        $this->getJson('/api/users/'.$otherTenantUser->id)->assertNotFound();

        $this->putJson('/api/users/'.$createdUserId, [
            'name' => 'Updated User',
            'email' => 'updated.user@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'role_id' => $managerRole->id,
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $createdUserId,
            'name' => 'Updated User',
            'email' => 'updated.user@example.com',
        ]);

        $this->putJson('/api/users/'.$otherTenantUser->id, [
            'name' => 'Nope',
            'email' => 'nope@example.com',
            'role_id' => $managerRole->id,
        ])->assertNotFound();

        $this->deleteJson('/api/users/'.$this->user->id)->assertStatus(422);

        $this->deleteJson('/api/users/'.$createdUserId)->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $createdUserId]);

        $this->deleteJson('/api/users/'.$otherTenantUser->id)->assertNotFound();
    }

    public function test_members_crud_success_and_failure_paths(): void
    {
        $createPayload = $this->memberPayload([
            'username' => 'first_member',
            'email' => 'first.member@example.com',
        ]);

        $createResponse = $this->postJson('/api/members', $createPayload);

        $createResponse->assertCreated();
        $memberId = (int) $createResponse->json('data.id');

        $member = Member::query()->findOrFail($memberId);

        $this->assertDatabaseHas('wallets', [
            'tenant_id' => $this->tenant->id,
            'member_id' => $memberId,
        ]);

        $this->assertNotNull($member->user_id);
        $this->assertDatabaseHas('users', [
            'id' => $member->user_id,
            'tenant_id' => $this->tenant->id,
            'email' => 'first.member@example.com',
            'username' => 'first_member',
        ]);

        $invalidPayload = $createPayload;
        unset($invalidPayload['first_name']);

        $this->postJson('/api/members', $invalidPayload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name']);

        $this->getJson('/api/members')
            ->assertOk()
            ->assertJsonFragment(['id' => $memberId]);

        $this->getJson('/api/members/'.$memberId)
            ->assertOk()
            ->assertJsonPath('data.id', $memberId);

        $otherTenantMember = $this->createMemberRecord($this->otherTenant);

        $this->getJson('/api/members/'.$otherTenantMember->id)->assertNotFound();

        $updatePayload = $this->memberPayload([
            'first_name' => 'Updated',
            'last_name' => 'Member',
            'username' => 'updated_member',
            'email' => 'updated.member@example.com',
            'phone_number' => '0771234567',
            'member_role' => 'Coach',
            'payment_plan' => 'Monthly',
            'price' => 4500,
            'admission_fee' => 600,
            'comment' => 'Updated profile',
        ]);

        $this->putJson('/api/members/'.$memberId, $updatePayload)->assertOk();

        $this->assertDatabaseHas('members', [
            'id' => $memberId,
            'first_name' => 'Updated',
            'username' => 'updated_member',
            'email' => 'updated.member@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $member->user_id,
            'email' => 'updated.member@example.com',
            'username' => 'updated_member',
        ]);

        $invalidUpdatePayload = $updatePayload;
        $invalidUpdatePayload['first_name'] = '';

        $this->putJson('/api/members/'.$memberId, $invalidUpdatePayload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name']);

        $this->putJson('/api/members/'.$otherTenantMember->id, $updatePayload)->assertNotFound();

        $this->deleteJson('/api/members/'.$memberId)->assertOk();
        $this->assertDatabaseMissing('members', ['id' => $memberId]);
        $this->assertDatabaseMissing('users', ['id' => $member->user_id]);

        $this->deleteJson('/api/members/'.$otherTenantMember->id)->assertNotFound();
    }

    public function test_roles_crud_success_and_failure_paths(): void
    {
        $permission = Permission::create([
            'name' => 'View Reports',
            'slug' => 'reports.view',
            'feature' => 'reports',
            'description' => 'Can view reports',
        ]);

        $createResponse = $this->postJson('/api/roles', [
            'name' => 'Trainer',
            'slug' => 'trainer',
            'description' => 'Trainer role',
        ]);

        $createResponse->assertCreated();
        $roleId = (int) $createResponse->json('data.id');

        $this->postJson('/api/roles', [
            'name' => 'Trainer Duplicate',
            'slug' => 'trainer',
            'description' => 'Duplicate slug',
        ])->assertStatus(422)->assertJsonValidationErrors(['slug']);

        $this->getJson('/api/roles')
            ->assertOk()
            ->assertJsonFragment(['id' => $roleId]);

        $this->getJson('/api/roles/'.$roleId)
            ->assertOk()
            ->assertJsonPath('role.id', $roleId);

        $this->putJson('/api/roles/'.$roleId, [
            'name' => 'Senior Trainer',
            'slug' => 'senior-trainer',
            'description' => 'Updated role',
        ])->assertOk();

        $this->assertDatabaseHas('roles', [
            'id' => $roleId,
            'name' => 'Senior Trainer',
            'slug' => 'senior-trainer',
        ]);

        $this->patchJson('/api/roles/'.$roleId.'/permissions', [
            'permissions' => [$permission->id],
        ])->assertOk();

        $this->assertDatabaseHas('role_permission', [
            'role_id' => $roleId,
            'permission_id' => $permission->id,
        ]);

        $lockedRole = Role::create([
            'name' => 'Locked Role',
            'slug' => 'locked-role',
            'description' => 'Predefined role',
            'is_editable' => false,
        ]);

        $this->putJson('/api/roles/'.$lockedRole->id, [
            'name' => 'Cannot Update',
            'slug' => 'cannot-update',
            'description' => 'Should fail',
        ])->assertStatus(422);

        $this->patchJson('/api/roles/'.$lockedRole->id.'/permissions', [
            'permissions' => [$permission->id],
        ])->assertStatus(422);

        $this->getJson('/api/roles/999999')->assertNotFound();
    }

    public function test_company_accounts_crud_success_and_failure_paths(): void
    {
        $createResponse = $this->postJson('/api/company-accounts', [
            'account_name' => 'Main Cash',
            'description' => 'Front desk cash account',
            'initial_balance' => 0,
        ]);

        $createResponse->assertCreated();
        $accountId = (int) $createResponse->json('data.id');

        $this->assertDatabaseHas('company_accounts', [
            'id' => $accountId,
            'tenant_id' => $this->tenant->id,
            'account_name' => 'Main Cash',
        ]);

        $this->postJson('/api/company-accounts', [
            'account_name' => 'Main Cash',
            'description' => 'Duplicate name',
        ])->assertStatus(422)->assertJsonValidationErrors(['account_name']);

        $this->getJson('/api/company-accounts')
            ->assertOk()
            ->assertJsonFragment(['id' => $accountId]);

        $this->getJson('/api/company-accounts/'.$accountId)
            ->assertOk()
            ->assertJsonPath('data.id', $accountId);

        $foreignAccount = CompanyAccount::create([
            'tenant_id' => $this->otherTenant->id,
            'account_name' => 'Foreign Account',
            'description' => 'Other tenant account',
            'current_balance' => 0,
        ]);

        $this->getJson('/api/company-accounts/'.$foreignAccount->id)->assertNotFound();

        $this->putJson('/api/company-accounts/'.$accountId, [
            'account_name' => 'Main Cash Updated',
            'description' => 'Updated description',
        ])->assertOk();

        $this->assertDatabaseHas('company_accounts', [
            'id' => $accountId,
            'account_name' => 'Main Cash Updated',
        ]);

        $this->putJson('/api/company-accounts/'.$foreignAccount->id, [
            'account_name' => 'Should Fail',
            'description' => 'No access',
        ])->assertNotFound();
    }

    public function test_inventory_products_crud_success_and_failure_paths(): void
    {
        $createResponse = $this->postJson('/api/inventory/products', [
            'name' => 'Whey Protein',
            'variations' => [
                ['name' => '1kg'],
                ['name' => '2kg'],
            ],
        ]);

        $createResponse->assertCreated();
        $productId = (int) $createResponse->json('data.id');

        $product = Product::query()->with('variations')->findOrFail($productId);
        $this->assertCount(2, $product->variations);

        $this->postJson('/api/inventory/products', [
            'name' => 'Whey Protein',
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);

        $this->getJson('/api/inventory/products')
            ->assertOk()
            ->assertJsonFragment(['id' => $productId]);

        $this->getJson('/api/inventory/products/'.$productId)
            ->assertOk()
            ->assertJsonPath('data.id', $productId);

        $foreignProduct = Product::create([
            'tenant_id' => $this->otherTenant->id,
            'name' => 'Foreign Product',
        ]);

        $this->getJson('/api/inventory/products/'.$foreignProduct->id)->assertNotFound();

        $existingVariation = $product->variations->first();

        $this->putJson('/api/inventory/products/'.$productId, [
            'name' => 'Whey Protein Plus',
            'variations' => [
                [
                    'id' => $existingVariation->id,
                    'name' => '1.5kg',
                ],
                [
                    'name' => '3kg',
                ],
            ],
        ])->assertOk();

        $product->refresh();
        $product->load('variations');

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'name' => 'Whey Protein Plus',
        ]);
        $this->assertCount(2, $product->variations);
        $this->assertTrue($product->variations->contains(fn (ProductVariation $variation) => $variation->name === '1.5kg'));
        $this->assertTrue($product->variations->contains(fn (ProductVariation $variation) => $variation->name === '3kg'));

        $this->putJson('/api/inventory/products/'.$foreignProduct->id, [
            'name' => 'Should Fail',
        ])->assertNotFound();

        $this->deleteJson('/api/inventory/products/'.$productId)->assertOk();
        $this->assertDatabaseMissing('products', ['id' => $productId]);

        $this->deleteJson('/api/inventory/products/'.$foreignProduct->id)->assertNotFound();
    }

    public function test_inventory_variations_crud_success_and_failure_paths(): void
    {
        $productA = Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Mass Gainer',
        ]);

        $productB = Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Mass Gainer Pro',
        ]);

        $createResponse = $this->postJson('/api/inventory/variations', [
            'product_id' => $productA->id,
            'name' => 'Small',
        ]);

        $createResponse->assertCreated();
        $variationId = (int) $createResponse->json('data.id');

        $this->postJson('/api/inventory/variations', [
            'product_id' => $productA->id,
            'name' => 'Small',
        ])->assertStatus(422);

        $this->getJson('/api/inventory/variations')
            ->assertOk()
            ->assertJsonFragment(['id' => $variationId]);

        $this->putJson('/api/inventory/variations/'.$variationId, [
            'product_id' => $productB->id,
            'name' => 'Large',
        ])->assertOk();

        $this->assertDatabaseHas('product_variations', [
            'id' => $variationId,
            'product_id' => $productB->id,
            'name' => 'Large',
        ]);

        $foreignProduct = Product::create([
            'tenant_id' => $this->otherTenant->id,
            'name' => 'Foreign Product',
        ]);

        $foreignVariation = ProductVariation::create([
            'tenant_id' => $this->otherTenant->id,
            'product_id' => $foreignProduct->id,
            'name' => 'Foreign Variation',
        ]);

        $this->putJson('/api/inventory/variations/'.$foreignVariation->id, [
            'product_id' => $productB->id,
            'name' => 'Should Fail',
        ])->assertNotFound();

        $this->deleteJson('/api/inventory/variations/'.$variationId)->assertOk();
        $this->assertDatabaseMissing('product_variations', ['id' => $variationId]);

        $this->deleteJson('/api/inventory/variations/'.$foreignVariation->id)->assertNotFound();
    }

    public function test_inventory_stock_crud_success_and_failure_paths(): void
    {
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Creatine',
        ]);

        $variation = ProductVariation::create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $product->id,
            'name' => '500g',
        ]);

        $otherProduct = Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'BCAA',
        ]);

        $otherVariation = ProductVariation::create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $otherProduct->id,
            'name' => '300g',
        ]);

        $createResponse = $this->postJson('/api/inventory/stock', [
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'quantity' => 12,
            'manufacturing_date' => '2026-01-01',
            'expiry_date' => '2027-01-01',
            'purchasing_price' => 40,
            'local_selling_price' => 75,
            'foreign_selling_price' => 90,
        ]);

        $createResponse->assertCreated();
        $stockId = (int) $createResponse->json('data.id');

        $this->postJson('/api/inventory/stock', [
            'product_id' => $product->id,
            'product_variation_id' => $otherVariation->id,
            'quantity' => 5,
            'manufacturing_date' => '2026-01-01',
            'expiry_date' => '2027-01-01',
            'purchasing_price' => 30,
            'local_selling_price' => 50,
            'foreign_selling_price' => 60,
        ])->assertStatus(422);

        $this->getJson('/api/inventory/stock')
            ->assertOk()
            ->assertJsonFragment(['id' => $stockId]);

        $this->getJson('/api/inventory/stock/'.$stockId)
            ->assertOk()
            ->assertJsonPath('data.id', $stockId);

        $foreignProduct = Product::create([
            'tenant_id' => $this->otherTenant->id,
            'name' => 'Foreign Stock Product',
        ]);

        $foreignVariation = ProductVariation::create([
            'tenant_id' => $this->otherTenant->id,
            'product_id' => $foreignProduct->id,
            'name' => 'Foreign Stock Variation',
        ]);

        $foreignStock = StockEntry::create([
            'tenant_id' => $this->otherTenant->id,
            'product_id' => $foreignProduct->id,
            'product_variation_id' => $foreignVariation->id,
            'quantity' => 9,
            'manufacturing_date' => '2026-01-01',
            'expiry_date' => '2027-01-01',
            'purchasing_price' => 35,
            'local_selling_price' => 60,
            'foreign_selling_price' => 75,
        ]);

        $this->getJson('/api/inventory/stock/'.$foreignStock->id)->assertNotFound();

        $this->putJson('/api/inventory/stock/'.$stockId, [
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'quantity' => 8,
            'manufacturing_date' => '2026-01-01',
            'expiry_date' => '2027-01-01',
            'purchasing_price' => 42,
            'local_selling_price' => 79,
            'foreign_selling_price' => 95,
        ])->assertOk();

        $this->assertDatabaseHas('stock_entries', [
            'id' => $stockId,
            'quantity' => 8,
        ]);

        $this->putJson('/api/inventory/stock/'.$foreignStock->id, [
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'quantity' => 2,
            'manufacturing_date' => '2026-01-01',
            'expiry_date' => '2027-01-01',
            'purchasing_price' => 10,
            'local_selling_price' => 15,
            'foreign_selling_price' => 20,
        ])->assertNotFound();

        $this->deleteJson('/api/inventory/stock/'.$stockId)->assertOk();
        $this->assertDatabaseMissing('stock_entries', ['id' => $stockId]);

        $this->deleteJson('/api/inventory/stock/'.$foreignStock->id)->assertNotFound();
    }

    public function test_sales_crud_success_and_failure_paths(): void
    {
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pre Workout',
        ]);

        $variation = ProductVariation::create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $product->id,
            'name' => '30 Servings',
        ]);

        $stock = StockEntry::create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'quantity' => 15,
            'manufacturing_date' => now()->subMonth()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'purchasing_price' => 45,
            'local_selling_price' => 80,
            'foreign_selling_price' => 95,
        ]);

        $createResponse = $this->postJson('/api/sales', $this->salePayload($variation->id, 3));

        $createResponse->assertCreated();
        $saleId = (int) $createResponse->json('data.id');

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $saleId,
            'product_variation_id' => $variation->id,
            'quantity' => 3,
        ]);

        $this->postJson('/api/sales', $this->salePayload($variation->id, 999))->assertStatus(422);

        $this->getJson('/api/sales')
            ->assertOk()
            ->assertJsonFragment(['id' => $saleId]);

        $this->getJson('/api/sales/'.$saleId)
            ->assertOk()
            ->assertJsonPath('data.id', $saleId);

        $foreignSale = Sale::create([
            'tenant_id' => $this->otherTenant->id,
            'customer_name' => 'Foreign Customer',
            'customer_type' => 'local',
            'payment_method' => 'cash',
            'total_amount' => 0,
            'paid_amount' => 0,
            'balance' => 0,
        ]);

        $this->getJson('/api/sales/'.$foreignSale->id)->assertNotFound();

        $this->putJson('/api/sales/'.$saleId, $this->salePayload($variation->id, 2, [
            'paid_amount' => 300,
        ]))->assertOk();

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $saleId,
            'product_variation_id' => $variation->id,
            'quantity' => 2,
        ]);

        $this->putJson('/api/sales/'.$foreignSale->id, $this->salePayload($variation->id, 1))->assertNotFound();

        $this->deleteJson('/api/sales/'.$saleId)->assertOk();
        $this->assertSoftDeleted('sales', ['id' => $saleId]);
        $this->assertSame(15, (int) $stock->fresh()->quantity);

        $this->deleteJson('/api/sales/'.$foreignSale->id)->assertNotFound();
    }

    private function memberPayload(array $overrides = []): array
    {
        $seed = strtolower(Str::random(6));

        return array_merge([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'username' => 'member_'.$seed,
            'gender' => 'male',
            'email' => 'member.'.$seed.'@example.com',
            'phone_number' => '0770000000',
            'nic' => '987654321V',
            'date_of_birth' => '1996-05-10',
            'age' => 29,
            'address' => 'Sample address',
            'member_role' => 'Trainee',
            'admission_fee' => 500,
            'payment_plan' => 'Monthly',
            'price' => 3500,
            'joined_date' => '2026-01-15',
            'comment' => 'Test member',
        ], $overrides);
    }

    private function createMemberRecord(Tenant $tenant): Member
    {
        $seed = strtolower(Str::random(6));

        return Member::create([
            'tenant_id' => $tenant->id,
            'member_id' => Member::generateMemberId(),
            'name' => 'Member '.$seed,
            'first_name' => 'Member',
            'last_name' => ucfirst($seed),
            'username' => 'member_'.$seed,
            'gender' => 'male',
            'email' => 'member.'.$seed.'@example.com',
            'phone_number' => '0771111111',
            'date_of_birth' => '1994-02-01',
            'joined_date' => '2025-01-01',
            'is_active' => true,
            'is_verified' => false,
            'payment_plan' => 'Monthly',
            'price' => 3000,
        ]);
    }

    private function salePayload(int $variationId, int $quantity, array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Walk In Customer',
            'customer_type' => 'local',
            'payment_method' => 'cash',
            'reference_number' => 'INV-1001',
            'paid_amount' => 500,
            'items' => [
                [
                    'product_variation_id' => $variationId,
                    'quantity' => $quantity,
                ],
            ],
        ], $overrides);
    }
}

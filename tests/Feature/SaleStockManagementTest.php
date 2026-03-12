<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckPermission;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\StockEntry;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleStockManagementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

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

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'sales.tester@example.com',
            'username' => 'sales-tester',
        ]);

        $this->actingAs($this->user);
        $this->withoutMiddleware(CheckPermission::class);
    }

    public function test_sale_creation_deducts_stock_for_the_selected_variation(): void
    {
        $variation = $this->seedVariationWithStock('Whey Protein', '1kg', 20);

        $response = $this->postJson('/api/sales', $this->salePayload($variation, 5));

        $response->assertCreated();
        $saleId = (int) $response->json('data.id');

        $this->assertSame(15, $this->totalStockForVariation($variation));
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $saleId,
            'product_variation_id' => $variation->id,
            'quantity' => 5,
        ]);
    }

    public function test_sale_update_restores_old_stock_and_deducts_new_stock(): void
    {
        $firstVariation = $this->seedVariationWithStock('Mass Gainer', '2kg', 20);
        $secondVariation = $this->seedVariationWithStock('Mass Gainer Pro', '5kg', 20);

        $createResponse = $this->postJson('/api/sales', $this->salePayload($firstVariation, 5));
        $createResponse->assertCreated();
        $saleId = (int) $createResponse->json('data.id');

        $this->assertSame(15, $this->totalStockForVariation($firstVariation));
        $this->assertSame(20, $this->totalStockForVariation($secondVariation));

        $updateResponse = $this->putJson('/api/sales/'.$saleId, $this->salePayload($secondVariation, 3));

        $updateResponse->assertOk();

        $this->assertSame(20, $this->totalStockForVariation($firstVariation));
        $this->assertSame(17, $this->totalStockForVariation($secondVariation));
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $saleId,
            'product_variation_id' => $secondVariation->id,
            'quantity' => 3,
        ]);
        $this->assertDatabaseMissing('sale_items', [
            'sale_id' => $saleId,
            'product_variation_id' => $firstVariation->id,
            'quantity' => 5,
        ]);
    }

    public function test_sale_deletion_restores_stock_for_sale_items(): void
    {
        $variation = $this->seedVariationWithStock('Creatine', '500g', 20);

        $createResponse = $this->postJson('/api/sales', $this->salePayload($variation, 7));
        $createResponse->assertCreated();
        $saleId = (int) $createResponse->json('data.id');

        $this->assertSame(13, $this->totalStockForVariation($variation));

        $deleteResponse = $this->deleteJson('/api/sales/'.$saleId);

        $deleteResponse->assertOk();
        $this->assertSoftDeleted('sales', ['id' => $saleId]);
        $this->assertSame(20, $this->totalStockForVariation($variation));
    }

    private function seedVariationWithStock(string $productName, string $variationName, int $quantity): ProductVariation
    {
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => $productName,
        ]);

        $variation = ProductVariation::create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $product->id,
            'name' => $variationName,
        ]);

        StockEntry::create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'quantity' => $quantity,
            'manufacturing_date' => now()->subDays(10)->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'purchasing_price' => 60,
            'local_selling_price' => 100,
            'foreign_selling_price' => 120,
        ]);

        return $variation;
    }

    private function totalStockForVariation(ProductVariation $variation): int
    {
        return (int) StockEntry::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('product_variation_id', $variation->id)
            ->sum('quantity');
    }

    private function salePayload(ProductVariation $variation, int $quantity): array
    {
        return [
            'customer_name' => 'Walk In',
            'customer_type' => 'local',
            'payment_method' => 'cash',
            'paid_amount' => 1000,
            'items' => [
                [
                    'product_variation_id' => $variation->id,
                    'quantity' => $quantity,
                ],
            ],
        ];
    }
}

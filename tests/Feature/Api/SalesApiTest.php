<?php

namespace Tests\Feature\Api;

class SalesApiTest extends ApiRouteTestCase
{
    // Inventory impact tests

    public function test_sale_create_deducts_inventory_stock(): void
    {
        $this->actingAsUser(['sales.process']);

        $product   = $this->createProduct(['name' => 'Stock Test Product']);
        $variation = $this->createVariation($product, ['name' => 'Stock Test Variation']);
        $stock     = $this->createStockEntry($product, $variation, [
            'quantity' => 10,
            'local_selling_price' => 100,
            'foreign_selling_price' => 130,
        ]);

        // Assert initial stock
        $this->assertDatabaseHas('stock_entries', ['id' => $stock->id, 'quantity' => 10]);

        $this->postJson('/api/sales', $this->salePayload($variation, [
            'paid_amount' => 300,
            'items' => [['product_variation_id' => $variation->id, 'quantity' => 3]],
        ]))->assertCreated();

        // After create: 10 - 3 = 7
        $this->assertDatabaseHas('stock_entries', ['id' => $stock->id, 'quantity' => 7]);
    }

    public function test_sale_update_adjusts_inventory_for_increase_and_decrease(): void
    {
        $this->actingAsUser(['sales.process']);

        $product   = $this->createProduct(['name' => 'Update Stock Product']);
        $variation = $this->createVariation($product, ['name' => 'Update Stock Variation']);
        $stock     = $this->createStockEntry($product, $variation, [
            'quantity' => 20,
            'local_selling_price' => 100,
            'foreign_selling_price' => 130,
        ]);

        // Create sale with qty 5 => stock 15
        $saleId = (int) $this->postJson('/api/sales', $this->salePayload($variation, [
            'paid_amount' => 500,
            'items' => [['product_variation_id' => $variation->id, 'quantity' => 5]],
        ]))->assertCreated()->json('data.id');

        $this->assertDatabaseHas('stock_entries', ['id' => $stock->id, 'quantity' => 15]);

        // Increase by 2 (5 -> 7) => stock 13
        $this->putJson('/api/sales/'.$saleId, $this->salePayload($variation, [
            'paid_amount' => 700,
            'items' => [['product_variation_id' => $variation->id, 'quantity' => 7]],
        ]))->assertOk();

        $this->assertDatabaseHas('stock_entries', ['id' => $stock->id, 'quantity' => 13]);

        // Decrease by 3 (7 -> 4) => stock 16
        $this->putJson('/api/sales/'.$saleId, $this->salePayload($variation, [
            'paid_amount' => 400,
            'items' => [['product_variation_id' => $variation->id, 'quantity' => 4]],
        ]))->assertOk();

        $this->assertDatabaseHas('stock_entries', ['id' => $stock->id, 'quantity' => 16]);
    }

    public function test_sale_destroy_restores_inventory_stock(): void
    {
        $this->actingAsUser(['sales.process']);

        $product   = $this->createProduct(['name' => 'Delete Stock Product']);
        $variation = $this->createVariation($product, ['name' => 'Delete Stock Variation']);
        $stock     = $this->createStockEntry($product, $variation, [
            'quantity' => 10,
            'local_selling_price' => 100,
            'foreign_selling_price' => 130,
        ]);

        // Create sale: deducts 4
        $saleId = (int) $this->postJson('/api/sales', $this->salePayload($variation, [
            'paid_amount' => 400,
            'items' => [['product_variation_id' => $variation->id, 'quantity' => 4]],
        ]))->assertCreated()->json('data.id');

        // After create: 10 - 4 = 6
        $this->assertDatabaseHas('stock_entries', ['id' => $stock->id, 'quantity' => 6]);

        // Destroy sale should restore sold quantity back to stock
        $this->deleteJson('/api/sales/'.$saleId)->assertOk();

        // Stock restored: 6 + 4 = 10
        $this->assertDatabaseHas('stock_entries', ['id' => $stock->id, 'quantity' => 10]);
    }

    public function test_sale_create_returns_422_when_stock_insufficient(): void
    {
        $this->actingAsUser(['sales.process']);

        $product   = $this->createProduct(['name' => 'Low Stock Product']);
        $variation = $this->createVariation($product, ['name' => 'Low Stock Variation']);
        $this->createStockEntry($product, $variation, [
            'quantity' => 2,
            'local_selling_price' => 100,
            'foreign_selling_price' => 130,
        ]);

        // Try to sell 5 when only 2 in stock
        $this->postJson('/api/sales', $this->salePayload($variation, [
            'paid_amount' => 500,
            'items' => [['product_variation_id' => $variation->id, 'quantity' => 5]],
        ]))->assertStatus(422);
    }

    public function test_sales_meta_route_returns_variations_and_members(): void
    {
        $this->actingAsUser(['sales.process']);

        $product = $this->createProduct();
        $variation = $this->createVariation($product);
        $this->createStockEntry($product, $variation, ['quantity' => 30]);
        $this->createMember();

        $response = $this->getJson('/api/sales/meta');

        $response
            ->assertOk()
            ->assertJsonStructure(['variations', 'members']);
    }

    public function test_sales_member_wallet_route_returns_member_balance(): void
    {
        $this->actingAsUser(['sales.process']);
        $member = $this->createMember(null, ['current_balance' => 450.50]);

        $response = $this->getJson('/api/sales/member-wallet/'.$member->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.member_id', $member->id)
            ->assertJsonPath('data.current_balance', 450.5);
    }

    public function test_sales_routes_cover_index_store_show_update_and_destroy(): void
    {
        $this->actingAsUser(['sales.process']);

        $product = $this->createProduct(['name' => 'Sale Product']);
        $variation = $this->createVariation($product, ['name' => 'Sale Variation']);
        $this->createStockEntry($product, $variation, [
            'quantity' => 50,
            'local_selling_price' => 150,
            'foreign_selling_price' => 200,
        ]);

        $this->getJson('/api/sales')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);

        $storePayload = $this->salePayload($variation, [
            'paid_amount' => 500,
            'items' => [
                ['product_variation_id' => $variation->id, 'quantity' => 2],
            ],
        ]);

        $storeResponse = $this->postJson('/api/sales', $storePayload);

        $storeResponse
            ->assertCreated()
            ->assertJsonPath('message', 'Sale completed successfully.');

        $saleId = (int) $storeResponse->json('data.id');

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $saleId,
            'product_variation_id' => $variation->id,
        ]);

        $this->getJson('/api/sales')
            ->assertOk()
            ->assertJsonFragment(['id' => $saleId]);

        $this->getJson('/api/sales/'.$saleId)
            ->assertOk()
            ->assertJsonPath('data.id', $saleId)
            ->assertJsonPath('data.items.0.product_variation_id', $variation->id);

        $updatePayload = $this->salePayload($variation, [
            'customer_name' => 'Updated Customer',
            'paid_amount' => 200,
            'items' => [
                ['product_variation_id' => $variation->id, 'quantity' => 1],
            ],
        ]);

        $this->putJson('/api/sales/'.$saleId, $updatePayload)
            ->assertOk()
            ->assertJsonPath('message', 'Sale updated successfully.');

        $this->deleteJson('/api/sales/'.$saleId)
            ->assertOk()
            ->assertJsonPath('message', 'Sale deleted successfully.');
    }

    private function salePayload($variation, array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Walk In',
            'customer_member_id' => null,
            'customer_type' => 'local',
            'payment_method' => 'cash',
            'reference_number' => 'REF-TEST-001',
            'paid_amount' => 300,
            'items' => [
                ['product_variation_id' => $variation->id, 'quantity' => 1],
            ],
        ], $overrides);
    }
}

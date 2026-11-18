<?php

namespace Tests\Feature\Api;

use App\Models\SaleItem;

class UntrackedVariationPricingTest extends ApiRouteTestCase
{
    private function salePayload(int $variationId, string $customerType = 'local', array $item = []): array
    {
        return [
            'customer_type' => $customerType,
            'paid_amount' => 0,
            'is_paid' => false,
            'items' => [array_merge(['product_variation_id' => $variationId, 'quantity' => 3], $item)],
        ];
    }

    public function testNestedVariationPricesAreStoredReturnedAndPreservedOnPartialUpdate(): void
    {
        $this->actingAsUser(['inventory.manage']);
        $response = $this->postJson('/api/inventory/products', [
            'name' => 'Service', 'track_stock' => false,
            'variations' => [
                ['name' => 'One', 'untracked_local_price' => 12.5, 'untracked_foreign_price' => 25, 'untracked_unit_cost' => 1.2345],
                ['name' => 'Free', 'untracked_local_price' => 0],
            ],
        ])->assertCreated()->assertJsonPath('data.variations.0.untracked_local_price', 12.5)
            ->assertJsonPath('data.variations.0.untracked_unit_cost', 1.2345)
            ->assertJsonPath('data.variations.1.untracked_local_price', 0);
        $productId = $response->json('data.id');
        $variationId = $response->json('data.variations.0.id');
        $freeId = $response->json('data.variations.1.id');

        $this->putJson('/api/inventory/products/' . $productId, [
            'name' => 'Service', 'variations' => [
                ['id' => $variationId, 'name' => 'Renamed', 'untracked_foreign_price' => null],
                ['id' => $freeId, 'name' => 'Free'],
                ['name' => 'New', 'untracked_foreign_price' => 7.25],
            ],
        ])->assertOk()->assertJsonPath('data.variations.0.untracked_local_price', 12.5)
            ->assertJsonPath('data.variations.0.untracked_foreign_price', null)
            ->assertJsonPath('data.variations.0.untracked_unit_cost', 1.2345)
            ->assertJsonPath('data.variations.1.untracked_local_price', 0)
            ->assertJsonPath('data.variations.2.untracked_foreign_price', 7.25);

        $this->getJson('/api/inventory/products/' . $productId)->assertOk()
            ->assertJsonPath('data.variations.0.untracked_local_price', 12.5)
            ->assertJsonPath('data.variations.0.untracked_unit_cost', 1.2345);
    }

    public function testStandaloneVariationEndpointsValidateAndPreservePricing(): void
    {
        $this->actingAsUser(['inventory.manage']);
        $product = $this->createProduct(['track_stock' => false]);
        $response = $this->postJson('/api/inventory/variations', [
            'product_id' => $product->id, 'name' => 'Standard',
            'untracked_local_price' => 15, 'untracked_foreign_price' => 20, 'untracked_unit_cost' => 3.125,
        ])->assertCreated();
        $id = $response->json('data.id');

        $this->putJson('/api/inventory/variations/' . $id, [
            'product_id' => $product->id, 'name' => 'Changed', 'untracked_unit_cost' => 0,
        ])->assertOk();
        $this->assertDatabaseHas('product_variations', [
            'id' => $id, 'untracked_local_price' => 15, 'untracked_foreign_price' => 20, 'untracked_unit_cost' => 0,
        ]);
        $this->getJson('/api/inventory/variations')->assertOk()->assertJsonFragment([
            'id' => $id, 'untracked_local_price' => 15, 'untracked_foreign_price' => 20, 'untracked_unit_cost' => 0,
        ]);

        foreach (['untracked_local_price', 'untracked_foreign_price', 'untracked_unit_cost'] as $field) {
            $this->postJson('/api/inventory/variations', ['product_id' => $product->id, 'name' => 'Invalid', $field => -1])
                ->assertUnprocessable()->assertJsonValidationErrors($field);
            $this->putJson('/api/inventory/variations/' . $id, ['product_id' => $product->id, 'name' => 'Changed', $field => -1])
                ->assertUnprocessable()->assertJsonValidationErrors($field);
            $this->postJson('/api/inventory/products', ['name' => 'Invalid', 'variations' => [['name' => 'One', $field => -1]]])
                ->assertUnprocessable()->assertJsonValidationErrors('variations.0.' . $field);
            $this->putJson('/api/inventory/products/' . $product->id, [
                'name' => $product->name, 'variations' => [['id' => $id, 'name' => 'Changed', $field => 'invalid']],
            ])->assertUnprocessable()->assertJsonValidationErrors('variations.0.' . $field);
        }
        $this->postJson('/api/inventory/products', ['name' => 'Invalid', 'variations' => [['name' => 'One', 'untracked_local_price' => 1.234]]])
            ->assertUnprocessable()->assertJsonValidationErrors('variations.0.untracked_local_price');
        $this->putJson('/api/inventory/variations/' . $id, ['product_id' => $product->id, 'name' => 'Changed', 'untracked_unit_cost' => 0.00001])
            ->assertUnprocessable()->assertJsonValidationErrors('untracked_unit_cost');
        $this->putJson('/api/inventory/variations/' . $id, ['product_id' => $product->id, 'name' => 'Changed', 'untracked_local_price' => null])->assertOk();
        $this->assertDatabaseHas('product_variations', ['id' => $id, 'untracked_local_price' => null, 'untracked_foreign_price' => 20, 'untracked_unit_cost' => 0]);
    }

    public function testLegacyInventoryUpdatesDoNotResetStoredPrices(): void
    {
        $this->actingAsUser(['inventory.manage']);
        $product = $this->createProduct(['track_stock' => false]);
        $variation = $this->createVariation($product, ['untracked_local_price' => 11, 'untracked_foreign_price' => 19, 'untracked_unit_cost' => 2.5]);
        $this->put('/inventory/products/' . $product->id, [
            'name' => $product->name, 'variations' => [['id' => $variation->id, 'name' => 'Updated']],
        ])->assertRedirect();
        $this->put('/inventory/variations/' . $variation->id, ['product_id' => $product->id, 'name' => 'Updated again'])->assertRedirect();
        $this->assertDatabaseHas('product_variations', [
            'id' => $variation->id, 'name' => 'Updated again', 'untracked_local_price' => 11, 'untracked_foreign_price' => 19, 'untracked_unit_cost' => 2.5,
        ]);
    }

    public function testMetaAndSalesUseVariationPricingWithoutConsumingStock(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create']);
        $product = $this->createProduct(['track_stock' => false]);
        $variation = $this->createVariation($product, ['untracked_local_price' => 12.5, 'untracked_foreign_price' => 25, 'untracked_unit_cost' => 1.2345]);
        $stock = $this->createStockEntry($product, $variation, ['quantity' => 2, 'local_selling_price' => 99, 'foreign_selling_price' => 199]);
        $this->getJson('/api/sales/meta')->assertOk()->assertJsonFragment([
            'id' => $variation->id, 'available_stock' => null, 'prices' => ['local' => 12.5, 'foreign' => 25], 'unit_cost' => 1.2345,
        ]);

        foreach (['local' => 12.5, 'foreign' => 25] as $type => $price) {
            $id = $this->postJson('/api/sales', $this->salePayload($variation->id, $type))->assertCreated()->json('data.id');
            $item = SaleItem::where('sale_id', $id)->firstOrFail();
            $this->assertEquals($price, $item->unit_price);
            $this->assertEquals(1.2345, $item->unit_cost);
            $this->assertEquals(3.7, $item->cost_total);
            $this->assertEquals($price * 3, $item->subtotal);
            $this->assertSame([], $item->stock_allocations);
        }
        $this->assertSame(2, $stock->fresh()->quantity);
    }

    public function testMissingPricesExplicitOverridesAndZeroConfiguration(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create']);
        $product = $this->createProduct(['track_stock' => false]);
        $variation = $this->createVariation($product, ['untracked_local_price' => 0, 'untracked_unit_cost' => 0]);
        $this->getJson('/api/sales/meta')->assertOk()->assertJsonFragment([
            'id' => $variation->id, 'available_stock' => null, 'prices' => ['local' => 0, 'foreign' => null], 'unit_cost' => 0,
        ]);
        $id = $this->postJson('/api/sales', $this->salePayload($variation->id))->assertCreated()->json('data.id');
        $item = SaleItem::where('sale_id', $id)->firstOrFail();
        $this->assertEquals(0, $item->unit_price);
        $this->assertEquals(0, $item->cost_total);
        $this->postJson('/api/sales', $this->salePayload($variation->id, 'foreign'))->assertUnprocessable();
        $this->postJson('/api/sales', $this->salePayload($variation->id, 'foreign', ['unit_price' => null]))->assertUnprocessable();
        $id = $this->postJson('/api/sales', $this->salePayload($variation->id, 'foreign', ['unit_price' => 7, 'unit_cost' => 2]))->assertCreated()->json('data.id');
        $this->assertDatabaseHas('sale_items', ['sale_id' => $id, 'unit_price' => 7, 'unit_cost' => 2, 'cost_total' => 6]);
        $id = $this->postJson('/api/sales', $this->salePayload($variation->id, 'local', ['unit_price' => 5, 'unit_cost' => null]))->assertCreated()->json('data.id');
        $this->assertDatabaseHas('sale_items', ['sale_id' => $id, 'unit_price' => 5, 'unit_cost' => 0]);
    }

    public function testEditingUnchangedSaleRetainsHistoricalPricesAndCosts(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create', 'sales.edit']);
        $product = $this->createProduct(['track_stock' => false]);
        $variation = $this->createVariation($product, ['untracked_local_price' => 10, 'untracked_foreign_price' => 20, 'untracked_unit_cost' => 2]);
        $payload = $this->salePayload($variation->id);
        $id = $this->postJson('/api/sales', $payload)->assertCreated()->json('data.id');
        $original = SaleItem::where('sale_id', $id)->firstOrFail();
        $variation->update(['untracked_local_price' => 30, 'untracked_foreign_price' => 40, 'untracked_unit_cost' => 4]);
        $this->putJson('/api/sales/' . $id, $payload)->assertOk();
        $this->assertDatabaseHas('sale_items', ['id' => $original->id, 'unit_price' => 10, 'unit_cost' => 2, 'cost_total' => 6]);
        $this->putJson('/api/sales/' . $id, $this->salePayload($variation->id, 'foreign'))->assertOk();
        $this->assertDatabaseHas('sale_items', ['sale_id' => $id, 'unit_price' => 40, 'unit_cost' => 4, 'cost_total' => 12]);
    }

    public function testHistoricalMissingCostIsNotBackfilledWhenSaleIsUnchanged(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create', 'sales.edit']);
        $product = $this->createProduct(['track_stock' => false]);
        $variation = $this->createVariation($product, ['untracked_local_price' => 10]);
        $payload = $this->salePayload($variation->id);
        $id = $this->postJson('/api/sales', $payload)->assertCreated()->json('data.id');
        $original = SaleItem::where('sale_id', $id)->firstOrFail();
        $variation->update(['untracked_unit_cost' => 3]);
        $this->putJson('/api/sales/' . $id, $payload)->assertOk();
        $this->assertDatabaseHas('sale_items', ['id' => $original->id, 'unit_price' => 10, 'unit_cost' => null, 'cost_total' => null]);
    }

    public function testTrackedProductsStillUseStockPricesAndCosts(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create']);
        $product = $this->createProduct();
        $variation = $this->createVariation($product, ['untracked_local_price' => 1, 'untracked_foreign_price' => 2, 'untracked_unit_cost' => 3]);
        $stock = $this->createStockEntry($product, $variation, ['quantity' => 5, 'local_selling_price' => 10, 'foreign_selling_price' => 20, 'purchasing_price' => 4]);
        $this->getJson('/api/sales/meta')->assertOk()->assertJsonFragment([
            'id' => $variation->id, 'available_stock' => 5, 'prices' => ['local' => 10, 'foreign' => 20], 'unit_cost' => null,
        ]);
        $id = $this->postJson('/api/sales', $this->salePayload($variation->id, 'foreign'))->assertCreated()->json('data.id');
        $this->assertDatabaseHas('sale_items', ['sale_id' => $id, 'unit_price' => 20, 'unit_cost' => 4, 'cost_total' => 12]);
        $this->assertSame(2, $stock->fresh()->quantity);
    }

    public function testLegacySalesUseConfiguredPriceAndRespectExplicitOverrides(): void
    {
        $this->actingAsUser(['sales.process']);
        $product = $this->createProduct(['track_stock' => false]);
        $variation = $this->createVariation($product, ['untracked_local_price' => 10, 'untracked_foreign_price' => 20, 'untracked_unit_cost' => 2]);
        $this->post('/sales', $this->salePayload($variation->id, 'foreign'))->assertRedirect();
        $this->assertDatabaseHas('sale_items', ['product_variation_id' => $variation->id, 'unit_price' => 20, 'unit_cost' => 2, 'cost_total' => 6]);
        $this->post('/sales', $this->salePayload($variation->id, 'local', ['unit_price' => 5, 'unit_cost' => 1]))->assertRedirect();
        $this->assertDatabaseHas('sale_items', ['product_variation_id' => $variation->id, 'unit_price' => 5, 'unit_cost' => 1, 'cost_total' => 3]);
    }
}

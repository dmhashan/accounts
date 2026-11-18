<?php

namespace Tests\Feature\Api;

use App\Models\SaleItem;

class ProductSaleOptionsTest extends ApiRouteTestCase
{
    private function payload(array $items, array $extra = []): array
    {
        return array_merge([
            'customer_type' => 'local',
            'paid_amount' => 0,
            'is_paid' => false,
            'items' => $items,
        ], $extra);
    }

    public function testProductOptionContractAndPartialUpdates(): void
    {
        $this->actingAsUser(['inventory.manage', 'sales.process']);
        $response = $this->postJson('/api/inventory/products', [
            'name' => 'Options', 'variations' => [['name' => 'One']],
        ])->assertCreated()->assertJsonPath('data.track_stock', true)->assertJsonPath('data.is_active', true);
        $id = $response->json('data.id');
        $variationId = $response->json('data.variations.0.id');

        $this->putJson('/api/inventory/products/' . $id, [
            'name' => 'Options', 'track_stock' => false, 'is_active' => false,
        ])->assertOk()->assertJsonPath('data.track_stock', false)->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.variations.0.id', $variationId);
        $this->getJson('/api/inventory/products/' . $id)->assertOk()->assertJsonPath('data.is_active', false);
        $this->getJson('/api/inventory/products')->assertOk()->assertJsonFragment(['id' => $id, 'track_stock' => false, 'is_active' => false]);
        $this->getJson('/api/sales/meta')->assertOk()->assertJsonFragment([
            'id' => $variationId, 'track_stock' => false, 'is_active' => false, 'available_stock' => null, 'prices' => null,
        ]);
        $this->putJson('/api/inventory/products/' . $id, ['name' => 'Options', 'track_stock' => 'invalid'])->assertUnprocessable();
    }

    public function testMixedSaleTracksCostsAndRestoresExactOriginalBatches(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create', 'sales.edit', 'sales.delete']);
        $counted = $this->createProduct();
        $v = $this->createVariation($counted);
        $a = $this->createStockEntry($counted, $v, ['quantity' => 2, 'purchasing_price' => 10, 'local_selling_price' => 40, 'expiry_date' => now()->addDays(1)]);
        $b = $this->createStockEntry($counted, $v, ['quantity' => 5, 'purchasing_price' => 20, 'local_selling_price' => 50, 'expiry_date' => now()->addDays(2)]);
        $unlimited = $this->createProduct(['track_stock' => false]);
        $u = $this->createVariation($unlimited);
        $unlimitedStock = $this->createStockEntry($unlimited, $u, ['quantity' => 7]);

        $items = [
            ['product_variation_id' => $v->id, 'quantity' => 3],
            ['product_variation_id' => $u->id, 'quantity' => 100, 'unit_price' => 3, 'unit_cost' => 1.25],
        ];
        $id = $this->postJson('/api/sales', $this->payload($items))->assertCreated()->json('data.id');
        $this->assertSame(0, $a->fresh()->quantity);
        $this->assertSame(4, $b->fresh()->quantity);
        $this->assertSame(7, $unlimitedStock->fresh()->quantity);
        $this->assertEquals(40, SaleItem::where('sale_id', $id)->where('product_id', $counted->id)->firstOrFail()->cost_total);
        $this->assertEquals(125, SaleItem::where('sale_id', $id)->where('product_id', $unlimited->id)->firstOrFail()->cost_total);
        $this->assertDatabaseHas('sales', ['id' => $id, 'total_amount' => 420]);

        // The original batches must be restored even if expired or tracking is toggled.
        $a->update(['expiry_date' => now()->subDay()]);
        $counted->update(['track_stock' => false]);
        $unlimited->update(['track_stock' => true]);
        $this->putJson('/api/sales/' . $id, $this->payload($items, ['customer_name' => 'Edited']))->assertOk();
        $this->assertSame(0, $a->fresh()->quantity);
        $this->assertSame(4, $b->fresh()->quantity);
        $this->deleteJson('/api/sales/' . $id)->assertOk();
        $this->assertSame(2, $a->fresh()->quantity);
        $this->assertSame(5, $b->fresh()->quantity);
        $this->assertSame(7, $unlimitedStock->fresh()->quantity);
    }

    public function testStockFreeSalesRequirePriceAndNeverConsumeNewlyAddedStock(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create', 'sales.edit', 'sales.delete']);
        $product = $this->createProduct(['track_stock' => false]);
        $variation = $this->createVariation($product);
        $item = ['product_variation_id' => $variation->id, 'quantity' => 100];
        $this->postJson('/api/sales', $this->payload([$item]))->assertUnprocessable();
        $item['unit_price'] = 5;
        $id = $this->postJson('/api/sales', $this->payload([$item]))->assertCreated()->json('data.id');
        $saleItem = SaleItem::where('sale_id', $id)->firstOrFail();
        $this->assertSame([], $saleItem->stock_allocations);
        $this->assertNull($saleItem->cost_total);
        $stock = $this->createStockEntry($product, $variation, ['quantity' => 3]);
        $product->update(['track_stock' => true]);
        $this->deleteJson('/api/sales/' . $id)->assertOk();
        $this->assertSame(3, $stock->fresh()->quantity);
    }

    public function testDisabledProductCannotBeAddedButExistingLinesRemainEditableOrRemovable(): void
    {
        $this->actingAsUser(['inventory.manage', 'sales.process', 'sales.create', 'sales.edit', 'sales.delete']);
        $product = $this->createProduct();
        $variation = $this->createVariation($product);
        $stock = $this->createStockEntry($product, $variation, ['quantity' => 10]);
        $other = $this->createProduct(['track_stock' => false]);
        $otherVariation = $this->createVariation($other);
        $line = ['product_variation_id' => $variation->id, 'quantity' => 2];
        $otherLine = ['product_variation_id' => $otherVariation->id, 'quantity' => 1, 'unit_price' => 50];
        $id = $this->postJson('/api/sales', $this->payload([$line, $otherLine]))->assertCreated()->json('data.id');
        $itemId = SaleItem::where('sale_id', $id)->where('product_id', $product->id)->value('id');
        $product->update(['is_active' => false]);

        $this->postJson('/api/sales', $this->payload([$line]))->assertUnprocessable();
        $this->putJson('/api/sales/' . $id, $this->payload([$line, $line, $otherLine]))->assertUnprocessable();
        $this->putJson('/api/sales/' . $id, $this->payload([array_merge($line, ['quantity' => 3]), $otherLine]))->assertUnprocessable();
        $this->assertSame(8, $stock->fresh()->quantity);
        $this->putJson('/api/sales/' . $id, $this->payload([$line, $otherLine], ['customer_name' => 'Updated']))->assertOk();
        $this->assertDatabaseHas('sale_items', ['id' => $itemId, 'quantity' => 2]);
        $this->getJson('/api/sales/' . $id)->assertOk()
            ->assertJsonPath('data.items.0.product_variation_id', $variation->id)
            ->assertJsonPath('data.items.0.product_name', $product->name)
            ->assertJsonPath('data.items.0.variation_name', $variation->name);
        $this->deleteJson('/api/inventory/products/' . $product->id)->assertUnprocessable();
        $this->deleteJson('/api/inventory/variations/' . $variation->id)->assertUnprocessable();
        $this->putJson('/api/inventory/products/' . $product->id, ['name' => $product->name, 'variations' => []])->assertUnprocessable();
        $this->putJson('/api/inventory/products/' . $product->id, ['name' => $product->name, 'variations' => [['id' => $variation->id, 'name' => '']]])->assertUnprocessable();
        $this->putJson('/api/sales/' . $id, $this->payload([$otherLine]))->assertOk();
        $this->assertSame(10, $stock->fresh()->quantity);
        $this->deleteJson('/api/sales/' . $id)->assertOk();
    }

    public function testMissingOriginalBatchAbortsAndRollsBackRestoration(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create', 'sales.edit', 'sales.delete']);
        $product = $this->createProduct();
        $variation = $this->createVariation($product);
        $a = $this->createStockEntry($product, $variation, ['quantity' => 1, 'expiry_date' => now()->addDay()]);
        $b = $this->createStockEntry($product, $variation, ['quantity' => 2, 'expiry_date' => now()->addDays(2)]);
        $id = $this->postJson('/api/sales', $this->payload([['product_variation_id' => $variation->id, 'quantity' => 2]]))->assertCreated()->json('data.id');
        $b->delete();
        $this->putJson('/api/sales/' . $id, $this->payload([['product_variation_id' => $variation->id, 'quantity' => 1]]))->assertUnprocessable();
        $this->assertSame(0, $a->fresh()->quantity);
        $this->deleteJson('/api/sales/' . $id)->assertUnprocessable();
        $this->assertSame(0, $a->fresh()->quantity);
        $this->assertDatabaseHas('sales', ['id' => $id, 'deleted_at' => null]);
    }

    public function testRepeatedCountedLinesCannotOversellAndCostUsesActualBatches(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create']);
        $product = $this->createProduct();
        $variation = $this->createVariation($product);
        $a = $this->createStockEntry($product, $variation, ['quantity' => 1, 'purchasing_price' => 10, 'expiry_date' => now()->addDay()]);
        $b = $this->createStockEntry($product, $variation, ['quantity' => 2, 'purchasing_price' => 30, 'expiry_date' => now()->addDays(2)]);
        $line = ['product_variation_id' => $variation->id, 'quantity' => 2];
        $this->postJson('/api/sales', $this->payload([$line, $line]))->assertUnprocessable();
        $this->assertSame(1, $a->fresh()->quantity);
        $this->assertSame(2, $b->fresh()->quantity);
        $id = $this->postJson('/api/sales', $this->payload([$line, ['product_variation_id' => $variation->id, 'quantity' => 1]]))->assertCreated()->json('data.id');
        $costs = SaleItem::where('sale_id', $id)->orderBy('id')->pluck('cost_total');
        $this->assertEquals([40, 30], $costs->all());
    }

    public function testLegacyItemsUseLegacyRestorationEvenAfterTrackingChanges(): void
    {
        $this->actingAsUser(['sales.process', 'sales.delete']);
        $product = $this->createProduct(['track_stock' => false]);
        $variation = $this->createVariation($product);
        $stock = $this->createStockEntry($product, $variation, ['quantity' => 5]);
        $sale = $this->createSale(['is_paid' => false]);
        SaleItem::create(['sale_id' => $sale->id, 'product_id' => $product->id, 'product_variation_id' => $variation->id, 'quantity' => 2, 'unit_price' => 10, 'subtotal' => 20]);
        $this->deleteJson('/api/sales/' . $sale->id)->assertOk();
        $this->assertSame(7, $stock->fresh()->quantity);
    }

    public function testUntrackedMissingCostIsNotEstimatedFromExistingStockInProfitReport(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create', 'reports.view', 'reports.real_profit']);
        $product = $this->createProduct(['track_stock' => false]);
        $variation = $this->createVariation($product);
        $this->createStockEntry($product, $variation, ['quantity' => 5, 'purchasing_price' => 99]);
        $this->postJson('/api/sales', $this->payload([['product_variation_id' => $variation->id, 'quantity' => 1, 'unit_price' => 50]]))->assertCreated();
        $this->getJson('/api/reports/real-profit?month=' . now()->format('Y-m'))->assertOk()
            ->assertJsonPath('summary.missing_cost_items', 1)
            ->assertJsonPath('summary.estimated_cost_items', 0);
    }

    public function testEditAfterTrackingChangesRestoresOnlyStockActuallyConsumed(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create', 'sales.edit', 'sales.delete']);
        $product = $this->createProduct();
        $variation = $this->createVariation($product);
        $stock = $this->createStockEntry($product, $variation, ['quantity' => 10]);
        $line = ['product_variation_id' => $variation->id, 'quantity' => 3];
        $id = $this->postJson('/api/sales', $this->payload([$line]))->assertCreated()->json('data.id');
        $product->update(['track_stock' => false]);
        $this->putJson('/api/sales/' . $id, $this->payload([[...$line, 'quantity' => 20, 'unit_price' => 5]]))->assertOk();
        $this->assertSame(10, $stock->fresh()->quantity);
        $product->update(['track_stock' => true]);
        $this->putJson('/api/sales/' . $id, $this->payload([[...$line, 'quantity' => 4]]))->assertOk();
        $this->assertSame(6, $stock->fresh()->quantity);
        $this->deleteJson('/api/sales/' . $id)->assertOk();
        $this->assertSame(10, $stock->fresh()->quantity);
    }

    public function testPaidStockFreeSaleRecordsFinancialTransaction(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create']);
        $product = $this->createProduct(['track_stock' => false]);
        $variation = $this->createVariation($product);
        $account = $this->createCompanyAccount();
        $id = $this->postJson('/api/sales', $this->payload([
            ['product_variation_id' => $variation->id, 'quantity' => 2, 'unit_price' => 75, 'unit_cost' => 10],
        ], ['is_paid' => true, 'account_id' => $account->id]))->assertCreated()->json('data.id');
        $this->assertDatabaseHas('company_account_transactions', ['model_name' => 'sale', 'reference_id' => $id, 'amount' => 150]);
        $this->assertDatabaseHas('sale_items', ['sale_id' => $id, 'cost_total' => 20]);
        $this->deleteJson('/api/sales/' . $id)->assertForbidden();
    }

    public function testLegacyWebSalesCannotBypassInactiveProductCheck(): void
    {
        $this->actingAsUser(['sales.process']);
        $product = $this->createProduct(['is_active' => false]);
        $variation = $this->createVariation($product);
        $this->createStockEntry($product, $variation);
        $this->post('/sales', $this->payload([['product_variation_id' => $variation->id, 'quantity' => 1]]))->assertUnprocessable();
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\SaleItem;
use App\Models\StockEntry;

class StockAllocationGuardTest extends ApiRouteTestCase
{
    private function stockPayload(StockEntry $stock, array $overrides = []): array
    {
        return array_merge([
            'product_id' => $stock->product_id,
            'product_variation_id' => $stock->product_variation_id,
            'quantity' => $stock->quantity,
            'display_quantity' => $stock->display_quantity,
            'manufacturing_date' => $stock->manufacturing_date?->toDateString(),
            'expiry_date' => $stock->expiry_date?->toDateString(),
            'purchasing_price' => $stock->purchasing_price,
            'local_selling_price' => $stock->local_selling_price,
            'foreign_selling_price' => $stock->foreign_selling_price,
        ], $overrides);
    }

    private function salePayload(array $items): array
    {
        return ['customer_type' => 'local', 'paid_amount' => 0, 'is_paid' => false, 'items' => $items];
    }

    public function testAllocatedBatchesCannotBeDeletedOrReassignedAndSaleCanStillBeEditedAndDeleted(): void
    {
        $this->actingAsUser(['inventory.stock', 'sales.process', 'sales.create', 'sales.edit', 'sales.delete']);
        $product = $this->createProduct();
        $variation = $this->createVariation($product);
        $otherVariation = $this->createVariation($product);
        $otherProduct = $this->createProduct();
        $otherProductVariation = $this->createVariation($otherProduct);
        $a = $this->createStockEntry($product, $variation, ['quantity' => 2, 'expiry_date' => now()->addDay()]);
        $b = $this->createStockEntry($product, $variation, ['quantity' => 5, 'expiry_date' => now()->addDays(2)]);
        $unused = $this->createStockEntry($product, $variation, ['quantity' => 4, 'expiry_date' => now()->addDays(3)]);
        $line = ['product_variation_id' => $variation->id, 'quantity' => 3];
        $saleId = $this->postJson('/api/sales', $this->salePayload([$line]))->assertCreated()->json('data.id');

        foreach ([$a, $b] as $entry) {
            $this->deleteJson('/api/inventory/stock/' . $entry->id)->assertUnprocessable()
                ->assertJsonPath('message', 'Stock entries referenced by an unpaid sale cannot be deleted.');
        }
        $this->putJson('/api/inventory/stock/' . $b->id, $this->stockPayload($b->fresh(), [
            'product_variation_id' => $otherVariation->id,
        ]))->assertUnprocessable()->assertJsonPath('message', 'Stock entries referenced by an unpaid sale cannot be reassigned.');
        $this->putJson('/api/inventory/stock/' . $b->id, $this->stockPayload($b->fresh(), [
            'product_id' => $otherProduct->id, 'product_variation_id' => $otherProductVariation->id,
        ]))->assertUnprocessable();
        $this->assertSame($variation->id, $b->fresh()->product_variation_id);
        $this->assertSame(4, $b->fresh()->quantity);

        // Ordinary edits and unrelated batches remain available, including expired allocated batches.
        $this->putJson('/api/inventory/stock/' . $a->id, $this->stockPayload($a->fresh(), [
            'expiry_date' => now()->subDay()->toDateString(), 'manufacturing_date' => now()->subDays(5)->toDateString(),
        ]))->assertOk();
        $this->deleteJson('/api/inventory/stock/' . $a->id)->assertUnprocessable();
        $this->deleteJson('/api/inventory/stock/' . $unused->id)->assertOk();
        $this->putJson('/api/sales/' . $saleId, $this->salePayload([$line]))->assertOk();
        $this->deleteJson('/api/sales/' . $saleId)->assertOk();
        $this->assertSame(2, $a->fresh()->quantity);
        $this->assertSame(5, $b->fresh()->quantity);
        $this->deleteJson('/api/inventory/stock/' . $a->id)->assertOk();
        $this->deleteJson('/api/inventory/stock/' . $b->id)->assertOk();
    }

    public function testPaidAndSoftDeletedSalesDoNotBlockStockButAnyRemainingUnpaidSaleDoes(): void
    {
        $this->actingAsUser(['inventory.stock', 'sales.process', 'sales.create', 'sales.edit', 'sales.delete']);
        $product = $this->createProduct();
        $variation = $this->createVariation($product);
        $stock = $this->createStockEntry($product, $variation, ['quantity' => 10]);
        $line = ['product_variation_id' => $variation->id, 'quantity' => 1];
        $first = $this->postJson('/api/sales', $this->salePayload([$line]))->assertCreated()->json('data.id');
        $second = $this->postJson('/api/sales', $this->salePayload([$line]))->assertCreated()->json('data.id');
        $account = $this->createCompanyAccount();
        $this->postJson('/api/sales/' . $first . '/mark-as-paid', ['account_id' => $account->id])->assertOk();
        $this->deleteJson('/api/inventory/stock/' . $stock->id)->assertUnprocessable();
        $this->deleteJson('/api/sales/' . $second)->assertOk();
        $this->deleteJson('/api/inventory/stock/' . $stock->id)->assertOk();
    }

    public function testEditingASaleReleasesBatchesNoLongerAllocated(): void
    {
        $this->actingAsUser(['inventory.stock', 'sales.process', 'sales.create', 'sales.edit', 'sales.delete']);
        $product = $this->createProduct();
        $variation = $this->createVariation($product);
        $first = $this->createStockEntry($product, $variation, ['quantity' => 1, 'expiry_date' => now()->addDay()]);
        $second = $this->createStockEntry($product, $variation, ['quantity' => 5, 'expiry_date' => now()->addDays(2)]);
        $line = ['product_variation_id' => $variation->id, 'quantity' => 2];
        $saleId = $this->postJson('/api/sales', $this->salePayload([$line]))->assertCreated()->json('data.id');
        $first->update(['expiry_date' => now()->subDay()]);

        $this->putJson('/api/sales/' . $saleId, $this->salePayload([[...$line, 'quantity' => 1]]))->assertOk();
        $this->assertSame(1, $first->fresh()->quantity);
        $this->deleteJson('/api/inventory/stock/' . $first->id)->assertOk();
        $this->deleteJson('/api/inventory/stock/' . $second->id)->assertUnprocessable();
        $this->deleteJson('/api/sales/' . $saleId)->assertOk();
        $this->assertSame(5, $second->fresh()->quantity);
    }

    public function testAllocationMatchingAcceptsStringIdsWithoutMatchingOtherNumbers(): void
    {
        $this->actingAsUser(['inventory.stock']);
        $product = $this->createProduct();
        $variation = $this->createVariation($product);
        $referenced = $this->createStockEntry($product, $variation);
        $unreferenced = $this->createStockEntry($product, $variation);
        $sale = $this->createSale(['is_paid' => false]);
        SaleItem::create([
            'sale_id' => $sale->id, 'product_id' => $product->id, 'product_variation_id' => $variation->id,
            'quantity' => 1, 'unit_price' => 10, 'subtotal' => 10,
            'stock_allocations' => [['stock_entry_id' => (string) $referenced->id, 'quantity' => $unreferenced->id]],
        ]);

        $this->deleteJson('/api/inventory/stock/' . $unreferenced->id)->assertOk();
        $this->deleteJson('/api/inventory/stock/' . $referenced->id)->assertUnprocessable();
    }

    public function testLegacyAndEmptyAllocationsDoNotImplyABatchReference(): void
    {
        $this->actingAsUser(['inventory.stock', 'sales.process', 'sales.delete']);
        $product = $this->createProduct();
        $variation = $this->createVariation($product);
        $first = $this->createStockEntry($product, $variation, ['quantity' => 5]);
        $second = $this->createStockEntry($product, $variation, ['quantity' => 5]);
        $sale = $this->createSale(['is_paid' => false]);
        SaleItem::create(['sale_id' => $sale->id, 'product_id' => $product->id, 'product_variation_id' => $variation->id, 'quantity' => 2, 'unit_price' => 10, 'subtotal' => 20]);
        SaleItem::create(['sale_id' => $sale->id, 'product_id' => $product->id, 'product_variation_id' => $variation->id, 'quantity' => 1, 'unit_price' => 10, 'subtotal' => 10, 'stock_allocations' => []]);
        $this->deleteJson('/api/inventory/stock/' . $first->id)->assertOk();
        $this->deleteJson('/api/sales/' . $sale->id)->assertOk();
        $this->assertSame(7, $second->fresh()->quantity);
    }

    public function testLegacyStockRoutesCannotBypassTheGuard(): void
    {
        $this->actingAsUser(['inventory.manage', 'inventory.stock', 'sales.process', 'sales.create']);
        $product = $this->createProduct();
        $variation = $this->createVariation($product);
        $other = $this->createVariation($product);
        $stock = $this->createStockEntry($product, $variation, ['quantity' => 5]);
        $this->postJson('/api/sales', $this->salePayload([['product_variation_id' => $variation->id, 'quantity' => 1]]))->assertCreated();
        $this->put('/inventory/stock/' . $stock->id, $this->stockPayload($stock->fresh(), ['product_variation_id' => $other->id]))
            ->assertSessionHasErrors('product_variation_id');
        $this->delete('/inventory/stock/' . $stock->id)->assertUnprocessable();
        $this->assertSame($variation->id, $stock->fresh()->product_variation_id);
    }
}

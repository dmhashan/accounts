<?php

namespace Tests\Feature\Api;

use App\Models\CompanyAccountTransaction;
use App\Models\SaleItem;

class SalesApiTest extends ApiRouteTestCase
{
    // Inventory impact tests

    public function test_sale_create_deducts_inventory_stock(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create']);

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
        $this->actingAsUser(['sales.process', 'sales.create', 'sales.edit']);

        $product   = $this->createProduct(['name' => 'Update Stock Product']);
        $variation = $this->createVariation($product, ['name' => 'Update Stock Variation']);
        $stock     = $this->createStockEntry($product, $variation, [
            'quantity' => 20,
            'local_selling_price' => 100,
            'foreign_selling_price' => 130,
        ]);

        // Create sale with qty 5 => stock 15
        $saleId = (int) $this->postJson('/api/sales', $this->salePayload($variation, [
            'is_paid' => false,
            'paid_amount' => 0,
            'items' => [['product_variation_id' => $variation->id, 'quantity' => 5]],
        ]))->assertCreated()->json('data.id');

        $this->assertDatabaseHas('stock_entries', ['id' => $stock->id, 'quantity' => 15]);

        // Increase by 2 (5 -> 7) => stock 13
        $this->putJson('/api/sales/'.$saleId, $this->salePayload($variation, [
            'is_paid' => false,
            'paid_amount' => 700,
            'items' => [['product_variation_id' => $variation->id, 'quantity' => 7]],
        ]))->assertOk();

        $this->assertDatabaseHas('stock_entries', ['id' => $stock->id, 'quantity' => 13]);

        // Decrease by 3 (7 -> 4) => stock 16
        $this->putJson('/api/sales/'.$saleId, $this->salePayload($variation, [
            'is_paid' => false,
            'paid_amount' => 0,
            'items' => [['product_variation_id' => $variation->id, 'quantity' => 4]],
        ]))->assertOk();

        $this->assertDatabaseHas('stock_entries', ['id' => $stock->id, 'quantity' => 16]);
    }

    public function test_sale_destroy_restores_inventory_stock(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create', 'sales.delete']);

        $product   = $this->createProduct(['name' => 'Delete Stock Product']);
        $variation = $this->createVariation($product, ['name' => 'Delete Stock Variation']);
        $stock     = $this->createStockEntry($product, $variation, [
            'quantity' => 10,
            'local_selling_price' => 100,
            'foreign_selling_price' => 130,
        ]);

        // Create sale: deducts 4
        $saleId = (int) $this->postJson('/api/sales', $this->salePayload($variation, [
            'is_paid' => false,
            'paid_amount' => 0,
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
        $this->actingAsUser(['sales.process', 'sales.create']);

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
            ->assertJsonStructure(['variations', 'members', 'accounts']);
    }

    public function test_sale_create_pay_now_sets_account_and_paid_fields(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create']);

        $product = $this->createProduct(['name' => 'Pay Now Product']);
        $variation = $this->createVariation($product, ['name' => 'Pay Now Variation']);
        $this->createStockEntry($product, $variation, [
            'quantity' => 20,
            'local_selling_price' => 100,
            'foreign_selling_price' => 130,
        ]);

        $account = $this->createCompanyAccount(['name' => 'Cash Drawer']);

        $saleId = (int) $this->postJson('/api/sales', $this->salePayload($variation, [
            'is_paid' => true,
            'account_id' => $account->id,
            'paid_amount' => 0,
            'items' => [
                ['product_variation_id' => $variation->id, 'quantity' => 3],
            ],
        ]))->assertCreated()->json('data.id');

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'account_id' => $account->id,
            'is_paid' => true,
            'total_amount' => 300,
            'paid_amount' => 300,
            'balance' => 0,
        ]);

        $this->assertDatabaseHas('company_account_transactions', [
            'sale_id' => $saleId,
            'company_account_id' => $account->id,
            'type' => 'sale_payment',
            'amount' => 300,
        ]);
    }

    public function test_sale_create_save_sets_unpaid_fields(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create']);

        $product = $this->createProduct(['name' => 'Save Product']);
        $variation = $this->createVariation($product, ['name' => 'Save Variation']);
        $this->createStockEntry($product, $variation, [
            'quantity' => 20,
            'local_selling_price' => 120,
            'foreign_selling_price' => 140,
        ]);

        $saleId = (int) $this->postJson('/api/sales', $this->salePayload($variation, [
            'is_paid' => false,
            'account_id' => null,
            'paid_amount' => 999,
            'items' => [
                ['product_variation_id' => $variation->id, 'quantity' => 2],
            ],
        ]))->assertCreated()->json('data.id');

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'account_id' => null,
            'is_paid' => false,
            'total_amount' => 240,
            'paid_amount' => 0,
            'balance' => -240,
        ]);
    }

    public function test_sales_meta_variations_are_sorted_by_recent_sales_count_desc(): void
    {
        $this->actingAsUser(['sales.process']);

        $topProduct = $this->createProduct(['name' => 'Top Product']);
        $topVariation = $this->createVariation($topProduct, ['name' => 'Top Variation']);
        $this->createStockEntry($topProduct, $topVariation, ['quantity' => 50]);

        $middleProduct = $this->createProduct(['name' => 'Middle Product']);
        $middleVariation = $this->createVariation($middleProduct, ['name' => 'Middle Variation']);
        $this->createStockEntry($middleProduct, $middleVariation, ['quantity' => 50]);

        $zeroProduct = $this->createProduct(['name' => 'Zero Product']);
        $zeroVariation = $this->createVariation($zeroProduct, ['name' => 'Zero Variation']);
        $this->createStockEntry($zeroProduct, $zeroVariation, ['quantity' => 50]);

        $recentSaleA = $this->createSale();
        $recentSaleA->forceFill([
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ])->saveQuietly();

        SaleItem::create([
            'sale_id' => $recentSaleA->id,
            'product_id' => $topProduct->id,
            'product_variation_id' => $topVariation->id,
            'quantity' => 5,
            'unit_price' => 100,
            'subtotal' => 500,
        ]);

        $recentSaleB = $this->createSale();
        $recentSaleB->forceFill([
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ])->saveQuietly();

        SaleItem::create([
            'sale_id' => $recentSaleB->id,
            'product_id' => $middleProduct->id,
            'product_variation_id' => $middleVariation->id,
            'quantity' => 3,
            'unit_price' => 100,
            'subtotal' => 300,
        ]);

        $oldSale = $this->createSale();
        $oldSale->forceFill([
            'created_at' => now()->subDays(8),
            'updated_at' => now()->subDays(8),
        ])->saveQuietly();

        SaleItem::create([
            'sale_id' => $oldSale->id,
            'product_id' => $middleProduct->id,
            'product_variation_id' => $middleVariation->id,
            'quantity' => 100,
            'unit_price' => 100,
            'subtotal' => 10000,
        ]);

        $response = $this->getJson('/api/sales/meta');

        $response->assertOk();

        $this->assertSame(
            [$topVariation->id, $middleVariation->id, $zeroVariation->id],
            collect($response->json('variations'))->pluck('id')->all()
        );
    }

    public function test_sales_member_wallet_route_returns_member_balance(): void
    {
        $this->actingAsUser(['sales.create']);
        $member = $this->createMember(null, ['current_balance' => 450.50]);

        $response = $this->getJson('/api/sales/member-wallet/'.$member->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.member_id', $member->id)
            ->assertJsonPath('data.current_balance', 450.5);
    }

    public function test_sales_index_can_filter_outstanding_and_paid(): void
    {
        $this->actingAsUser(['sales.process']);

        $outstandingSale = $this->createSale([
            'customer_name' => 'Outstanding Customer',
            'is_paid' => false,
            'paid_amount' => 0,
            'balance' => -300,
        ]);

        $paidSale = $this->createSale([
            'customer_name' => 'Paid Customer',
            'is_paid' => true,
            'paid_amount' => 300,
            'balance' => 0,
        ]);

        $this->getJson('/api/sales?status=outstanding')
            ->assertOk()
            ->assertJsonFragment(['id' => $outstandingSale->id])
            ->assertJsonMissing(['id' => $paidSale->id]);

        $this->getJson('/api/sales?status=paid')
            ->assertOk()
            ->assertJsonFragment(['id' => $paidSale->id])
            ->assertJsonMissing(['id' => $outstandingSale->id]);
    }

    public function test_outstanding_sale_can_be_marked_as_paid_with_account(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create', 'sales.edit']);

        $product = $this->createProduct(['name' => 'Outstanding Product']);
        $variation = $this->createVariation($product, ['name' => 'Outstanding Variation']);
        $this->createStockEntry($product, $variation, [
            'quantity' => 10,
            'local_selling_price' => 100,
            'foreign_selling_price' => 130,
        ]);

        $account = $this->createCompanyAccount(['name' => 'Main Cash']);

        $saleId = (int) $this->postJson('/api/sales', $this->salePayload($variation, [
            'is_paid' => false,
            'paid_amount' => 0,
        ]))->assertCreated()->json('data.id');

        $this->postJson('/api/sales/'.$saleId.'/mark-as-paid', [
            'account_id' => $account->id,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Sale marked as paid successfully.')
            ->assertJsonPath('data.is_paid', true)
            ->assertJsonPath('data.account_id', $account->id);

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'account_id' => $account->id,
            'is_paid' => true,
            'paid_amount' => 100,
            'balance' => 0,
        ]);

        $this->assertDatabaseHas('company_account_transactions', [
            'sale_id' => $saleId,
            'company_account_id' => $account->id,
            'type' => 'sale_payment',
            'amount' => 100,
        ]);
    }

    public function test_paid_sale_updates_company_account_balance(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create', 'accounts.manage']);

        $product = $this->createProduct(['name' => 'Balance Product']);
        $variation = $this->createVariation($product, ['name' => 'Balance Variation']);
        $this->createStockEntry($product, $variation, [
            'quantity' => 10,
            'local_selling_price' => 125,
            'foreign_selling_price' => 150,
        ]);

        $account = $this->createCompanyAccount([
            'name' => 'Balance Account',
            'opening_balance' => 500,
        ]);

        $saleId = (int) $this->postJson('/api/sales', $this->salePayload($variation, [
            'is_paid' => true,
            'account_id' => $account->id,
            'paid_amount' => 0,
        ]))->assertCreated()->json('data.id');

        $this->assertDatabaseHas('company_account_transactions', [
            'sale_id' => $saleId,
            'company_account_id' => $account->id,
            'amount' => 125,
        ]);

        $this->getJson('/api/accounts/'.$account->id)
            ->assertOk()
            ->assertJsonPath('data.current_balance', 625);
    }

    public function test_paid_sale_cannot_be_marked_as_paid_again(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create', 'sales.edit']);

        $product = $this->createProduct(['name' => 'Already Paid Product']);
        $variation = $this->createVariation($product, ['name' => 'Already Paid Variation']);
        $this->createStockEntry($product, $variation, [
            'quantity' => 10,
            'local_selling_price' => 100,
            'foreign_selling_price' => 130,
        ]);

        $account = $this->createCompanyAccount(['name' => 'Paid Account']);

        $saleId = (int) $this->postJson('/api/sales', $this->salePayload($variation, [
            'is_paid' => true,
            'account_id' => $account->id,
            'paid_amount' => 0,
        ]))->assertCreated()->json('data.id');

        $this->postJson('/api/sales/'.$saleId.'/mark-as-paid', [
            'account_id' => $account->id,
        ])
            ->assertStatus(422)
            ->assertSeeText('Sale is already paid.');
    }

    public function test_sales_routes_cover_index_store_show_update_and_destroy(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create', 'sales.edit', 'sales.delete']);

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
            'is_paid' => false,
            'paid_amount' => 0,
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
            'is_paid' => false,
            'paid_amount' => 0,
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

    public function test_sales_create_route_requires_create_permission(): void
    {
        $this->actingAsUser(['sales.process']);

        $product = $this->createProduct();
        $variation = $this->createVariation($product);
        $this->createStockEntry($product, $variation, ['quantity' => 10]);

        $this->postJson('/api/sales', $this->salePayload($variation))
            ->assertForbidden();
    }

    public function test_sales_update_route_requires_edit_permission(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create']);

        $product = $this->createProduct();
        $variation = $this->createVariation($product);
        $this->createStockEntry($product, $variation, ['quantity' => 10]);

        $saleId = (int) $this->postJson('/api/sales', $this->salePayload($variation))
            ->assertCreated()
            ->json('data.id');

        $this->putJson('/api/sales/'.$saleId, $this->salePayload($variation, [
            'items' => [['product_variation_id' => $variation->id, 'quantity' => 2]],
        ]))->assertForbidden();
    }

    public function test_sales_delete_route_requires_delete_permission(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create']);

        $product = $this->createProduct();
        $variation = $this->createVariation($product);
        $this->createStockEntry($product, $variation, ['quantity' => 10]);

        $saleId = (int) $this->postJson('/api/sales', $this->salePayload($variation))
            ->assertCreated()
            ->json('data.id');

        $this->deleteJson('/api/sales/'.$saleId)
            ->assertForbidden();
    }

    public function test_paid_sale_cannot_be_updated(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create', 'sales.edit']);

        $product = $this->createProduct(['name' => 'Locked Product']);
        $variation = $this->createVariation($product, ['name' => 'Locked Variation']);
        $this->createStockEntry($product, $variation, [
            'quantity' => 10,
            'local_selling_price' => 100,
            'foreign_selling_price' => 130,
        ]);

        $account = $this->createCompanyAccount();

        $saleId = (int) $this->postJson('/api/sales', $this->salePayload($variation, [
            'is_paid' => true,
            'account_id' => $account->id,
            'paid_amount' => 0,
        ]))->assertCreated()->json('data.id');

        $this->putJson('/api/sales/'.$saleId, $this->salePayload($variation, [
            'customer_name' => 'Should Not Update',
            'items' => [['product_variation_id' => $variation->id, 'quantity' => 2]],
        ]))
            ->assertStatus(422)
            ->assertSeeText('Paid sales cannot be edited or deleted.');

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'customer_name' => 'Walk In',
        ]);
    }

    public function test_paid_sale_cannot_be_deleted(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create', 'sales.delete']);

        $product = $this->createProduct(['name' => 'Locked Delete Product']);
        $variation = $this->createVariation($product, ['name' => 'Locked Delete Variation']);
        $this->createStockEntry($product, $variation, [
            'quantity' => 10,
            'local_selling_price' => 100,
            'foreign_selling_price' => 130,
        ]);

        $account = $this->createCompanyAccount();

        $saleId = (int) $this->postJson('/api/sales', $this->salePayload($variation, [
            'is_paid' => true,
            'account_id' => $account->id,
            'paid_amount' => 0,
        ]))->assertCreated()->json('data.id');

        $this->deleteJson('/api/sales/'.$saleId)
            ->assertStatus(422)
            ->assertSeeText('Paid sales cannot be edited or deleted.');

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
        ]);
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

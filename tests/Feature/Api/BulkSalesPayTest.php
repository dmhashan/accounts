<?php

namespace Tests\Feature\Api;

class BulkSalesPayTest extends ApiRouteTestCase
{
    public function testBulkMarkAsPaidWithAccount(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create', 'sales.edit']);

        $product = $this->createProduct(['name' => 'Bulk Product']);
        $variation = $this->createVariation($product, ['name' => 'Bulk Variation']);
        $this->createStockEntry($product, $variation, [
            'quantity' => 20,
            'local_selling_price' => 100,
            'foreign_selling_price' => 130,
        ]);

        $account = $this->createCompanyAccount(['name' => 'Main Cash']);

        $saleId1 = (int) $this->postJson('/api/sales', $this->salePayload($variation, [
            'is_paid' => false,
            'paid_amount' => 0,
        ]))->assertCreated()->json('data.id');

        $saleId2 = (int) $this->postJson('/api/sales', $this->salePayload($variation, [
            'is_paid' => false,
            'paid_amount' => 0,
        ]))->assertCreated()->json('data.id');

        $this->postJson('/api/sales/bulk-mark-as-paid', [
            'sale_ids' => [$saleId1, $saleId2],
            'account_id' => $account->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.paid_count', 2);

        $this->assertDatabaseHas('sales', [
            'id' => $saleId1,
            'is_paid' => true,
            'account_id' => $account->id,
        ]);

        $this->assertDatabaseHas('sales', [
            'id' => $saleId2,
            'is_paid' => true,
            'account_id' => $account->id,
        ]);
    }

    public function testBulkMarkAsPaidRequiresEditPermission(): void
    {
        $this->actingAsUser(['sales.process', 'sales.create']);

        $product = $this->createProduct();
        $variation = $this->createVariation($product);
        $this->createStockEntry($product, $variation, ['quantity' => 10]);

        $saleId = (int) $this->postJson('/api/sales', $this->salePayload($variation, [
            'is_paid' => false,
            'paid_amount' => 0,
        ]))->assertCreated()->json('data.id');

        $this->postJson('/api/sales/bulk-mark-as-paid', [
            'sale_ids' => [$saleId],
            'payment_method' => 'cash',
        ])->assertForbidden();
    }

    public function testBulkMarkAsPaidValidation(): void
    {
        $this->actingAsUser(['sales.edit']);

        $this->postJson('/api/sales/bulk-mark-as-paid', [
            'sale_ids' => [],
        ])->assertStatus(422);
    }

    private function salePayload($variation, array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Walk In',
            'customer_member_id' => null,
            'customer_type' => 'local',
            'payment_method' => 'cash',
            'reference_number' => 'REF-BULK-001',
            'paid_amount' => 100,
            'items' => [
                ['product_variation_id' => $variation->id, 'quantity' => 1],
            ],
        ], $overrides);
    }
}

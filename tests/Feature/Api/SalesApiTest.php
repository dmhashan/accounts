<?php

namespace Tests\Feature\Api;

class SalesApiTest extends ApiRouteTestCase
{
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

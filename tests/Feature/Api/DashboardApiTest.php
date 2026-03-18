<?php

namespace Tests\Feature\Api;

class DashboardApiTest extends ApiRouteTestCase
{
    public function test_dashboard_overview_route_returns_summary_payload(): void
    {
        $this->actingAsUser([
            'inventory.manage',
            'sales.process',
        ]);

        $product = $this->createProduct();
        $variation = $this->createVariation($product, ['name' => 'Vanilla']);
        $this->createStockEntry($product, $variation, ['quantity' => 8]);

        $this->createSale([
            'total_amount' => 250,
            'paid_amount' => 250,
            'balance' => 0,
        ]);

        $response = $this->getJson('/api/dashboard/overview');

        $response
            ->assertOk()
            ->assertJsonPath('tenant.id', $this->tenant->id)
            ->assertJsonPath('stock_summary.can_view', true)
            ->assertJsonPath('daily_sales_summary.can_view', true)
            ->assertJsonPath('daily_sales_summary.transactions', 1);
    }
}

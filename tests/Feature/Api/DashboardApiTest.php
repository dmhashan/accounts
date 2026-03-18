<?php

namespace Tests\Feature\Api;

use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Carbon;

class DashboardApiTest extends ApiRouteTestCase
{
    public function test_dashboard_overview_route_returns_gross_sales_summary_payload(): void
    {
        $this->actingAsUser([
            'inventory.manage',
            'sales.process',
        ]);

        $product = $this->createProduct(['name' => 'Protein Powder']);
        $variation = $this->createVariation($product, ['name' => 'Vanilla']);
        $this->createStockEntry($product, $variation, ['quantity' => 12]);

        $todaySale = $this->createSale([
            'customer_name' => 'Today Customer',
            'total_amount' => 250,
            'paid_amount' => 250,
            'balance' => 0,
        ]);
        $todaySale->forceFill([
            'created_at' => now()->startOfDay()->addHours(10),
            'updated_at' => now()->startOfDay()->addHours(10),
        ])->saveQuietly();

        $yesterdaySale = $this->createSale([
            'customer_name' => 'Yesterday Customer',
            'total_amount' => 999,
            'paid_amount' => 999,
            'balance' => 0,
        ]);
        $yesterdaySale->forceFill([
            'created_at' => now()->subDay()->startOfDay(),
            'updated_at' => now()->subDay()->startOfDay(),
        ])->saveQuietly();

        $response = $this->getJson('/api/dashboard/overview');

        $response
            ->assertOk()
            ->assertJsonPath('tenant.id', $this->tenant->id)
            ->assertJsonPath('stock_summary.can_view', true)
            ->assertJsonPath('daily_sales_summary.can_view', true)
            ->assertJsonPath('daily_sales_summary.gross_amount', 250);
    }

    public function test_dashboard_stats_route_supports_selectable_date_week_month_year_ranges(): void
    {
        $this->actingAsUser([
            'sales.process',
        ]);

        $product = $this->createProduct(['name' => 'Test Product']);
        $variation = $this->createVariation($product, ['name' => 'Default']);

        $dateAnchor = now()->subDays(10)->startOfDay()->addHours(9);
        $weekAnchor = now()->subWeeks(4)->startOfWeek()->addDays(2)->addHours(11);
        $monthAnchor = now()->subMonths(2)->startOfMonth()->addDays(5)->addHours(14);
        $yearAnchor = now()->subYear()->startOfYear()->addMonths(2)->addDays(3)->addHours(15);

        $dateSale = $this->createSaleWithItem($dateAnchor, 'Date Customer', 110, 1, 110, $product->id, $variation->id);
        $weekSale = $this->createSaleWithItem($weekAnchor, 'Week Customer', 220, 2, 110, $product->id, $variation->id);
        $monthSale = $this->createSaleWithItem($monthAnchor, 'Month Customer', 330, 3, 110, $product->id, $variation->id);
        $yearSale = $this->createSaleWithItem($yearAnchor, 'Year Customer', 440, 4, 110, $product->id, $variation->id);

        $dateResponse = $this->getJson('/api/dashboard/stats?range_type=date&range_value='.$dateAnchor->toDateString());

        $dateResponse
            ->assertOk()
            ->assertJsonPath('range_type', 'date')
            ->assertJsonPath('range_value', $dateAnchor->toDateString())
            ->assertJsonPath('transactions', 1)
            ->assertJsonPath('gross_amount', 110)
            ->assertJsonPath('paid_amount', 110)
            ->assertJsonPath('transaction_list.0.sale_id', $dateSale->id)
            ->assertJsonPath('customer_wise_sales.0.customer_name', 'Date Customer')
            ->assertJsonPath('product_wise_sales.0.quantity_sold', 1);

        $weekValue = sprintf('%04d-W%02d', (int) $weekAnchor->isoWeekYear, (int) $weekAnchor->isoWeek);
        $weekResponse = $this->getJson('/api/dashboard/stats?range_type=week&range_value='.rawurlencode($weekValue));

        $weekResponse
            ->assertOk()
            ->assertJsonPath('range_type', 'week')
            ->assertJsonPath('range_value', $weekValue)
            ->assertJsonPath('transactions', 1)
            ->assertJsonPath('gross_amount', 220)
            ->assertJsonPath('transaction_list.0.sale_id', $weekSale->id)
            ->assertJsonPath('customer_wise_sales.0.customer_name', 'Week Customer')
            ->assertJsonPath('product_wise_sales.0.quantity_sold', 2);

        $monthValue = $monthAnchor->format('Y-m');
        $monthResponse = $this->getJson('/api/dashboard/stats?range_type=month&range_value='.rawurlencode($monthValue));

        $monthResponse
            ->assertOk()
            ->assertJsonPath('range_type', 'month')
            ->assertJsonPath('range_value', $monthValue)
            ->assertJsonPath('transactions', 1)
            ->assertJsonPath('gross_amount', 330)
            ->assertJsonPath('transaction_list.0.sale_id', $monthSale->id)
            ->assertJsonPath('customer_wise_sales.0.customer_name', 'Month Customer')
            ->assertJsonPath('product_wise_sales.0.quantity_sold', 3);

        $yearValue = $yearAnchor->format('Y');
        $yearResponse = $this->getJson('/api/dashboard/stats?range_type=year&range_value='.$yearValue);

        $yearResponse
            ->assertOk()
            ->assertJsonPath('range_type', 'year')
            ->assertJsonPath('range_value', $yearValue)
            ->assertJsonPath('transactions', 1)
            ->assertJsonPath('gross_amount', 440)
            ->assertJsonPath('transaction_list.0.sale_id', $yearSale->id)
            ->assertJsonPath('customer_wise_sales.0.customer_name', 'Year Customer')
            ->assertJsonPath('product_wise_sales.0.quantity_sold', 4);
    }

    private function createSaleWithItem(
        Carbon $createdAt,
        string $customerName,
        float $totalAmount,
        int $quantity,
        float $unitPrice,
        int $productId,
        int $variationId,
    ): Sale {
        $sale = $this->createSale([
            'customer_name' => $customerName,
            'total_amount' => $totalAmount,
            'paid_amount' => $totalAmount,
            'balance' => 0,
        ]);

        $sale->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $productId,
            'product_variation_id' => $variationId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $unitPrice * $quantity,
        ]);

        return $sale;
    }
}

<?php

namespace App\Services;

use App\Models\ProductVariation;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\User;

class DashboardOverviewService
{
    private const LOW_STOCK_THRESHOLD = 5;

    public function build(User $user, Tenant $tenant): array
    {
        $tenantId = $tenant->id;
        $today = now()->toDateString();

        return [
            'tenant' => [
                'name' => $tenant->name,
                'id' => $tenant->id,
                'domain' => $tenant->domain,
            ],
            'user' => [
                'name' => $user->name,
                'id' => $user->id,
                'email' => $user->email,
            ],
            'stock_summary' => $this->buildStockSummary($user, $tenantId, $today),
            'daily_sales_summary' => $this->buildDailySalesSummary($user, $tenantId, $today),
        ];
    }

    private function buildStockSummary(User $user, int $tenantId, string $today): array
    {
        $canViewStockSummary = $user->hasPermission('inventory.manage');

        $stockSummary = [
            'can_view' => $canViewStockSummary,
            'available_units' => 0,
            'tracked_variations' => 0,
            'low_stock_variations' => 0,
            'low_stock_threshold' => self::LOW_STOCK_THRESHOLD,
            'variation_availability' => [],
        ];

        if (!$canViewStockSummary) {
            return $stockSummary;
        }

        $variationAvailability = ProductVariation::query()
            ->where('product_variations.tenant_id', $tenantId)
            ->leftJoin('products', 'products.id', '=', 'product_variations.product_id')
            ->leftJoin('stock_entries', function ($join) use ($tenantId, $today) {
                $join->on('stock_entries.product_variation_id', '=', 'product_variations.id')
                    ->where('stock_entries.tenant_id', $tenantId)
                    ->where(function ($query) use ($today) {
                        $query->whereDate('stock_entries.expiry_date', '>=', $today)
                            ->orWhereNull('stock_entries.expiry_date');
                    });
            })
            ->groupBy('product_variations.id', 'product_variations.name', 'products.name')
            ->orderBy('products.name')
            ->orderBy('product_variations.name')
            ->selectRaw('product_variations.id as variation_id, product_variations.name as variation_name, products.name as product_name, COALESCE(SUM(stock_entries.quantity), 0) as available_quantity')
            ->get()
            ->map(function ($item) {
                $availableQuantity = (int) $item->available_quantity;
                $productName = (string) ($item->product_name ?? 'Product');
                $variationName = (string) $item->variation_name;

                return [
                    'variation_id' => (int) $item->variation_id,
                    'product_name' => $productName,
                    'variation_name' => $variationName,
                    'label' => trim($productName . ' - ' . $variationName),
                    'available_quantity' => $availableQuantity,
                    'is_low_stock' => $availableQuantity <= self::LOW_STOCK_THRESHOLD,
                ];
            })
            ->values();

        $stockSummary['available_units'] = (int) $variationAvailability->sum('available_quantity');
        $stockSummary['tracked_variations'] = $variationAvailability->count();
        $stockSummary['low_stock_variations'] = $variationAvailability
            ->filter(fn ($item) => $item['is_low_stock'])
            ->count();
        $stockSummary['variation_availability'] = $variationAvailability;

        return $stockSummary;
    }

    private function buildDailySalesSummary(User $user, int $tenantId, string $today): array
    {
        $canViewSalesSummary = $user->hasPermission('sales.process');

        $dailySalesSummary = [
            'can_view' => $canViewSalesSummary,
            'date' => $today,
            'transactions' => 0,
            'gross_amount' => 0,
            'paid_amount' => 0,
        ];

        if (!$canViewSalesSummary) {
            return $dailySalesSummary;
        }

        $todaySalesQuery = Sale::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('created_at', $today);

        $dailySalesSummary['transactions'] = (int) $todaySalesQuery->count();
        $dailySalesSummary['gross_amount'] = (float) $todaySalesQuery->sum('total_amount');
        $dailySalesSummary['paid_amount'] = (float) $todaySalesQuery->sum('paid_amount');

        return $dailySalesSummary;
    }
}

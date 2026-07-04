<?php

namespace App\Services;

use App\Models\CompanyAccount;
use App\Models\Member;
use App\Models\ProductVariation;
use App\Models\SaleItem;
use App\Models\StockEntry;
use Illuminate\Support\Carbon;

class SaleMetaService
{
    public function __construct(
        private readonly PaymentMethodService $paymentMethods,
    ) {}

    public function build(int $tenantId): array
    {
        $today = Carbon::today()->toDateString();
        $salesWindowStart = Carbon::now()->subDays(7)->startOfDay();

        $variationSalesCounts = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereNull('sales.deleted_at')
            ->where('sales.created_at', '>=', $salesWindowStart)
            ->groupBy('sale_items.product_variation_id')
            ->selectRaw('sale_items.product_variation_id, SUM(sale_items.quantity) as total_quantity_sold')
            ->pluck('total_quantity_sold', 'sale_items.product_variation_id');

        $variations = ProductVariation::query()
            ->with('product:id,name')
            ->get()
            ->sort(function (ProductVariation $left, ProductVariation $right) use ($variationSalesCounts) {
                $leftSales = (int) ($variationSalesCounts[$left->id] ?? 0);
                $rightSales = (int) ($variationSalesCounts[$right->id] ?? 0);

                if ($leftSales !== $rightSales) {
                    return $rightSales <=> $leftSales;
                }

                $leftLabel = trim(($left->product?->name ?? 'Product') . ' - ' . $left->name);
                $rightLabel = trim(($right->product?->name ?? 'Product') . ' - ' . $right->name);

                return strcmp($leftLabel, $rightLabel);
            })
            ->values();

        $availableStock = StockEntry::query()
            ->where(function ($query) use ($today) {
                $query->whereDate('expiry_date', '>=', $today)
                    ->orWhereNull('expiry_date');
            })
            ->groupBy('product_variation_id')
            ->selectRaw('product_variation_id, SUM(display_quantity) as total')
            ->pluck('total', 'product_variation_id');

        $priceMap = StockEntry::query()
            ->where(function ($query) use ($today) {
                $query->whereDate('expiry_date', '>=', $today)
                    ->orWhereNull('expiry_date');
            })
            ->orderBy('expiry_date')
            ->get()
            ->groupBy('product_variation_id')
            ->map(function ($entries) {
                $entry = $entries->first();

                return [
                    'local' => (float) $entry->local_selling_price,
                    'foreign' => (float) $entry->foreign_selling_price,
                ];
            });

        $members = Member::query()
            ->orderBy('name')
            ->get(['id', 'name', 'phone_number']);

        $accounts = CompanyAccount::query()
            ->orderBy('name')
            ->withSum('incomingTransfers as incoming_total', 'amount')
            ->withSum('outgoingTransfers as outgoing_total', 'amount')
            ->withSum('transactions as transaction_total', 'amount')
            ->get();

        return [
            'variations' => $variations->map(function (ProductVariation $variation) use ($availableStock, $priceMap) {
                return [
                    'id' => $variation->id,
                    'name' => $variation->name,
                    'product_name' => $variation->product?->name,
                    'label' => trim(($variation->product?->name ?? 'Product') . ' - ' . $variation->name),
                    'available_stock' => (int) ($availableStock[$variation->id] ?? 0),
                    'prices' => $priceMap[$variation->id] ?? ['local' => 0, 'foreign' => 0],
                ];
            })->values(),
            'members' => $members->map(function (Member $member) {
                $name = trim((string) ($member->name ?? '')) ?: 'Member';

                $phone = $member->phone_number ?: 'N/A';

                return [
                    'id' => $member->id,
                    'label' => $name . ' (' . $phone . ')',
                    'customer_name' => $name,
                    'phone_number' => $phone,
                ];
            })->values(),
            'accounts' => $accounts->map(fn (CompanyAccount $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'label' => $account->name,
                'current_balance' => round(
                    (float) $account->opening_balance
                    + (float) ($account->incoming_total ?? 0)
                    + (float) ($account->transaction_total ?? 0)
                    - (float) ($account->outgoing_total ?? 0),
                    2,
                ),
            ])->values(),
            'payment_methods' => $this->paymentMethods->activeMethods($tenantId),
        ];
    }
}

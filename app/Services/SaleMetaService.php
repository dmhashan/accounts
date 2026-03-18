<?php

namespace App\Services;

use App\Models\Member;
use App\Models\ProductVariation;
use App\Models\StockEntry;
use Illuminate\Support\Carbon;

class SaleMetaService
{
    public function build(int $tenantId): array
    {
        $today = Carbon::today()->toDateString();

        $variations = ProductVariation::query()
            ->where('tenant_id', $tenantId)
            ->with('product:id,name')
            ->orderBy('name')
            ->get();

        $availableStock = StockEntry::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($query) use ($today) {
                $query->whereDate('expiry_date', '>=', $today)
                    ->orWhereNull('expiry_date');
            })
            ->groupBy('product_variation_id')
            ->selectRaw('product_variation_id, SUM(quantity) as total')
            ->pluck('total', 'product_variation_id');

        $priceMap = StockEntry::query()
            ->where('tenant_id', $tenantId)
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
            ->where('tenant_id', $tenantId)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'name', 'phone_number']);

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
                $name = trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? ''));
                if ($name === '') {
                    $name = $member->name ?: 'Member';
                }

                $phone = $member->phone_number ?: 'N/A';

                return [
                    'id' => $member->id,
                    'label' => $name . ' (' . $phone . ')',
                    'customer_name' => $name,
                    'phone_number' => $phone,
                ];
            })->values(),
        ];
    }
}

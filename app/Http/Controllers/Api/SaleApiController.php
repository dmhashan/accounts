<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\ProductVariation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleApiController extends Controller
{
    public function memberWallet(Member $member): JsonResponse
    {
        if ($member->tenant_id !== app('tenant')->id) {
            abort(404);
        }

        return response()->json([
            'data' => [
                'member_id' => $member->id,
                'current_balance' => (float) $member->current_balance,
            ],
        ]);
    }

    public function meta(): JsonResponse
    {
        $tenantId = app('tenant')->id;
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

        return response()->json([
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
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 15), 50);

        $sales = Sale::query()
            ->where('tenant_id', app('tenant')->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => collect($sales->items())->map(fn (Sale $sale) => [
                'id' => $sale->id,
                'customer_name' => $sale->customer_name,
                'customer_type' => $sale->customer_type,
                'payment_method' => $sale->payment_method,
                'reference_number' => $sale->reference_number,
                'total_amount' => (float) $sale->total_amount,
                'paid_amount' => (float) $sale->paid_amount,
                'balance' => (float) $sale->balance,
                'created_at' => optional($sale->created_at)->toDateString(),
            ]),
            'meta' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = app('tenant')->id;

        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_member_id' => ['nullable', 'exists:members,id'],
            'customer_type' => ['required', 'in:local,foreign'],
            'payment_method' => ['required', 'in:cash,bank,card,member_wallet'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variation_id' => ['required', 'exists:product_variations,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $today = Carbon::today()->toDateString();

        return DB::transaction(function () use ($validated, $tenantId, $today) {
            $itemsPayload = $validated['items'];
            $variationIds = collect($itemsPayload)->pluck('product_variation_id')->unique();

            $variations = ProductVariation::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $variationIds)
                ->with('product')
                ->get()
                ->keyBy('id');

            if ($variations->count() !== $variationIds->count()) {
                abort(422, 'Invalid variation selection.');
            }

            $saleItems = [];
            $total = 0;

            foreach ($itemsPayload as $item) {
                $variation = $variations->get($item['product_variation_id']);

                $available = StockEntry::query()
                    ->where('tenant_id', $tenantId)
                    ->where('product_variation_id', $variation->id)
                    ->where(function ($query) use ($today) {
                        $query->whereDate('expiry_date', '>=', $today)
                            ->orWhereNull('expiry_date');
                    })
                    ->sum('quantity');

                if ($item['quantity'] > $available) {
                    abort(422, 'Insufficient stock for ' . $variation->product->name . ' - ' . $variation->name);
                }

                $priceEntry = StockEntry::query()
                    ->where('tenant_id', $tenantId)
                    ->where('product_variation_id', $variation->id)
                    ->where(function ($query) use ($today) {
                        $query->whereDate('expiry_date', '>=', $today)
                            ->orWhereNull('expiry_date');
                    })
                    ->orderBy('expiry_date')
                    ->first();

                if (!$priceEntry) {
                    abort(422, 'No valid stock for ' . $variation->product->name . ' - ' . $variation->name);
                }

                $unitPrice = $validated['customer_type'] === 'local'
                    ? $priceEntry->local_selling_price
                    : $priceEntry->foreign_selling_price;

                $subtotal = $unitPrice * $item['quantity'];
                $total += $subtotal;

                $saleItems[] = [
                    'product_id' => $variation->product_id,
                    'product_variation_id' => $variation->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ];
            }

            $member = null;
            if (!empty($validated['customer_member_id'])) {
                $member = Member::query()
                    ->where('tenant_id', $tenantId)
                    ->lockForUpdate()
                    ->find($validated['customer_member_id']);

                if (!$member) {
                    abort(422, 'Invalid member selection.');
                }
            }

            if (($validated['payment_method'] ?? null) === 'member_wallet') {
                if (!$member) {
                    abort(422, 'Please select a member for wallet payment.');
                }

                if ((float) $member->current_balance < $total) {
                    abort(422, 'Insufficient member wallet balance.');
                }

                $paidAmount = $total;
                $balance = 0;
                $member->update([
                    'current_balance' => (float) $member->current_balance - $total,
                ]);
            } else {
                $paidAmount = (float) $validated['paid_amount'];
                $balance = $paidAmount - $total;
            }

            $sale = Sale::create([
                'tenant_id' => $tenantId,
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_member_id' => $member?->id,
                'customer_type' => $validated['customer_type'],
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'total_amount' => $total,
                'paid_amount' => $paidAmount,
                'balance' => $balance,
            ]);

            foreach ($saleItems as $saleItem) {
                $saleItem['sale_id'] = $sale->id;
                SaleItem::create($saleItem);
            }

            foreach ($itemsPayload as $item) {
                $remaining = $item['quantity'];

                $entries = StockEntry::query()
                    ->where('tenant_id', $tenantId)
                    ->where('product_variation_id', $item['product_variation_id'])
                    ->where(function ($query) use ($today) {
                        $query->whereDate('expiry_date', '>=', $today)
                            ->orWhereNull('expiry_date');
                    })
                    ->orderBy('expiry_date')
                    ->lockForUpdate()
                    ->get();

                foreach ($entries as $entry) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $deduct = min($entry->quantity, $remaining);
                    $entry->update(['quantity' => $entry->quantity - $deduct]);
                    $remaining -= $deduct;
                }
            }

            return response()->json([
                'message' => 'Sale completed successfully.',
                'data' => [
                    'id' => $sale->id,
                ],
            ], 201);
        });
    }

    public function destroy(Sale $sale): JsonResponse
    {
        if ($sale->tenant_id !== app('tenant')->id) {
            abort(404);
        }

        $sale->delete();

        return response()->json([
            'message' => 'Sale deleted successfully.',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockEntry;
use App\Services\FinancialTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index()
    {
        $tenantId = app('tenant')->id;

        $sales = Sale::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('sales.index', compact('sales'));
    }

    public function create()
    {
        $tenantId = app('tenant')->id;
        $today = Carbon::today()->toDateString();

        $variations = ProductVariation::where('tenant_id', $tenantId)
            ->with('product')
            ->orderBy('name')
            ->get();

        $availableStock = StockEntry::where('tenant_id', $tenantId)
            ->where(function ($query) use ($today) {
                $query->whereDate('expiry_date', '>=', $today)
                    ->orWhereNull('expiry_date');
            })
            ->groupBy('product_variation_id')
            ->selectRaw('product_variation_id, SUM(quantity) as total')
            ->pluck('total', 'product_variation_id');

        $priceMap = StockEntry::where('tenant_id', $tenantId)
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
                    'local' => $entry->local_selling_price,
                    'foreign' => $entry->foreign_selling_price,
                ];
            });

        $members = Member::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        return view('sales.create', compact('variations', 'availableStock', 'priceMap', 'members'));
    }

    public function store(Request $request, FinancialTransactionService $financialTransactionService)
    {
        $tenantId = app('tenant')->id;
        $userId = Auth::id();

        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_type' => ['required', 'in:local,foreign'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variation_id' => ['required', 'exists:product_variations,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $today = Carbon::today()->toDateString();

        return DB::transaction(function () use ($validated, $tenantId, $today, $financialTransactionService, $userId) {
            $itemsPayload = $validated['items'];
            $variationIds = collect($itemsPayload)->pluck('product_variation_id')->unique();

            $variations = ProductVariation::where('tenant_id', $tenantId)
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

                $available = StockEntry::where('tenant_id', $tenantId)
                    ->where('product_variation_id', $variation->id)
                    ->where(function ($query) use ($today) {
                        $query->whereDate('expiry_date', '>=', $today)
                            ->orWhereNull('expiry_date');
                    })
                    ->sum('quantity');

                if ($item['quantity'] > $available) {
                    abort(422, 'Insufficient stock for ' . $variation->product->name . ' - ' . $variation->name);
                }

                $priceEntry = StockEntry::where('tenant_id', $tenantId)
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

            $paidAmount = (float) $validated['paid_amount'];
            $balance = $paidAmount - $total;

            $sale = Sale::create([
                'tenant_id' => $tenantId,
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_type' => $validated['customer_type'],
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

                $entries = StockEntry::where('tenant_id', $tenantId)
                    ->where('product_variation_id', $item['product_variation_id'])
                    ->whereDate('expiry_date', '>=', $today)
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

            $financialTransactionService->recordSaleTransaction($sale, [
                'amount' => $paidAmount,
                'transaction_type' => 'credit',
                'description' => 'Sale #'.$sale->id.' completed via cash',
                'status' => 'completed',
            ], $userId);

            return redirect()->route('sales.index')
                ->with('success', 'Sale completed successfully.');
        });
    }

    public function destroy(Sale $sale)
    {
        $tenantId = app('tenant')->id;

        if ($sale->tenant_id !== $tenantId) {
            abort(404);
        }

        $sale->delete();

        return redirect()->route('sales.index')
            ->with('success', 'Sale deleted successfully.');
    }
}

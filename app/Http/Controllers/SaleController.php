<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\ProductVariation;
use App\Models\Sale;
use App\Models\StockEntry;
use App\Services\SaleProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SaleController extends Controller
{
    public function index()
    {
        $tenantId = app('tenant')->id;

        $sales = Sale::query()
            ->orderBy('created_at', 'desc')
            ->get();

        return view('sales.index', compact('sales'));
    }

    public function create()
    {
        $tenantId = app('tenant')->id;
        $today = Carbon::today()->toDateString();

        $variations = ProductVariation::query()
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->with('product')
            ->orderBy('name')
            ->get();

        $availableStock = StockEntry::query()
            ->where(function ($query) use ($today) {
                $query->whereDate('expiry_date', '>=', $today)
                    ->orWhereNull('expiry_date');
            })
            ->groupBy('product_variation_id')
            ->selectRaw('product_variation_id, SUM(quantity) as total')
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
                    'local' => $entry->local_selling_price,
                    'foreign' => $entry->foreign_selling_price,
                ];
            });

        $members = Member::query()
            ->orderBy('name')
            ->get();

        return view('sales.create', compact('variations', 'availableStock', 'priceMap', 'members'));
    }

    public function store(Request $request, SaleProcessingService $sales)
    {
        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_type' => ['required', 'in:local,foreign'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variation_id' => ['required', 'exists:product_variations,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0', 'decimal:0,4'],
        ]);

        $sales->create(app('tenant')->id, $validated);

        return redirect()->route('sales.index')->with('success', 'Sale completed successfully.');
    }

    public function destroy(Sale $sale, SaleProcessingService $sales)
    {
        $sales->delete($sale, app('tenant')->id);

        return redirect()->route('sales.index')->with('success', 'Sale deleted successfully.');
    }
}

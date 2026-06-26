<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\StockEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StockController extends Controller
{
    public function index()
    {
        $tenantId = app('tenant')->id;
        $today = Carbon::today()->toDateString();
        $lowStockThreshold = 5;

        $stockEntries = StockEntry::query()
            ->with(['product', 'variation'])
            ->orderBy('created_at', 'desc')
            ->get();

        $availableTotals = StockEntry::query()
            ->whereDate('expiry_date', '>=', $today)
            ->groupBy('product_variation_id')
            ->selectRaw('product_variation_id, SUM(quantity) as total')
            ->pluck('total', 'product_variation_id');

        return view('inventory.stock.index', compact('stockEntries', 'availableTotals', 'lowStockThreshold'));
    }

    public function create()
    {
        $tenantId = app('tenant')->id;

        $products = Product::query()
            ->orderBy('name')
            ->get();

        $variations = ProductVariation::query()
            ->with('product')
            ->orderBy('name')
            ->get();

        return view('inventory.stock.create', compact('products', 'variations'));
    }

    public function store(Request $request)
    {
        $tenantId = app('tenant')->id;

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'product_variation_id' => ['required', 'exists:product_variations,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'manufacturing_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after:manufacturing_date'],
            'purchasing_price' => ['required', 'numeric', 'min:0'],
            'local_selling_price' => ['required', 'numeric', 'min:0'],
            'foreign_selling_price' => ['required', 'numeric', 'min:0'],
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);
        $variation = ProductVariation::query()->findOrFail($validated['product_variation_id']);

        if ($variation->product_id !== $product->id) {
            return back()->withInput()->withErrors([
                'product_variation_id' => 'Selected variation does not belong to the selected product.',
            ]);
        }

        StockEntry::create([
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'quantity' => $validated['quantity'],
            'manufacturing_date' => $validated['manufacturing_date'],
            'expiry_date' => $validated['expiry_date'],
            'purchasing_price' => $validated['purchasing_price'],
            'local_selling_price' => $validated['local_selling_price'],
            'foreign_selling_price' => $validated['foreign_selling_price'],
        ]);

        return redirect()->route('inventory.stock.index')
            ->with('success', 'Stock added successfully.');
    }

    public function edit(StockEntry $stock)
    {
        $this->ensureTenant($stock);
        $tenantId = app('tenant')->id;

        $products = Product::query()
            ->orderBy('name')
            ->get();

        $variations = ProductVariation::query()
            ->with('product')
            ->orderBy('name')
            ->get();

        return view('inventory.stock.edit', compact('stock', 'products', 'variations'));
    }

    public function update(Request $request, StockEntry $stock)
    {
        $this->ensureTenant($stock);
        $tenantId = app('tenant')->id;

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'product_variation_id' => ['required', 'exists:product_variations,id'],
            'quantity' => ['required', 'integer', 'min:0'],
            'manufacturing_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after:manufacturing_date'],
            'purchasing_price' => ['required', 'numeric', 'min:0'],
            'local_selling_price' => ['required', 'numeric', 'min:0'],
            'foreign_selling_price' => ['required', 'numeric', 'min:0'],
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);
        $variation = ProductVariation::query()->findOrFail($validated['product_variation_id']);

        if ($variation->product_id !== $product->id) {
            return back()->withInput()->withErrors([
                'product_variation_id' => 'Selected variation does not belong to the selected product.',
            ]);
        }

        $stock->update([
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'quantity' => $validated['quantity'],
            'manufacturing_date' => $validated['manufacturing_date'],
            'expiry_date' => $validated['expiry_date'],
            'purchasing_price' => $validated['purchasing_price'],
            'local_selling_price' => $validated['local_selling_price'],
            'foreign_selling_price' => $validated['foreign_selling_price'],
        ]);

        return redirect()->route('inventory.stock.index')
            ->with('success', 'Stock updated successfully.');
    }

    public function destroy(StockEntry $stock)
    {
        $this->ensureTenant($stock);

        $stock->delete();

        return redirect()->route('inventory.stock.index')
            ->with('success', 'Stock entry deleted successfully.');
    }

    private function ensureTenant(StockEntry $stock): void {}
}

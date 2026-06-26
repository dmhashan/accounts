<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Http\Request;

class ProductVariationController extends Controller
{
    public function index()
    {
        $tenantId = app('tenant')->id;

        $variations = ProductVariation::query()
            ->with('product')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('inventory.variations.index', compact('variations'));
    }

    public function create()
    {
        $products = Product::query()
            ->orderBy('name')
            ->get();

        return view('inventory.variations.create', compact('products'));
    }

    public function store(Request $request)
    {
        $tenantId = app('tenant')->id;

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);

        $exists = ProductVariation::where('product_id', $product->id)
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'name' => 'Variation name already exists for this product.',
            ]);
        }

        ProductVariation::create([
            'product_id' => $product->id,
            'name' => $validated['name'],
        ]);

        return redirect()->route('inventory.variations.index')
            ->with('success', 'Variation created successfully.');
    }

    public function edit(ProductVariation $variation)
    {
        $this->ensureTenant($variation);

        $products = Product::query()
            ->orderBy('name')
            ->get();

        return view('inventory.variations.edit', compact('variation', 'products'));
    }

    public function update(Request $request, ProductVariation $variation)
    {
        $this->ensureTenant($variation);
        $tenantId = app('tenant')->id;

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);

        $exists = ProductVariation::where('product_id', $product->id)
            ->where('name', $validated['name'])
            ->where('id', '!=', $variation->id)
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'name' => 'Variation name already exists for this product.',
            ]);
        }

        $variation->update([
            'product_id' => $product->id,
            'name' => $validated['name'],
        ]);

        return redirect()->route('inventory.variations.index')
            ->with('success', 'Variation updated successfully.');
    }

    public function destroy(ProductVariation $variation)
    {
        $this->ensureTenant($variation);

        $variation->delete();

        return redirect()->route('inventory.variations.index')
            ->with('success', 'Variation deleted successfully.');
    }

    private function ensureTenant(ProductVariation $variation): void {}
}

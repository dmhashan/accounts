<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        $tenantId = app('tenant')->id;

        $products = Product::where('tenant_id', $tenantId)
            ->withCount('variations')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('inventory.products.index', compact('products'));
    }

    public function create()
    {
        return view('inventory.products.create');
    }

    public function store(Request $request)
    {
        $tenantId = app('tenant')->id;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'variations' => ['nullable', 'array'],
            'variations.*.name' => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
        ]);

        $variationNames = collect($validated['variations'] ?? [])
            ->pluck('name')
            ->filter(fn ($name) => filled($name))
            ->unique();

        foreach ($variationNames as $name) {
            $product->variations()->create([
                'tenant_id' => $tenantId,
                'name' => $name,
            ]);
        }

        return redirect()->route('inventory.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        $this->ensureTenant($product);

        $product->load('variations');

        return view('inventory.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $this->ensureTenant($product);
        $tenantId = app('tenant')->id;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($product->id),
            ],
            'variations' => ['nullable', 'array'],
            'variations.*.id' => ['nullable', 'integer'],
            'variations.*.name' => ['nullable', 'string', 'max:255'],
        ]);

        $product->update([
            'name' => $validated['name'],
        ]);

        $variationPayload = collect($validated['variations'] ?? [])
            ->filter(fn ($variation) => filled($variation['name'] ?? null));

        $existingIds = $product->variations()->pluck('id')->all();
        $incomingIds = $variationPayload->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

        $idsToDelete = array_diff($existingIds, $incomingIds);
        if (!empty($idsToDelete)) {
            $product->variations()->whereIn('id', $idsToDelete)->delete();
        }

        foreach ($variationPayload as $variation) {
            $name = $variation['name'];
            $variationId = $variation['id'] ?? null;

            if ($variationId) {
                $product->variations()->where('id', $variationId)->update(['name' => $name]);
            } else {
                $exists = $product->variations()->where('name', $name)->exists();
                if (!$exists) {
                    $product->variations()->create([
                        'tenant_id' => $tenantId,
                        'name' => $name,
                    ]);
                }
            }
        }

        return redirect()->route('inventory.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $this->ensureTenant($product);

        $product->delete();

        return redirect()->route('inventory.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    private function ensureTenant(Product $product): void
    {
        if ($product->tenant_id !== app('tenant')->id) {
            abort(404);
        }
    }
}

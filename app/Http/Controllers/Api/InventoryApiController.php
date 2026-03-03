<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\StockEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class InventoryApiController extends Controller
{
    public function meta(): JsonResponse
    {
        $tenantId = app('tenant')->id;

        $products = Product::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $variations = ProductVariation::query()
            ->where('tenant_id', $tenantId)
            ->with('product:id,name')
            ->orderBy('name')
            ->get(['id', 'product_id', 'name']);

        return response()->json([
            'products' => $products,
            'variations' => $variations->map(function (ProductVariation $variation) {
                return [
                    'id' => $variation->id,
                    'product_id' => $variation->product_id,
                    'name' => $variation->name,
                    'label' => trim(($variation->product?->name ?? 'Product') . ' - ' . $variation->name),
                ];
            })->values(),
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $tenantId = app('tenant')->id;
        $perPage = min((int) $request->integer('per_page', 10), 50);

        $products = Product::query()
            ->where('tenant_id', $tenantId)
            ->withCount('variations')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => collect($products->items())->map(function (Product $product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'variations_count' => $product->variations_count,
                ];
            }),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function showProduct(Product $product): JsonResponse
    {
        $this->ensureProductTenant($product);

        $product->load('variations:id,product_id,name');

        return response()->json([
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'variations' => $product->variations->map(function (ProductVariation $variation) {
                    return [
                        'id' => $variation->id,
                        'name' => $variation->name,
                    ];
                })->values(),
            ],
        ]);
    }

    public function storeProduct(Request $request): JsonResponse
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
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->unique();

        foreach ($variationNames as $name) {
            $product->variations()->create([
                'tenant_id' => $tenantId,
                'name' => $name,
            ]);
        }

        return response()->json([
            'message' => 'Product created successfully.',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
            ],
        ], 201);
    }

    public function updateProduct(Request $request, Product $product): JsonResponse
    {
        $this->ensureProductTenant($product);
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
            ->filter(fn ($variation) => filled($variation['name'] ?? null))
            ->map(function ($variation) {
                return [
                    'id' => isset($variation['id']) ? (int) $variation['id'] : null,
                    'name' => trim((string) $variation['name']),
                ];
            })
            ->filter(fn ($variation) => $variation['name'] !== '');

        $existingIds = $product->variations()->pluck('id')->all();
        $incomingIds = $variationPayload->pluck('id')->filter()->all();

        $idsToDelete = array_diff($existingIds, $incomingIds);
        if (!empty($idsToDelete)) {
            $product->variations()->whereIn('id', $idsToDelete)->delete();
        }

        foreach ($variationPayload as $variation) {
            if ($variation['id']) {
                $product->variations()->where('id', $variation['id'])->update([
                    'name' => $variation['name'],
                ]);
                continue;
            }

            $exists = $product->variations()->where('name', $variation['name'])->exists();
            if (!$exists) {
                $product->variations()->create([
                    'tenant_id' => $tenantId,
                    'name' => $variation['name'],
                ]);
            }
        }

        return response()->json([
            'message' => 'Product updated successfully.',
        ]);
    }

    public function destroyProduct(Product $product): JsonResponse
    {
        $this->ensureProductTenant($product);

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }

    public function variations(): JsonResponse
    {
        $tenantId = app('tenant')->id;

        $variations = ProductVariation::query()
            ->where('tenant_id', $tenantId)
            ->with('product:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $variations->map(function (ProductVariation $variation) {
                return [
                    'id' => $variation->id,
                    'name' => $variation->name,
                    'product_id' => $variation->product_id,
                    'product_name' => $variation->product?->name,
                ];
            }),
        ]);
    }

    public function storeVariation(Request $request): JsonResponse
    {
        $tenantId = app('tenant')->id;

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $product = Product::query()->where('tenant_id', $tenantId)->findOrFail($validated['product_id']);

        $exists = ProductVariation::query()
            ->where('product_id', $product->id)
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Variation name already exists for this product.',
            ], 422);
        }

        $variation = ProductVariation::create([
            'tenant_id' => $tenantId,
            'product_id' => $product->id,
            'name' => $validated['name'],
        ]);

        return response()->json([
            'message' => 'Variation created successfully.',
            'data' => [
                'id' => $variation->id,
                'name' => $variation->name,
            ],
        ], 201);
    }

    public function updateVariation(Request $request, ProductVariation $variation): JsonResponse
    {
        $this->ensureVariationTenant($variation);
        $tenantId = app('tenant')->id;

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $product = Product::query()->where('tenant_id', $tenantId)->findOrFail($validated['product_id']);

        $exists = ProductVariation::query()
            ->where('product_id', $product->id)
            ->where('name', $validated['name'])
            ->where('id', '!=', $variation->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Variation name already exists for this product.',
            ], 422);
        }

        $variation->update([
            'product_id' => $product->id,
            'name' => $validated['name'],
        ]);

        return response()->json([
            'message' => 'Variation updated successfully.',
        ]);
    }

    public function destroyVariation(ProductVariation $variation): JsonResponse
    {
        $this->ensureVariationTenant($variation);

        $variation->delete();

        return response()->json([
            'message' => 'Variation deleted successfully.',
        ]);
    }

    public function stock(Request $request): JsonResponse
    {
        $tenantId = app('tenant')->id;
        $today = Carbon::today()->toDateString();
        $lowStockThreshold = 5;
        $perPage = min((int) $request->integer('per_page', 10), 50);

        $stockEntries = StockEntry::query()
            ->where('tenant_id', $tenantId)
            ->with(['product:id,name', 'variation:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $availableTotals = StockEntry::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('expiry_date', '>=', $today)
            ->groupBy('product_variation_id')
            ->selectRaw('product_variation_id, SUM(quantity) as total')
            ->pluck('total', 'product_variation_id');

        return response()->json([
            'data' => collect($stockEntries->items())->map(function (StockEntry $entry) use ($availableTotals, $lowStockThreshold) {
                $available = (int) ($availableTotals[$entry->product_variation_id] ?? 0);

                return [
                    'id' => $entry->id,
                    'product_id' => $entry->product_id,
                    'product_name' => $entry->product?->name,
                    'product_variation_id' => $entry->product_variation_id,
                    'variation_name' => $entry->variation?->name,
                    'quantity' => (int) $entry->quantity,
                    'available' => $available,
                    'is_low_stock' => $available > 0 && $available <= $lowStockThreshold,
                    'manufacturing_date' => optional($entry->manufacturing_date)->format('Y-m-d'),
                    'expiry_date' => optional($entry->expiry_date)->format('Y-m-d'),
                    'purchasing_price' => (float) $entry->purchasing_price,
                    'local_selling_price' => (float) $entry->local_selling_price,
                    'foreign_selling_price' => (float) $entry->foreign_selling_price,
                ];
            }),
            'meta' => [
                'current_page' => $stockEntries->currentPage(),
                'last_page' => $stockEntries->lastPage(),
                'per_page' => $stockEntries->perPage(),
                'total' => $stockEntries->total(),
                'low_stock_threshold' => $lowStockThreshold,
            ],
        ]);
    }

    public function showStock(StockEntry $stock): JsonResponse
    {
        $this->ensureStockTenant($stock);

        return response()->json([
            'data' => [
                'id' => $stock->id,
                'product_id' => $stock->product_id,
                'product_variation_id' => $stock->product_variation_id,
                'quantity' => (int) $stock->quantity,
                'manufacturing_date' => optional($stock->manufacturing_date)->format('Y-m-d'),
                'expiry_date' => optional($stock->expiry_date)->format('Y-m-d'),
                'purchasing_price' => (float) $stock->purchasing_price,
                'local_selling_price' => (float) $stock->local_selling_price,
                'foreign_selling_price' => (float) $stock->foreign_selling_price,
            ],
        ]);
    }

    public function storeStock(Request $request): JsonResponse
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

        $product = Product::query()->where('tenant_id', $tenantId)->findOrFail($validated['product_id']);
        $variation = ProductVariation::query()->where('tenant_id', $tenantId)->findOrFail($validated['product_variation_id']);

        if ($variation->product_id !== $product->id) {
            return response()->json([
                'message' => 'Selected variation does not belong to the selected product.',
            ], 422);
        }

        $stock = StockEntry::create([
            'tenant_id' => $tenantId,
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'quantity' => $validated['quantity'],
            'manufacturing_date' => $validated['manufacturing_date'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'purchasing_price' => $validated['purchasing_price'],
            'local_selling_price' => $validated['local_selling_price'],
            'foreign_selling_price' => $validated['foreign_selling_price'],
        ]);

        return response()->json([
            'message' => 'Stock added successfully.',
            'data' => [
                'id' => $stock->id,
            ],
        ], 201);
    }

    public function updateStock(Request $request, StockEntry $stock): JsonResponse
    {
        $this->ensureStockTenant($stock);
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

        $product = Product::query()->where('tenant_id', $tenantId)->findOrFail($validated['product_id']);
        $variation = ProductVariation::query()->where('tenant_id', $tenantId)->findOrFail($validated['product_variation_id']);

        if ($variation->product_id !== $product->id) {
            return response()->json([
                'message' => 'Selected variation does not belong to the selected product.',
            ], 422);
        }

        $stock->update([
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'quantity' => $validated['quantity'],
            'manufacturing_date' => $validated['manufacturing_date'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'purchasing_price' => $validated['purchasing_price'],
            'local_selling_price' => $validated['local_selling_price'],
            'foreign_selling_price' => $validated['foreign_selling_price'],
        ]);

        return response()->json([
            'message' => 'Stock updated successfully.',
        ]);
    }

    public function destroyStock(StockEntry $stock): JsonResponse
    {
        $this->ensureStockTenant($stock);

        $stock->delete();

        return response()->json([
            'message' => 'Stock entry deleted successfully.',
        ]);
    }

    private function ensureProductTenant(Product $product): void
    {
        if ($product->tenant_id !== app('tenant')->id) {
            abort(404);
        }
    }

    private function ensureVariationTenant(ProductVariation $variation): void
    {
        if ($variation->tenant_id !== app('tenant')->id) {
            abort(404);
        }
    }

    private function ensureStockTenant(StockEntry $stock): void
    {
        if ($stock->tenant_id !== app('tenant')->id) {
            abort(404);
        }
    }
}

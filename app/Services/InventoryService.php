<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\AuditLog;
use App\Models\StockEntry;
use Illuminate\Support\Carbon;

class InventoryService
{
    private const LOW_STOCK_THRESHOLD = 5;

    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function meta(int $tenantId): array
    {
        $products = Product::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $variations = ProductVariation::query()
            ->where('tenant_id', $tenantId)
            ->with('product:id,name')
            ->orderBy('name')
            ->get(['id', 'product_id', 'name']);

        return [
            'products' => $products,
            'variations' => $variations->map(function (ProductVariation $variation) {
                return [
                    'id' => $variation->id,
                    'product_id' => $variation->product_id,
                    'name' => $variation->name,
                    'label' => trim(($variation->product?->name ?? 'Product').' - '.$variation->name),
                ];
            })->values(),
        ];
    }

    public function products(int $tenantId, int $perPage): array
    {
        $products = Product::query()
            ->where('tenant_id', $tenantId)
            ->withCount('variations')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
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
        ];
    }

    public function showProduct(Product $product, int $tenantId): array
    {
        $this->ensureProductTenant($product, $tenantId);
        $product->load('variations:id,product_id,name');

        return [
            'id' => $product->id,
            'name' => $product->name,
            'variations' => $product->variations->map(function (ProductVariation $variation) {
                return [
                    'id' => $variation->id,
                    'name' => $variation->name,
                ];
            })->values(),
        ];
    }

    public function storeProduct(int $tenantId, array $validated): Product
    {
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

        return $product;
    }

    public function updateProduct(Product $product, int $tenantId, array $validated): void
    {
        $this->ensureProductTenant($product, $tenantId);

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
    }

    public function destroyProduct(Product $product, int $tenantId): void
    {
        $this->ensureProductTenant($product, $tenantId);
        $product->delete();
    }

    public function variations(int $tenantId): array
    {
        $variations = ProductVariation::query()
            ->where('tenant_id', $tenantId)
            ->with('product:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'data' => $variations->map(function (ProductVariation $variation) {
                return [
                    'id' => $variation->id,
                    'name' => $variation->name,
                    'product_id' => $variation->product_id,
                    'product_name' => $variation->product?->name,
                ];
            }),
        ];
    }

    public function storeVariation(int $tenantId, array $validated): array
    {
        $product = Product::query()->where('tenant_id', $tenantId)->findOrFail($validated['product_id']);

        $exists = ProductVariation::query()
            ->where('product_id', $product->id)
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return [
                'error' => 'Variation name already exists for this product.',
            ];
        }

        $variation = ProductVariation::create([
            'tenant_id' => $tenantId,
            'product_id' => $product->id,
            'name' => $validated['name'],
        ]);

        return [
            'variation' => $variation,
        ];
    }

    public function updateVariation(ProductVariation $variation, int $tenantId, array $validated): ?string
    {
        $this->ensureVariationTenant($variation, $tenantId);

        $product = Product::query()->where('tenant_id', $tenantId)->findOrFail($validated['product_id']);

        $exists = ProductVariation::query()
            ->where('product_id', $product->id)
            ->where('name', $validated['name'])
            ->where('id', '!=', $variation->id)
            ->exists();

        if ($exists) {
            return 'Variation name already exists for this product.';
        }

        $variation->update([
            'product_id' => $product->id,
            'name' => $validated['name'],
        ]);

        return null;
    }

    public function destroyVariation(ProductVariation $variation, int $tenantId): void
    {
        $this->ensureVariationTenant($variation, $tenantId);
        $variation->delete();
    }

    public function stock(int $tenantId, int $perPage): array
    {
        $today = Carbon::today()->toDateString();

        $stockEntries = StockEntry::query()
            ->where('tenant_id', $tenantId)
            ->with(['product:id,name', 'variation:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $displayTotals = StockEntry::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($query) use ($today) {
                $query->whereDate('expiry_date', '>=', $today)->orWhereNull('expiry_date');
            })
            ->groupBy('product_variation_id')
            ->selectRaw('product_variation_id, SUM(display_quantity) as total')
            ->pluck('total', 'product_variation_id');

        return [
            'data' => collect($stockEntries->items())->map(function (StockEntry $entry) use ($displayTotals) {
                $displayTotal = (int) ($displayTotals[$entry->product_variation_id] ?? 0);

                return [
                    'id' => $entry->id,
                    'product_id' => $entry->product_id,
                    'product_name' => $entry->product?->name,
                    'product_variation_id' => $entry->product_variation_id,
                    'variation_name' => $entry->variation?->name,
                    'quantity' => (int) $entry->quantity,
                    'display_quantity' => (int) $entry->display_quantity,
                    'available' => $displayTotal,
                    'is_low_stock' => $displayTotal > 0 && $displayTotal <= self::LOW_STOCK_THRESHOLD,
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
                'low_stock_threshold' => self::LOW_STOCK_THRESHOLD,
            ],
        ];
    }

    public function showStock(StockEntry $stock, int $tenantId): array
    {
        $this->ensureStockTenant($stock, $tenantId);

        return [
            'id' => $stock->id,
            'product_id' => $stock->product_id,
            'product_variation_id' => $stock->product_variation_id,
            'quantity' => (int) $stock->quantity,
            'display_quantity' => (int) $stock->display_quantity,
            'manufacturing_date' => optional($stock->manufacturing_date)->format('Y-m-d'),
            'expiry_date' => optional($stock->expiry_date)->format('Y-m-d'),
            'purchasing_price' => (float) $stock->purchasing_price,
            'local_selling_price' => (float) $stock->local_selling_price,
            'foreign_selling_price' => (float) $stock->foreign_selling_price,
        ];
    }

    public function storeStock(int $tenantId, array $validated): array
    {
        $product = Product::query()->where('tenant_id', $tenantId)->findOrFail($validated['product_id']);
        $variation = ProductVariation::query()->where('tenant_id', $tenantId)->findOrFail($validated['product_variation_id']);

        if ($variation->product_id !== $product->id) {
            return [
                'error' => 'Selected variation does not belong to the selected product.',
            ];
        }

        $displayQty = (int) ($validated['display_quantity'] ?? 0);
        if ($displayQty > (int) $validated['quantity']) {
            return ['error' => 'Display quantity cannot exceed total stock quantity.'];
        }

        $stock = StockEntry::create([
            'tenant_id' => $tenantId,
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'quantity' => $validated['quantity'],
            'display_quantity' => $displayQty,
            'manufacturing_date' => $validated['manufacturing_date'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'purchasing_price' => $validated['purchasing_price'],
            'local_selling_price' => $validated['local_selling_price'],
            'foreign_selling_price' => $validated['foreign_selling_price'],
        ]);

        $this->auditService->log($tenantId, 'created', $stock, null, [
            'quantity' => (int) $stock->quantity,
            'display_quantity' => (int) $stock->display_quantity,
            'product_id' => $stock->product_id,
            'product_variation_id' => $stock->product_variation_id,
        ]);

        return [
            'stock' => $stock,
        ];
    }

    public function updateStock(StockEntry $stock, int $tenantId, array $validated): ?string
    {
        $this->ensureStockTenant($stock, $tenantId);

        $product = Product::query()->where('tenant_id', $tenantId)->findOrFail($validated['product_id']);
        $variation = ProductVariation::query()->where('tenant_id', $tenantId)->findOrFail($validated['product_variation_id']);

        if ($variation->product_id !== $product->id) {
            return 'Selected variation does not belong to the selected product.';
        }

        $newQty = (int) $validated['quantity'];
        $newDisplayQty = isset($validated['display_quantity']) ? (int) $validated['display_quantity'] : (int) $stock->display_quantity;

        if ($newDisplayQty > $newQty) {
            return 'Display quantity cannot exceed total stock quantity.';
        }

        $before = [
            'quantity' => (int) $stock->quantity,
            'display_quantity' => (int) $stock->display_quantity,
            'purchasing_price' => (float) $stock->purchasing_price,
            'local_selling_price' => (float) $stock->local_selling_price,
            'foreign_selling_price' => (float) $stock->foreign_selling_price,
        ];

        $stock->update([
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'quantity' => $newQty,
            'display_quantity' => $newDisplayQty,
            'manufacturing_date' => $validated['manufacturing_date'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'purchasing_price' => $validated['purchasing_price'],
            'local_selling_price' => $validated['local_selling_price'],
            'foreign_selling_price' => $validated['foreign_selling_price'],
        ]);

        $this->auditService->log($tenantId, 'updated', $stock, $before, [
            'quantity' => (int) $stock->quantity,
            'display_quantity' => (int) $stock->display_quantity,
            'purchasing_price' => (float) $stock->purchasing_price,
            'local_selling_price' => (float) $stock->local_selling_price,
            'foreign_selling_price' => (float) $stock->foreign_selling_price,
        ]);

        return null;
    }

    public function destroyStock(StockEntry $stock, int $tenantId): void
    {
        $this->ensureStockTenant($stock, $tenantId);

        $before = [
            'quantity' => (int) $stock->quantity,
            'display_quantity' => (int) $stock->display_quantity,
        ];

        $this->auditService->log($tenantId, 'deleted', $stock, $before, null);

        $stock->delete();
    }

    public function releaseToDisplay(StockEntry $stock, int $tenantId, int $displayQuantity): ?string
    {
        $this->ensureStockTenant($stock, $tenantId);

        if ($displayQuantity < 0) {
            return 'Display quantity cannot be negative.';
        }

        if ($displayQuantity > (int) $stock->quantity) {
            return 'Display quantity cannot exceed total stock quantity of ' . $stock->quantity . '.';
        }

        $before = ['display_quantity' => (int) $stock->display_quantity];

        $stock->update(['display_quantity' => $displayQuantity]);

        $this->auditService->log($tenantId, 'display_released', $stock, $before, [
            'display_quantity' => (int) $stock->display_quantity,
        ]);

        return null;
    }

    public function stockAuditLogs(int $tenantId): array
    {
        return $this->auditService->recent($tenantId, StockEntry::class, 100);
    }

    private function ensureProductTenant(Product $product, int $tenantId): void
    {
        if ($product->tenant_id !== $tenantId) {
            abort(404);
        }
    }

    private function ensureVariationTenant(ProductVariation $variation, int $tenantId): void
    {
        if ($variation->tenant_id !== $tenantId) {
            abort(404);
        }
    }

    private function ensureStockTenant(StockEntry $stock, int $tenantId): void
    {
        if ($stock->tenant_id !== $tenantId) {
            abort(404);
        }
    }
}

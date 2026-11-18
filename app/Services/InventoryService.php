<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\SaleItem;
use App\Models\StockEntry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    private const LOW_STOCK_THRESHOLD = 5;

    private const UNTRACKED_PRICING_FIELDS = ['untracked_local_price', 'untracked_foreign_price', 'untracked_unit_cost'];

    public function __construct(
        private readonly AuditService $auditService,
        private readonly ManagerNotificationDispatcher $managerNotifications,
        private readonly MediaStorageService $media,
    ) {}

    public function meta(int $tenantId): array
    {
        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $variations = ProductVariation::query()
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
                    'label' => trim(($variation->product?->name ?? 'Product') . ' - ' . $variation->name),
                ];
            })->values(),
        ];
    }

    public function products(int $tenantId, int $perPage): array
    {
        $products = Product::query()
            ->withCount('variations')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'data' => collect($products->items())->map(function (Product $product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'track_stock' => $product->track_stock,
                    'is_active' => $product->is_active,
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
        $product->load(['variations' => fn ($query) => $query->select('id', 'product_id', 'name', 'image_path', ...self::UNTRACKED_PRICING_FIELDS)->orderBy('id')]);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'category_id' => $product->category_id,
            'track_stock' => $product->track_stock,
            'is_active' => $product->is_active,
            'variations' => $product->variations->map(function (ProductVariation $variation) {
                return [
                    'id' => $variation->id,
                    'name' => $variation->name,
                    'image_url' => $this->variationImageUrl($variation),
                    ...$this->variationPricing($variation),
                ];
            })->values(),
        ];
    }

    public function storeProduct(int $tenantId, array $validated): Product
    {
        $product = Product::create([
            'name' => $validated['name'],
            'category_id' => $validated['category_id'] ?? null,
            'track_stock' => $validated['track_stock'] ?? true,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $variations = collect($validated['variations'] ?? [])
            ->filter(fn ($variation) => filled($variation['name'] ?? null))
            ->map(fn ($variation) => array_merge($variation, ['name' => trim((string) $variation['name'])]))
            ->filter(fn ($variation) => $variation['name'] !== '')
            ->unique('name');

        foreach ($variations as $variation) {
            $product->variations()->create([
                'name' => $variation['name'],
                ...Arr::only($variation, self::UNTRACKED_PRICING_FIELDS),
            ]);
        }

        return $product;
    }

    public function updateProduct(Product $product, int $tenantId, array $validated): void
    {
        $this->ensureProductTenant($product, $tenantId);

        if (array_key_exists('variations', $validated)) {
            $incomingIds = collect($validated['variations'] ?? [])
                ->filter(fn ($variation) => filled($variation['name'] ?? null) && trim((string) $variation['name']) !== '')
                ->pluck('id')->filter()->all();
            $removedIds = $product->variations()->whereNotIn('id', $incomingIds)->pluck('id');

            if (SaleItem::query()->whereIn('product_variation_id', $removedIds)->exists()) {
                abort(422, 'Variations used in sales cannot be removed. Disable the product instead.');
            }
        }

        $attributes = ['name' => $validated['name']];

        if (array_key_exists('category_id', $validated)) {
            $attributes['category_id'] = $validated['category_id'];
        }

        foreach (['track_stock', 'is_active'] as $key) {
            if (array_key_exists($key, $validated)) {
                $attributes[$key] = $validated[$key];
            }
        }
        $product->update($attributes);

        if (!array_key_exists('variations', $validated)) {
            return;
        }

        $variationPayload = collect($validated['variations'] ?? [])
            ->filter(fn ($variation) => filled($variation['name'] ?? null))
            ->map(function ($variation) {
                return [
                    'id' => isset($variation['id']) ? (int) $variation['id'] : null,
                    'name' => trim((string) $variation['name']),
                    ...Arr::only($variation, self::UNTRACKED_PRICING_FIELDS),
                ];
            })
            ->filter(fn ($variation) => $variation['name'] !== '');

        $existingIds = $product->variations()->pluck('id')->all();
        $incomingIds = $variationPayload->pluck('id')->filter()->all();

        $idsToDelete = array_diff($existingIds, $incomingIds);

        if (!empty($idsToDelete)) {
            $imagePaths = $product->variations()->whereIn('id', $idsToDelete)->whereNotNull('image_path')->pluck('image_path');
            $product->variations()->whereIn('id', $idsToDelete)->delete();

            foreach ($imagePaths as $path) {
                $this->media->delete($path);
            }
        }

        foreach ($variationPayload as $variation) {
            if ($variation['id']) {
                $product->variations()->where('id', $variation['id'])->update([
                    'name' => $variation['name'],
                    ...Arr::only($variation, self::UNTRACKED_PRICING_FIELDS),
                ]);
                continue;
            }

            $exists = $product->variations()->where('name', $variation['name'])->exists();

            if (!$exists) {
                $product->variations()->create([
                    'name' => $variation['name'],
                    ...Arr::only($variation, self::UNTRACKED_PRICING_FIELDS),
                ]);
            }
        }
    }

    public function destroyProduct(Product $product, int $tenantId): void
    {
        $this->ensureProductTenant($product, $tenantId);

        if (SaleItem::query()->where('product_id', $product->id)->exists()) {
            abort(422, 'Products used in sales cannot be deleted. Disable the product instead.');
        }
        $imagePaths = $product->variations()->whereNotNull('image_path')->pluck('image_path');
        $product->delete();

        foreach ($imagePaths as $path) {
            $this->media->delete($path);
        }
    }

    public function variations(int $tenantId): array
    {
        $variations = ProductVariation::query()
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
                    ...$this->variationPricing($variation),
                ];
            }),
        ];
    }

    public function storeVariation(int $tenantId, array $validated): array
    {
        $product = Product::query()->findOrFail($validated['product_id']);

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
            'product_id' => $product->id,
            'name' => $validated['name'],
            ...Arr::only($validated, self::UNTRACKED_PRICING_FIELDS),
        ]);

        return [
            'variation' => $variation,
        ];
    }

    public function updateVariation(ProductVariation $variation, int $tenantId, array $validated): ?string
    {
        $this->ensureVariationTenant($variation, $tenantId);

        $product = Product::query()->findOrFail($validated['product_id']);

        if ((int) $variation->product_id !== (int) $product->id && SaleItem::query()->where('product_variation_id', $variation->id)->exists()) {
            return 'Variations used in sales cannot be moved to another product.';
        }

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
            ...Arr::only($validated, self::UNTRACKED_PRICING_FIELDS),
        ]);

        return null;
    }

    public function destroyVariation(ProductVariation $variation, int $tenantId): void
    {
        $this->ensureVariationTenant($variation, $tenantId);

        if (SaleItem::query()->where('product_variation_id', $variation->id)->exists()) {
            abort(422, 'Variations used in sales cannot be deleted. Disable the product instead.');
        }
        $path = $variation->image_path;
        $variation->delete();

        if ($path) {
            $this->media->delete($path);
        }
    }

    public function variationImageUrl(ProductVariation $variation): ?string
    {
        return $variation->image_path ? $this->media->url($variation->image_path) : null;
    }

    private function variationPricing(ProductVariation $variation): array
    {
        $prices = [];

        foreach (self::UNTRACKED_PRICING_FIELDS as $field) {
            $prices[$field] = $variation->$field !== null ? (float) $variation->$field : null;
        }

        return $prices;
    }

    public function uploadVariationImage(ProductVariation $variation, int $tenantId, UploadedFile $file): ?string
    {
        $this->ensureVariationTenant($variation, $tenantId);
        $newPath = $this->media->store($file, 'product-variation-images');

        if ($newPath === '') {
            throw new \RuntimeException('Unable to store variation image.');
        }

        try {
            $oldPath = DB::transaction(function () use ($variation, $newPath) {
                $locked = ProductVariation::query()->lockForUpdate()->findOrFail($variation->id);
                $oldPath = $locked->image_path;
                $locked->update(['image_path' => $newPath]);

                return $oldPath;
            });
        } catch (\Throwable $exception) {
            $this->media->delete($newPath);

            throw $exception;
        }

        if ($oldPath && $oldPath !== $newPath) {
            $this->media->delete($oldPath);
        }

        return $this->media->url($newPath);
    }

    public function deleteVariationImage(ProductVariation $variation, int $tenantId): void
    {
        $this->ensureVariationTenant($variation, $tenantId);
        $path = DB::transaction(function () use ($variation) {
            $locked = ProductVariation::query()->lockForUpdate()->findOrFail($variation->id);
            $path = $locked->image_path;
            $locked->update(['image_path' => null]);

            return $path;
        });

        if ($path) {
            $this->media->delete($path);
        }
    }

    public function stock(int $tenantId, int $perPage): array
    {
        $today = Carbon::today()->toDateString();

        $stockEntries = StockEntry::query()
            ->with(['product:id,name', 'variation:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $displayTotals = StockEntry::query()
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
        $product = Product::query()->findOrFail($validated['product_id']);
        $variation = ProductVariation::query()->findOrFail($validated['product_variation_id']);

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

        $this->managerNotifications->dispatch($tenantId, 'stock.created', "Stock entry #{$stock->id} was added.", [
            'Product' => $product->name,
            'Variation' => $variation->name,
            'Quantity' => (int) $stock->quantity,
            'Display quantity' => (int) $stock->display_quantity,
        ]);

        return [
            'stock' => $stock,
        ];
    }

    public function updateStock(StockEntry $stock, int $tenantId, array $validated): ?string
    {
        $this->ensureStockTenant($stock, $tenantId);

        $product = Product::query()->findOrFail($validated['product_id']);
        $variation = ProductVariation::query()->findOrFail($validated['product_variation_id']);

        if ($variation->product_id !== $product->id) {
            return 'Selected variation does not belong to the selected product.';
        }

        $result = DB::transaction(function () use ($stock, $tenantId, $validated, $product, $variation) {
            $stock = StockEntry::query()->lockForUpdate()->findOrFail($stock->id);
            $this->ensureStockTenant($stock, $tenantId);

            if (((int) $stock->product_id !== (int) $product->id || (int) $stock->product_variation_id !== (int) $variation->id)
                && $this->hasMutableSaleAllocation($stock)) {
                return ['error' => 'Stock entries referenced by an unpaid sale cannot be reassigned.'];
            }

            $newQty = (int) $validated['quantity'];
            $newDisplayQty = isset($validated['display_quantity']) ? (int) $validated['display_quantity'] : (int) $stock->display_quantity;

            if ($newDisplayQty > $newQty) {
                return ['error' => 'Display quantity cannot exceed total stock quantity.'];
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

            return ['stock' => $stock, 'before' => $before];
        }, 3);

        if (isset($result['error'])) {
            return $result['error'];
        }

        $stock = $result['stock'];
        $before = $result['before'];
        $this->managerNotifications->dispatch($tenantId, 'stock.updated', "Stock entry #{$stock->id} was edited.", [
            'Product' => $product->name,
            'Variation' => $variation->name,
            'Previous quantity' => $before['quantity'],
            'New quantity' => (int) $stock->quantity,
            'Previous display quantity' => $before['display_quantity'],
            'New display quantity' => (int) $stock->display_quantity,
        ]);

        return null;
    }

    public function destroyStock(StockEntry $stock, int $tenantId): void
    {
        $this->ensureStockTenant($stock, $tenantId);

        $details = DB::transaction(function () use ($stock, $tenantId) {
            $stock = StockEntry::query()->lockForUpdate()->findOrFail($stock->id);
            $this->ensureStockTenant($stock, $tenantId);

            if ($this->hasMutableSaleAllocation($stock)) {
                abort(422, 'Stock entries referenced by an unpaid sale cannot be deleted.');
            }

            $before = [
                'quantity' => (int) $stock->quantity,
                'display_quantity' => (int) $stock->display_quantity,
            ];
            $product = Product::query()->find($stock->product_id);
            $variation = ProductVariation::query()->find($stock->product_variation_id);

            $this->auditService->log($tenantId, 'deleted', $stock, $before, null);

            $stock->delete();

            return [
                'Product' => $product?->name ?? 'Unknown product',
                'Variation' => $variation?->name ?? 'Unknown variation',
                'Quantity' => $before['quantity'],
                'Display quantity' => $before['display_quantity'],
            ];
        }, 3);

        $this->managerNotifications->dispatch($tenantId, 'stock.deleted', "Stock entry #{$stock->id} was deleted.", $details);
    }

    private function hasMutableSaleAllocation(StockEntry $stock): bool
    {
        // A locking read sees committed allocations after waiting for the stock lock,
        // even under MySQL's repeatable-read isolation. Decode in PHP so SQLite and
        // MySQL agree on JSON object matching and numeric/string IDs.
        $items = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereNull('sales.deleted_at')
            ->where('sales.is_paid', false)
            ->where('sale_items.product_variation_id', $stock->product_variation_id)
            ->whereNotNull('sale_items.stock_allocations')
            ->select('sale_items.stock_allocations')
            ->lockForUpdate()
            ->cursor();

        foreach ($items as $item) {
            foreach ($item->stock_allocations ?? [] as $allocation) {
                if (isset($allocation['stock_entry_id']) && (string) $allocation['stock_entry_id'] === (string) $stock->id) {
                    return true;
                }
            }
        }

        return false;
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
        $product = Product::query()->find($stock->product_id);
        $variation = ProductVariation::query()->find($stock->product_variation_id);

        $stock->update(['display_quantity' => $displayQuantity]);

        $this->auditService->log($tenantId, 'display_released', $stock, $before, [
            'display_quantity' => (int) $stock->display_quantity,
        ]);

        if ($before['display_quantity'] !== (int) $stock->display_quantity) {
            $this->managerNotifications->dispatch($tenantId, 'display.released', "Display quantity for stock entry #{$stock->id} was changed.", [
                'Product' => $product?->name ?? 'Unknown product',
                'Variation' => $variation?->name ?? 'Unknown variation',
                'Previous display quantity' => $before['display_quantity'],
                'New display quantity' => (int) $stock->display_quantity,
            ]);
        }

        return null;
    }

    public function stockAuditLogs(int $tenantId): array
    {
        return $this->auditService->recent($tenantId, StockEntry::class, 100);
    }

    private function ensureProductTenant(Product $product, int $tenantId): void {}

    private function ensureVariationTenant(ProductVariation $variation, int $tenantId): void {}

    private function ensureStockTenant(StockEntry $stock, int $tenantId): void {}
}

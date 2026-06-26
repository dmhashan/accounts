<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\StockEntry;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryApiController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function meta(): JsonResponse
    {
        return response()->json($this->inventoryService->meta(app('tenant')->id));
    }

    public function products(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 10), 50);

        return response()->json($this->inventoryService->products(app('tenant')->id, $perPage));
    }

    public function showProduct(Product $product): JsonResponse
    {
        return response()->json([
            'data' => $this->inventoryService->showProduct($product, app('tenant')->id),
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
                Rule::unique('products'),
            ],
            'variations' => ['nullable', 'array'],
            'variations.*.name' => ['nullable', 'string', 'max:255'],
        ]);

        $product = $this->inventoryService->storeProduct($tenantId, $validated);

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
        $tenantId = app('tenant')->id;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products')
                    ->ignore($product->id),
            ],
            'variations' => ['nullable', 'array'],
            'variations.*.id' => ['nullable', 'integer'],
            'variations.*.name' => ['nullable', 'string', 'max:255'],
        ]);

        $this->inventoryService->updateProduct($product, $tenantId, $validated);

        return response()->json([
            'message' => 'Product updated successfully.',
        ]);
    }

    public function destroyProduct(Product $product): JsonResponse
    {
        $this->inventoryService->destroyProduct($product, app('tenant')->id);

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }

    public function variations(): JsonResponse
    {
        return response()->json($this->inventoryService->variations(app('tenant')->id));
    }

    public function storeVariation(Request $request): JsonResponse
    {
        $tenantId = app('tenant')->id;

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $result = $this->inventoryService->storeVariation($tenantId, $validated);

        if (isset($result['error'])) {
            return response()->json([
                'message' => $result['error'],
            ], 422);
        }

        $variation = $result['variation'];

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
        $tenantId = app('tenant')->id;

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $error = $this->inventoryService->updateVariation($variation, $tenantId, $validated);

        if ($error) {
            return response()->json([
                'message' => $error,
            ], 422);
        }

        return response()->json([
            'message' => 'Variation updated successfully.',
        ]);
    }

    public function destroyVariation(ProductVariation $variation): JsonResponse
    {
        $this->inventoryService->destroyVariation($variation, app('tenant')->id);

        return response()->json([
            'message' => 'Variation deleted successfully.',
        ]);
    }

    public function stock(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 10), 50);

        return response()->json($this->inventoryService->stock(app('tenant')->id, $perPage));
    }

    public function showStock(StockEntry $stock): JsonResponse
    {
        return response()->json([
            'data' => $this->inventoryService->showStock($stock, app('tenant')->id),
        ]);
    }

    public function storeStock(Request $request): JsonResponse
    {
        $tenantId = app('tenant')->id;

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'product_variation_id' => ['required', 'exists:product_variations,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'display_quantity' => ['nullable', 'integer', 'min:0'],
            'manufacturing_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after:manufacturing_date'],
            'purchasing_price' => ['required', 'numeric', 'min:0'],
            'local_selling_price' => ['required', 'numeric', 'min:0'],
            'foreign_selling_price' => ['required', 'numeric', 'min:0'],
        ]);

        $result = $this->inventoryService->storeStock($tenantId, $validated);

        if (isset($result['error'])) {
            return response()->json([
                'message' => $result['error'],
            ], 422);
        }

        $stock = $result['stock'];

        return response()->json([
            'message' => 'Stock added successfully.',
            'data' => [
                'id' => $stock->id,
            ],
        ], 201);
    }

    public function updateStock(Request $request, StockEntry $stock): JsonResponse
    {
        $tenantId = app('tenant')->id;

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'product_variation_id' => ['required', 'exists:product_variations,id'],
            'quantity' => ['required', 'integer', 'min:0'],
            'display_quantity' => ['nullable', 'integer', 'min:0'],
            'manufacturing_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after:manufacturing_date'],
            'purchasing_price' => ['required', 'numeric', 'min:0'],
            'local_selling_price' => ['required', 'numeric', 'min:0'],
            'foreign_selling_price' => ['required', 'numeric', 'min:0'],
        ]);

        $error = $this->inventoryService->updateStock($stock, $tenantId, $validated);

        if ($error) {
            return response()->json([
                'message' => $error,
            ], 422);
        }

        return response()->json([
            'message' => 'Stock updated successfully.',
        ]);
    }

    public function destroyStock(StockEntry $stock): JsonResponse
    {
        $this->inventoryService->destroyStock($stock, app('tenant')->id);

        return response()->json([
            'message' => 'Stock entry deleted successfully.',
        ]);
    }

    public function display(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 10), 50);

        return response()->json($this->inventoryService->stock(app('tenant')->id, $perPage));
    }

    public function releaseToDisplay(Request $request, StockEntry $stock): JsonResponse
    {
        $validated = $request->validate([
            'display_quantity' => ['required', 'integer', 'min:0'],
        ]);

        $error = $this->inventoryService->releaseToDisplay(
            $stock,
            app('tenant')->id,
            (int) $validated['display_quantity'],
        );

        if ($error) {
            return response()->json(['message' => $error], 422);
        }

        return response()->json([
            'message' => 'Display quantity updated successfully.',
            'data' => [
                'id' => $stock->id,
                'display_quantity' => (int) $stock->display_quantity,
            ],
        ]);
    }

    public function auditLogs(): JsonResponse
    {
        $logs = $this->inventoryService->stockAuditLogs(app('tenant')->id);

        return response()->json(['data' => $logs]);
    }
}

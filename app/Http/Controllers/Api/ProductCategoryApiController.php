<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductCategoryApiController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => ProductCategory::query()->orderBy('name')->get(['id', 'name', 'color', 'icon'])]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateCategory($request);
        $category = DB::transaction(function () use ($validated) {
            $category = ProductCategory::create($validated);
            $this->audit->log(app('tenant')->id, 'created', $category, null, $category->only(['name', 'color', 'icon']));

            return $category;
        });

        return response()->json(['data' => $category->only(['id', 'name', 'color', 'icon'])], 201);
    }

    public function update(Request $request, ProductCategory $category): JsonResponse
    {
        $validated = $this->validateCategory($request, $category);
        DB::transaction(function () use ($category, $validated) {
            $before = $category->only(['name', 'color', 'icon']);
            $category->update($validated);
            $this->audit->log(app('tenant')->id, 'updated', $category, $before, $category->only(['name', 'color', 'icon']));
        });

        return response()->json(['data' => $category->only(['id', 'name', 'color', 'icon'])]);
    }

    public function destroy(ProductCategory $category): JsonResponse
    {
        DB::transaction(function () use ($category) {
            $before = $category->only(['name', 'color', 'icon']);
            $category->delete();
            $this->audit->log(app('tenant')->id, 'deleted', $category, $before);
        });

        return response()->json(['message' => 'Category deleted successfully.']);
    }

    private function validateCategory(Request $request, ?ProductCategory $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('product_categories')->ignore($category?->id)],
            'color' => ['required', 'string', 'max:30'],
            'icon' => ['required', 'string', 'max:50'],
        ]);
    }
}

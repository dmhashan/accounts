<?php

namespace Tests\Feature\Api;

use App\Models\ProductCategory;
use PHPUnit\Framework\Attributes\DataProvider;

class InventoryProductFilterTest extends ApiRouteTestCase
{
    public static function inventoryEndpoints(): array
    {
        return [
            'products' => ['/api/inventory/products', 'inventory.manage', true],
            'stock' => ['/api/inventory/stock', 'inventory.stock', false],
            'display' => ['/api/inventory/display', 'inventory.display', false],
        ];
    }

    #[DataProvider('inventoryEndpoints')]
    public function testFiltersApplyBeforePagination(string $endpoint, string $permission, bool $products): void
    {
        $this->actingAsUser([$permission]);
        $category = ProductCategory::create(['name' => 'Gear', 'color' => 'red', 'icon' => 'Tag']);
        $otherCategory = ProductCategory::create(['name' => 'Food', 'color' => 'blue', 'icon' => 'Box']);

        $first = $this->createProduct(['category_id' => $category->id]);
        $second = $this->createProduct(['category_id' => $category->id]);
        $uncategorized = $this->createProduct(['category_id' => null]);
        $other = $this->createProduct(['category_id' => $otherCategory->id]);
        $disabled = $this->createProduct(['category_id' => $category->id, 'is_active' => false]);
        $disabledNone = $this->createProduct(['category_id' => null, 'is_active' => false]);

        $records = [];

        foreach ([$first, $second, $uncategorized, $other, $disabled, $disabledNone] as $product) {
            $product->refresh();
            $variation = $this->createVariation($product);
            $stock = $this->createStockEntry($product, $variation);
            $records[$product->id] = $products ? $product->id : $stock->id;
        }

        $cases = [
            null => [$first, $second, $uncategorized, $other],
            'all' => [$first, $second, $uncategorized, $other],
            'none' => [$uncategorized],
            'disabled' => [$disabled, $disabledNone],
            'category:' . $category->id => [$first, $second],
            'category:' . $otherCategory->id => [$other],
            'category:999999' => [],
        ];

        foreach ($cases as $filter => $expectedProducts) {
            $expectedIds = array_map(fn ($product) => $records[$product->id], $expectedProducts);
            $actualIds = [];
            $pages = max(1, count($expectedIds));

            for ($page = 1; $page <= $pages; $page++) {
                $params = ['per_page' => 1, 'page' => $page];

                if ($filter !== '') {
                    $params['product_filter'] = $filter;
                }
                $response = $this->getJson($endpoint . '?' . http_build_query($params))
                    ->assertOk()
                    ->assertJsonPath('meta.total', count($expectedIds))
                    ->assertJsonPath('meta.last_page', $pages);

                foreach ($response->json('data') as $row) {
                    $actualIds[] = $row['id'];
                    $productId = $products ? $row['id'] : $row['product_id'];
                    $product = collect($expectedProducts)->firstWhere('id', $productId);
                    $this->assertNotNull($product);
                    $this->assertSame($product->category_id, $row['category_id']);
                    $this->assertSame($product->is_active, $row['is_active']);
                }
            }

            sort($expectedIds);
            sort($actualIds);
            $this->assertSame($expectedIds, $actualIds, 'Filter: ' . ($filter ?: 'default'));
        }
    }

    #[DataProvider('inventoryEndpoints')]
    public function testInvalidFiltersAreRejected(string $endpoint, string $permission, bool $products): void
    {
        $this->actingAsUser([$permission]);

        foreach (['', 'active', 'category:', 'category:0', 'category:-1', 'category:1extra', 'category:1\n', ['all']] as $filter) {
            $this->getJson($endpoint . '?' . http_build_query(['product_filter' => $filter]))
                ->assertUnprocessable()->assertJsonValidationErrors('product_filter');
        }
    }

    #[DataProvider('stockEndpoints')]
    public function testAvailabilityUsesAllUnexpiredBatchesAcrossPages(string $endpoint, string $permission): void
    {
        $this->actingAsUser([$permission]);
        $category = ProductCategory::create(['name' => 'Gear', 'color' => 'red', 'icon' => 'Tag']);
        $product = $this->createProduct(['category_id' => $category->id]);
        $variation = $this->createVariation($product);
        $this->createStockEntry($product, $variation, ['display_quantity' => 2, 'expiry_date' => null]);
        $this->createStockEntry($product, $variation, ['display_quantity' => 3, 'expiry_date' => now()->addDay()->toDateString()]);
        $this->createStockEntry($product, $variation, ['display_quantity' => 10, 'expiry_date' => now()->subDay()->toDateString()]);

        foreach (['all', 'category:' . $category->id] as $filter) {
            for ($page = 1; $page <= 3; $page++) {
                $this->getJson($endpoint . '?' . http_build_query(['product_filter' => $filter, 'per_page' => 1, 'page' => $page]))
                    ->assertOk()->assertJsonPath('meta.total', 3)
                    ->assertJsonPath('meta.low_stock_threshold', 5)
                    ->assertJsonPath('data.0.available', 5)
                    ->assertJsonPath('data.0.is_low_stock', true);
            }
        }

        $product->update(['is_active' => false]);
        $this->getJson($endpoint . '?product_filter=disabled&per_page=1')
            ->assertOk()->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.available', 5)->assertJsonPath('data.0.is_low_stock', true);
    }

    public static function stockEndpoints(): array
    {
        return [
            'stock' => ['/api/inventory/stock', 'inventory.stock'],
            'display' => ['/api/inventory/display', 'inventory.display'],
        ];
    }

    public function testCategoryFiltersRequireAnyInventoryPermissionWithoutExpandingOtherPermissions(): void
    {
        $first = ProductCategory::create(['name' => 'Alpha', 'color' => 'red', 'icon' => 'Tag']);
        $second = ProductCategory::create(['name' => 'Zeta', 'color' => 'blue', 'icon' => 'Box']);
        $this->getJson('/api/inventory/category-filters')->assertUnauthorized();

        foreach (['inventory.manage', 'inventory.stock', 'inventory.display'] as $permission) {
            $this->actingAsUser([$permission]);
            $this->getJson('/api/inventory/category-filters')->assertOk()->assertExactJson(['data' => [
                ['id' => $first->id, 'name' => 'Alpha', 'color' => 'red', 'icon' => 'Tag'],
                ['id' => $second->id, 'name' => 'Zeta', 'color' => 'blue', 'icon' => 'Box'],
            ]]);

            if ($permission !== 'inventory.manage') {
                $this->getJson('/api/inventory/categories')->assertForbidden();
                $this->getJson('/api/inventory/products')->assertForbidden();
                $this->postJson('/api/inventory/categories', [])->assertForbidden();
            }

            if ($permission !== 'inventory.stock') {
                $this->getJson('/api/inventory/stock')->assertForbidden();
            }

            if ($permission !== 'inventory.display') {
                $this->getJson('/api/inventory/display')->assertForbidden();
            }
        }

        $this->actingAsUser(['sales.process']);
        $this->getJson('/api/inventory/category-filters')->assertForbidden();
    }
}

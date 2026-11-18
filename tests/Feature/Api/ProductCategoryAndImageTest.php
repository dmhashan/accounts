<?php

namespace Tests\Feature\Api;

use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\InitializeTenantConnection;
use App\Models\ProductCategory;
use App\Models\ProductVariation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ProductCategoryAndImageTest extends ApiRouteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.media_disk' => 'public', 'filesystems.disks.public.url' => 'http://localhost/storage']);
        Storage::fake('public');
    }

    public function testCategoriesCanBeManagedAndDeletingOneUnassignsProducts(): void
    {
        $this->actingAsUser(['inventory.manage']);
        $category = ['name' => 'Supplements', 'color' => 'blue', 'icon' => 'Package'];
        $id = $this->postJson('/api/inventory/categories', $category)
            ->assertCreated()->assertJsonPath('data.name', 'Supplements')->json('data.id');

        $this->getJson('/api/inventory/categories')->assertOk()->assertExactJson(['data' => [array_merge(['id' => $id], $category)]]);
        $this->postJson('/api/inventory/categories', $category)->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->putJson('/api/inventory/categories/' . $id, ['name' => 'Nutrition', 'color' => 'red', 'icon' => 'Apple'])
            ->assertOk()->assertJsonPath('data.name', 'Nutrition');

        $product = $this->createProduct(['category_id' => $id]);
        $this->deleteJson('/api/inventory/categories/' . $id)->assertOk();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'category_id' => null]);
        $this->assertDatabaseMissing('product_categories', ['id' => $id]);
        $this->assertDatabaseHas('audit_logs', ['auditable_type' => ProductCategory::class, 'auditable_id' => $id, 'action' => 'deleted']);
    }

    public function testProductAssignmentsAndVariationResponses(): void
    {
        $this->actingAsUser(['inventory.manage']);
        $category = ProductCategory::create(['name' => 'Gear', 'color' => 'slate', 'icon' => 'Tag']);

        $response = $this->postJson('/api/inventory/products', [
            'name' => 'Gloves', 'category_id' => $category->id,
            'variations' => [['name' => 'Small'], ['name' => 'Large']],
        ])->assertCreated()->assertJsonPath('data.category_id', $category->id)
            ->assertJsonPath('data.variations.0.name', 'Small')->assertJsonPath('data.variations.0.image_url', null)
            ->assertJsonPath('data.variations.1.name', 'Large');

        $id = $response->json('data.id');
        $variations = $response->json('data.variations');
        $this->getJson('/api/inventory/products/' . $id)->assertOk()->assertJsonPath('data.variations', $variations);
        $this->putJson('/api/inventory/products/' . $id, [
            'name' => 'Gloves', 'category_id' => null, 'variations' => $variations,
        ])->assertOk()->assertJsonPath('data.category_id', null)->assertJsonPath('data.variations', $variations);
        $this->putJson('/api/inventory/products/' . $id, [
            'name' => 'Gloves', 'category_id' => 99999, 'variations' => $variations,
        ])->assertUnprocessable()->assertJsonValidationErrors('category_id');
    }

    public function testImageValidationReplacementDeletionAndProductResponse(): void
    {
        $this->actingAsUser(['inventory.manage']);
        $product = $this->createProduct();
        $variation = $this->createVariation($product);
        $url = '/api/inventory/variations/' . $variation->id . '/image';

        $this->postJson($url, ['image' => UploadedFile::fake()->image('wrong.png', 100, 101)])
            ->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->postJson($url, ['image' => UploadedFile::fake()->create('file.txt', 10, 'text/plain')])
            ->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->postJson($url, ['image' => UploadedFile::fake()->image('large.jpg', 100, 100)->size(2049)])
            ->assertUnprocessable()->assertJsonValidationErrors('image');

        $firstUrl = $this->postJson($url, ['image' => UploadedFile::fake()->image('one.jpg', 100, 100)])
            ->assertOk()->json('data.image_url');
        $oldPath = $variation->fresh()->image_path;
        $this->assertTrue(Storage::disk('public')->exists($oldPath));
        $this->getJson('/api/inventory/products/' . $product->id)->assertJsonPath('data.variations.0.image_url', $firstUrl);
        $this->postJson($url, ['image' => UploadedFile::fake()->image('invalid.png', 101, 100)])->assertUnprocessable();
        $this->assertSame($oldPath, $variation->fresh()->image_path);
        $this->assertTrue(Storage::disk('public')->exists($oldPath));

        $newUrl = $this->postJson($url, ['image' => UploadedFile::fake()->image('two.png', 100, 100)])
            ->assertOk()->json('data.image_url');
        $newPath = $variation->fresh()->image_path;
        $this->assertNotSame($oldPath, $newPath);
        $this->assertNotSame($firstUrl, $newUrl);
        $this->assertFalse(Storage::disk('public')->exists($oldPath));
        $this->assertTrue(Storage::disk('public')->exists($newPath));

        $this->deleteJson($url)->assertOk()->assertJsonPath('data.image_url', null);
        $this->deleteJson($url)->assertOk()->assertJsonPath('data.image_url', null);
        $this->assertFalse(Storage::disk('public')->exists($newPath));
        $this->assertNull($variation->fresh()->image_path);
    }

    public function testWebpImagesAreAccepted(): void
    {
        if (!function_exists('imagewebp')) {
            $this->markTestSkipped('GD WebP support is not available.');
        }
        $this->actingAsUser(['inventory.manage']);
        $variation = $this->createVariation($this->createProduct());
        $this->postJson('/api/inventory/variations/' . $variation->id . '/image', [
            'image' => UploadedFile::fake()->image('image.webp', 100, 100),
        ])->assertOk()->assertJsonStructure(['data' => ['image_url']]);
    }

    public function testFailedImageDatabaseUpdatePreservesOldFileAndRemovesNewFile(): void
    {
        $this->actingAsUser(['inventory.manage']);
        $variation = $this->createVariation($this->createProduct());
        $url = '/api/inventory/variations/' . $variation->id . '/image';
        $this->postJson($url, ['image' => UploadedFile::fake()->image('one.jpg', 100, 100)])->assertOk();
        $oldPath = $variation->fresh()->image_path;
        ProductVariation::updating(function () {
            throw new \RuntimeException('Simulated database failure');
        });

        try {
            $this->postJson($url, ['image' => UploadedFile::fake()->image('two.png', 100, 100)])->assertServerError();
            $this->assertSame($oldPath, $variation->fresh()->image_path);
            $this->assertSame([$oldPath], Storage::disk('public')->allFiles('testing/' . $this->tenant->tenant_uuid . '/product-variation-images'));
        } finally {
            ProductVariation::flushEventListeners();
        }
    }

    public function testRemovedVariationsAndProductsCleanUpImages(): void
    {
        $this->actingAsUser(['inventory.manage']);
        $product = $this->createProduct();
        $variation = $this->createVariation($product);
        $url = '/api/inventory/variations/' . $variation->id . '/image';
        $this->postJson($url, ['image' => UploadedFile::fake()->image('one.jpg', 100, 100)])->assertOk();
        $path = $variation->fresh()->image_path;
        $this->putJson('/api/inventory/products/' . $product->id, ['name' => $product->name, 'variations' => []])->assertOk();
        $this->assertFalse(Storage::disk('public')->exists($path));

        $variation = $this->createVariation($product);
        $this->postJson('/api/inventory/variations/' . $variation->id . '/image', ['image' => UploadedFile::fake()->image('two.jpg', 100, 100)])->assertOk();
        $path = $variation->fresh()->image_path;
        $this->deleteJson('/api/inventory/products/' . $product->id)->assertOk();
        $this->assertFalse(Storage::disk('public')->exists($path));
    }

    public function testSalesMetaIncludesCategoriesAndVariationImagesWithoutInventoryPermission(): void
    {
        $this->actingAsUser(['sales.process']);
        $category = ProductCategory::create(['name' => 'Gear', 'color' => 'red', 'icon' => 'Tag']);
        $product = $this->createProduct(['category_id' => $category->id]);
        $variation = $this->createVariation($product, ['image_path' => 'testing/example/image.jpg']);
        $this->createStockEntry($product, $variation, ['display_quantity' => 3, 'local_selling_price' => 120]);

        $response = $this->getJson('/api/sales/meta')->assertOk()
            ->assertJsonPath('categories.0', ['id' => $category->id, 'name' => 'Gear', 'color' => 'red', 'icon' => 'Tag'])
            ->assertJsonPath('variations.0.category_id', $category->id)
            ->assertJsonPath('variations.0.available_stock', 3)
            ->assertJsonPath('variations.0.prices.local', 120);
        $this->assertStringContainsString('testing/example/image.jpg', $response->json('variations.0.image_url'));
    }

    public function testCategoryAndImageRoutesRequireInventoryPermission(): void
    {
        $this->actingAsUser(['sales.process']);
        $category = ProductCategory::create(['name' => 'Gear', 'color' => 'red', 'icon' => 'Tag']);
        $variation = $this->createVariation($this->createProduct());
        $this->getJson('/api/inventory/categories')->assertForbidden();
        $this->postJson('/api/inventory/categories', [])->assertForbidden();
        $this->putJson('/api/inventory/categories/' . $category->id, [])->assertForbidden();
        $this->deleteJson('/api/inventory/categories/' . $category->id)->assertForbidden();
        $this->postJson('/api/inventory/variations/' . $variation->id . '/image', [])->assertForbidden();
        $this->deleteJson('/api/inventory/variations/' . $variation->id . '/image')->assertForbidden();
    }

    public function testResourcesFromAnotherTenantDatabaseAreNotAccessible(): void
    {
        $this->actingAsUser(['inventory.manage']);
        $this->withoutMiddleware([InitializeTenantConnection::class, IdentifyTenant::class]);
        $original = DB::getDefaultConnection();
        config(['database.connections.other_tenant' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        $schema = Schema::connection('other_tenant');
        $schema->create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color');
            $table->string('icon');
            $table->timestamps();
        });
        $schema->create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('name');
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
        DB::connection('other_tenant')->table('product_categories')->insert(['id' => 999, 'name' => 'Other', 'color' => 'red', 'icon' => 'Tag']);
        DB::connection('other_tenant')->table('product_variations')->insert(['id' => 999, 'product_id' => 1, 'name' => 'Other']);

        try {
            $this->putJson('/api/inventory/categories/999', ['name' => 'Other', 'color' => 'red', 'icon' => 'Tag'])->assertNotFound();
            $this->deleteJson('/api/inventory/categories/999')->assertNotFound();
            $this->postJson('/api/inventory/variations/999/image', ['image' => UploadedFile::fake()->image('one.jpg', 100, 100)])->assertNotFound();
            $this->postJson('/api/inventory/products', ['name' => 'Test', 'category_id' => 999])->assertUnprocessable()->assertJsonValidationErrors('category_id');
            $this->assertSame('Other', DB::connection('other_tenant')->table('product_categories')->where('id', 999)->value('name'));
            $this->assertNull(DB::connection('other_tenant')->table('product_variations')->where('id', 999)->value('image_path'));
            DB::setDefaultConnection('other_tenant');
            $this->getJson('/api/inventory/categories')->assertOk()->assertJsonPath('data.0.name', 'Other');
        } finally {
            DB::setDefaultConnection($original);
            DB::purge('other_tenant');
        }
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\ProductVariation;

class InventoryApiTest extends ApiRouteTestCase
{
    public function testInventoryMetaRouteReturnsProductsAndVariations(): void
    {
        $this->actingAsUser(['inventory.manage']);
        $product = $this->createProduct();
        $this->createVariation($product);

        $response = $this->getJson('/api/inventory/meta');

        $response
            ->assertOk()
            ->assertJsonStructure(['products', 'variations']);
    }

    public function testInventoryProductRoutesCoverListShowCreateUpdateAndDelete(): void
    {
        $this->actingAsUser(['inventory.manage']);

        $existingProduct = $this->createProduct(['name' => 'Existing Product']);
        $this->createVariation($existingProduct, ['name' => 'Existing Variation']);

        $this->getJson('/api/inventory/products')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);

        $this->getJson('/api/inventory/products/' . $existingProduct->id)
            ->assertOk()
            ->assertJsonPath('data.id', $existingProduct->id);

        $storeResponse = $this->postJson('/api/inventory/products', [
            'name' => 'Protein Powder',
            'variations' => [
                ['name' => 'Small'],
                ['name' => 'Large'],
            ],
        ]);

        $storeResponse
            ->assertCreated()
            ->assertJsonPath('message', 'Product created successfully.');

        $productId = (int) $storeResponse->json('data.id');
        $storedProduct = Product::findOrFail($productId);
        $storedVariationId = ProductVariation::where('product_id', $storedProduct->id)->value('id');

        $this->putJson('/api/inventory/products/' . $storedProduct->id, [
            'name' => 'Protein Powder Updated',
            'variations' => [
                ['id' => $storedVariationId, 'name' => 'Medium'],
            ],
        ])->assertOk()->assertJsonPath('message', 'Product updated successfully.');

        $this->deleteJson('/api/inventory/products/' . $storedProduct->id)
            ->assertOk()
            ->assertJsonPath('message', 'Product deleted successfully.');
    }

    public function testInventoryVariationRoutesCoverListCreateUpdateAndDelete(): void
    {
        $this->actingAsUser(['inventory.manage']);

        $product = $this->createProduct(['name' => 'Variation Product']);
        $existingVariation = $this->createVariation($product, ['name' => 'Flavor A']);

        $this->getJson('/api/inventory/variations')
            ->assertOk()
            ->assertJsonStructure(['data']);

        $storeResponse = $this->postJson('/api/inventory/variations', [
            'product_id' => $product->id,
            'name' => 'Flavor B',
        ]);

        $storeResponse
            ->assertCreated()
            ->assertJsonPath('message', 'Variation created successfully.');

        $createdVariationId = (int) $storeResponse->json('data.id');

        $this->putJson('/api/inventory/variations/' . $createdVariationId, [
            'product_id' => $product->id,
            'name' => 'Flavor C',
        ])->assertOk()->assertJsonPath('message', 'Variation updated successfully.');

        $this->deleteJson('/api/inventory/variations/' . $existingVariation->id)
            ->assertOk()
            ->assertJsonPath('message', 'Variation deleted successfully.');
    }

    public function testInventoryStockRoutesCoverListShowCreateUpdateAndDelete(): void
    {
        $this->actingAsUser(['inventory.manage', 'inventory.stock']);

        $product = $this->createProduct(['name' => 'Stock Product']);
        $variation = $this->createVariation($product, ['name' => 'Stock Variation']);
        $existingStock = $this->createStockEntry($product, $variation, ['quantity' => 12]);

        $this->getJson('/api/inventory/stock')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);

        $this->getJson('/api/inventory/stock/' . $existingStock->id)
            ->assertOk()
            ->assertJsonPath('data.id', $existingStock->id);

        $storeResponse = $this->postJson('/api/inventory/stock', [
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'quantity' => 20,
            'manufacturing_date' => now()->subDay()->toDateString(),
            'expiry_date' => now()->addDays(30)->toDateString(),
            'purchasing_price' => 100,
            'local_selling_price' => 150,
            'foreign_selling_price' => 180,
        ]);

        $storeResponse
            ->assertCreated()
            ->assertJsonPath('message', 'Stock added successfully.');

        $createdStockId = (int) $storeResponse->json('data.id');

        $this->putJson('/api/inventory/stock/' . $createdStockId, [
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'quantity' => 10,
            'manufacturing_date' => now()->subDays(2)->toDateString(),
            'expiry_date' => now()->addDays(20)->toDateString(),
            'purchasing_price' => 110,
            'local_selling_price' => 160,
            'foreign_selling_price' => 190,
        ])->assertOk()->assertJsonPath('message', 'Stock updated successfully.');

        $this->deleteJson('/api/inventory/stock/' . $createdStockId)
            ->assertOk()
            ->assertJsonPath('message', 'Stock entry deleted successfully.');
    }
}

<?php

use App\Http\Controllers\Api\InventoryApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('/inventory')->middleware('auth')->group(function () {

    // Products & variations — requires inventory.manage
    Route::middleware('permission:inventory.manage')->group(function () {
        Route::get('/meta', [InventoryApiController::class, 'meta']);

        Route::get('/products', [InventoryApiController::class, 'products']);
        Route::get('/products/{product}', [InventoryApiController::class, 'showProduct']);
        Route::post('/products', [InventoryApiController::class, 'storeProduct']);
        Route::put('/products/{product}', [InventoryApiController::class, 'updateProduct']);
        Route::delete('/products/{product}', [InventoryApiController::class, 'destroyProduct']);

        Route::get('/variations', [InventoryApiController::class, 'variations']);
        Route::post('/variations', [InventoryApiController::class, 'storeVariation']);
        Route::put('/variations/{variation}', [InventoryApiController::class, 'updateVariation']);
        Route::delete('/variations/{variation}', [InventoryApiController::class, 'destroyVariation']);
    });

    // Stock entries — requires inventory.stock
    Route::middleware('permission:inventory.stock')->group(function () {
        Route::get('/stock', [InventoryApiController::class, 'stock']);
        Route::get('/stock/{stock}', [InventoryApiController::class, 'showStock']);
        Route::post('/stock', [InventoryApiController::class, 'storeStock']);
        Route::put('/stock/{stock}', [InventoryApiController::class, 'updateStock']);
        Route::delete('/stock/{stock}', [InventoryApiController::class, 'destroyStock']);
    });

    // Display management — requires inventory.display
    Route::middleware('permission:inventory.display')->group(function () {
        Route::get('/display', [InventoryApiController::class, 'display']);
        Route::post('/stock/{stock}/release', [InventoryApiController::class, 'releaseToDisplay']);
    });

    // Audit logs — requires inventory.stock or inventory.display (either is enough)
    Route::get('/audit-logs', [InventoryApiController::class, 'auditLogs'])
        ->middleware('permission:inventory.audit,inventory.stock,inventory.display');
});

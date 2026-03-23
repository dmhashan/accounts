<?php

use App\Http\Controllers\Api\SaleApiController;
use Illuminate\Support\Facades\Route;

Route::get('/sales/meta', [SaleApiController::class, 'meta'])->middleware(['auth', 'permission:sales.process']);
Route::get('/sales/member-wallet/{member}', [SaleApiController::class, 'memberWallet'])->middleware(['auth', 'permission:sales.create,sales.edit']);
Route::get('/sales', [SaleApiController::class, 'index'])->middleware(['auth', 'permission:sales.process']);
Route::post('/sales', [SaleApiController::class, 'store'])->middleware(['auth', 'permission:sales.create']);
Route::get('/sales/{sale}', [SaleApiController::class, 'show'])->middleware(['auth', 'permission:sales.edit']);
Route::post('/sales/{sale}/mark-as-paid', [SaleApiController::class, 'markAsPaid'])->middleware(['auth', 'permission:sales.edit']);
Route::put('/sales/{sale}', [SaleApiController::class, 'update'])->middleware(['auth', 'permission:sales.edit']);
Route::delete('/sales/{sale}', [SaleApiController::class, 'destroy'])->middleware(['auth', 'permission:sales.delete']);
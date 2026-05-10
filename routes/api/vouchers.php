<?php

use App\Http\Controllers\Api\VoucherApiController;
use Illuminate\Support\Facades\Route;

// Admin voucher management
Route::middleware(['auth', 'permission:vouchers.manage'])->group(function () {
    Route::get('/vouchers', [VoucherApiController::class, 'index']);
    Route::post('/vouchers', [VoucherApiController::class, 'store']);
    Route::get('/vouchers/{voucher}', [VoucherApiController::class, 'show']);
    Route::put('/vouchers/{voucher}', [VoucherApiController::class, 'update']);
    Route::delete('/vouchers/{voucher}', [VoucherApiController::class, 'destroy']);
});

// Member-level redeem (payments.manage permission — same as wallet topup)
Route::middleware(['auth', 'permission:payments.manage'])->group(function () {
    Route::post('/members/{member}/wallet/redeem-voucher', [VoucherApiController::class, 'redeem']);
    Route::get('/members/{member}/wallet/voucher-redemptions', [VoucherApiController::class, 'redemptionHistory']);
});

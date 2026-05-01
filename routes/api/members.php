<?php

use App\Http\Controllers\Api\MemberApiController;
use App\Http\Controllers\Api\WalletApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:users.view'])->group(function () {
    Route::get('/members/meta', [MemberApiController::class, 'meta']);
    Route::get('/members', [MemberApiController::class, 'index']);
    Route::get('/members/export/google-contacts', [MemberApiController::class, 'exportGoogleContacts']);
    Route::get('/members/{member}', [MemberApiController::class, 'show']);
    Route::post('/members', [MemberApiController::class, 'store'])->middleware('permission:users.create');
    Route::post('/members/temp', [MemberApiController::class, 'storeTemp'])->middleware('permission:users.create');
    Route::put('/members/{member}', [MemberApiController::class, 'update'])->middleware('permission:users.edit');
    Route::patch('/members/{member}/toggle-status', [MemberApiController::class, 'toggleStatus'])->middleware('permission:users.edit');
    Route::patch('/members/{member}/toggle-verification', [MemberApiController::class, 'toggleVerification'])->middleware('permission:users.edit');
    Route::delete('/members/{member}', [MemberApiController::class, 'destroy'])->middleware('permission:users.delete');
});

// Wallet routes — require payments.manage permission
Route::middleware(['auth', 'permission:payments.manage'])->group(function () {
    Route::get('/wallet/meta', [WalletApiController::class, 'meta']);
    Route::get('/wallet-topups/{topup}', [WalletApiController::class, 'showTopup']);
    Route::post('/members/{member}/wallet/topup', [WalletApiController::class, 'topup']);
    Route::get('/members/{member}/wallet/topup-history', [WalletApiController::class, 'topupHistory']);
    Route::get('/members/{member}/wallet/transactions', [WalletApiController::class, 'transactions']);
});
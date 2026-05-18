<?php

use App\Http\Controllers\Api\MemberApiController;
use App\Http\Controllers\Api\MemberDocumentApiController;
use App\Http\Controllers\Api\PaymentApiController;
use App\Http\Controllers\Api\SaleApiController;
use App\Http\Controllers\Api\WalletApiController;
use App\Http\Controllers\Api\WorkoutProgramApiController;
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
    Route::post('/members/{member}/avatar', [MemberApiController::class, 'uploadAvatar'])->middleware('permission:users.edit');
    Route::put('/members/{member}/avatar', [MemberApiController::class, 'uploadAvatar'])->middleware('permission:users.edit');
    Route::delete('/members/{member}/avatar', [MemberApiController::class, 'deleteAvatar'])->middleware('permission:users.edit');

    // Member documents — view requires users.view; upload/delete requires users.edit
    Route::get('/members/{member}/documents', [MemberDocumentApiController::class, 'index']);
    Route::get('/members/{member}/documents/{document}/url', [MemberDocumentApiController::class, 'url']);
    Route::post('/members/{member}/documents', [MemberDocumentApiController::class, 'store'])->middleware('permission:users.edit');
    Route::delete('/members/{member}/documents/{document}', [MemberDocumentApiController::class, 'destroy'])->middleware('permission:users.edit');
});

// Member-scoped payments (requires payments.manage)
Route::middleware(['auth', 'permission:payments.manage'])->group(function () {
    Route::get('/members/{member}/payments', [PaymentApiController::class, 'memberPayments']);
});

// Member-scoped sales (requires sales.process)
Route::middleware(['auth', 'permission:sales.process'])->group(function () {
    Route::get('/members/{member}/sales', [SaleApiController::class, 'memberSales']);
});

// Member-scoped workout assignments (requires workouts.manage)
Route::middleware(['auth', 'permission:workouts.manage'])->group(function () {
    Route::get('/members/{member}/workouts', [WorkoutProgramApiController::class, 'memberAssignments']);
});

// Member attendance history (requires users.view)
Route::middleware(['auth', 'permission:users.view'])->group(function () {
    Route::get('/members/{member}/attendance', [MemberApiController::class, 'attendance']);
});

// Wallet routes — require payments.manage permission
Route::middleware(['auth', 'permission:payments.manage'])->group(function () {
    Route::get('/wallet/meta', [WalletApiController::class, 'meta']);
    Route::get('/wallet-topups/{topup}', [WalletApiController::class, 'showTopup']);
    Route::post('/members/{member}/wallet/topup', [WalletApiController::class, 'topup']);
    Route::get('/members/{member}/wallet/topup-history', [WalletApiController::class, 'topupHistory']);
    Route::get('/members/{member}/wallet/transactions', [WalletApiController::class, 'transactions']);
});

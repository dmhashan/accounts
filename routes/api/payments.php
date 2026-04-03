<?php

use App\Http\Controllers\Api\PaymentApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:payments.manage'])->group(function () {
    Route::get('/payments/meta', [PaymentApiController::class, 'meta']);
    Route::get('/payments', [PaymentApiController::class, 'index']);
    Route::post('/payments', [PaymentApiController::class, 'store']);
    Route::get('/payments/{payment}', [PaymentApiController::class, 'show']);
    Route::put('/payments/{payment}', [PaymentApiController::class, 'update']);
    Route::delete('/payments/{payment}', [PaymentApiController::class, 'destroy']);
});

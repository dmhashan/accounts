<?php

use App\Http\Controllers\Api\PaymentApiController;
use App\Http\Controllers\Api\PaymentMethodApiController;
use App\Http\Controllers\Api\PaymentPlanApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:payment_methods.manage,payments.manage'])->group(function () {
    Route::get('/payment-methods/meta', [PaymentMethodApiController::class, 'meta']);
    Route::get('/payment-methods', [PaymentMethodApiController::class, 'index']);
    Route::post('/payment-methods', [PaymentMethodApiController::class, 'store']);
    Route::get('/payment-methods/{paymentMethod}', [PaymentMethodApiController::class, 'show']);
    Route::put('/payment-methods/{paymentMethod}', [PaymentMethodApiController::class, 'update']);
    Route::delete('/payment-methods/{paymentMethod}', [PaymentMethodApiController::class, 'destroy']);
});

Route::middleware(['auth', 'permission:payments.manage'])->group(function () {
    Route::get('/payments/meta', [PaymentApiController::class, 'meta']);
    Route::get('/payments/member/{member}/payment-info', [PaymentApiController::class, 'memberPaymentInfo']);
    Route::get('/payments', [PaymentApiController::class, 'index']);
    Route::post('/payments', [PaymentApiController::class, 'store']);
    Route::post('/payments/{payment}/mark-as-paid', [PaymentApiController::class, 'markAsPaid']);
    Route::get('/payments/{payment}', [PaymentApiController::class, 'show']);
    Route::put('/payments/{payment}', [PaymentApiController::class, 'update']);
    Route::delete('/payments/{payment}', [PaymentApiController::class, 'destroy']);
});

Route::middleware(['auth', 'permission:payment_plans.manage,payments.manage'])->group(function () {
    Route::get('/payment-plans', [PaymentPlanApiController::class, 'index']);
    Route::post('/payment-plans', [PaymentPlanApiController::class, 'store']);
    Route::put('/payment-plans/{paymentPlan}', [PaymentPlanApiController::class, 'update']);
    Route::delete('/payment-plans/{paymentPlan}', [PaymentPlanApiController::class, 'destroy']);
});

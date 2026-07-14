<?php

use App\Http\Controllers\Api\PortalAuthController;
use App\Http\Controllers\Api\PortalTenantController;
use App\Http\Controllers\Api\PortalUserController;
use App\Http\Middleware\VerifyPortalOtp;
use Illuminate\Support\Facades\Route;

// Public authentication routes
Route::post('/auth/request-otp', [PortalAuthController::class, 'requestLoginOtp']);
Route::post('/auth/login', [PortalAuthController::class, 'login']);

// Authenticated portal routes
Route::middleware(['auth:portal'])->group(function () {
    Route::post('/auth/logout', [PortalAuthController::class, 'logout']);
    Route::get('/auth/me', [PortalAuthController::class, 'me']);
    Route::post('/auth/action-otp', [PortalAuthController::class, 'requestActionOtp']);
    Route::get('/dashboard/stats', [PortalTenantController::class, 'dashboardStats']);
    Route::get('tenants/{tenant}', [PortalTenantController::class, 'show']);

    // Mutating actions are checked by VerifyPortalOtp middleware
    Route::middleware([VerifyPortalOtp::class])->group(function () {
        Route::apiResource('tenants', PortalTenantController::class)->except(['show']);
        Route::apiResource('users', PortalUserController::class)->except(['show']);
    });
});

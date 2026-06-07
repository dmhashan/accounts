<?php

use App\Http\Controllers\Api\PublicProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->group(function () {
    Route::post('/request-otp', [PublicProfileController::class, 'requestOtp'])->middleware('throttle:5,1');
    Route::post('/verify-otp', [PublicProfileController::class, 'verifyOtp'])->middleware('throttle:10,1');
    Route::post('/activity', [PublicProfileController::class, 'logActivity'])->middleware('throttle:60,1');

    // Public event pages — no auth required
    Route::get('/event/{slug}', [PublicProfileController::class, 'showEvent']);
    Route::post('/event/{slug}/register', [PublicProfileController::class, 'registerEvent'])->middleware('throttle:10,1');

    // Upcoming events — public, no auth required
    Route::get('/upcoming-events', [PublicProfileController::class, 'getUpcomingEvents']);

    Route::middleware('pp.token')->group(function () {
        Route::get('/member-profile', [PublicProfileController::class, 'getProfile']);
        Route::get('/wallet/transactions', [PublicProfileController::class, 'getWalletTransactions']);
        Route::get('/notifications', [PublicProfileController::class, 'getNotifications']);
        Route::get('/event/{slug}/my-registration', [PublicProfileController::class, 'getMyEventRegistration']);
        Route::put('/event/{slug}/my-registration', [PublicProfileController::class, 'updateMyEventRegistration']);
    });
});

<?php

use App\Http\Controllers\Api\PublicProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->group(function () {
    Route::post('/request-otp', [PublicProfileController::class, 'requestOtp']);
    Route::post('/verify-otp',  [PublicProfileController::class, 'verifyOtp']);
    Route::post('/activity',    [PublicProfileController::class, 'logActivity']);

    // Public event pages — no auth required
    Route::get('/event/{slug}',             [PublicProfileController::class, 'showEvent']);
    Route::post('/event/{slug}/register',   [PublicProfileController::class, 'registerEvent']);

    // Upcoming events — public, no auth required
    Route::get('/upcoming-events', [PublicProfileController::class, 'getUpcomingEvents']);

    Route::middleware('pp.token')->group(function () {
        Route::get('/member-profile',  [PublicProfileController::class, 'getProfile']);
        Route::get('/notifications',   [PublicProfileController::class, 'getNotifications']);
        Route::get('/event/{slug}/my-registration',  [PublicProfileController::class, 'getMyEventRegistration']);
        Route::put('/event/{slug}/my-registration',  [PublicProfileController::class, 'updateMyEventRegistration']);
    });
});

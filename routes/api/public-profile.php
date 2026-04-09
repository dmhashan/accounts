<?php

use App\Http\Controllers\Api\PublicProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->group(function () {
    Route::post('/request-otp', [PublicProfileController::class, 'requestOtp']);
    Route::post('/verify-otp',  [PublicProfileController::class, 'verifyOtp']);
    Route::post('/activity',    [PublicProfileController::class, 'logActivity']);

    Route::middleware('pp.token')->group(function () {
        Route::get('/member-profile', [PublicProfileController::class, 'getProfile']);
    });
});

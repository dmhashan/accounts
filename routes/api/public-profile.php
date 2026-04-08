<?php

use App\Http\Controllers\Api\PublicProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->group(function () {
    Route::post('/request-otp', [PublicProfileController::class, 'requestOtp']);
    Route::post('/verify-otp',  [PublicProfileController::class, 'verifyOtp']);
    Route::get('/member-profile/{memberId}', [PublicProfileController::class, 'getProfile']);
});

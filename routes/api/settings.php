<?php

use App\Http\Controllers\Api\SettingsApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:settings.manage'])->group(function () {
    Route::get('/settings/general', [SettingsApiController::class, 'general']);
    Route::put('/settings/general', [SettingsApiController::class, 'updateGeneral']);
    Route::post('/settings/general/logo', [SettingsApiController::class, 'uploadLogo']);
    Route::delete('/settings/general/logo', [SettingsApiController::class, 'deleteLogo']);
});

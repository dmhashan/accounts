<?php

use App\Http\Controllers\Api\BiometricApiController;
use App\Http\Controllers\Api\ConfigurationApiController;
use App\Http\Controllers\Api\SettingsApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:settings.manage'])->group(function () {
    Route::get('/settings/general', [SettingsApiController::class, 'general']);
    Route::put('/settings/general', [SettingsApiController::class, 'updateGeneral']);
    Route::post('/settings/general/logo', [SettingsApiController::class, 'uploadLogo']);
    Route::delete('/settings/general/logo', [SettingsApiController::class, 'deleteLogo']);
    Route::post('/settings/legacy-tools/run', [SettingsApiController::class, 'runLegacyTool']);
    Route::get('/settings/legacy-tools/logs', [SettingsApiController::class, 'legacyToolLogs']);

    Route::get('/settings/configuration', [ConfigurationApiController::class, 'index']);
    Route::put('/settings/configuration', [ConfigurationApiController::class, 'update']);

    // Biometric device
    Route::post('/settings/biometric/test-connection', [BiometricApiController::class, 'testConnection']);
    Route::post('/settings/biometric/sync-all', [BiometricApiController::class, 'syncAllMembers']);
    Route::post('/settings/biometric/sync-attendance', [BiometricApiController::class, 'syncAttendance']);
    Route::get('/settings/biometric/recent-logs', [BiometricApiController::class, 'recentLogs']);
});

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
});

Route::middleware(['auth', 'permission:settings.legacy_tools,settings.manage'])->group(function () {
    Route::post('/settings/legacy-tools/run', [SettingsApiController::class, 'runLegacyTool']);
    Route::get('/settings/legacy-tools/logs', [SettingsApiController::class, 'legacyToolLogs']);
});

Route::middleware(['auth', 'permission:settings.configuration,settings.manage'])->group(function () {
    Route::get('/settings/configuration', [ConfigurationApiController::class, 'index']);
    Route::get('/settings/configuration/format-options', [ConfigurationApiController::class, 'formatOptions']);
    Route::put('/settings/configuration', [ConfigurationApiController::class, 'update']);
});

Route::middleware(['auth', 'permission:settings.biometric,settings.manage'])->group(function () {
    // Biometric device
    Route::post('/settings/biometric/test-connection', [BiometricApiController::class, 'testConnection']);
    Route::post('/settings/biometric/sync-all', [BiometricApiController::class, 'syncAllMembers']);
    Route::post('/settings/biometric/unlock', [BiometricApiController::class, 'unlockDoor']);
    Route::post('/settings/biometric/keep-unlock', [BiometricApiController::class, 'keepDoorUnlocked']);
    Route::post('/settings/biometric/close', [BiometricApiController::class, 'closeDoor']);
    Route::post('/settings/biometric/keep-close', [BiometricApiController::class, 'keepDoorClosed']);
    Route::get('/settings/biometric/door-status', [BiometricApiController::class, 'doorStatus']);
    Route::get('/settings/biometric/recent-logs', [BiometricApiController::class, 'recentLogs']);
    Route::get('/settings/biometric/access-events', [BiometricApiController::class, 'accessEvents']);
    Route::post('/settings/biometric/access-events/sync', [BiometricApiController::class, 'syncAccessEvents']);
    Route::get('/settings/biometric/queue-status', [BiometricApiController::class, 'queueStatus']);
    Route::post('/settings/biometric/failed-jobs/{failedJob}/retry', [BiometricApiController::class, 'retryFailedJob']);
    Route::delete('/settings/biometric/failed-jobs/{failedJob}', [BiometricApiController::class, 'deleteFailedJob']);

    // Biometric real-time webhook management
    Route::post('/settings/biometric/webhook/generate-token', [BiometricApiController::class, 'generateWebhookToken']);
    Route::post('/settings/biometric/webhook/configure', [BiometricApiController::class, 'configureWebhook']);
    Route::get('/settings/biometric/webhook/status', [BiometricApiController::class, 'webhookStatus']);
});

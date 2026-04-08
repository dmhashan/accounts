<?php

use App\Http\Controllers\Api\NotificationApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:notifications.send'])->group(function () {
    Route::get('/notifications', [NotificationApiController::class, 'index']);
    Route::get('/notifications/members', [NotificationApiController::class, 'members']);
    Route::get('/notifications/{bulkNotification}', [NotificationApiController::class, 'show']);
    Route::post('/notifications', [NotificationApiController::class, 'store']);
    Route::put('/notifications/{bulkNotification}', [NotificationApiController::class, 'update']);
    Route::delete('/notifications/{bulkNotification}', [NotificationApiController::class, 'destroy']);
    Route::post('/notifications/{bulkNotification}/send', [NotificationApiController::class, 'send']);
});

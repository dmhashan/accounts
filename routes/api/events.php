<?php

use App\Http\Controllers\Api\EventApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:events.manage'])->group(function () {
    Route::get('/events', [EventApiController::class, 'index']);
    Route::post('/events', [EventApiController::class, 'store']);
    Route::get('/events/{event}', [EventApiController::class, 'show']);
    Route::put('/events/{event}', [EventApiController::class, 'update']);
    Route::delete('/events/{event}', [EventApiController::class, 'destroy']);
    Route::get('/events/{event}/registrations', [EventApiController::class, 'registrations']);
    Route::post('/events/{event}/registrations', [EventApiController::class, 'adminRegister']);
    Route::put('/events/{event}/registrations/{registration}', [EventApiController::class, 'updateRegistration']);
    Route::delete('/events/{event}/registrations/{registration}', [EventApiController::class, 'destroyRegistration']);
    Route::post('/events/{event}/registrations/{registration}/mark-paid', [EventApiController::class, 'markRegistrationPaid']);
});

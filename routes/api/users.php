<?php

use App\Http\Controllers\Api\UserApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:users.view'])->group(function () {
    Route::get('/users/meta', [UserApiController::class, 'meta']);
    Route::get('/users', [UserApiController::class, 'index']);
    Route::get('/users/{user}', [UserApiController::class, 'show']);
    Route::post('/users', [UserApiController::class, 'store'])->middleware('permission:users.create');
    Route::put('/users/{user}', [UserApiController::class, 'update'])->middleware('permission:users.edit');
    Route::delete('/users/{user}', [UserApiController::class, 'destroy'])->middleware('permission:users.delete');
});
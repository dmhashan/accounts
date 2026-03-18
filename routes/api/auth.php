<?php

use App\Http\Controllers\Api\AuthApiController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthApiController::class, 'login']);
Route::post('/auth/logout', [AuthApiController::class, 'logout'])->middleware('auth');
Route::post('/auth/refresh', [AuthApiController::class, 'refresh']);
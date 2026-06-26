<?php

use App\Http\Controllers\Api\DashboardApiController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard/overview', [DashboardApiController::class, 'overview'])->middleware('auth');
Route::get('/dashboard/stats', [DashboardApiController::class, 'stats'])->middleware('auth');

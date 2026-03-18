<?php

use App\Http\Controllers\Api\ReportApiController;
use Illuminate\Support\Facades\Route;

Route::get('/reports/overview', [ReportApiController::class, 'overview'])->middleware(['auth', 'permission:reports.view']);
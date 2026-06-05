<?php

use App\Http\Controllers\Api\ReportApiController;
use Illuminate\Support\Facades\Route;

Route::get('/reports/overview', [ReportApiController::class, 'overview'])->middleware(['auth', 'permission:reports.view']);
Route::get('/reports/daily-summary', [ReportApiController::class, 'dailySummary'])->middleware(['auth', 'permission:reports.view']);
Route::post('/reports/daily-summary/generate', [ReportApiController::class, 'generateDailySummary'])->middleware(['auth', 'permission:reports.view']);
Route::get('/reports/daily-summary/history', [ReportApiController::class, 'dailySummaryHistory'])->middleware(['auth', 'permission:reports.view']);
Route::get('/reports/daily-summary/reports/{report}', [ReportApiController::class, 'showDailySummaryReport'])->middleware(['auth', 'permission:reports.view']);
Route::get('/reports/daily-summary/reports/{report}/pdf', [ReportApiController::class, 'downloadDailySummaryReport'])->middleware(['auth', 'permission:reports.view']);

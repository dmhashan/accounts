<?php

use App\Http\Controllers\Api\ReconciliationApiController;
use Illuminate\Support\Facades\Route;

// Admin config — requires reconciliation.manage
Route::get('/reconciliation/config', [ReconciliationApiController::class, 'config'])
    ->middleware(['auth', 'permission:reconciliation.manage']);
Route::post('/reconciliation/config', [ReconciliationApiController::class, 'saveConfig'])
    ->middleware(['auth', 'permission:reconciliation.manage']);

// History & detail — requires reconciliation.manage
Route::get('/reconciliation', [ReconciliationApiController::class, 'index'])
    ->middleware(['auth', 'permission:reconciliation.manage']);
Route::get('/reconciliation/sessions/{session}', [ReconciliationApiController::class, 'show'])
    ->middleware(['auth', 'permission:reconciliation.manage']);

// Daily operations — requires reconciliation.perform
Route::get('/reconciliation/today', [ReconciliationApiController::class, 'today'])
    ->middleware(['auth', 'permission:reconciliation.perform']);
Route::get('/reconciliation/form-config', [ReconciliationApiController::class, 'formConfig'])
    ->middleware(['auth', 'permission:reconciliation.perform']);
Route::post('/reconciliation/open', [ReconciliationApiController::class, 'open'])
    ->middleware(['auth', 'permission:reconciliation.perform']);
Route::post('/reconciliation/sessions/{session}/save-close', [ReconciliationApiController::class, 'saveClose'])
    ->middleware(['auth', 'permission:reconciliation.perform']);
Route::get('/reconciliation/sessions/{session}/preview', [ReconciliationApiController::class, 'closePreview'])
    ->middleware(['auth', 'permission:reconciliation.perform']);
Route::post('/reconciliation/sessions/{session}/close', [ReconciliationApiController::class, 'close'])
    ->middleware(['auth', 'permission:reconciliation.perform']);

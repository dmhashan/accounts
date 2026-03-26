<?php

use App\Http\Controllers\Api\CompanyAccountApiController;
use Illuminate\Support\Facades\Route;

Route::get('/accounts/meta', [CompanyAccountApiController::class, 'meta'])->middleware(['auth', 'permission:accounts.manage']);
Route::get('/accounts', [CompanyAccountApiController::class, 'index'])->middleware(['auth', 'permission:accounts.manage']);
Route::post('/accounts', [CompanyAccountApiController::class, 'store'])->middleware(['auth', 'permission:accounts.manage']);

Route::get('/accounts/transactions', [CompanyAccountApiController::class, 'transactions'])->middleware(['auth', 'permission:accounts.manage']);
Route::get('/accounts/transfers', [CompanyAccountApiController::class, 'transfers'])->middleware(['auth', 'permission:accounts.manage']);
Route::post('/accounts/transfers', [CompanyAccountApiController::class, 'storeTransfer'])->middleware(['auth', 'permission:accounts.manage']);
Route::get('/accounts/transfers/{transfer}', [CompanyAccountApiController::class, 'showTransfer'])->middleware(['auth', 'permission:accounts.manage']);
Route::put('/accounts/transfers/{transfer}', [CompanyAccountApiController::class, 'updateTransfer'])->middleware(['auth', 'permission:accounts.manage']);
Route::delete('/accounts/transfers/{transfer}', [CompanyAccountApiController::class, 'destroyTransfer'])->middleware(['auth', 'permission:accounts.manage']);

Route::get('/accounts/expenses', [CompanyAccountApiController::class, 'expenses'])->middleware(['auth', 'permission:accounts.manage']);
Route::post('/accounts/expenses', [CompanyAccountApiController::class, 'storeExpense'])->middleware(['auth', 'permission:accounts.manage']);
Route::get('/accounts/expenses/{expense}', [CompanyAccountApiController::class, 'showExpense'])->middleware(['auth', 'permission:accounts.manage']);
Route::put('/accounts/expenses/{expense}', [CompanyAccountApiController::class, 'updateExpense'])->middleware(['auth', 'permission:accounts.manage']);
Route::delete('/accounts/expenses/{expense}', [CompanyAccountApiController::class, 'destroyExpense'])->middleware(['auth', 'permission:accounts.manage']);

Route::get('/accounts/{account}', [CompanyAccountApiController::class, 'show'])->middleware(['auth', 'permission:accounts.manage']);
Route::put('/accounts/{account}', [CompanyAccountApiController::class, 'update'])->middleware(['auth', 'permission:accounts.manage']);
Route::delete('/accounts/{account}', [CompanyAccountApiController::class, 'destroy'])->middleware(['auth', 'permission:accounts.manage']);
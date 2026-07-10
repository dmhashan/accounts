<?php

use App\Http\Controllers\Api\CompanyAccountApiController;
use App\Http\Controllers\Api\PaymentSettlementApiController;
use Illuminate\Support\Facades\Route;

Route::get('/accounts/meta', [CompanyAccountApiController::class, 'meta'])->middleware(['auth', 'permission:accounts.manage,accounts.transfers,accounts.transactions,expenses.manage']);

Route::get('/accounts/adjustments', [CompanyAccountApiController::class, 'adjustments'])->middleware(['auth', 'permission:accounts.adjust,accounts.manage']);
Route::post('/accounts/adjustments', [CompanyAccountApiController::class, 'storeAdjustment'])->middleware(['auth', 'permission:accounts.adjust,accounts.manage']);
Route::get('/accounts/adjustments/{adjustment}', [CompanyAccountApiController::class, 'showAdjustment'])->middleware(['auth', 'permission:accounts.adjust,accounts.manage']);
Route::put('/accounts/adjustments/{adjustment}', [CompanyAccountApiController::class, 'updateAdjustment'])->middleware(['auth', 'permission:accounts.adjust,accounts.manage']);
Route::delete('/accounts/adjustments/{adjustment}', [CompanyAccountApiController::class, 'destroyAdjustment'])->middleware(['auth', 'permission:accounts.adjust,accounts.manage']);

Route::get('/accounts', [CompanyAccountApiController::class, 'index'])->middleware(['auth', 'permission:accounts.manage']);
Route::post('/accounts', [CompanyAccountApiController::class, 'store'])->middleware(['auth', 'permission:accounts.manage']);

Route::get('/accounts/transactions', [CompanyAccountApiController::class, 'transactions'])->middleware(['auth', 'permission:accounts.transactions,accounts.manage']);
Route::get('/accounts/transfers', [CompanyAccountApiController::class, 'transfers'])->middleware(['auth', 'permission:accounts.transfers,accounts.manage']);
Route::post('/accounts/transfers', [CompanyAccountApiController::class, 'storeTransfer'])->middleware(['auth', 'permission:accounts.transfers,accounts.manage']);
Route::get('/accounts/transfers/{transfer}', [CompanyAccountApiController::class, 'showTransfer'])->middleware(['auth', 'permission:accounts.transfers,accounts.manage']);
Route::put('/accounts/transfers/{transfer}', [CompanyAccountApiController::class, 'updateTransfer'])->middleware(['auth', 'permission:accounts.transfers,accounts.manage']);
Route::delete('/accounts/transfers/{transfer}', [CompanyAccountApiController::class, 'destroyTransfer'])->middleware(['auth', 'permission:accounts.transfers,accounts.manage']);

Route::get('/accounts/expenses', [CompanyAccountApiController::class, 'expenses'])->middleware(['auth', 'permission:expenses.manage,accounts.manage']);
Route::post('/accounts/expenses', [CompanyAccountApiController::class, 'storeExpense'])->middleware(['auth', 'permission:expenses.manage,accounts.manage']);
Route::get('/accounts/expenses/{expense}', [CompanyAccountApiController::class, 'showExpense'])->middleware(['auth', 'permission:expenses.manage,accounts.manage']);
Route::put('/accounts/expenses/{expense}', [CompanyAccountApiController::class, 'updateExpense'])->middleware(['auth', 'permission:expenses.manage,accounts.manage']);
Route::delete('/accounts/expenses/{expense}', [CompanyAccountApiController::class, 'destroyExpense'])->middleware(['auth', 'permission:expenses.manage,accounts.manage']);
Route::get('/accounts/expenses/{expense}/documents/{document}/url', [CompanyAccountApiController::class, 'expenseDocumentUrl'])->middleware(['auth', 'permission:expenses.manage,accounts.manage']);
Route::delete('/accounts/expenses/{expense}/documents/{document}', [CompanyAccountApiController::class, 'destroyExpenseDocument'])->middleware(['auth', 'permission:expenses.manage,accounts.manage']);

Route::get('/accounts/{account}/payment-settlements', [PaymentSettlementApiController::class, 'accountIndex'])->middleware(['auth', 'permission:accounts.manage,accounts.transactions']);
Route::post('/accounts/payment-settlements/{settlement}/confirm', [PaymentSettlementApiController::class, 'confirm'])->middleware(['auth', 'permission:accounts.manage,accounts.transactions']);
Route::post('/accounts/{account}/payment-settlements/confirm-bulk', [PaymentSettlementApiController::class, 'confirmBulk'])->middleware(['auth', 'permission:accounts.manage,accounts.transactions']);

Route::get('/accounts/{account}', [CompanyAccountApiController::class, 'show'])->middleware(['auth', 'permission:accounts.manage']);
Route::put('/accounts/{account}', [CompanyAccountApiController::class, 'update'])->middleware(['auth', 'permission:accounts.manage']);
Route::delete('/accounts/{account}', [CompanyAccountApiController::class, 'destroy'])->middleware(['auth', 'permission:accounts.manage']);

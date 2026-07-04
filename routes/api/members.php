<?php

use App\Http\Controllers\Api\BiometricApiController;
use App\Http\Controllers\Api\MemberApiController;
use App\Http\Controllers\Api\MemberBodyMeasurementApiController;
use App\Http\Controllers\Api\MemberDocumentApiController;
use App\Http\Controllers\Api\PaymentApiController;
use App\Http\Controllers\Api\PaymentPlanApiController;
use App\Http\Controllers\Api\SaleApiController;
use App\Http\Controllers\Api\WalletApiController;
use App\Http\Controllers\Api\WorkoutProgramApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:members.view,members.temp.view,users.view'])->group(function () {
    Route::get('/members/meta', [MemberApiController::class, 'meta']);
    Route::get('/members', [MemberApiController::class, 'index']);
    Route::get('/members/export/google-contacts', [MemberApiController::class, 'exportGoogleContacts']);
    Route::get('/members/biometric-status', [BiometricApiController::class, 'memberStatus']);
    Route::get('/members/{member}', [MemberApiController::class, 'show']);
    Route::post('/members', [MemberApiController::class, 'store'])->middleware('permission:members.create,users.create');
    Route::post('/members/temp', [MemberApiController::class, 'storeTemp'])->middleware('permission:members.create,users.create');
    Route::put('/members/{member}', [MemberApiController::class, 'update'])->middleware('permission:members.edit,users.edit');
    Route::patch('/members/{member}/toggle-status', [MemberApiController::class, 'toggleStatus'])->middleware('permission:members.edit,users.edit');
    Route::patch('/members/{member}/toggle-verification', [MemberApiController::class, 'toggleVerification'])->middleware('permission:members.edit,users.edit,campaigns.verify');
    Route::delete('/members/{member}', [MemberApiController::class, 'destroy'])->middleware('permission:members.delete,users.delete');
    Route::post('/members/{member}/avatar', [MemberApiController::class, 'uploadAvatar'])->middleware('permission:members.edit,users.edit');
    Route::put('/members/{member}/avatar', [MemberApiController::class, 'uploadAvatar'])->middleware('permission:members.edit,users.edit');
    Route::delete('/members/{member}/avatar', [MemberApiController::class, 'deleteAvatar'])->middleware('permission:members.edit,users.edit');

    // Member body measurements — view requires users.view; changes require users.edit
    Route::get('/members/{member}/body-measurements', [MemberBodyMeasurementApiController::class, 'index']);
    Route::post('/members/{member}/body-measurements', [MemberBodyMeasurementApiController::class, 'store'])->middleware('permission:members.edit,users.edit');
    Route::put('/members/{member}/body-measurements/{bodyMeasurement}', [MemberBodyMeasurementApiController::class, 'update'])->middleware('permission:members.edit,users.edit');
    Route::delete('/members/{member}/body-measurements/{bodyMeasurement}', [MemberBodyMeasurementApiController::class, 'destroy'])->middleware('permission:members.edit,users.edit');

    // Payment plans (read-only) — needed for member create/edit form
    Route::get('/members/form/payment-plans', [PaymentPlanApiController::class, 'index']);

    // Member documents — view requires users.view; upload/delete requires users.edit
    Route::get('/members/{member}/documents', [MemberDocumentApiController::class, 'index']);
    Route::get('/members/{member}/documents/{document}/url', [MemberDocumentApiController::class, 'url']);
    Route::post('/members/{member}/documents', [MemberDocumentApiController::class, 'store'])->middleware('permission:members.edit,users.edit');
    Route::delete('/members/{member}/documents/{document}', [MemberDocumentApiController::class, 'destroy'])->middleware('permission:members.edit,users.edit');
});

// Member-scoped payments (requires payments.manage)
Route::middleware(['auth', 'permission:payments.manage'])->group(function () {
    Route::get('/members/{member}/payments', [PaymentApiController::class, 'memberPayments']);
});

// Member-scoped sales (requires sales.process)
Route::middleware(['auth', 'permission:sales.process'])->group(function () {
    Route::get('/members/{member}/sales', [SaleApiController::class, 'memberSales']);
});

// Member-scoped workout assignments
Route::middleware(['auth', 'permission:workouts.assignments,workouts.manage'])->group(function () {
    Route::get('/members/{member}/workouts', [WorkoutProgramApiController::class, 'memberAssignments']);
});

// Member attendance history (requires users.view)
Route::middleware(['auth', 'permission:members.view,users.view'])->group(function () {
    Route::get('/members/{member}/attendance', [MemberApiController::class, 'attendance']);
});

// Wallet routes — require payments.manage permission
Route::middleware(['auth', 'permission:payments.manage'])->group(function () {
    Route::get('/wallet/meta', [WalletApiController::class, 'meta']);
    Route::get('/wallet-topups/{topup}', [WalletApiController::class, 'showTopup']);
    Route::post('/members/{member}/wallet/topup', [WalletApiController::class, 'topup']);
    Route::get('/members/{member}/wallet/topup-history', [WalletApiController::class, 'topupHistory']);
    Route::get('/members/{member}/wallet/transactions', [WalletApiController::class, 'transactions']);
});

// Biometric per-member read access (requires member view permission)
Route::middleware(['auth', 'permission:members.view,members.temp.view,users.view'])->group(function () {
    Route::get('/members/{member}/biometric-logs', [BiometricApiController::class, 'memberLogs']);
    Route::get('/members/{member}/biometric-device-info', [BiometricApiController::class, 'memberDeviceInfo']);
    Route::get('/members/{member}/biometric-face-image', [BiometricApiController::class, 'faceImage']);
});

// Biometric per-member actions (requires member edit permission)
Route::middleware(['auth', 'permission:members.edit,users.edit'])->group(function () {
    Route::post('/members/{member}/biometric-assign-id', [BiometricApiController::class, 'assignMemberId']);
    Route::post('/members/{member}/biometric-sync', [BiometricApiController::class, 'syncMember']);
    Route::post('/members/{member}/biometric-setup-fingerprint', [BiometricApiController::class, 'setupFingerprint']);
    Route::post('/members/{member}/biometric-upload-face-photo', [BiometricApiController::class, 'uploadFaceAsPhoto']);
});

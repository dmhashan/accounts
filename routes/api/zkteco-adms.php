<?php

use App\Http\Controllers\Api\ZktecoAdmsController;
use Illuminate\Support\Facades\Route;

/**
 * Isolated ZKTeco ADMS Cloud Server / Push protocol routes.
 *
 * Public biometric endpoints — no session or CSRF protection.
 * Tenant is identified automatically by Device Serial Number (SN) or domain.
 */
Route::prefix('iclock')->group(function () {
    Route::any('cdata', [ZktecoAdmsController::class, 'cdata'])->name('zkteco.cdata');
    Route::get('getrequest', [ZktecoAdmsController::class, 'getrequest'])->name('zkteco.getrequest');
    Route::post('devicecmd', [ZktecoAdmsController::class, 'devicecmd'])->name('zkteco.devicecmd');
    Route::post('fdata', [ZktecoAdmsController::class, 'fdata'])->name('zkteco.fdata');
    Route::any('ping', [ZktecoAdmsController::class, 'ping'])->name('zkteco.ping');
    Route::any('registry', [ZktecoAdmsController::class, 'registry'])->name('zkteco.registry');
});

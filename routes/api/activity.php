<?php

use App\Http\Controllers\Api\MemberActivityLogController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('member-activity')->group(function () {
    Route::get('/',       [MemberActivityLogController::class, 'index'])->middleware('permission:activity.view');
    Route::get('/export', [MemberActivityLogController::class, 'export'])->middleware('permission:activity.view');
});

<?php

use App\Http\Controllers\Api\RoleApiController;
use Illuminate\Support\Facades\Route;

Route::get('/roles', [RoleApiController::class, 'index'])->middleware(['auth', 'permission:roles.view']);
Route::post('/roles', [RoleApiController::class, 'store'])->middleware(['auth', 'permission:roles.permissions']);
Route::get('/roles/{role}', [RoleApiController::class, 'show'])->middleware(['auth', 'permission:roles.view']);
Route::put('/roles/{role}', [RoleApiController::class, 'update'])->middleware(['auth', 'permission:roles.permissions']);
Route::patch('/roles/{role}/permissions', [RoleApiController::class, 'updatePermissions'])->middleware(['auth', 'permission:roles.permissions']);
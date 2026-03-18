<?php

use App\Http\Controllers\Api\AppContextApiController;
use Illuminate\Support\Facades\Route;

Route::get('/app/context', [AppContextApiController::class, 'show'])->middleware('auth');
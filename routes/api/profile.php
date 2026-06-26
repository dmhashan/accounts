<?php

use App\Http\Controllers\Api\ProfileApiController;
use Illuminate\Support\Facades\Route;

Route::get('/profile', [ProfileApiController::class, 'show'])->middleware('auth');

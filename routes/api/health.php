<?php

use App\Http\Controllers\Api\HealthApiController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthApiController::class, 'show']);

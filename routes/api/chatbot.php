<?php

use App\Http\Controllers\Api\ChatBotController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::post('/chatbot/message', [ChatBotController::class, 'message']);
});

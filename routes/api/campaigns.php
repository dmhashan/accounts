<?php

use App\Http\Controllers\Api\CampaignApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('public/campaigns')->group(function () {
    Route::get('/{slug}', [CampaignApiController::class, 'publicShow']);
    Route::post('/{slug}/register', [CampaignApiController::class, 'publicRegister'])->middleware('throttle:10,1');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/campaigns/meta', [CampaignApiController::class, 'meta'])->middleware('permission:campaigns.view,campaigns.create,campaigns.edit');
    Route::get('/campaigns', [CampaignApiController::class, 'index'])->middleware('permission:campaigns.view');
    Route::post('/campaigns', [CampaignApiController::class, 'store'])->middleware('permission:campaigns.create');
    Route::get('/campaigns/{campaign}', [CampaignApiController::class, 'show'])->middleware('permission:campaigns.view,campaigns.edit');
    Route::put('/campaigns/{campaign}', [CampaignApiController::class, 'update'])->middleware('permission:campaigns.edit');
    Route::patch('/campaigns/{campaign}/status', [CampaignApiController::class, 'updateStatus'])->middleware('permission:campaigns.publish,campaigns.close,campaigns.edit');
    Route::delete('/campaigns/{campaign}', [CampaignApiController::class, 'destroy'])->middleware('permission:campaigns.delete');
    Route::get('/campaigns/{campaign}/registrations', [CampaignApiController::class, 'registrations'])->middleware('permission:campaigns.registrations,campaigns.view');
});

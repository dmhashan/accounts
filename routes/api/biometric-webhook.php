<?php

use App\Http\Controllers\Api\BiometricWebhookController;
use Illuminate\Support\Facades\Route;

/**
 * Public biometric device webhook.
 *
 * No session / CSRF — the tenant is identified by domain in the URL and
 * authenticated by a per-tenant token in the query string.
 *
 * The device posts real-time access events here after being configured via
 * BiometricApiController@configureWebhook.
 */
Route::post('/biometric/events/{tenantDomain}', [BiometricWebhookController::class, 'handle'])
    ->middleware('throttle:300,1')
    ->name('biometric.webhook');

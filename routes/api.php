<?php

use App\Http\Middleware\IdentifyTenant;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', IdentifyTenant::class])->group(function () {
    require __DIR__ . '/api/health.php';
    require __DIR__ . '/api/auth.php';
    require __DIR__ . '/api/context.php';
    require __DIR__ . '/api/dashboard.php';

    require __DIR__ . '/api/users.php';
    require __DIR__ . '/api/members.php';
    require __DIR__ . '/api/roles.php';
    require __DIR__ . '/api/settings.php';
    require __DIR__ . '/api/reports.php';
    require __DIR__ . '/api/inventory.php';
    require __DIR__ . '/api/accounts.php';
    require __DIR__ . '/api/sales.php';
    require __DIR__ . '/api/workouts.php';
    require __DIR__ . '/api/payments.php';
    require __DIR__ . '/api/employees.php';
    require __DIR__ . '/api/notifications.php';
    require __DIR__ . '/api/events.php';
    require __DIR__ . '/api/public-profile.php';
    require __DIR__ . '/api/activity.php';
    require __DIR__ . '/api/reconciliation.php';
    require __DIR__ . '/api/vouchers.php';
    require __DIR__ . '/api/forms.php';
    require __DIR__ . '/api/campaigns.php';
    require __DIR__ . '/api/chatbot.php';
});

// Public biometric device webhook — no session/CSRF, tenant identified by URL param
require __DIR__ . '/api/biometric-webhook.php';

<?php

use App\Http\Middleware\IdentifyTenant;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', IdentifyTenant::class])->group(function () {
    require __DIR__.'/api/health.php';
    require __DIR__.'/api/auth.php';
    require __DIR__.'/api/context.php';
    require __DIR__.'/api/profile.php';
    require __DIR__.'/api/dashboard.php';

    require __DIR__.'/api/users.php';
    require __DIR__.'/api/members.php';
    require __DIR__.'/api/roles.php';
    require __DIR__.'/api/reports.php';
    require __DIR__.'/api/inventory.php';
    require __DIR__.'/api/sales.php';
});

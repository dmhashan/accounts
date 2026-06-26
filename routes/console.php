<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notifications:membership-expiry', function () {
    $service = app(App\Services\AutomatedMemberNotificationService::class);
    $tenancy = app(App\Services\Tenancy\TenantDatabaseManager::class);
    $count = 0;

    if ($tenancy->isolationEnabled()) {
        $tenancy->eachTenant(function () use ($service, &$count): void {
            $count += $service->sendMembershipExpiryReminders();
        });
    } else {
        $count = $service->sendMembershipExpiryReminders();
    }

    $this->info("Queued {$count} membership expiry notification(s).");
})->purpose('Queue membership payment expiry notifications');

Artisan::command('notifications:member-milestones', function () {
    $service = app(App\Services\AutomatedMemberNotificationService::class);
    $tenancy = app(App\Services\Tenancy\TenantDatabaseManager::class);
    $count = 0;

    if ($tenancy->isolationEnabled()) {
        $tenancy->eachTenant(function () use ($service, &$count): void {
            $count += $service->sendMemberMilestoneNotifications();
        });
    } else {
        $count = $service->sendMemberMilestoneNotifications();
    }

    $this->info("Queued {$count} member milestone notification(s).");
})->purpose('Queue member birthday and join anniversary notifications');

Schedule::command('notifications:membership-expiry')->dailyAt('01:00')->timezone('UTC')->onOneServer();
Schedule::command('notifications:member-milestones')->dailyAt('01:00')->timezone('UTC')->onOneServer();

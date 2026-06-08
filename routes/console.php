<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notifications:membership-expiry', function () {
    $count = app(App\Services\AutomatedMemberNotificationService::class)
        ->sendMembershipExpiryReminders();

    $this->info("Queued {$count} membership expiry notification(s).");
})->purpose('Queue membership payment expiry notifications');

Schedule::command('notifications:membership-expiry')->dailyAt('06:00');

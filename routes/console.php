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

Artisan::command('notifications:member-milestones', function () {
    $count = app(App\Services\AutomatedMemberNotificationService::class)
        ->sendMemberMilestoneNotifications();

    $this->info("Queued {$count} member milestone notification(s).");
})->purpose('Queue member birthday and join anniversary notifications');

Schedule::command('notifications:membership-expiry')->dailyAt('06:00');
Schedule::command('notifications:member-milestones')->dailyAt('06:00');

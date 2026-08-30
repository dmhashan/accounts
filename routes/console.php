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

Artisan::command('biometric:import-access-events', function () {
    $service = app(App\Services\BiometricSyncService::class);
    $tenancy = app(App\Services\Tenancy\TenantDatabaseManager::class);
    $config = app(App\Services\TenantConfigurationService::class);
    $count = 0;

    $dispatchForTenant = function (App\Models\Tenant $tenant) use ($service, $config, &$count): void {
        if (!$service->isEnabled($tenant->id)) {
            return;
        }

        $allConfig = $config->all($tenant->id);
        $configuredFrom = (string) ($allConfig['biometric.access_events_sync_from'] ?? '');
        $syncFrom = $configuredFrom !== ''
            ? Illuminate\Support\Carbon::parse($configuredFrom)->toIso8601String()
            : null;
        $syncTo = now()->toIso8601String();

        App\Jobs\ImportBiometricAccessEventsJob::dispatch($tenant->id, $syncFrom, $syncTo);
        $count++;
    };

    if ($tenancy->isolationEnabled()) {
        $tenancy->eachTenant($dispatchForTenant);
    } else {
        /** @var App\Models\Tenant|null $tenant */
        $tenant = app()->bound('tenant') ? app('tenant') : App\Models\Tenant::first();

        if ($tenant) {
            $dispatchForTenant($tenant);
        }
    }

    $this->info("Dispatched biometric access events import job for {$count} tenant(s).");
})->purpose('Import biometric access events from last sync time up to now for enabled tenants');

Schedule::command('notifications:membership-expiry')->dailyAt('01:00')->timezone('UTC')->onOneServer();
Schedule::command('notifications:member-milestones')->dailyAt('01:00')->timezone('UTC')->onOneServer();
Schedule::command('biometric:import-access-events')->everyFourHours()->onOneServer();

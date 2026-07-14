<?php

namespace App\Providers;

use App\Services\Tenancy\TenantDatabaseManager;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Apple\Provider as AppleExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantDatabaseManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('testing')) {
            $this->loadMigrationsFrom(database_path('migrations/tenant'));
        }

        Blade::component('layouts.app', 'app-layout');
        Blade::component('layouts.guest', 'guest-layout');

        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('apple', AppleExtendSocialite::class);
        });

        Queue::createPayloadUsing(function (): array {
            $domain = app(TenantDatabaseManager::class)->currentDomain();

            return $domain ? ['tenant_domain' => $domain] : [];
        });

        Event::listen(function (JobProcessing $event): void {
            $tenancy = app(TenantDatabaseManager::class);
            $domain = $event->job->payload()['tenant_domain'] ?? null;

            if (is_string($domain) && $domain !== '') {
                $tenancy->activateByDomain($domain);
            } else {
                $tenancy->deactivate();
            }
        });

        Event::listen(fn (JobProcessed $event) => app(TenantDatabaseManager::class)->deactivate());
        Event::listen(fn (JobExceptionOccurred $event) => app(TenantDatabaseManager::class)->deactivate());

        Event::listen(\Illuminate\Auth\Events\Login::class, function (\Illuminate\Auth\Events\Login $event): void {
            try {
                $tenant = app('tenant') ?? null;

                if ($tenant && $event->user && \Illuminate\Support\Facades\Schema::hasTable('audit_logs')) {
                    \Illuminate\Support\Facades\DB::table('audit_logs')->insert([
                        'tenant_id' => $tenant->id,
                        'user_id' => $event->user->id,
                        'action' => 'user.login',
                        'auditable_type' => get_class($event->user),
                        'auditable_id' => $event->user->id,
                        'created_at' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                // Ignore failures
            }
        });
    }
}

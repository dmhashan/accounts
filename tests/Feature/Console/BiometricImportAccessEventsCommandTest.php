<?php

namespace Tests\Feature\Console;

use App\Jobs\ImportBiometricAccessEventsJob;
use App\Models\Tenant;
use App\Services\TenantConfigurationService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Feature\Api\ApiRouteTestCase;

class BiometricImportAccessEventsCommandTest extends ApiRouteTestCase
{
    public function testCommandDispatchesJobFromLastSyncTimeUptoNowForEnabledTenants(): void
    {
        Queue::fake();

        // Enable biometric for current tenant and set last sync cursor
        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'biometric.enabled' => '1',
            'biometric.access_events_sync_from' => '2026-08-30T10:00',
        ]);

        $exitCode = Artisan::call('biometric:import-access-events');
        $this->assertSame(0, $exitCode);

        Queue::assertPushed(ImportBiometricAccessEventsJob::class, 1);
        Queue::assertPushed(ImportBiometricAccessEventsJob::class, function (ImportBiometricAccessEventsJob $job) {
            $tenantId = $this->readPrivate($job, 'tenantId');
            $syncFrom = $this->readPrivate($job, 'syncFrom');
            $syncTo = $this->readPrivate($job, 'syncTo');

            return $tenantId === $this->tenant->id
                && Str::startsWith((string) $syncFrom, '2026-08-30T10:00:00')
                && $syncTo !== null;
        });
    }

    public function testCommandDoesNotDispatchWhenBiometricIsDisabled(): void
    {
        Queue::fake();

        // Disable biometric for current tenant
        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'biometric.enabled' => '0',
        ]);

        $exitCode = Artisan::call('biometric:import-access-events');
        $this->assertSame(0, $exitCode);

        Queue::assertNothingPushed();
    }

    public function testCommandIsScheduledEveryFourHours(): void
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $event = $events->first(function ($e) {
            return str_contains((string) $e->command, 'biometric:import-access-events');
        });

        $this->assertNotNull($event, 'The biometric:import-access-events command is not scheduled.');
        $this->assertSame('0 */4 * * *', $event->expression);
    }

    private function readPrivate(object $object, string $property): mixed
    {
        $reflection = new \ReflectionClass($object);
        $reflectedProperty = $reflection->getProperty($property);
        $reflectedProperty->setAccessible(true);

        return $reflectedProperty->getValue($object);
    }
}

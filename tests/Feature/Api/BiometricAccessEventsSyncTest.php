<?php

namespace Tests\Feature\Api;

use App\Jobs\ImportBiometricAccessEventsJob;
use App\Models\Tenant;
use App\Services\TenantConfigurationService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

class BiometricAccessEventsSyncTest extends ApiRouteTestCase
{
    public function testSyncDispatchesJobForCurrentTenantWithProvidedCursor(): void
    {
        Queue::fake();

        $this->actingAsUser(['settings.manage']);

        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'biometric.enabled' => '1',
        ]);

        $syncFrom = '2026-06-05T08:00';
        $syncTo = '2026-06-05T12:00';

        $response = $this->postJson('/api/settings/biometric/access-events/sync', [
            'sync_from' => $syncFrom,
            'sync_to' => $syncTo,
        ]);

        $response->assertOk();

        Queue::assertPushed(ImportBiometricAccessEventsJob::class, 1);
        Queue::assertPushed(ImportBiometricAccessEventsJob::class, function (ImportBiometricAccessEventsJob $job) {
            return $this->getPrivate($job, 'tenantId') === $this->tenant->id
                && Str::startsWith((string) $this->getPrivate($job, 'syncFrom'), '2026-06-05T08:00:00')
                && Str::startsWith((string) $this->getPrivate($job, 'syncTo'), '2026-06-05T12:00:00');
        });
    }

    public function testSyncDoesNotDispatchForOtherTenants(): void
    {
        Queue::fake();

        $this->actingAsUser(['settings.manage']);

        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'biometric.enabled' => '1',
        ]);

        $otherTenant = Tenant::create([
            'name' => 'Other Gym',
            'domain' => 'other-gym',
            'tenant_uuid' => (string) Str::uuid(),
            'use_custom_landing_page' => false,
        ]);

        $this->postJson('/api/settings/biometric/access-events/sync', [
            'sync_from' => '2026-06-05T08:00',
        ])->assertOk();

        Queue::assertPushed(ImportBiometricAccessEventsJob::class, 1);
        Queue::assertPushed(ImportBiometricAccessEventsJob::class, fn (ImportBiometricAccessEventsJob $job) => $this->getPrivate($job, 'tenantId') === $this->tenant->id,
        );
        Queue::assertPushed(ImportBiometricAccessEventsJob::class, fn (ImportBiometricAccessEventsJob $job) => $this->getPrivate($job, 'tenantId') !== $otherTenant->id,
        );
    }

    private function getPrivate(object $object, string $property): mixed
    {
        $ref = new \ReflectionClass($object);
        $prop = $ref->getProperty($property);
        $prop->setAccessible(true);

        return $prop->getValue($object);
    }
}

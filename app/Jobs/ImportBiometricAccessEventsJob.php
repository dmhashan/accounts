<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\BiometricSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Imports the full access-event history held on a tenant's biometric device and
 * stores each authentication (success/failed) as a BiometricAccessEvent with its
 * captured snapshot. Successful events also mark member attendance.
 *
 * Runs on the queue because pulling events + fetching each snapshot from the
 * device can take a while.
 */
class ImportBiometricAccessEventsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        private readonly int $tenantId,
        private readonly ?string $syncFrom = null,
        private readonly ?string $syncTo = null,
    ) {}

    public function handle(BiometricSyncService $biometric): void
    {
        $tenant = Tenant::find($this->tenantId);

        if (!$tenant) {
            Log::warning('ImportBiometricAccessEventsJob: tenant not found.');

            return;
        }

        // Bind the tenant so MediaStorageService can namespace stored snapshots.
        app()->instance('tenant', $tenant);

        $result = $biometric->importDeviceEvents($tenant, $this->syncFrom, $this->syncTo);

        Log::info('ImportBiometricAccessEventsJob: complete', $result);
    }
}

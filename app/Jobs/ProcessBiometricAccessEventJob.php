<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\BiometricSyncService;
use App\Services\Tenancy\TenantDatabaseManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBiometricAccessEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    /**
     * @param  array<string, mixed>  $event
     */
    public function __construct(
        private readonly int $tenantId,
        private readonly array $event,
    ) {
        $this->onConnection((string) config('queue.biometric_connection', 'database'));
        $this->onQueue((string) config('queue.biometric_queue', 'biometric'));
    }

    public function handle(BiometricSyncService $biometric): void
    {
        $tenant = app(TenantDatabaseManager::class)->activateById($this->tenantId)
            ?? Tenant::find($this->tenantId);

        if (!$tenant) {
            throw new \RuntimeException('Tenant not found for biometric access event.');
        }

        app()->instance('tenant', $tenant);

        $biometric->handleIncomingEvent($tenant, $this->eventForProcessing());
    }

    /**
     * @return array<string, mixed>
     */
    private function eventForProcessing(): array
    {
        $event = $this->event;
        $encodedPicture = $event['picture_bytes_base64'] ?? null;

        if (is_string($encodedPicture) && $encodedPicture !== '') {
            $decoded = base64_decode($encodedPicture, true);

            if ($decoded !== false) {
                $event['picture_bytes'] = $decoded;
            }
        }

        unset($event['picture_bytes_base64']);

        return $event;
    }
}

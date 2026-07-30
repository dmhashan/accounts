<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\GoWaService;
use App\Services\Tenancy\TenantDatabaseManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncGoWaGroupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    /**
     * @param  array<string>  $phones
     */
    public function __construct(
        private readonly ?int $tenantId,
        private readonly string $url,
        private readonly string $groupId,
        private readonly string $action,
        private readonly array $phones,
        private readonly ?string $apiKey = null,
        private readonly ?string $sessionId = null,
    ) {}

    public static function dispatchForTenant(
        ?int $tenantId,
        string $url,
        string $groupId,
        string $action,
        array $phones,
        ?string $apiKey = null,
        ?string $sessionId = null,
    ): void {
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        try {
            self::dispatch($tenantId, $url, $groupId, $action, $phones, $apiKey, $sessionId);
        } finally {
            if ($tenant) {
                app()->instance('tenant', $tenant);
            }
        }
    }

    public function handle(GoWaService $goWaService): void
    {
        if ($this->tenantId) {
            $tenant = app(TenantDatabaseManager::class)->activateById($this->tenantId)
                ?? Tenant::find($this->tenantId);

            if ($tenant) {
                app()->instance('tenant', $tenant);
            }
        }

        Log::info('SyncGoWaGroupJob: Starting group sync', [
            'tenant_id' => $this->tenantId,
            'group_id' => $this->groupId,
            'action' => $this->action,
            'phones_count' => count($this->phones),
        ]);

        if ($this->action === 'add') {
            $result = $goWaService->addParticipants(
                $this->url,
                $this->groupId,
                $this->phones,
                $this->apiKey,
                $this->sessionId,
            );
        } else {
            $result = $goWaService->removeParticipants(
                $this->url,
                $this->groupId,
                $this->phones,
                $this->apiKey,
                $this->sessionId,
            );
        }

        Log::info('SyncGoWaGroupJob: Finished group sync', [
            'tenant_id' => $this->tenantId,
            'group_id' => $this->groupId,
            'action' => $this->action,
            'success' => $result['success'] ?? false,
            'processed_count' => count($result['added'] ?? $result['removed'] ?? []),
            'failed_count' => count($result['failed'] ?? []),
        ]);
    }
}

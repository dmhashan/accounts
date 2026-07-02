<?php

namespace App\Jobs;

use App\Models\BiometricSyncLog;
use App\Models\Member;
use App\Models\Tenant;
use App\Services\BiometricSyncService;
use App\Services\Tenancy\TenantDatabaseManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncBiometricMemberJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        private readonly int $tenantId,
        private readonly int $memberId,
        private readonly string $action,
    ) {
        $this->onQueue((string) config('queue.biometric_queue', 'biometric'));
    }

    public static function dispatchForTenant(int $tenantId, int $memberId, string $action): void
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        try {
            self::dispatch($tenantId, $memberId, $action);
        } finally {
            if ($tenant) {
                app()->instance('tenant', $tenant);
            }
        }
    }

    public function handle(BiometricSyncService $biometric): void
    {
        $tenant = app(TenantDatabaseManager::class)->activateById($this->tenantId)
            ?? Tenant::find($this->tenantId);

        if (!$tenant) {
            throw new \RuntimeException('Tenant not found for biometric member sync.');
        }

        app()->instance('tenant', $tenant);

        $member = Member::query()->find($this->memberId);

        if (!$member) {
            Log::warning('SyncBiometricMemberJob: member not found.', [
                'tenant_id' => $this->tenantId,
                'member_id' => $this->memberId,
                'action' => $this->action,
            ]);

            return;
        }

        $startedAt = now()->subSecond();

        $biometric->syncMember($member, $this->action);

        $latestLog = BiometricSyncLog::query()
            ->where('member_id', $member->id)
            ->where('action', $this->action)
            ->where('created_at', '>=', $startedAt)
            ->latest('created_at')
            ->first();

        if ($latestLog?->status === 'failed') {
            throw new \RuntimeException($latestLog->error_message ?: 'Biometric member sync failed.');
        }
    }
}

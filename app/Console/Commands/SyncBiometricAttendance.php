<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\BiometricSyncService;
use Illuminate\Console\Command;

class SyncBiometricAttendance extends Command
{
    protected $signature = 'biometric:sync-attendance {--tenant= : Specific tenant ID to sync}';

    protected $description = 'Pull attendance events from biometric devices for all eligible tenants';

    public function handle(BiometricSyncService $biometric): int
    {
        $tenantId = $this->option('tenant');

        $query = Tenant::query();

        if ($tenantId) {
            $query->where('id', $tenantId);
        }

        $tenants = $query->get();

        foreach ($tenants as $tenant) {
            if (!$biometric->isAttendanceSyncEnabled($tenant->id)) {
                continue;
            }

            $this->info("Syncing attendance for tenant: {$tenant->name} (ID: {$tenant->id})");

            $result = $biometric->pullAttendance($tenant);

            $this->info("  → {$result['message']}");
        }

        return Command::SUCCESS;
    }
}

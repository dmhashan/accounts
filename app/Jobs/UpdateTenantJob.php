<?php

namespace App\Jobs;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class UpdateTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $jobId;

    public function __construct(string $jobId)
    {
        $this->jobId = $jobId;
    }

    public function handle(): void
    {
        $centralConnection = (string) config('tenancy.central_connection', 'central');
        $jobRecord = DB::connection($centralConnection)->table('tenant_operation_jobs')->where('id', $this->jobId)->first();

        if (!$jobRecord) {
            return;
        }

        $payload = json_decode($jobRecord->payload ?? '{}', true);
        $subdomain = strtolower(trim($jobRecord->tenant_subdomain));
        $name = $payload['name'] ?? '';
        $email = $payload['email'] ?? null;
        $phone = $payload['phone'] ?? null;

        $steps = json_decode($jobRecord->steps, true);

        $updateStep = function (string $stepKey, string $status, ?string $errorMsg = null) use (&$steps, $centralConnection) {
            $currentStepIndex = 0;

            foreach ($steps as $idx => &$step) {
                if ($step['key'] === $stepKey) {
                    $step['status'] = $status;

                    if ($errorMsg) {
                        $step['error'] = $errorMsg;
                    }
                    $currentStepIndex = $idx + 1;
                    break;
                }
            }

            $overallStatus = ($status === 'failed')
                ? 'failed'
                : (($currentStepIndex === count($steps) && $status === 'completed') ? 'completed' : 'processing');

            $updateData = [
                'steps' => json_encode($steps),
                'current_step' => $currentStepIndex,
                'status' => $overallStatus,
                'updated_at' => now(),
            ];

            if ($errorMsg) {
                $updateData['error_message'] = $errorMsg;
            }

            DB::connection($centralConnection)->table('tenant_operation_jobs')->where('id', $this->jobId)->update($updateData);
        };

        $tenant = DB::connection($centralConnection)->table('tenants')->where('subdomain', $subdomain)->first();
        $isolationEnabled = (bool) config('tenancy.database_isolation_enabled', false);

        // Backup original state for revert capability
        $originalState = $tenant ? [
            'name' => $tenant->name,
            'email' => $tenant->email,
            'phone' => $tenant->phone,
            'is_active' => $tenant->is_active ?? true,
        ] : null;

        try {
            // Step 1: validate
            $updateStep('validate', 'processing');

            if (!$tenant) {
                throw new \InvalidArgumentException("Tenant '{$subdomain}' not found in registry.");
            }

            if (empty($name)) {
                throw new \InvalidArgumentException('Tenant name is required.');
            }
            $updateStep('validate', 'completed');

            // Step 2: central_registry
            $updateStep('central_registry', 'processing');
            $updateData = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'updated_at' => now(),
            ];

            if (isset($payload['is_active'])) {
                $updateData['is_active'] = (bool) $payload['is_active'];
            }
            DB::connection($centralConnection)->table('tenants')->where('subdomain', $subdomain)->update($updateData);
            $updateStep('central_registry', 'completed');

            // Step 3: tenant_database
            $updateStep('tenant_database', 'processing');
            $uuid = $tenant->database_name;

            if ($isolationEnabled && $uuid) {
                $centralConnectionConfig = config('database.connections.central');
                $tenantConfig = $centralConnectionConfig;
                $tenantConfig['database'] = $uuid;
                $tenantConfig['url'] = null;
                config(['database.connections.tenant' => $tenantConfig]);

                DB::purge('tenant');
                DB::reconnect('tenant');

                $tableExists = DB::connection('tenant')->selectOne(
                    "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'tenants'",
                    [$uuid],
                );

                if ($tableExists) {
                    DB::connection('tenant')->table('tenants')->where('tenant_uuid', $uuid)->update([
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'updated_at' => now(),
                    ]);
                }
            } else {
                if (Schema::hasTable('tenants')) {
                    Tenant::where('domain', $subdomain)->update([
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                    ]);
                }
            }
            $updateStep('tenant_database', 'completed');

            // Step 4: finalize
            $updateStep('finalize', 'processing');
            $updateStep('finalize', 'completed');

        } catch (\Throwable $e) {
            Log::error("UpdateTenantJob failed for {$subdomain}: " . $e->getMessage());

            // REVERT: Rollback central and isolated tenant records back to original state
            if ($originalState) {
                try {
                    DB::connection($centralConnection)->table('tenants')->where('subdomain', $subdomain)->update([
                        'name' => $originalState['name'],
                        'email' => $originalState['email'],
                        'phone' => $originalState['phone'],
                        'is_active' => $originalState['is_active'],
                        'updated_at' => now(),
                    ]);
                } catch (\Throwable $revertErr) {
                    // Ignore revert error
                }

                if ($isolationEnabled && !empty($tenant->database_name)) {
                    try {
                        DB::connection('tenant')->table('tenants')->where('tenant_uuid', $tenant->database_name)->update([
                            'name' => $originalState['name'],
                            'email' => $originalState['email'],
                            'phone' => $originalState['phone'],
                            'updated_at' => now(),
                        ]);
                    } catch (\Throwable $revertErr) {
                        // Ignore revert error
                    }
                } else {
                    try {
                        if (Schema::hasTable('tenants')) {
                            Tenant::where('domain', $subdomain)->update([
                                'name' => $originalState['name'],
                                'email' => $originalState['email'],
                                'phone' => $originalState['phone'],
                            ]);
                        }
                    } catch (\Throwable $revertErr) {
                        // Ignore revert error
                    }
                }
            }

            $failedStepKey = 'validate';

            foreach ($steps as $step) {
                if ($step['status'] === 'processing') {
                    $failedStepKey = $step['key'];
                    break;
                }
            }
            $updateStep($failedStepKey, 'failed', $e->getMessage());
        } finally {
            DB::purge('tenant');
        }
    }
}

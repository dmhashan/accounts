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

class DeleteTenantJob implements ShouldQueue
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

        $subdomain = strtolower(trim($jobRecord->tenant_subdomain));
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

        try {
            // Step 1: validate
            $updateStep('validate', 'processing');

            if (!$tenant) {
                throw new \InvalidArgumentException("Tenant '{$subdomain}' not found in registry.");
            }

            if ($tenant->is_active) {
                throw new \InvalidArgumentException("Cannot delete active tenant '{$subdomain}'. Please suspend/block the tenant first.");
            }
            $updateStep('validate', 'completed');

            // Step 2: drop_database
            $updateStep('drop_database', 'processing');
            $uuid = $tenant->database_name;

            if ($isolationEnabled && $uuid) {
                if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid)) {
                    DB::connection($centralConnection)->statement("DROP DATABASE IF EXISTS `{$uuid}`");
                }
            }
            $updateStep('drop_database', 'completed');

            // Step 3: local_cleanup
            $updateStep('local_cleanup', 'processing');

            try {
                $localTenant = Tenant::where('domain', $subdomain)->first();

                if ($localTenant) {
                    $localTenant->delete();
                }
            } catch (\Throwable $e) {
                // Ignore local model delete error
            }
            $updateStep('local_cleanup', 'completed');

            // Step 4: central_registry
            $updateStep('central_registry', 'processing');
            DB::connection($centralConnection)->table('tenants')->where('subdomain', $subdomain)->delete();
            $updateStep('central_registry', 'completed');

        } catch (\Throwable $e) {
            Log::error("DeleteTenantJob failed for {$subdomain}: " . $e->getMessage());

            $failedStepKey = 'validate';

            foreach ($steps as $step) {
                if ($step['status'] === 'processing') {
                    $failedStepKey = $step['key'];
                    break;
                }
            }
            $updateStep($failedStepKey, 'failed', $e->getMessage());
        }
    }
}

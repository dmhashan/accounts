<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateTenantJob implements ShouldQueue
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
        $subdomain = strtolower(trim($payload['domain'] ?? ''));
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

        $uuid = (string) Str::uuid();
        $isolationEnabled = (bool) config('tenancy.database_isolation_enabled', false);

        try {
            // Step 1: validate
            $updateStep('validate', 'processing');

            if (empty($subdomain) || empty($name)) {
                throw new \InvalidArgumentException('Tenant name and domain are required.');
            }
            $updateStep('validate', 'completed');

            // Step 2: central_registry
            $updateStep('central_registry', 'processing');
            DB::connection($centralConnection)->table('tenants')->insert([
                'subdomain' => $subdomain,
                'database_name' => $uuid,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $updateStep('central_registry', 'completed');

            if ($isolationEnabled) {
                // Step 3: create_database
                $updateStep('create_database', 'processing');
                DB::connection($centralConnection)->statement("CREATE DATABASE `{$uuid}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $updateStep('create_database', 'completed');

                // Step 4: migrate_database
                $updateStep('migrate_database', 'processing');
                $centralConnectionConfig = config('database.connections.central');
                $tenantConfig = $centralConnectionConfig;
                $tenantConfig['database'] = $uuid;
                $tenantConfig['url'] = null;
                config(['database.connections.tenant' => $tenantConfig]);

                DB::purge('tenant');
                DB::reconnect('tenant');

                $migrationPath = config('tenancy.tenant_migrations_path', 'database/migrations/tenant');
                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => [$migrationPath],
                    '--force' => true,
                    '--no-interaction' => true,
                ]);
                $updateStep('migrate_database', 'completed');

                // Step 5: seed_database
                $updateStep('seed_database', 'processing');
                Artisan::call('db:seed', [
                    '--database' => 'tenant',
                    '--class' => 'Database\\Seeders\\RoleSeeder',
                    '--force' => true,
                    '--no-interaction' => true,
                ]);
                $updateStep('seed_database', 'completed');

                // Step 6: finalize
                $updateStep('finalize', 'processing');
                DB::connection('tenant')->table('tenants')->insert([
                    'name' => $name,
                    'domain' => $subdomain,
                    'tenant_uuid' => $uuid,
                    'email' => $email,
                    'phone' => $phone,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $updateStep('finalize', 'completed');
            } else {
                // Single DB mode bypass steps
                $updateStep('create_database', 'completed');
                $updateStep('migrate_database', 'completed');
                $updateStep('seed_database', 'completed');

                $updateStep('finalize', 'processing');

                if (Schema::hasTable('tenants')) {
                    \App\Models\Tenant::updateOrCreate(
                        ['domain' => $subdomain],
                        [
                            'name' => $name,
                            'tenant_uuid' => $uuid,
                            'email' => $email,
                            'phone' => $phone,
                        ],
                    );
                }
                $updateStep('finalize', 'completed');
            }

        } catch (\Throwable $e) {
            Log::error("CreateTenantJob failed for {$subdomain}: " . $e->getMessage());

            if ($isolationEnabled && isset($uuid)) {
                try {
                    DB::connection($centralConnection)->statement("DROP DATABASE IF EXISTS `{$uuid}`");
                } catch (\Throwable $cleanupErr) {
                    // Ignore
                }
            }

            try {
                DB::connection($centralConnection)->table('tenants')->where('subdomain', $subdomain)->delete();
            } catch (\Throwable $cleanupErr) {
                // Ignore
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

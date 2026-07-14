<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:delete {subdomain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete an inactive tenant registry record and drop its isolated database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $subdomain = $this->argument('subdomain');
        $centralConnection = (string) config('tenancy.central_connection', 'central');

        // 1. Fetch central tenant record
        $tenant = DB::connection($centralConnection)
            ->table('tenants')
            ->where('subdomain', $subdomain)
            ->first();

        if (!$tenant) {
            $this->error("Tenant '{$subdomain}' not found in the central registry.");

            return self::FAILURE;
        }

        // 2. Enforce check that only inactive tenants can be deleted
        if ($tenant->is_active) {
            $this->error("Cannot delete active tenant '{$subdomain}'. You must suspend/block the tenant first.");

            return self::FAILURE;
        }

        // 3. Confirm deletion
        if (!$this->confirm("Are you absolutely sure you want to permanently delete tenant '{$subdomain}'? This drops its database and cannot be undone.")) {
            $this->info('Deletion cancelled.');

            return self::SUCCESS;
        }

        // 4. Drop isolated database if database isolation is enabled
        $isolationEnabled = (bool) config('tenancy.database_isolation_enabled', false);
        $uuid = $tenant->database_name;

        if ($isolationEnabled && $uuid) {
            try {
                if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid)) {
                    $this->info("Dropping isolated database '{$uuid}'...");
                    DB::connection($centralConnection)->statement("DROP DATABASE IF EXISTS `{$uuid}`");
                } else {
                    $this->warn("Skipping database drop: '{$uuid}' does not appear to be a safe UUID database name.");
                }
            } catch (\Throwable $e) {
                $this->warn("Failed dropping database '{$uuid}': " . $e->getMessage());
                Log::warning("DeleteTenantCommand: failed dropping database '{$uuid}': " . $e->getMessage());
            }
        }

        // 5. Clean up local tenant row (non-isolated / bypass mode fallback)
        try {
            $localTenant = \App\Models\Tenant::where('domain', $subdomain)->first();

            if ($localTenant) {
                $this->info('Deleting local tenant representation...');
                $localTenant->delete();
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        // 6. Delete central registry row
        DB::connection($centralConnection)->table('tenants')->where('subdomain', $subdomain)->delete();

        $this->info("Tenant '{$subdomain}' has been successfully deleted.");

        return self::SUCCESS;
    }
}

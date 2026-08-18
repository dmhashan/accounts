<?php

namespace App\Console\Commands\import_data_from_fitrobit;

use App\Models\Tenant;
use App\Services\Tenancy\TenantDatabaseManager;
use Illuminate\Console\Command;

class ImportFitrobitDataCommand extends Command
{
    protected $signature = 'fitrobit:import
        {--tenant-id= : Target tenant ID}
        {--tenant-domain= : Target tenant domain}
        {--members-file=resources/import/hulkfitness_members.xlsx : Path to members Excel file}
        {--plans-file=resources/import/hulkfitness_payment_plans.xlsx : Path to payment plans Excel file}
        {--sheet=Members : Sheet name in members file}
        {--create-memberships=true : Create payment and membership records from RenewalDate}
        {--account-name=Cash Account : Name of the company account to assign payments to}
        {--skip-plans : Skip importing payment plans}
        {--dry-run : Simulate the import without saving changes}';

    protected $description = 'Master command to import payment plans and members from Fitrobit Excel exports';

    public function handle(): int
    {
        $tenant = $this->resolveTenant();

        if (!$tenant) {
            $this->error('Tenant not found. Provide --tenant-id or --tenant-domain.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $skipPlans = (bool) $this->option('skip-plans');
        $tenantId = $tenant->id;

        $this->info('=========================================================');
        $this->info("  Fitrobit Data Import: Tenant #{$tenantId} ({$tenant->domain})");
        $this->info('=========================================================');

        if ($dryRun) {
            $this->warn("RUNNING IN DRY-RUN MODE (No database records will be modified)\n");
        }

        // 1. Import Payment Plans
        if (!$skipPlans) {
            $this->info('Step 1/2: Importing Payment Plans...');
            $planResult = $this->call('fitrobit:import-plans', array_filter([
                '--tenant-id' => (string) $tenantId,
                '--file' => (string) $this->option('plans-file'),
                '--dry-run' => $dryRun,
            ]));

            if ($planResult !== self::SUCCESS) {
                $this->error('Plan import failed. Aborting master import.');

                return self::FAILURE;
            }
            $this->newLine();
        } else {
            $this->info("Step 1/2: Skipped Payment Plans (--skip-plans enabled).\n");
        }

        // 2. Import Members
        $this->info('Step 2/2: Importing Members & Membership History...');
        $memberResult = $this->call('fitrobit:import-members', array_filter([
            '--tenant-id' => (string) $tenantId,
            '--file' => (string) $this->option('members-file'),
            '--sheet' => (string) $this->option('sheet'),
            '--create-memberships' => (string) $this->option('create-memberships'),
            '--account-name' => (string) $this->option('account-name'),
            '--dry-run' => $dryRun,
        ]));

        if ($memberResult !== self::SUCCESS) {
            $this->error('Member import encountered errors.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('=========================================================');
        $this->info('  Fitrobit Import Completed Successfully!');
        $this->info('=========================================================');

        return self::SUCCESS;
    }

    private function resolveTenant(): ?Tenant
    {
        $tenancy = app(TenantDatabaseManager::class);
        $tenantId = $this->option('tenant-id');

        if ($tenantId !== null && $tenantId !== '') {
            return $tenancy->activateById((int) $tenantId);
        }

        $tenantDomain = trim((string) $this->option('tenant-domain'));

        if ($tenantDomain !== '') {
            return $tenancy->activateByDomain($tenantDomain);
        }

        $bypassDomain = (string) config('app.multitenancy_bypass_domain');

        if ($bypassDomain !== '') {
            return $tenancy->activateByDomain($bypassDomain);
        }

        return null;
    }
}

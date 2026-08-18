<?php

namespace App\Console\Commands\import_data_from_fitrobit;

use App\Models\PaymentPlan;
use App\Models\Tenant;
use App\Services\Tenancy\TenantDatabaseManager;
use Illuminate\Console\Command;

class ImportFitrobitPlansCommand extends Command
{
    protected $signature = 'fitrobit:import-plans
        {--tenant-id= : Target tenant ID}
        {--tenant-domain= : Target tenant domain}
        {--file=resources/import/hulkfitness_payment_plans.xlsx : Path to payment plans Excel file}
        {--sheet=Sheet1 : Sheet name in the Excel file}
        {--dry-run : Preview changes without saving to the database}';

    protected $description = 'Import payment plans from Fitrobit exported Excel file';

    public function handle(FitrobitXlsxReader $reader): int
    {
        $tenant = $this->resolveTenant();

        if (!$tenant) {
            $this->error('Tenant not found. Provide --tenant-id or --tenant-domain.');

            return self::FAILURE;
        }

        $filePath = base_path((string) $this->option('file'));
        $sheetName = (string) $this->option('sheet');
        $dryRun = (bool) $this->option('dry-run');

        if (!file_exists($filePath)) {
            $this->error("Plans file not found at: {$filePath}");

            return self::FAILURE;
        }

        $this->info("Importing payment plans for tenant {$tenant->id} ({$tenant->domain})" . ($dryRun ? ' [DRY-RUN]' : ''));

        try {
            $rows = $reader->readSheet($filePath, $sheetName);
        } catch (\Throwable $e) {
            $this->error("Failed to read Excel file: {$e->getMessage()}");

            return self::FAILURE;
        }

        if (count($rows) === 0) {
            $this->warn('No plan rows found in the Excel file.');

            return self::SUCCESS;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $tableRows = [];

        foreach ($rows as $row) {
            $name = trim((string) ($row['Name'] ?? ''));
            $priceRaw = trim((string) ($row['Price'] ?? '0'));
            $statusRaw = trim((string) ($row['Status'] ?? 'Active'));

            if ($name === '') {
                $skipped++;
                continue;
            }

            $price = is_numeric($priceRaw) ? (float) $priceRaw : 0.0;
            $isActive = strtolower($statusRaw) === 'active';
            [$durationValue, $durationUnit] = $this->classifyDuration($name);

            $existingPlan = PaymentPlan::withTrashed()
                ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                ->first();

            $statusText = 'unchanged';

            if ($existingPlan) {
                if (!$dryRun) {
                    $existingPlan->restore();
                    $existingPlan->update([
                        'duration_value' => $durationValue,
                        'duration_unit' => $durationUnit,
                        'price' => $price,
                        'is_active' => $isActive,
                    ]);
                }
                $updated++;
                $statusText = 'updated';
            } else {
                if (!$dryRun) {
                    PaymentPlan::create([
                        'name' => $name,
                        'duration_value' => $durationValue,
                        'duration_unit' => $durationUnit,
                        'price' => $price,
                        'is_active' => $isActive,
                    ]);
                }
                $created++;
                $statusText = 'created';
            }

            $tableRows[] = [
                $name,
                number_format($price, 2),
                "{$durationValue} {$durationUnit}(s)",
                $isActive ? 'Active' : 'Inactive',
                $dryRun ? "would {$statusText}" : $statusText,
            ];
        }

        $this->table(['Plan Name', 'Price (LKR)', 'Duration', 'Status', 'Action'], $tableRows);

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Total Rows', (string) count($rows)],
            ['Created', (string) $created],
            ['Updated', (string) $updated],
            ['Skipped', (string) $skipped],
        ]);

        $this->info($dryRun ? 'Dry run completed. No database changes were made.' : 'Payment plans imported successfully.');

        return self::SUCCESS;
    }

    /**
     * Infer duration value and unit from plan name.
     *
     * @return array{0: int, 1: string}
     */
    public function classifyDuration(string $name): array
    {
        $normalized = strtolower(trim($name));

        if (str_contains($normalized, 'half month') || str_contains($normalized, 'half-month') || str_contains($normalized, '15 day')) {
            return [15, 'day'];
        }

        if (str_contains($normalized, 'annual') || str_contains($normalized, 'year') || str_contains($normalized, '12 month')) {
            return [1, 'year'];
        }

        if (str_contains($normalized, '6 month')) {
            return [6, 'month'];
        }

        if (str_contains($normalized, '3 month')) {
            return [3, 'month'];
        }

        if (str_contains($normalized, '2 month')) {
            return [2, 'month'];
        }

        if (str_contains($normalized, 'week') || str_contains($normalized, '7 day')) {
            return [1, 'week'];
        }

        if (str_contains($normalized, 'day pass') || str_contains($normalized, 'daily') || str_contains($normalized, '1 day')) {
            return [1, 'day'];
        }

        // Default standard gym duration is 1 month
        return [1, 'month'];
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

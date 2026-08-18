<?php

namespace App\Console\Commands\import_data_from_fitrobit;

use App\Models\CompanyAccount;
use App\Models\CompanyAccountTransaction;
use App\Models\Member;
use App\Models\MemberPayment;
use App\Models\PaymentMembership;
use App\Models\PaymentPlan;
use App\Models\Tenant;
use App\Services\Tenancy\TenantDatabaseManager;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportFitrobitMembersCommand extends Command
{
    protected $signature = 'fitrobit:import-members
        {--tenant-id= : Target tenant ID}
        {--tenant-domain= : Target tenant domain}
        {--file=resources/import/hulkfitness_members.xlsx : Path to members Excel file}
        {--sheet=Members : Sheet name in the Excel file}
        {--create-memberships=true : Create payment and membership records from RenewalDate}
        {--account-name=Cash Account : Name of the company account to assign payments to}
        {--dry-run : Simulate the import without saving changes}';

    protected $description = 'Import members and membership history from Fitrobit exported Excel file';

    public function handle(FitrobitXlsxReader $reader): int
    {
        $tenant = $this->resolveTenant();

        if (!$tenant) {
            $this->error('Tenant not found. Provide --tenant-id or --tenant-domain.');

            return self::FAILURE;
        }

        $filePath = base_path((string) $this->option('file'));
        $sheetName = (string) $this->option('sheet');
        $createMemberships = filter_var($this->option('create-memberships'), FILTER_VALIDATE_BOOLEAN);
        $dryRun = (bool) $this->option('dry-run');

        if (!file_exists($filePath)) {
            $this->error("Members file not found at: {$filePath}");

            return self::FAILURE;
        }

        $cashAccount = null;

        if ($createMemberships && !$dryRun) {
            $accountName = trim((string) $this->option('account-name'));
            $cashAccount = CompanyAccount::query()
                ->where('name', $accountName)
                ->first()
                ?? CompanyAccount::query()->first();

            if ($cashAccount) {
                $this->info("Assigned payments to company account: {$cashAccount->name} (ID: {$cashAccount->id})");
            }
        }

        $this->info("Importing members for tenant {$tenant->id} ({$tenant->domain})" . ($dryRun ? ' [DRY-RUN]' : ''));

        try {
            $rows = $reader->readSheet($filePath, $sheetName);
        } catch (\Throwable $e) {
            $this->error("Failed to read Excel file: {$e->getMessage()}");

            return self::FAILURE;
        }

        if (count($rows) === 0) {
            $this->warn('No member rows found in the Excel file.');

            return self::SUCCESS;
        }

        $planLookup = PaymentPlan::withTrashed()
            ->get()
            ->keyBy(fn (PaymentPlan $p) => strtolower(trim($p->name)));

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $membershipsCreated = 0;

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach ($rows as $row) {
            $rawMemberId = trim((string) ($row['MemberId'] ?? ''));
            $name = trim((string) ($row['Name'] ?? ''));

            if ($name === '' && $rawMemberId === '') {
                $skipped++;
                $bar->advance();
                continue;
            }

            $memberCode = $this->sanitizeMemberCode($rawMemberId, $tenant->id);
            $phoneNumber = $this->sanitizeText($row['PhoneNumber'] ?? '');
            $email = $this->sanitizeEmail($row['Email'] ?? '');
            $nic = $this->sanitizeText($row['NIC'] ?? '');
            $dob = $this->parseDate($row['DateOfBirth'] ?? '');
            $gender = $this->normalizeGender($row['Gender'] ?? '');
            $address = $this->sanitizeText($row['Address'] ?? '');
            $isActive = strtolower(trim((string) ($row['Status'] ?? ''))) === 'active';
            $joinedDate = $this->parseDate($row['CreatedDate'] ?? '') ?? now()->toDateString();
            $renewalDate = $this->parseDate($row['RenewalDate'] ?? '');
            $comment = $this->sanitizeText($row['Remark'] ?? '');

            $planNameRaw = trim((string) ($row['MemberShip'] ?? ''));
            $matchedPlan = $planNameRaw !== '' && $planNameRaw !== '_'
                ? ($planLookup->get(strtolower($planNameRaw)) ?? null)
                : null;

            $planId = $matchedPlan?->id;
            $price = $matchedPlan ? (float) $matchedPlan->price : 0.0;

            if ($dryRun) {
                $created++;
                $bar->advance();
                continue;
            }

            DB::transaction(function () use (

                $memberCode,
                $name,
                $gender,
                $phoneNumber,
                $email,
                $nic,
                $dob,
                $address,
                $isActive,
                $joinedDate,
                $renewalDate,
                $comment,
                $planId,
                $price,
                $createMemberships,
                $cashAccount,
                &$created,
                &$updated,
                &$membershipsCreated
            ) {
                /** @var Member|null $member */
                $member = Member::query()
                    ->where('biometric_member_id', $memberCode)
                    ->when($phoneNumber, fn ($q) => $q->orWhere('phone_number', $phoneNumber))
                    ->first();

                $isNew = false;

                if (!$member) {
                    $member = new Member;
                    $member->biometric_member_id = $memberCode;
                    $member->registration_source = 'fitrobit_import';
                    $member->biometric_last_synced_at = now();
                    $isNew = true;
                }

                $member->name = $name !== '' ? $name : 'Member ' . $memberCode;
                $member->gender = $gender;
                $member->phone_number = $phoneNumber;
                $member->email = $email;
                $member->nic = $nic;
                $member->date_of_birth = $dob;
                $member->address = $address;
                $member->is_active = $isActive;
                $member->is_verified = true;
                $member->joined_date = $joinedDate;
                $member->comment = $comment;
                $member->payment_plan_id = $planId;
                $member->price = $price;
                $member->save();

                if ($isNew) {
                    $created++;
                } else {
                    $updated++;
                }

                // Create initial membership validity & payment record from RenewalDate
                if ($createMemberships && $renewalDate) {
                    $legacyKey = "fitrobit-member-{$member->id}-{$renewalDate}";

                    $payment = MemberPayment::firstOrNew(['legacy_uuid' => $legacyKey]);
                    $payment->member_id = $member->id;
                    $payment->company_account_id = $cashAccount?->id;
                    $payment->amount = $price;
                    $payment->paid_amount = $price;
                    $payment->balance = 0.00;
                    $payment->payment_date = $joinedDate;
                    $payment->is_paid = true;
                    $payment->notes = "Imported from Fitrobit | Renewal: {$renewalDate}";
                    $payment->save();

                    PaymentMembership::updateOrCreate(
                        ['member_payment_id' => $payment->id],
                        [
                            'payment_plan_id' => $planId,
                            'start_date' => $joinedDate,
                            'end_date' => $renewalDate,
                        ],
                    );

                    if ($cashAccount && $price > 0) {
                        CompanyAccountTransaction::updateOrCreate(
                            [
                                'model_name' => 'payment',
                                'reference_id' => $payment->id,
                            ],
                            [
                                'company_account_id' => $cashAccount->id,
                                'type' => 'payment',
                                'amount' => $price,
                                'transaction_date' => $joinedDate,
                                'reference_number' => $payment->reference_number,
                                'notes' => 'Payment: ' . $member->name,
                            ],
                        );
                    }

                    $membershipsCreated++;
                }
            });

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['Metric', 'Count'], [
            ['Total Rows Processed', (string) count($rows)],
            ['Members Created', (string) $created],
            ['Members Updated', (string) $updated],
            ['Members Skipped', (string) $skipped],
            ['Memberships & Expiries Synced', (string) $membershipsCreated],
        ]);

        $this->info($dryRun ? 'Dry run completed. No database changes were made.' : 'Members imported successfully.');

        return self::SUCCESS;
    }

    private function sanitizeMemberCode(string $raw, int $tenantId): string
    {
        $trimmed = trim($raw);

        if ($trimmed === '' || $trimmed === '_') {
            return Member::generateBiometricMemberId($tenantId);
        }

        // If numeric, preserve ID
        if (ctype_digit($trimmed)) {
            return $trimmed;
        }

        // Extract leading numeric part if available (e.g. "12 Forgeon" -> "12")
        if (preg_match('/^(\d+)/', $trimmed, $matches)) {
            return $matches[1];
        }

        return Str::slug($trimmed, '_');
    }

    private function sanitizeText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $str = trim((string) $value);

        if ($str === '' || $str === '_') {
            return null;
        }

        return $str;
    }

    private function sanitizeEmail(mixed $value): ?string
    {
        $clean = $this->sanitizeText($value);

        if (!$clean) {
            return null;
        }

        $lower = strtolower($clean);

        return filter_var($lower, FILTER_VALIDATE_EMAIL) ? $lower : null;
    }

    private function normalizeGender(mixed $value): string
    {
        $clean = strtolower(trim((string) $value));

        return match ($clean) {
            'male', 'm' => 'male',
            'female', 'f' => 'female',
            default => 'other',
        };
    }

    private function parseDate(mixed $value): ?string
    {
        $clean = $this->sanitizeText($value);

        if (!$clean) {
            return null;
        }

        try {
            return Carbon::parse($clean)->toDateString();
        } catch (\Throwable) {
            return null;
        }
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

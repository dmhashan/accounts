<?php

namespace App\Console\Commands;

use App\Models\CompanyAccount;
use App\Models\CompanyAccountTransaction;
use App\Models\Member;
use App\Models\MemberPayment;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SyncPaymentsCommand extends Command
{
    protected $signature = 'legacy:sync-payments
        {--access-token= : Bearer access token for the legacy API}
        {--date-start= : Start date (YYYY-MM-DD, inclusive) — filters by paymentdate}
        {--date-end= : End date (YYYY-MM-DD, inclusive) — filters by paymentdate}
        {--tenant-id= : Target tenant ID}
        {--tenant-domain= : Target tenant domain}
        {--account-name=Cash : Name of the company account to assign payments to}
        {--page-size=100 : Number of records per API page (max 500)}
        {--base-url=https://gm-api.nanosoft.lk/api/gym : Legacy API base URL}';

    protected $description = 'Sync membership payment history from the legacy gym API into member_payments';

    public function handle(): int
    {
        $token = trim((string) $this->option('access-token'));
        if ($token === '') {
            $this->error('--access-token is required.');
            return self::FAILURE;
        }

        $tenant = $this->resolveTenant();
        if (!$tenant) {
            $this->error('Tenant not found. Provide --tenant-id or --tenant-domain.');
            return self::FAILURE;
        }

        $accountName = trim((string) $this->option('account-name'));
        $cashAccount = CompanyAccount::where('tenant_id', $tenant->id)
            ->where('name', $accountName)
            ->first();

        if (!$cashAccount) {
            $this->error("Company account \"{$accountName}\" not found for tenant {$tenant->domain}.");
            return self::FAILURE;
        }

        $this->info("Using account: {$cashAccount->name} (ID: {$cashAccount->id})");

        $dateStart = $this->resolveDate('date-start');
        $dateEnd   = $this->resolveDate('date-end');

        if ($dateStart && $dateEnd && $dateStart->gt($dateEnd)) {
            $this->error('--date-start must not be after --date-end.');
            return self::FAILURE;
        }

        $pageSize = max(1, min(500, (int) $this->option('page-size')));
        $baseUrl  = rtrim((string) $this->option('base-url'), '/');

        $this->info("Syncing payments for tenant {$tenant->id} ({$tenant->domain})");

        if ($dateStart || $dateEnd) {
            $from = $dateStart?->toDateString() ?? '(any)';
            $to   = $dateEnd?->toDateString()   ?? '(any)';
            $this->info("Date filter: {$from} → {$to}");
        } else {
            $this->info('Date filter: none (all payments)');
        }

        $page      = 1;
        $inserted  = 0;
        $skipped   = 0;
        $outOfRange = 0;

        do {
            $response = $this->requestWithRetry($token, "{$baseUrl}/getpaymenthistory", [
                'page' => $page,
                'size' => $pageSize,
            ]);

            if (!$response) {
                $this->warn("  Page {$page} — request failed after retries, aborting.");
                return self::FAILURE;
            }

            if (!$response->successful()) {
                $this->warn("  Page {$page} — HTTP {$response->status()}, aborting.");
                return self::FAILURE;
            }

            $payload    = $response->json();
            $items      = $payload['items'] ?? [];
            $totalCount = (int) ($payload['totalCount'] ?? 0);

            if (!is_array($items) || count($items) === 0) {
                break;
            }

            $pageInserted = 0;
            $pageSkipped  = 0;

            foreach ($items as $entry) {
                if (!is_array($entry)) {
                    $pageSkipped++;
                    continue;
                }

                $legacyUuid     = isset($entry['id'])       ? (string) $entry['id']       : null;
                $legacyMemberId = isset($entry['memberid']) ? (int) $entry['memberid']     : null;
                $username       = isset($entry['username']) ? (string) $entry['username']  : null;
                $amount         = isset($entry['amount'])   ? (float) $entry['amount']     : null;
                $paymentDateRaw = $entry['paymentdate'] ?? null;

                if (!$legacyUuid || $amount === null || !$paymentDateRaw) {
                    $pageSkipped++;
                    continue;
                }

                try {
                    $paymentDate = Carbon::parse($paymentDateRaw)->toDateString();
                } catch (\Throwable) {
                    $pageSkipped++;
                    continue;
                }

                // Apply date range filter
                if ($dateStart && Carbon::parse($paymentDate)->lt($dateStart)) {
                    $outOfRange++;
                    continue;
                }
                if ($dateEnd && Carbon::parse($paymentDate)->gt($dateEnd)) {
                    $outOfRange++;
                    continue;
                }

                $localMemberId = $this->resolveLocalMemberId($tenant->id, $username);

                $notes = 'Synced from legacy system';
                if (isset($entry['nextpaymentdate'])) {
                    try {
                        $next = Carbon::parse($entry['nextpaymentdate'])->toDateString();
                        $notes .= " | next payment: {$next}";
                    } catch (\Throwable) {
                        // ignore bad dates
                    }
                }

                try {
                    DB::table('member_payments')->upsert(
                        [
                            'tenant_id'          => $tenant->id,
                            'member_id'          => $localMemberId,
                            'company_account_id' => $cashAccount->id,
                            'amount'             => $amount,
                            'payment_date'       => $paymentDate,
                            'legacy_uuid'        => $legacyUuid,
                            'legacy_member_id'   => $legacyMemberId,
                            'legacy_username'    => $username,
                            'notes'              => $notes,
                            'created_at'         => now(),
                            'updated_at'         => now(),
                        ],
                        ['tenant_id', 'legacy_uuid'],
                        ['member_id', 'company_account_id', 'legacy_member_id', 'legacy_username', 'amount', 'payment_date', 'notes', 'updated_at']
                    );
                    $pageInserted++;
                } catch (\Throwable $e) {
                    $this->warn("  Failed to upsert {$legacyUuid}: {$e->getMessage()}");
                    $pageSkipped++;
                }
            }

            $inserted += $pageInserted;
            $skipped  += $pageSkipped;

            $fetched = ($page - 1) * $pageSize + count($items);
            $this->line("  Page {$page} — {$pageInserted} upserted, {$pageSkipped} skipped (fetched {$fetched}/{$totalCount})");

            $page++;
        } while (($page - 1) * $pageSize < $totalCount);

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Upserted',      (string) $inserted],
            ['Skipped',       (string) $skipped],
            ['Out of range',  (string) $outOfRange],
        ]);

        $this->info('Payment sync completed.');

        // ── Remap missing member_id by username ──
        $this->newLine();
        $this->info('Re-mapping member_id for unlinked payment records...');

        $unlinked = DB::table('member_payments')
            ->where('tenant_id', $tenant->id)
            ->whereNull('member_id')
            ->whereNotNull('legacy_username')
            ->distinct()
            ->pluck('legacy_username');

        if ($unlinked->isEmpty()) {
            $this->line('  Nothing to remap.');
        } else {
            $memberMap = Member::where('tenant_id', $tenant->id)
                ->whereIn('username', $unlinked)
                ->pluck('id', 'username');

            $remapped = 0;
            foreach ($memberMap as $username => $memberId) {
                $affected = DB::table('member_payments')
                    ->where('tenant_id', $tenant->id)
                    ->whereNull('member_id')
                    ->where('legacy_username', $username)
                    ->update(['member_id' => $memberId, 'updated_at' => now()]);

                $remapped += $affected;
                $this->line("  @{$username} → member #{$memberId} ({$affected} record(s) updated)");
            }

            $this->line("  Done. {$remapped} record(s) linked.");
        }

        // ── Sync company account transactions ──
        $this->newLine();
        $this->info('Syncing account transactions...');

        $txSynced = 0;
        MemberPayment::where('tenant_id', $tenant->id)
            ->whereNotNull('legacy_uuid')
            ->whereNotNull('company_account_id')
            ->with('member:id,first_name,last_name,name')
            ->chunkById(200, function ($payments) use ($tenant, &$txSynced) {
                foreach ($payments as $payment) {
                    $member = $payment->member;
                    $memberName = $member
                        ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) ?: ($member->name ?: 'Member')
                        : 'Member';

                    CompanyAccountTransaction::updateOrCreate(
                        [
                            'model_name'   => 'payment',
                            'reference_id' => $payment->id,
                        ],
                        [
                            'tenant_id'          => $tenant->id,
                            'company_account_id' => $payment->company_account_id,
                            'type'               => 'payment',
                            'amount'             => (float) $payment->amount,
                            'transaction_date'   => $payment->payment_date->toDateString(),
                            'reference_number'   => $payment->reference_number,
                            'notes'              => filled($payment->notes) ? $payment->notes : 'Payment: ' . $memberName,
                        ]
                    );

                    $txSynced++;
                }
            });

        $this->line("  Done. {$txSynced} transaction(s) synced.");

        return self::SUCCESS;
    }

    private function resolveDate(string $option): ?Carbon
    {
        $value = trim((string) $this->option($option));
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveTenant(): ?Tenant
    {
        $tenantId = $this->option('tenant-id');
        if ($tenantId !== null && $tenantId !== '') {
            return Tenant::find((int) $tenantId);
        }

        $tenantDomain = trim((string) $this->option('tenant-domain'));
        if ($tenantDomain !== '') {
            return Tenant::where('domain', $tenantDomain)->first();
        }

        $bypassDomain = (string) config('app.multitenancy_bypass_domain');
        if ($bypassDomain !== '') {
            return Tenant::where('domain', $bypassDomain)->first();
        }

        return null;
    }

    private function resolveLocalMemberId(int $tenantId, ?string $username): ?int
    {
        if (!$username) {
            return null;
        }

        $id = Member::where('tenant_id', $tenantId)
            ->where('username', $username)
            ->value('id');

        return $id ? (int) $id : null;
    }

    private function requestWithRetry(string $token, string $url, array $query = []): ?Response
    {
        $attempts          = 3;
        $delayMicroseconds = 500000;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return Http::acceptJson()
                    ->withToken($token)
                    ->connectTimeout(10)
                    ->timeout(30)
                    ->get($url, $query);
            } catch (\Throwable) {
                if ($attempt === $attempts) {
                    return null;
                }

                usleep($delayMicroseconds);
            }
        }

        return null;
    }
}

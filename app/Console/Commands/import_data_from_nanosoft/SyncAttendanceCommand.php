<?php

namespace App\Console\Commands\import_data_from_nanosoft;

use App\Models\Member;
use App\Models\Tenant;
use App\Services\Tenancy\TenantDatabaseManager;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SyncAttendanceCommand extends Command
{
    protected $signature = 'legacy:sync-attendance
        {--access-token= : Bearer access token for the legacy API}
        {--date-start= : Start date (YYYY-MM-DD, inclusive)}
        {--date-end= : End date (YYYY-MM-DD, inclusive)}
        {--tenant-id= : Target tenant ID}
        {--tenant-domain= : Target tenant domain}
        {--base-url=https://gm-api.nanosoft.lk/api/gym : Legacy API base URL}';

    protected $description = 'Sync daily attendance from the legacy gym API into member_attendances';

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

        $dateStart = $this->resolveDate('date-start');
        $dateEnd = $this->resolveDate('date-end');

        if (!$dateStart || !$dateEnd) {
            $this->error('--date-start and --date-end are required and must be valid dates (YYYY-MM-DD).');

            return self::FAILURE;
        }

        if ($dateStart->gt($dateEnd)) {
            $this->error('--date-start must not be after --date-end.');

            return self::FAILURE;
        }

        $baseUrl = rtrim((string) $this->option('base-url'), '/');

        $this->info("Syncing attendance for tenant {$tenant->id} ({$tenant->domain})");
        $this->info("Range: {$dateStart->toDateString()} → {$dateEnd->toDateString()}");

        $period = CarbonPeriod::create($dateStart, $dateEnd);

        $inserted = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($period as $date) {
            $dateStr = $date->toDateString();

            $response = $this->requestWithRetry($token, "{$baseUrl}/attendance-summary-to-date", [
                'date' => $dateStr,
            ]);

            if (!$response) {
                $this->warn("  {$dateStr} — request failed after retries, skipping.");
                $errors++;
                continue;
            }

            if (!$response->successful()) {
                $this->warn("  {$dateStr} — HTTP {$response->status()}, skipping.");
                $errors++;
                continue;
            }

            $payload = $response->json();
            $members = $payload['attendedMembers'] ?? [];

            if (!is_array($members) || count($members) === 0) {
                $this->line("  {$dateStr} — 0 attendees.");
                continue;
            }

            $dayInserted = 0;
            $daySkipped = 0;

            foreach ($members as $entry) {
                if (!is_array($entry)) {
                    $daySkipped++;
                    continue;
                }

                $legacyUuid = isset($entry['id']) ? (string) $entry['id'] : null;
                $legacyMemberId = isset($entry['memberId']) ? (int) $entry['memberId'] : null;
                $username = isset($entry['username']) ? (string) $entry['username'] : null;

                if (!$legacyUuid) {
                    $daySkipped++;
                    continue;
                }

                $localMemberId = $this->resolveLocalMemberId($legacyMemberId);

                try {
                    DB::table('member_attendances')->upsert(
                        [
                            'member_id' => $localMemberId,
                            'legacy_uuid' => $legacyUuid,
                            'legacy_member_id' => $legacyMemberId,
                            'username' => $username,
                            'attended_date' => $dateStr,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        ['legacy_uuid', 'attended_date'],
                        ['member_id', 'legacy_member_id', 'username', 'updated_at'],
                    );
                    $dayInserted++;
                } catch (\Throwable $e) {
                    $this->warn("  {$dateStr} — failed to upsert {$legacyUuid}: {$e->getMessage()}");
                    $daySkipped++;
                }
            }

            $inserted += $dayInserted;
            $skipped += $daySkipped;

            $this->line("  {$dateStr} — {$dayInserted} upserted, {$daySkipped} skipped (total in payload: " . count($members) . ')');
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Upserted', (string) $inserted],
            ['Skipped',  (string) $skipped],
            ['Date errors', (string) $errors],
        ]);

        $this->info('Attendance sync completed.');

        // ── Remap missing member_id by legacy member id ──
        $this->newLine();
        $this->info('Re-mapping member_id for unlinked attendance records...');

        $unlinked = DB::table('member_attendances')
            ->whereNull('member_id')
            ->whereNotNull('legacy_member_id')
            ->distinct()
            ->pluck('legacy_member_id');

        if ($unlinked->isEmpty()) {
            $this->line('  Nothing to remap.');
        } else {
            $memberMap = Member::query()
                ->whereIn('biometric_member_id', $unlinked->map(fn ($value) => (string) $value)->all())
                ->pluck('id', 'biometric_member_id');

            $remapped = 0;

            foreach ($memberMap as $legacyMemberId => $memberId) {
                $affected = DB::table('member_attendances')
                    ->whereNull('member_id')
                    ->where('legacy_member_id', $legacyMemberId)
                    ->update(['member_id' => $memberId, 'updated_at' => now()]);

                $remapped += $affected;
                $this->line("  legacy member #{$legacyMemberId} → member #{$memberId} ({$affected} records updated)");
            }

            $this->line("  Done. {$remapped} record(s) linked.");
        }

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

    private function resolveLocalMemberId(?int $legacyMemberId): ?int
    {
        if (!$legacyMemberId) {
            return null;
        }

        /** @var Member|null $member */
        $member = Member::query()
            ->where('biometric_member_id', (string) $legacyMemberId)
            ->value('id');

        return $member ? (int) $member : null;
    }

    private function requestWithRetry(string $token, string $url, array $query = []): ?Response
    {
        $attempts = 3;
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

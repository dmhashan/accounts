<?php

namespace App\Services;

use App\Jobs\ImportBiometricAccessEventsJob;
use App\Jobs\ProcessBiometricAccessEventJob;
use App\Jobs\SyncBiometricMemberJob;
use App\Models\Member;
use App\Models\Tenant;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BiometricQueueStatusService
{
    private const KNOWN_JOB_TYPES = [
        SyncBiometricMemberJob::class => 'Member up-sync',
        ProcessBiometricAccessEventJob::class => 'Webhook down-sync',
        ImportBiometricAccessEventsJob::class => 'Device event import',
    ];

    /**
     * @return array<string, mixed>
     */
    public function statusForTenant(Tenant $tenant, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        $pending = $this->pendingJobs($tenant, $page, $perPage);
        $failed = $this->failedJobs($tenant, $page, $perPage);

        return [
            'queue' => $this->queueName(),
            'pending_count' => $pending['meta']['total'],
            'failed_count' => $failed['meta']['total'],
            'pending' => $pending['data'],
            'failed' => $failed['data'],
            'meta' => [
                'pending' => $pending['meta'],
                'failed' => $failed['meta'],
            ],
        ];
    }

    public function retryFailedJob(Tenant $tenant, string $id): bool
    {
        $job = $this->findFailedJobForTenant($tenant, $id);

        if (!$job) {
            return false;
        }

        return Artisan::call('queue:retry', ['id' => [$id]]) === 0;
    }

    public function queueName(): string
    {
        return (string) config('queue.biometric_queue', 'biometric');
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    private function pendingJobs(Tenant $tenant, int $page, int $perPage): array
    {
        try {
            $connectionName = config('queue.connections.database.connection');
            $table = (string) config('queue.connections.database.table', 'jobs');

            if (!$connectionName || !Schema::connection($connectionName)->hasTable($table)) {
                return $this->emptyPage($page, $perPage);
            }

            $jobs = DB::connection($connectionName)
                ->table($table)
                ->where('queue', $this->queueName())
                ->orderByDesc('id')
                ->get()
                ->map(function ($job) use ($tenant): ?array {
                    $details = $this->payloadDetails((string) $job->payload, $tenant);

                    if (!$details['matches_tenant']) {
                        return null;
                    }

                    return array_merge($details['data'], [
                        'id' => (string) $job->id,
                        'attempts' => (int) $job->attempts,
                        'reserved' => $job->reserved_at !== null,
                        'available_at' => $this->timestampToIso($job->available_at),
                        'reserved_at' => $this->timestampToIso($job->reserved_at),
                        'created_at' => $this->timestampToIso($job->created_at),
                    ]);
                })
                ->filter()
                ->values();

            return $this->paginateArray($jobs->all(), $page, $perPage);
        } catch (\Throwable) {
            return $this->emptyPage($page, $perPage);
        }
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    private function failedJobs(Tenant $tenant, int $page, int $perPage): array
    {
        try {
            $jobs = collect(app('queue.failer')->all())
                ->filter(fn ($job): bool => ($job->queue ?? null) === $this->queueName())
                ->map(function ($job) use ($tenant): ?array {
                    $details = $this->payloadDetails((string) $job->payload, $tenant);

                    if (!$details['matches_tenant']) {
                        return null;
                    }

                    return array_merge($details['data'], [
                        'id' => (string) ($job->id ?? $job->uuid ?? ''),
                        'connection' => (string) ($job->connection ?? ''),
                        'failed_at' => $this->dateToIso($job->failed_at ?? null),
                        'exception' => $this->exceptionSummary((string) ($job->exception ?? '')),
                    ]);
                })
                ->filter()
                ->values();

            return $this->paginateArray($jobs->all(), $page, $perPage);
        } catch (\Throwable) {
            return $this->emptyPage($page, $perPage);
        }
    }

    private function findFailedJobForTenant(Tenant $tenant, string $id): ?object
    {
        try {
            $job = app('queue.failer')->find($id);

            if (!$job || ($job->queue ?? null) !== $this->queueName()) {
                return null;
            }

            $details = $this->payloadDetails((string) $job->payload, $tenant);

            return $details['matches_tenant'] ? $job : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{matches_tenant: bool, data: array<string, mixed>}
     */
    private function payloadDetails(string $payload, Tenant $tenant): array
    {
        $decoded = json_decode($payload, true) ?: [];
        $commandName = (string) (Arr::get($decoded, 'data.commandName') ?: ($decoded['displayName'] ?? 'Unknown job'));
        $command = $this->unserializeCommand(Arr::get($decoded, 'data.command'));
        $tenantDomain = is_string($decoded['tenant_domain'] ?? null) ? $decoded['tenant_domain'] : null;
        $tenantId = $this->readCommandProperty($command, 'tenantId');
        $memberId = $this->readCommandProperty($command, 'memberId');
        $event = $this->readCommandProperty($command, 'event');

        $member = is_numeric($memberId)
            ? Member::query()->find((int) $memberId)
            : null;

        $matchesTenant = $tenantDomain === $tenant->domain
            || (is_numeric($tenantId) && (int) $tenantId === (int) $tenant->id);

        return [
            'matches_tenant' => $matchesTenant,
            'data' => [
                'queue' => $this->queueName(),
                'type' => $this->jobTypeLabel($commandName),
                'command' => class_basename($commandName),
                'tenant_domain' => $tenantDomain,
                'action' => $this->readCommandProperty($command, 'action'),
                'member' => $member ? [
                    'id' => $member->id,
                    'name' => $member->name,
                    'biometric_member_id' => $member->biometric_member_id,
                ] : null,
                'member_id' => is_numeric($memberId) ? (int) $memberId : null,
                'employee_no' => is_array($event) ? ($event['employeeNoString'] ?? null) : null,
                'sync_from' => $this->readCommandProperty($command, 'syncFrom'),
                'sync_to' => $this->readCommandProperty($command, 'syncTo'),
            ],
        ];
    }

    private function unserializeCommand(mixed $command): ?object
    {
        if (!is_string($command) || $command === '') {
            return null;
        }

        try {
            $value = @unserialize($command, ['allowed_classes' => true]);

            return is_object($value) ? $value : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function readCommandProperty(?object $command, string $property): mixed
    {
        if (!$command || $command instanceof \__PHP_Incomplete_Class) {
            return null;
        }

        $reflection = new \ReflectionClass($command);

        do {
            if ($reflection->hasProperty($property)) {
                $prop = $reflection->getProperty($property);
                $prop->setAccessible(true);

                return $prop->getValue($command);
            }

            $reflection = $reflection->getParentClass();
        } while ($reflection);

        return null;
    }

    private function jobTypeLabel(string $commandName): string
    {
        return self::KNOWN_JOB_TYPES[$commandName] ?? Str::headline(class_basename($commandName));
    }

    private function exceptionSummary(string $exception): string
    {
        $line = trim(Str::before($exception, "\n"));

        return $line === '' ? 'Job failed.' : Str::limit($line, 300);
    }

    private function timestampToIso(mixed $timestamp): ?string
    {
        if (!is_numeric($timestamp)) {
            return null;
        }

        return Carbon::createFromTimestamp((int) $timestamp)->toIso8601String();
    }

    private function dateToIso(mixed $date): ?string
    {
        if (!$date) {
            return null;
        }

        try {
            return Carbon::parse($date)->toIso8601String();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    private function paginateArray(array $items, int $page, int $perPage): array
    {
        $total = count($items);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);

        return [
            'data' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ];
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    private function emptyPage(int $page, int $perPage): array
    {
        return [
            'data' => [],
            'meta' => [
                'current_page' => max(1, $page),
                'last_page' => 1,
                'per_page' => $perPage,
                'total' => 0,
            ],
        ];
    }
}

<?php

namespace App\Services\Reports;

use App\Models\BiometricAccessEvent;
use App\Models\BiometricSyncLog;
use App\Models\Member;
use App\Models\MemberAttendance;
use App\Models\MemberPayment;
use App\Models\PaymentMembership;
use App\Models\PaymentPlan;
use App\Models\Sale;
use App\Services\TenantConfigurationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberAnalysisReportService
{
    private const DEFAULT_THRESHOLDS = [
        'inactive_days' => 30,
        'low_activity_days' => 14,
        'paid_not_attending_days' => 14,
        'payment_grace_period_days' => 3,
        'critical_overdue_days' => 14,
        'regular_member_min_attendance_per_month' => 8,
        'new_member_days' => 30,
        'enable_outstanding_sales_check' => true,
        'enable_wallet_balance_check' => true,
    ];

    public function __construct(private readonly TenantConfigurationService $config) {}

    /**
     * @return array<string, mixed>
     */
    public function filterOptions(int $tenantId): array
    {
        $planQuery = PaymentPlan::query()
            ->select(['id', 'name']);

        $this->applyTenantScope($planQuery, 'payment_plans', $tenantId);

        $plans = $planQuery->orderBy('name')
            ->get()
            ->map(fn (PaymentPlan $plan): array => [
                'id' => (int) $plan->id,
                'name' => $plan->name,
            ])
            ->values()
            ->all();

        return [
            'plans' => $plans,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summary(int $tenantId, array $filters = []): array
    {
        $rows = $this->filteredRows($tenantId, $filters);

        return [
            'as_of' => Carbon::today()->toDateString(),
            'thresholds' => self::DEFAULT_THRESHOLDS,
            'summary' => [
                'total_members' => $rows->count(),
                'active_members' => $rows->where('status', 'active')->count(),
                'inactive_members' => $rows->where('flags.inactive', true)->count(),
                'low_activity_members' => $rows->where('flags.low_activity', true)->count(),
                'payment_missed_members' => $rows->where('flags.payment_missed', true)->count(),
                'outstanding_members' => $rows->where('flags.outstanding', true)->count(),
                'paid_not_attending_members' => $rows->where('flags.paid_not_attending', true)->count(),
                'attending_with_expired_payment_members' => $rows->where('flags.attending_with_expired_payment', true)->count(),
                'regular_members' => $rows->where('flags.regular', true)->count(),
                'new_members' => $rows->where('flags.new_member', true)->count(),
                'total_outstanding_amount' => $this->roundMoney((float) $rows->sum('total_outstanding_amount')),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function members(int $tenantId, array $filters = [], int $perPage = 15): array
    {
        $rows = $this->sortRows($this->filteredRows($tenantId, $filters), $filters);
        $page = max(1, (int) ($filters['page'] ?? request('page', 1)));
        $perPage = max(1, min($perPage, 100));

        $paginator = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );

        return [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'as_of' => Carbon::today()->toDateString(),
            'thresholds' => self::DEFAULT_THRESHOLDS,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function export(int $tenantId, array $filters = []): StreamedResponse
    {
        $rows = $this->sortRows($this->filteredRows($tenantId, $filters), $filters);
        $filename = 'member-analysis-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'Member ID',
                'Member Code',
                'Name',
                'Phone',
                'Email',
                'Status',
                'Plan',
                'Joined Date',
                'Membership Start',
                'Payment Expiry Date',
                'Membership Status',
                'Payment Expiry Days',
                'Last Attendance Date',
                'Last Attendance Days',
                'Attendance Count',
                'Sales Outstanding',
                'Wallet Balance',
                'Total Outstanding',
                'Biometric Configured',
                'Biometric Synced',
                'Biometric Last Synced At',
                'Face ID',
                'Fingerprint',
            ]);

            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['member_id'],
                    $row['member_code'],
                    $row['name'],
                    $row['phone'],
                    $row['email'],
                    $row['status'],
                    $row['plan_name'],
                    $row['joined_date'],
                    $row['membership_start_date'],
                    $row['membership_expiry_date'],
                    $row['membership_status'],
                    $row['days_until_payment_expiry'],
                    $row['last_attendance_date'],
                    $row['days_since_last_attendance'],
                    $row['attendance_count'],
                    $row['sales_outstanding_amount'],
                    $row['wallet_balance'],
                    $row['total_outstanding_amount'],
                    $row['biometric_configured'] ? 'Yes' : 'No',
                    $row['biometric_synced'] === null ? 'Not configured' : ($row['biometric_synced'] ? 'Yes' : 'No'),
                    $row['biometric_last_synced_at'],
                    $row['has_face'] ? 'Given' : 'Not Given',
                    $row['has_fingerprint'] ? 'Given' : 'Not Given',
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<int, int|string>  $memberIds
     * @return array<string, int|string>
     */
    public function updateMemberStatus(int $tenantId, array $memberIds, string $status): array
    {
        $status = strtolower($status);
        $isActive = $status === 'active';
        $ids = collect($memberIds)
            ->map(fn (int|string $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $query = Member::query()->whereIn('members.id', $ids);
        $this->applyTenantScope($query, 'members', $tenantId);

        $selectedCount = (clone $query)->count();

        if ($selectedCount > 0) {
            $query->update([
                'is_active' => $isActive,
                'updated_at' => now(),
            ]);
        }

        $label = $isActive ? 'active' : 'inactive';

        return [
            'message' => $selectedCount === 1
                ? "1 member marked {$label}."
                : "{$selectedCount} members marked {$label}.",
            'status' => $label,
            'selected_count' => $selectedCount,
            'updated_count' => $selectedCount,
            'requested_count' => $ids->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function filteredRows(int $tenantId, array $filters): Collection
    {
        $filters = $this->normalizeFilterPayload($filters);
        $thresholds = self::DEFAULT_THRESHOLDS;
        $today = Carbon::today();
        $biometricConfigured = $this->biometricConfigured($tenantId);

        return $this->baseQuery($tenantId, $filters)
            ->get()
            ->map(fn (Member $member): array => $this->serializeMember($member, $thresholds, $today, $biometricConfigured))
            ->filter(fn (array $row): bool => $this->matchesComputedFilters($row, $filters))
            ->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(int $tenantId, array $filters): Builder
    {
        $query = Member::query()
            ->leftJoin('payment_plans as default_plan', 'default_plan.id', '=', 'members.payment_plan_id')
            ->select([
                'members.id',
                'members.biometric_member_id',
                'members.name',
                'members.phone_number',
                'members.email',
                'members.is_active',
                'members.is_verified',
                'members.is_temp',
                'members.payment_plan_id',
                'members.joined_date',
                'members.current_balance',
                'members.biometric_last_synced_at',
                'members.profile_photo_path',
                'members.has_face',
                'members.has_fingerprint',
                'members.created_at',
                DB::raw('default_plan.name as default_plan_name'),
            ])
            ->selectSub($this->lastPaymentDateSubquery($tenantId), 'last_payment_date')
            ->selectSub($this->latestMembershipSubquery($tenantId, 'payment_memberships.start_date'), 'membership_start_date')
            ->selectSub($this->latestMembershipSubquery($tenantId, 'payment_memberships.end_date'), 'membership_expiry_date')
            ->selectSub($this->latestMembershipSubquery($tenantId, 'payment_memberships.payment_plan_id'), 'membership_plan_id')
            ->selectSub($this->latestMembershipSubquery($tenantId, 'payment_plans.name'), 'membership_plan_name')
            ->selectSub($this->lastAttendanceDateSubquery($tenantId), 'last_attendance_date')
            ->selectSub($this->attendanceCountSubquery($tenantId), 'attendance_count')
            ->selectSub($this->salesOutstandingSubquery($tenantId), 'sales_outstanding_amount')
            ->selectSub($this->hasFaceEventSubquery($tenantId), 'has_face_event')
            ->selectSub($this->hasFingerprintEventSubquery($tenantId), 'has_fingerprint_event')
            ->selectSub($this->hasFingerprintSetupSubquery($tenantId), 'has_fingerprint_setup');

        $this->applyTenantScope($query, 'members', $tenantId);

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('members.biometric_member_id', 'like', "%{$search}%")
                    ->orWhere('members.name', 'like', "%{$search}%")
                    ->orWhere('members.email', 'like', "%{$search}%")
                    ->orWhere('members.phone_number', 'like', "%{$search}%");
            });
        }

        if (filled($filters['member_status'] ?? null)) {
            match ((string) $filters['member_status']) {
                'active' => $query->where('members.is_active', true),
                'inactive' => $query->where('members.is_active', false),
                'temp' => $query->where('members.is_temp', true),
                'verified' => $query->where('members.is_verified', true),
                'unverified' => $query->where('members.is_verified', false),
                default => null,
            };
        }

        $this->applyDatabaseFilterRules($query, $filters);

        return $query;
    }

    private function lastPaymentDateSubquery(int $tenantId): Builder
    {
        $query = MemberPayment::query()
            ->select('payment_date')
            ->whereColumn('member_payments.member_id', 'members.id')
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->limit(1);

        return $this->applyTenantScope($query, 'member_payments', $tenantId);
    }

    private function latestMembershipSubquery(int $tenantId, string $column): Builder
    {
        $query = PaymentMembership::query()
            ->select($column)
            ->join('member_payments', 'member_payments.id', '=', 'payment_memberships.member_payment_id')
            ->leftJoin('payment_plans', 'payment_plans.id', '=', 'payment_memberships.payment_plan_id')
            ->whereColumn('member_payments.member_id', 'members.id')
            ->orderByDesc('payment_memberships.end_date')
            ->orderByDesc('payment_memberships.id')
            ->limit(1);

        $this->applyTenantScope($query, 'payment_memberships', $tenantId);

        return $this->applyTenantScope($query, 'member_payments', $tenantId);
    }

    private function lastAttendanceDateSubquery(int $tenantId): Builder
    {
        $query = MemberAttendance::query()
            ->selectRaw('MAX(attended_date)')
            ->whereColumn('member_attendances.member_id', 'members.id');

        return $this->applyTenantScope($query, 'member_attendances', $tenantId);
    }

    private function attendanceCountSubquery(int $tenantId): Builder
    {
        $query = MemberAttendance::query()
            ->selectRaw('COUNT(*)')
            ->whereColumn('member_attendances.member_id', 'members.id');

        return $this->applyTenantScope($query, 'member_attendances', $tenantId);
    }

    private function salesOutstandingSubquery(int $tenantId): Builder
    {
        $query = Sale::query()
            ->selectRaw(
                'COALESCE(SUM(CASE '
                . 'WHEN is_paid = 0 AND total_amount > paid_amount THEN total_amount - paid_amount '
                . 'WHEN is_paid = 0 AND balance < 0 THEN 0 - balance '
                . 'WHEN is_paid = 0 THEN balance '
                . 'ELSE 0 END), 0)',
            )
            ->whereColumn('sales.customer_member_id', 'members.id')
            ->whereNull('sales.deleted_at');

        return $this->applyTenantScope($query, 'sales', $tenantId);
    }

    private function hasFaceEventSubquery(int $tenantId): Builder
    {
        $query = BiometricAccessEvent::query()
            ->selectRaw('1')
            ->whereColumn('biometric_access_events.member_id', 'members.id')
            ->where('auth_method', 'face')
            ->limit(1);

        return $this->applyTenantScope($query, 'biometric_access_events', $tenantId);
    }

    private function hasFingerprintEventSubquery(int $tenantId): Builder
    {
        $query = BiometricAccessEvent::query()
            ->selectRaw('1')
            ->whereColumn('biometric_access_events.member_id', 'members.id')
            ->where('auth_method', 'fingerprint')
            ->limit(1);

        return $this->applyTenantScope($query, 'biometric_access_events', $tenantId);
    }

    private function hasFingerprintSetupSubquery(int $tenantId): Builder
    {
        $query = BiometricSyncLog::query()
            ->selectRaw('1')
            ->whereColumn('biometric_sync_logs.member_id', 'members.id')
            ->where('action', 'fingerprint_setup')
            ->where('status', 'success')
            ->limit(1);

        return $this->applyTenantScope($query, 'biometric_sync_logs', $tenantId);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyDatabaseFilterRules(Builder $query, array $filters): void
    {
        foreach ($this->filterRules($filters) as $rule) {
            $values = $this->arrayValue($rule['value'] ?? []);

            if ($values === []) {
                continue;
            }

            match ($rule['field']) {
                'active' => $this->applyBooleanSelection($query, 'members.is_active', $values, 'active', 'inactive'),
                'verified' => $this->applyBooleanSelection($query, 'members.is_verified', $values, 'verified', 'unverified'),
                'temp' => $this->applyBooleanSelection($query, 'members.is_temp', $values, 'temp', 'full'),
                default => null,
            };
        }
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function applyBooleanSelection(Builder $query, string $column, array $values, string $trueValue, string $falseValue): void
    {
        $values = array_values(array_unique(array_map(fn (mixed $value): string => (string) $value, $values)));
        $includeTrue = in_array($trueValue, $values, true);
        $includeFalse = in_array($falseValue, $values, true);

        if ($includeTrue && !$includeFalse) {
            $query->where($column, true);
        }

        if ($includeFalse && !$includeTrue) {
            $query->where($column, false);
        }
    }

    /**
     * @param  array<string, int|float|bool>  $thresholds
     * @return array<string, mixed>
     */
    private function serializeMember(Member $member, array $thresholds, Carbon $today, bool $biometricConfigured): array
    {
        $membershipStart = $this->dateOrNull($member->membership_start_date);
        $membershipExpiry = $this->dateOrNull($member->membership_expiry_date);
        $lastAttendance = $this->dateOrNull($member->last_attendance_date);
        $lastPayment = $this->dateOrNull($member->last_payment_date);
        $joinedDate = $this->dateOrNull($member->joined_date) ?? $this->dateOrNull($member->created_at);

        $membershipValid = $membershipExpiry !== null && $membershipExpiry->gte($today);
        $membershipExpired = $membershipExpiry !== null && $membershipExpiry->lt($today);
        $membershipStatus = $membershipExpiry === null ? 'none' : ($membershipValid ? 'valid' : 'expired');
        $daysUntilPaymentExpiry = $membershipExpiry === null ? null : (int) $today->diffInDays($membershipExpiry, false);
        $daysOverdue = $daysUntilPaymentExpiry !== null && $daysUntilPaymentExpiry < 0 ? abs($daysUntilPaymentExpiry) : 0;
        $paymentMissed = $membershipExpired
            && $membershipExpiry->copy()->addDays((int) $thresholds['payment_grace_period_days'])->lt($today);
        $criticalPaymentOverdue = $paymentMissed && $daysOverdue >= (int) $thresholds['critical_overdue_days'];

        $daysSinceLastAttendance = $lastAttendance === null ? null : $lastAttendance->diffInDays($today);
        $inactive = $lastAttendance === null || $lastAttendance->lt($today->copy()->subDays((int) $thresholds['inactive_days']));
        $lowActivity = !$inactive
            && $lastAttendance !== null
            && $lastAttendance->lt($today->copy()->subDays((int) $thresholds['low_activity_days']));
        $paidNotAttending = $membershipValid && (
            $lastAttendance === null
            || $lastAttendance->lt($today->copy()->subDays((int) $thresholds['paid_not_attending_days']))
        );
        $attendingWithExpiredPayment = $membershipExpired
            && $lastAttendance !== null
            && $lastAttendance->gt($membershipExpiry);

        $attendanceCount = (int) ($member->attendance_count ?? 0);
        $regular = $attendanceCount >= (int) $thresholds['regular_member_min_attendance_per_month'];
        $newMember = $joinedDate !== null
            && $joinedDate->gte($today->copy()->subDays((int) $thresholds['new_member_days']));

        $salesOutstanding = (float) ($member->sales_outstanding_amount ?? 0);
        $walletBalance = (float) ($member->current_balance ?? 0);
        $walletOutstanding = (bool) $thresholds['enable_wallet_balance_check'] && $walletBalance < 0
            ? abs($walletBalance)
            : 0.0;
        $salesOutstandingForRisk = (bool) $thresholds['enable_outstanding_sales_check'] ? $salesOutstanding : 0.0;
        $totalOutstanding = $this->roundMoney($salesOutstandingForRisk + $walletOutstanding);
        $outstanding = $totalOutstanding > 0;
        $biometricLastSyncedAt = $this->dateTimeOrNull($member->biometric_last_synced_at);

        $hasFace = (bool) ($member->has_face ?? false)
            || filled($member->profile_photo_path)
            || (bool) ($member->has_face_event ?? false);

        $hasFingerprint = (bool) ($member->has_fingerprint ?? false)
            || (bool) ($member->has_fingerprint_event ?? false)
            || (bool) ($member->has_fingerprint_setup ?? false);

        $flags = [
            'inactive' => $inactive,
            'low_activity' => $lowActivity,
            'payment_missed' => $paymentMissed,
            'critical_payment_overdue' => $criticalPaymentOverdue,
            'outstanding' => $outstanding,
            'paid_not_attending' => $paidNotAttending,
            'attending_with_expired_payment' => $attendingWithExpiredPayment,
            'regular' => $regular,
            'new_member' => $newMember,
        ];

        return [
            'member_id' => (int) $member->id,
            'member_code' => $member->biometric_member_id,
            'member_number' => $member->biometric_member_id,
            'name' => trim((string) ($member->name ?? '')),
            'phone' => $member->phone_number,
            'email' => $member->email,
            'status' => (bool) $member->is_active ? 'active' : 'inactive',
            'is_active' => (bool) $member->is_active,
            'is_verified' => (bool) $member->is_verified,
            'is_temp' => (bool) $member->is_temp,
            'plan_id' => $member->membership_plan_id
                ? (int) $member->membership_plan_id
                : ($member->payment_plan_id ? (int) $member->payment_plan_id : null),
            'plan_name' => $member->membership_plan_name ?: $member->default_plan_name,
            'joined_date' => $joinedDate?->toDateString(),
            'last_attendance_date' => $lastAttendance?->toDateString(),
            'attendance_count' => $attendanceCount,
            'attendance_status' => $inactive ? 'inactive' : ($lowActivity ? 'low_activity' : 'active'),
            'last_payment_date' => $lastPayment?->toDateString(),
            'membership_start_date' => $membershipStart?->toDateString(),
            'membership_expiry_date' => $membershipExpiry?->toDateString(),
            'membership_status' => $membershipStatus,
            'days_until_payment_expiry' => $daysUntilPaymentExpiry,
            'payment_expiry_days' => $daysUntilPaymentExpiry,
            'days_since_last_attendance' => $daysSinceLastAttendance,
            'last_attendance_days' => $daysSinceLastAttendance,
            'days_overdue' => $daysOverdue,
            'sales_outstanding_amount' => $this->roundMoney($salesOutstanding),
            'wallet_balance' => $this->roundMoney($walletBalance),
            'total_outstanding_amount' => $totalOutstanding,
            'biometric_configured' => $biometricConfigured,
            'biometric_synced' => $biometricConfigured ? $biometricLastSyncedAt !== null : null,
            'biometric_last_synced_at' => $biometricLastSyncedAt,
            'has_face' => $hasFace,
            'has_fingerprint' => $hasFingerprint,
            'face_status' => $hasFace ? 'given' : 'not_given',
            'fingerprint_status' => $hasFingerprint ? 'given' : 'not_given',
            'flags' => $flags,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $filters
     */
    private function matchesComputedFilters(array $row, array $filters): bool
    {
        foreach ($this->filterRules($filters) as $rule) {
            if (!$this->matchesFilterRule($row, $rule)) {
                return false;
            }
        }

        foreach ([
            'outstanding_only' => 'outstanding',
            'payment_missed_only' => 'payment_missed',
            'inactive_only' => 'inactive',
            'paid_not_attending_only' => 'paid_not_attending',
            'attending_with_expired_payment_only' => 'attending_with_expired_payment',
            'regular_only' => 'regular',
            'new_member_only' => 'new_member',
        ] as $filter => $flag) {
            if ($this->truthy($filters[$filter] ?? false) && !$row['flags'][$flag]) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $rule
     */
    private function matchesFilterRule(array $row, array $rule): bool
    {
        return match ($rule['field']) {
            'plan' => $this->matchesMultiValue((string) ($row['plan_id'] ?? ''), $this->arrayValue($rule['value'] ?? [])),
            'active' => true,
            'verified' => true,
            'temp' => true,
            'payment_expiry_date' => $this->compareDate($row['membership_expiry_date'] ?? null, $rule),
            'expiry_days' => $this->compareNumber($row['days_until_payment_expiry'] ?? null, $rule),
            'last_attendance_date' => $this->compareDate($row['last_attendance_date'] ?? null, $rule),
            'attendance_days' => $this->compareNumber($row['days_since_last_attendance'] ?? null, $rule),
            'attendance_count' => $this->compareNumber($row['attendance_count'] ?? null, $rule),
            'biometric' => $this->matchesBiometric($row, $this->arrayValue($rule['value'] ?? [])),
            'face_id' => $this->matchesFaceId($row, $this->arrayValue($rule['value'] ?? [])),
            'fingerprint' => $this->matchesFingerprint($row, $this->arrayValue($rule['value'] ?? [])),
            'outstanding' => $this->compareNumber($row['total_outstanding_amount'] ?? null, $rule),
            default => true,
        };
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function matchesFaceId(array $row, array $values): bool
    {
        if ($values === []) {
            return true;
        }

        $values = array_map(fn (mixed $value): string => (string) $value, $values);

        return (in_array('given', $values, true) && (bool) ($row['has_face'] ?? false))
            || (in_array('not_given', $values, true) && !(bool) ($row['has_face'] ?? false));
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function matchesFingerprint(array $row, array $values): bool
    {
        if ($values === []) {
            return true;
        }

        $values = array_map(fn (mixed $value): string => (string) $value, $values);

        return (in_array('given', $values, true) && (bool) ($row['has_fingerprint'] ?? false))
            || (in_array('not_given', $values, true) && !(bool) ($row['has_fingerprint'] ?? false));
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function matchesMultiValue(string $actual, array $values): bool
    {
        if ($values === []) {
            return true;
        }

        return in_array($actual, array_map(fn (mixed $value): string => (string) $value, $values), true);
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function compareDate(mixed $actual, array $rule): bool
    {
        if (blank($actual) || blank($rule['value'] ?? null)) {
            return false;
        }

        $actualDate = Carbon::parse($actual)->toDateString();
        $filterDate = Carbon::parse((string) $rule['value'])->toDateString();

        return $this->compareScalar($actualDate, $filterDate, (string) ($rule['operator'] ?? 'eq'));
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function compareNumber(mixed $actual, array $rule): bool
    {
        if ($actual === null || $actual === '' || !is_numeric($actual) || !is_numeric($rule['value'] ?? null)) {
            return false;
        }

        return $this->compareScalar((float) $actual, (float) $rule['value'], (string) ($rule['operator'] ?? 'eq'));
    }

    private function compareScalar(float|string $actual, float|string $expected, string $operator): bool
    {
        return match ($operator) {
            'lt' => $actual < $expected,
            'lte' => $actual <= $expected,
            'gt' => $actual > $expected,
            'gte' => $actual >= $expected,
            default => $actual == $expected,
        };
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, mixed>  $values
     */
    private function matchesBiometric(array $row, array $values): bool
    {
        if ($values === []) {
            return true;
        }

        $values = array_map(fn (mixed $value): string => (string) $value, $values);

        return (in_array('configured', $values, true) && (bool) ($row['biometric_configured'] ?? false))
            || (in_array('not_configured', $values, true) && !(bool) ($row['biometric_configured'] ?? false))
            || (in_array('synced', $values, true) && ($row['biometric_synced'] ?? null) === true)
            || (in_array('not_synced', $values, true)
                && (bool) ($row['biometric_configured'] ?? false)
                && ($row['biometric_synced'] ?? null) === false);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function sortRows(Collection $rows, array $filters): Collection
    {
        $sort = (string) ($filters['sort'] ?? 'name');
        $direction = strtolower((string) ($filters['direction'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowed = [
            'member_code',
            'name',
            'status',
            'plan_name',
            'joined_date',
            'last_attendance_date',
            'attendance_count',
            'last_payment_date',
            'membership_expiry_date',
            'days_until_payment_expiry',
            'days_since_last_attendance',
            'days_overdue',
            'sales_outstanding_amount',
            'wallet_balance',
            'total_outstanding_amount',
            'biometric_synced',
        ];

        if (!in_array($sort, $allowed, true)) {
            $sort = 'name';
        }

        return $rows
            ->sortBy(
                fn (array $row): mixed => $row[$sort] ?? null,
                SORT_REGULAR,
                $direction === 'desc',
            )
            ->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function normalizeFilterPayload(array $filters): array
    {
        return [
            'search' => trim((string) ($filters['search'] ?? '')),
            'member_status' => (string) ($filters['member_status'] ?? ''),
            'outstanding_only' => $this->truthy($filters['outstanding_only'] ?? false),
            'payment_missed_only' => $this->truthy($filters['payment_missed_only'] ?? false),
            'inactive_only' => $this->truthy($filters['inactive_only'] ?? false),
            'paid_not_attending_only' => $this->truthy($filters['paid_not_attending_only'] ?? false),
            'attending_with_expired_payment_only' => $this->truthy($filters['attending_with_expired_payment_only'] ?? false),
            'regular_only' => $this->truthy($filters['regular_only'] ?? false),
            'new_member_only' => $this->truthy($filters['new_member_only'] ?? false),
            'filter_rules' => $this->filterRules($filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array{field: string, operator: string, value: mixed}>
     */
    private function filterRules(array $filters): array
    {
        $rules = $filters['filter_rules'] ?? [];

        if (is_string($rules)) {
            $decoded = json_decode($rules, true);
            $rules = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($rules)) {
            return [];
        }

        $allowedFields = [
            'plan',
            'active',
            'verified',
            'temp',
            'payment_expiry_date',
            'expiry_days',
            'last_attendance_date',
            'attendance_days',
            'attendance_count',
            'biometric',
            'face_id',
            'fingerprint',
            'outstanding',
        ];
        $allowedOperators = ['eq', 'lt', 'lte', 'gt', 'gte'];

        $allowedMultiValues = [
            'active' => ['active', 'inactive'],
            'verified' => ['verified', 'unverified'],
            'temp' => ['temp', 'full'],
            'biometric' => ['configured', 'not_configured', 'synced', 'not_synced'],
            'face_id' => ['given', 'not_given'],
            'fingerprint' => ['given', 'not_given'],
        ];

        return collect($rules)
            ->filter(fn (mixed $rule): bool => is_array($rule) && in_array((string) ($rule['field'] ?? ''), $allowedFields, true))
            ->map(function (array $rule) use ($allowedOperators, $allowedMultiValues): array {
                $field = (string) $rule['field'];
                $operator = in_array((string) ($rule['operator'] ?? 'eq'), $allowedOperators, true)
                    ? (string) ($rule['operator'] ?? 'eq')
                    : 'eq';
                $value = $rule['value'] ?? null;

                if ($field === 'plan') {
                    $value = collect($this->arrayValue($value))
                        ->map(fn (mixed $item): int => (int) $item)
                        ->filter(fn (int $item): bool => $item > 0)
                        ->unique()
                        ->values()
                        ->all();
                }

                if (in_array($field, ['active', 'verified', 'temp', 'biometric', 'face_id', 'fingerprint'], true)) {
                    $allowedValues = $allowedMultiValues[$field];
                    $value = collect($this->arrayValue($value))
                        ->map(fn (mixed $item): string => (string) $item)
                        ->filter(fn (string $item): bool => in_array($item, $allowedValues, true))
                        ->unique()
                        ->values()
                        ->all();
                }

                if (in_array($field, ['expiry_days', 'attendance_days', 'attendance_count', 'outstanding'], true)
                    && !is_numeric($value)) {
                    $value = null;
                } elseif (in_array($field, ['expiry_days', 'attendance_days', 'attendance_count', 'outstanding'], true)) {
                    $value = (float) $value;
                }

                if (in_array($field, ['payment_expiry_date', 'last_attendance_date'], true)) {
                    if (blank($value)) {
                        $value = null;
                    } else {
                        try {
                            $value = Carbon::parse((string) $value)->toDateString();
                        } catch (\Throwable) {
                            $value = null;
                        }
                    }
                }

                return compact('field', 'operator', 'value');
            })
            ->filter(function (array $rule): bool {
                if (in_array($rule['field'], ['plan', 'active', 'verified', 'temp', 'biometric', 'face_id', 'fingerprint'], true)) {
                    return $rule['value'] !== [];
                }

                return $rule['value'] !== null && $rule['value'] !== '';
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, mixed>
     */
    private function arrayValue(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn (mixed $item): bool => $item !== null && $item !== ''));
        }

        if ($value === null || $value === '') {
            return [];
        }

        return [$value];
    }

    private function biometricConfigured(int $tenantId): bool
    {
        $config = $this->config->all($tenantId);

        return ($config['biometric.enabled'] ?? '0') === '1'
            && filled($config['biometric.device_maker'] ?? '')
            && filled($config['biometric.device_ip'] ?? '');
    }

    private function applyTenantScope(Builder $query, string $table, int $tenantId): Builder
    {
        if (Schema::hasColumn($table, 'tenant_id')) {
            $query->where($table . '.tenant_id', $tenantId);
        }

        return $query;
    }

    private function dateOrNull(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        return $value instanceof Carbon ? $value->copy()->startOfDay() : Carbon::parse($value)->startOfDay();
    }

    private function dateTimeOrNull(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value)->toISOString();
    }

    private function truthy(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    private function roundMoney(float $value): float
    {
        return round($value, 2);
    }
}

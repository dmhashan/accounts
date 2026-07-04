<?php

namespace App\Services;

use App\Jobs\SyncBiometricMemberJob;
use App\Models\Member;
use App\Models\MemberAttendance;
use App\Models\PaymentMembership;
use App\Models\PaymentPlan;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberService
{
    public function __construct(
        private readonly MediaStorageService $media,
        private readonly BiometricSyncService $biometric,
        private readonly AutomatedMemberNotificationService $notifications,
        private readonly AuditService $audit,
    ) {}

    public function meta(): array
    {
        return [
            'generated_member_id' => Member::generateBiometricMemberId(0), // preview only
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function index(int $tenantId, User $currentUser, int $perPage, string $search, ?bool $isTemp = null, ?int $planId = null, array $filters = []): array
    {
        $today = Carbon::today();

        $query = Member::query()
            ->leftJoin('payment_plans as default_plan', 'default_plan.id', '=', 'members.payment_plan_id')
            ->select('members.*')
            ->selectRaw('default_plan.name as default_plan_name')
            ->selectSub($this->latestMembershipSubquery($tenantId, 'payment_memberships.end_date'), 'membership_expiry_date')
            ->selectSub($this->latestMembershipSubquery($tenantId, 'payment_memberships.payment_plan_id'), 'membership_plan_id')
            ->selectSub($this->latestMembershipSubquery($tenantId, 'payment_plans.name'), 'membership_plan_name')
            ->selectSub($this->lastAttendanceDateSubquery($tenantId), 'last_attendance_date')
            ->selectSub($this->salesOutstandingSubquery($tenantId), 'sales_outstanding_amount')
            ->when($isTemp !== null, fn ($q) => $q->where('members.is_temp', $isTemp))
            ->when($planId !== null, fn ($q) => $q->where('members.payment_plan_id', $planId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('members.biometric_member_id', 'like', "%{$search}%")
                        ->orWhere('members.name', 'like', "%{$search}%")
                        ->orWhere('members.email', 'like', "%{$search}%")
                        ->orWhere('members.phone_number', 'like', "%{$search}%");
                });
            });

        $this->applyListFilters($query, $tenantId, $filters, $today);

        $members = $query
            ->orderBy('members.created_at', 'desc')
            ->paginate($perPage);

        return [
            'data' => collect($members->items())->map(function (Member $member) use ($today) {
                $membershipExpiry = $this->dateOrNull($member->membership_expiry_date);
                $lastAttendance = $this->dateOrNull($member->last_attendance_date);
                $daysUntilPaymentExpiry = $membershipExpiry === null ? null : (int) $today->diffInDays($membershipExpiry, false);
                $daysSinceLastAttendance = $lastAttendance === null ? null : (int) $lastAttendance->diffInDays($today);
                $salesOutstanding = (float) ($member->sales_outstanding_amount ?? 0);
                $walletBalance = (float) ($member->current_balance ?? 0);
                $walletOutstanding = $walletBalance < 0 ? abs($walletBalance) : 0.0;
                $totalOutstanding = round($salesOutstanding + $walletOutstanding, 2);

                return [
                    'id' => $member->id,
                    'biometric_member_id' => $member->biometric_member_id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'gender' => $member->gender,
                    'phone_number' => $member->phone_number,
                    'allow_sms' => (bool) $member->allow_sms,
                    'allow_whatsapp' => (bool) $member->allow_whatsapp,
                    'whatsapp_number' => $member->whatsapp_number,
                    'payment_plan_id' => $member->payment_plan_id,
                    'plan_id' => $member->membership_plan_id
                        ? (int) $member->membership_plan_id
                        : ($member->payment_plan_id ? (int) $member->payment_plan_id : null),
                    'plan_name' => $member->membership_plan_name ?: $member->default_plan_name,
                    'membership_expiry_date' => $membershipExpiry?->toDateString(),
                    'days_until_payment_expiry' => $daysUntilPaymentExpiry,
                    'payment_expiry_days' => $daysUntilPaymentExpiry,
                    'last_attendance_date' => $lastAttendance?->toDateString(),
                    'days_since_last_attendance' => $daysSinceLastAttendance,
                    'last_attendance_days' => $daysSinceLastAttendance,
                    'sales_outstanding_amount' => round($salesOutstanding, 2),
                    'wallet_balance' => round($walletBalance, 2),
                    'total_outstanding_amount' => $totalOutstanding,
                    'profile_photo_url' => $member->profile_photo_path
                        ? $this->media->url($member->profile_photo_path)
                        : null,
                    'is_active' => (bool) $member->is_active,
                    'is_verified' => (bool) $member->is_verified,
                    'is_temp' => (bool) $member->is_temp,
                    'registration_source' => $member->registration_source,
                    'campaign_id' => $member->campaign_id,
                ];
            }),
            'meta' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
            ],
            'permissions' => [
                'create' => $currentUser->hasPermission('members.create') || $currentUser->hasPermission('users.create'),
                'edit' => $currentUser->hasPermission('members.edit') || $currentUser->hasPermission('users.edit'),
                'delete' => $currentUser->hasPermission('members.delete') || $currentUser->hasPermission('users.delete'),
            ],
        ];
    }

    public function exportGoogleContacts(Tenant $tenant): StreamedResponse
    {
        $headers = [
            'Name Prefix',
            'First Name',
            'Middle Name',
            'Last Name',
            'Name Suffix',
            'Phonetic First Name',
            'Phonetic Middle Name',
            'Phonetic Last Name',
            'Nickname',
            'File As',
            'E-mail 1 - Label',
            'E-mail 1 - Value',
            'Phone 1 - Label',
            'Phone 1 - Value',
            'Address 1 - Label',
            'Address 1 - Country',
            'Address 1 - Street',
            'Address 1 - Extended Address',
            'Address 1 - City',
            'Address 1 - Region',
            'Address 1 - Postal Code',
            'Address 1 - PO Box',
            'Organization Name',
            'Organization Title',
            'Organization Department',
            'Birthday',
            'Event 1 - Label',
            'Event 1 - Value',
            'Relation 1 - Label',
            'Relation 1 - Value',
            'Website 1 - Label',
            'Website 1 - Value',
            'Custom Field 1 - Label',
            'Custom Field 1 - Value',
            'Notes',
            'Labels',
        ];

        $tenantId = $tenant->id;
        $tenantName = (string) $tenant->name;
        $fileName = 'google-contacts-members-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($headers, $tenantName) {
            $output = fopen('php://output', 'w');

            fputcsv($output, $headers);

            foreach (Member::query()->orderBy('created_at', 'desc')->cursor() as $member) {
                $fileAs = trim((string) ($member->name ?? '')) ?: 'Member';

                $genderLabel = $member->gender === 'female' ? 'Female' : 'Male';
                $namePrefix = trim($tenantName . ' ' . $genderLabel . ' ' . (string) ($member->biometric_member_id ?? ''));
                $contactName = trim($namePrefix . ' ' . $fileAs);

                fputcsv($output, [
                    '',
                    $contactName,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    $fileAs,
                    '* Home',
                    (string) ($member->email ?? ''),
                    '* Mobile',
                    (string) ($member->phone_number ?? ''),
                    '* Home',
                    '',
                    (string) ($member->address ?? ''),
                    '',
                    '',
                    '',
                    '',
                    '',
                    $tenantName,
                    (string) ($member->member_role ?? ''),
                    '',
                    optional($member->date_of_birth)->format('Y-m-d'),
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Member ID',
                    (string) ($member->biometric_member_id ?? ''),
                    (string) ($member->comment ?? ''),
                    'Members',
                ]);
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function storeTemp(Tenant $tenant, array $validated): Member
    {
        $validated['biometric_member_id'] = Member::generateBiometricMemberId($tenant->id);
        $validated['name'] = trim((string) ($validated['name'] ?? ''));
        $validated['is_active'] = true;
        $validated['is_verified'] = false;
        $validated['is_temp'] = true;

        unset($validated['first_name'], $validated['last_name']);

        return Member::create($validated);
    }

    public function store(Tenant $tenant, array $validated): Member
    {
        $validated['email'] = filled($validated['email'] ?? null)
            ? trim((string) $validated['email'])
            : null;
        $validated['biometric_member_id'] = Member::generateBiometricMemberId($tenant->id);
        $validated['name'] = trim((string) $validated['name']);
        $validated['is_active'] = true;
        $validated['is_verified'] = true;
        unset($validated['first_name'], $validated['last_name']);

        if (!empty($validated['payment_plan_id'])) {
            $plan = PaymentPlan::find($validated['payment_plan_id']);

            if ($plan) {
                $validated['payment_plan'] = $plan->name;
                $validated['price'] = $plan->price;
            }
        }

        $member = Member::create($validated);

        SyncBiometricMemberJob::dispatchForTenant($tenant->id, $member->id, 'create');
        $this->notifications->sendWelcome($member);

        return $member;
    }

    public function show(Member $member): array
    {
        $member->loadMissing(['campaign:id,title,slug,status', 'paymentPlan:id,name']);
        $this->syncMissingProfilePhotoFromBiometric($member);

        return [
            'id' => $member->id,
            'biometric_member_id' => $member->biometric_member_id,
            'name' => $member->name,
            'gender' => $member->gender,
            'email' => $member->email,
            'phone_number' => $member->phone_number,
            'allow_sms' => (bool) $member->allow_sms,
            'allow_whatsapp' => (bool) $member->allow_whatsapp,
            'whatsapp_number' => $member->whatsapp_number,
            'nic' => $member->nic,
            'date_of_birth' => optional($member->date_of_birth)->format('Y-m-d'),
            'age' => null,
            'address' => $member->address,
            'member_role' => null,
            'admission_fee' => $member->admission_fee,
            'payment_plan_id' => $member->payment_plan_id,
            'payment_plan' => $member->paymentPlan?->name,
            'price' => $member->price,
            'current_balance' => $member->current_balance,
            'joined_date' => optional($member->joined_date)->format('Y-m-d'),
            'comment' => $member->comment,
            'is_active' => (bool) $member->is_active,
            'is_verified' => (bool) $member->is_verified,
            'is_temp' => (bool) $member->is_temp,
            'registration_source' => $member->registration_source,
            'campaign' => $member->campaign ? [
                'id' => $member->campaign->id,
                'title' => $member->campaign->title,
                'slug' => $member->campaign->slug,
                'status' => $member->campaign->status,
            ] : null,
            'profile_photo_url' => $member->profile_photo_path
                ? $this->media->url($member->profile_photo_path)
                : null,
            'created_at' => optional($member->created_at)->toDateString(),
            'biometric_last_synced_at' => optional($member->biometric_last_synced_at)->toISOString(),
        ];
    }

    private function syncMissingProfilePhotoFromBiometric(Member $member): void
    {
        if ($member->profile_photo_path || !$member->biometric_member_id) {
            return;
        }

        try {
            $deviceInfo = $this->biometric->getMemberDeviceInfo($member);

            if (($deviceInfo['connection_failed'] ?? false) || ($deviceInfo['not_assigned'] ?? false) || ($deviceInfo['not_found'] ?? false)) {
                return;
            }

            if (!(bool) ($deviceInfo['face']['enrolled'] ?? false)) {
                return;
            }

            $this->biometric->uploadFaceAsAvatar($member);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function update(Member $member, array $validated): void
    {
        $validated['email'] = filled($validated['email'] ?? null)
            ? trim((string) $validated['email'])
            : null;
        $validated['name'] = trim((string) $validated['name']);
        unset($validated['first_name'], $validated['last_name']);

        if (!empty($validated['payment_plan_id'])) {
            $plan = PaymentPlan::find($validated['payment_plan_id']);

            if ($plan) {
                $validated['payment_plan'] = $plan->name;
                $validated['price'] = $plan->price;
            }
        }

        $member->update($validated);

        if ($member->user) {
            $userData = [
                'name' => $validated['name'],
            ];

            if (!empty($validated['email'])) {
                $userData['email'] = $validated['email'];
            }

            $member->user->update($userData);
        }

        SyncBiometricMemberJob::dispatchForTenant((int) app('tenant')->id, $member->id, 'update');
    }

    public function toggleStatus(Member $member): array
    {
        $member->update([
            'is_active' => !$member->is_active,
        ]);

        return [
            'message' => $member->is_active ? 'Member activated successfully.' : 'Member deactivated successfully.',
            'is_active' => (bool) $member->is_active,
        ];
    }

    public function toggleVerification(Member $member): array
    {
        $before = ['is_verified' => (bool) $member->is_verified];

        $member->update([
            'is_verified' => !$member->is_verified,
        ]);

        if ($member->registration_source === 'campaign') {
            $this->audit->log((int) app('tenant')->id, $member->is_verified ? 'campaign.member_verified' : 'campaign.member_unverified', $member, $before, [
                'is_verified' => (bool) $member->is_verified,
                'campaign_id' => $member->campaign_id,
            ]);
        }

        return [
            'message' => $member->is_verified ? 'Member verified successfully.' : 'Member unverified successfully.',
            'is_verified' => (bool) $member->is_verified,
        ];
    }

    public function destroy(Member $member): void
    {
        $this->biometric->syncMember($member, 'delete');

        if ($member->user) {
            $member->user->delete();
        }

        $this->deleteAvatar($member);
        $member->delete();
    }

    public function uploadAvatar(Member $member, UploadedFile $file): string
    {
        if ($member->profile_photo_path) {
            $this->media->delete($member->profile_photo_path);
        }

        $path = $this->media->store($file, 'member-avatars');

        $member->update(['profile_photo_path' => $path]);

        return $this->media->url($path);
    }

    public function deleteAvatar(Member $member): void
    {
        if (!$member->profile_photo_path) {
            return;
        }

        $this->media->delete($member->profile_photo_path);
        $member->update(['profile_photo_path' => null]);
    }

    public function ensureTenantMember(Member $member, int $tenantId): void
    {
        //
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyListFilters(Builder $query, int $tenantId, array $filters, Carbon $today): void
    {
        $active = (string) ($filters['active'] ?? '');

        if (in_array($active, ['active', 'inactive'], true)) {
            $query->where('members.is_active', $active === 'active');
        }

        $verified = (string) ($filters['verified'] ?? '');

        if (in_array($verified, ['verified', 'unverified'], true)) {
            $query->where('members.is_verified', $verified === 'verified');
        }

        $gender = (string) ($filters['gender'] ?? '');

        if (in_array($gender, ['male', 'female', 'other'], true)) {
            $query->where('members.gender', $gender);
        }

        $expiryPresetDays = $this->presetDays((string) ($filters['expiry_preset'] ?? ''), 'expired');
        $expiryDays = $expiryPresetDays !== null
            ? 0 - $expiryPresetDays
            : $this->integerFilterValue($filters['expiry_days'] ?? null);

        if ($expiryPresetDays !== null) {
            $this->applyDaysUntilFilter(
                $query,
                $this->latestMembershipSubquery($tenantId, 'payment_memberships.end_date'),
                '<=',
                $expiryDays,
                $today,
            );
        } elseif ($expiryDays !== null) {
            $this->applyDaysUntilFilter(
                $query,
                $this->latestMembershipSubquery($tenantId, 'payment_memberships.end_date'),
                $this->comparisonOperator((string) ($filters['expiry_days_operator'] ?? 'lte')),
                $expiryDays,
                $today,
            );
        }

        $attendancePresetDays = $this->presetDays((string) ($filters['attendance_preset'] ?? ''), 'older');
        $attendanceDays = $attendancePresetDays
            ?? $this->integerFilterValue($filters['attendance_days'] ?? null);

        if ($attendancePresetDays !== null) {
            $this->applyDaysSinceFilter(
                $query,
                $this->lastAttendanceDateSubquery($tenantId),
                '>=',
                $attendanceDays,
                $today,
            );
        } elseif ($attendanceDays !== null) {
            $this->applyDaysSinceFilter(
                $query,
                $this->lastAttendanceDateSubquery($tenantId),
                $this->comparisonOperator((string) ($filters['attendance_days_operator'] ?? 'gte')),
                $attendanceDays,
                $today,
            );
        }

        $outstanding = (string) ($filters['outstanding'] ?? '');

        if (in_array($outstanding, ['with', 'without'], true)) {
            $this->applyOutstandingFilter($query, $tenantId, $outstanding);
        }
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

    private function applyDaysUntilFilter(Builder $query, Builder $dateSubquery, string $operator, int $days, Carbon $today): void
    {
        $this->whereSubqueryDate(
            $query,
            $dateSubquery,
            $operator,
            $today->copy()->addDays($days)->toDateString(),
        );
    }

    private function applyDaysSinceFilter(Builder $query, Builder $dateSubquery, string $operator, int $days, Carbon $today): void
    {
        $inverseOperator = match ($operator) {
            '<' => '>',
            '<=' => '>=',
            '>' => '<',
            '>=' => '<=',
            default => '=',
        };

        $this->whereSubqueryDate(
            $query,
            $dateSubquery,
            $inverseOperator,
            $today->copy()->subDays($days)->toDateString(),
        );
    }

    private function applyOutstandingFilter(Builder $query, int $tenantId, string $outstanding): void
    {
        $salesSubquery = $this->salesOutstandingSubquery($tenantId);
        $expression = '(COALESCE((' . $salesSubquery->toSql() . '), 0) '
            . '+ CASE WHEN members.current_balance < 0 THEN ABS(members.current_balance) ELSE 0 END)';
        $operator = $outstanding === 'with' ? '>' : '<=';

        $query->whereRaw($expression . " {$operator} 0", $salesSubquery->getBindings());
    }

    private function whereSubqueryDate(Builder $query, Builder $dateSubquery, string $operator, string $date): void
    {
        $query->whereRaw(
            'DATE((' . $dateSubquery->toSql() . ")) {$operator} ?",
            array_merge($dateSubquery->getBindings(), [$date]),
        );
    }

    private function comparisonOperator(string $operator): string
    {
        return match ($operator) {
            'lt' => '<',
            'lte' => '<=',
            'gt' => '>',
            'gte' => '>=',
            default => '=',
        };
    }

    private function integerFilterValue(mixed $value): ?int
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function presetDays(string $preset, string $prefix): ?int
    {
        if (!preg_match('/^' . preg_quote($prefix, '/') . '_(30|60|90)$/', $preset, $matches)) {
            return null;
        }

        return (int) $matches[1];
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
}

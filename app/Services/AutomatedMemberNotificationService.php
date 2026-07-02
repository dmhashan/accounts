<?php

namespace App\Services;

use App\Jobs\SendMemberNotificationJob;
use App\Models\Member;
use App\Models\MemberAttendance;
use App\Models\MemberNotification;
use App\Models\MemberPayment;
use App\Models\PaymentMembership;
use App\Models\Tenant;
use Illuminate\Support\Carbon;

class AutomatedMemberNotificationService
{
    public const CONFIG_KEY = 'general.member_notifications';

    public function __construct(
        private readonly TenantConfigurationService $tenantConfig,
        private readonly MemberPortalUrlService $memberPortalUrl,
    ) {}

    public function sendWelcome(Member $member): void
    {
        if (!app()->bound('tenant')) {
            return;
        }

        $tenant = app('tenant');
        $tenantId = (int) $tenant->id;
        $config = $this->notificationConfig($tenantId);
        $message = $this->welcomeMessage($member, $tenant, $config);

        SendMemberNotificationJob::dispatch(
            $tenantId,
            $member->id,
            'member_welcome',
            'Welcome to ' . $this->tenantName($tenant),
            $message,
        );
    }

    public function sendPaymentReceipt(MemberPayment $payment): void
    {
        $payment->loadMissing(['member', 'paymentMethod', 'membership']);

        if (!$payment->member || !app()->bound('tenant')) {
            return;
        }

        $tenant = app('tenant');
        $tenantId = (int) $tenant->id;
        $memberName = $this->memberName($payment->member);
        $tenantName = $this->tenantName($tenant);
        $paymentMethodName = $payment->paymentMethod?->name
            ?: match ($payment->payment_method) {
                'member_wallet' => 'Member Wallet',
                'cash' => 'Cash',
                default => trim((string) $payment->payment_method) ?: 'Cash',
            };
        $amount = number_format((float) $payment->amount, 2, '.', ',');
        $paymentDate = $payment->payment_date?->toDateString() ?? now()->toDateString();
        $validUntil = $payment->membership?->end_date?->toDateString();

        $message = "Payment received! {$memberName} paid {$amount} at {$tenantName} on {$paymentDate} via {$paymentMethodName}";

        if ($validUntil) {
            $message .= ". Membership valid until {$validUntil}";
        }

        SendMemberNotificationJob::dispatch(
            $tenantId,
            $payment->member->id,
            'membership_payment_received',
            'Payment received',
            $message,
        );
    }

    public function sendMembershipExpiryReminders(?Carbon $today = null): int
    {
        $today ??= today();
        $offsets = [7, 2, 0, -2];
        $sent = 0;

        foreach ($offsets as $offset) {
            $dueDate = $today->copy()->addDays($offset)->toDateString();

            $memberships = PaymentMembership::query()
                ->whereDate('end_date', $dueDate)
                ->with(['payment.member'])
                ->get();

            foreach ($memberships as $membership) {
                $payment = $membership->payment;
                $member = $payment?->member;
                $tenant = app()->bound('tenant') ? app('tenant') : null;

                if (!$payment || !$member || !$tenant) {
                    continue;
                }

                if (!$this->isLatestMembershipForMember($membership)) {
                    continue;
                }

                $message = sprintf(
                    'Hey %s, your payment at %s was due on %s. Please renew and stay active!',
                    $this->memberName($member),
                    $this->tenantName($tenant),
                    $membership->end_date?->toDateString() ?? $dueDate,
                );
                $type = $this->expiryReminderType($offset);

                if ($this->alreadyCreated($member, $type, $message)) {
                    continue;
                }

                SendMemberNotificationJob::dispatch(
                    (int) $tenant->id,
                    $member->id,
                    $type,
                    'Membership payment reminder',
                    $message,
                );

                $sent++;
            }
        }

        return $sent;
    }

    public function sendMemberMilestoneNotifications(?Carbon $today = null): int
    {
        $today ??= today();

        return $this->sendBirthdayNotifications($today)
            + $this->sendJoinAnniversaryNotifications($today);
    }

    /**
     * @return array{member_login_url?: string, whatsapp_group_url?: string, whatsapp_groups?: array<int, mixed>}
     */
    public function notificationConfig(int $tenantId): array
    {
        $raw = $this->tenantConfig->all($tenantId)[self::CONFIG_KEY] ?? '{}';
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function welcomeMessage(Member $member, Tenant $tenant, array $config): string
    {
        $lines = [
            'Hi ' . $this->memberName($member) . ', welcome to ' . $this->tenantName($tenant),
        ];

        $loginUrl = $this->memberPortalUrl->urlForTenant($tenant);

        if ($loginUrl !== '') {
            $lines[] = '';
            $lines[] = 'Login: ' . $loginUrl;
        }

        $whatsappUrls = $this->applicableWhatsappUrls($member, $config);

        if ($whatsappUrls !== []) {
            $lines[] = '';
            $lines[] = 'WhatsApp: ' . implode(', ', $whatsappUrls);
        }

        $lines[] = "Let's begin your fitness journey!";

        return implode("\n", $lines);
    }

    /**
     * @return string[]
     */
    private function applicableWhatsappUrls(Member $member, array $config): array
    {
        $urls = [];
        $defaultUrl = trim((string) ($config['whatsapp_group_url'] ?? ''));

        if ($defaultUrl !== '') {
            $urls[] = $defaultUrl;
        }

        foreach (($config['whatsapp_groups'] ?? []) as $group) {
            if (!is_array($group)) {
                continue;
            }

            $url = trim((string) ($group['url'] ?? ''));

            if ($url === '' || !$this->matchesRules($member, $group['rules'] ?? [])) {
                continue;
            }

            $urls[] = $url;
        }

        return array_values(array_unique($urls));
    }

    private function matchesRules(Member $member, mixed $rules): bool
    {
        if (!is_array($rules) || $rules === []) {
            return true;
        }

        $matches = null;

        foreach (array_values($rules) as $index => $rule) {
            if (!is_array($rule)) {
                return false;
            }

            $field = (string) ($rule['field'] ?? '');
            $operator = (string) ($rule['operator'] ?? 'equals');
            $expected = $rule['value'] ?? null;
            $actual = data_get($member, $field);
            $ruleMatches = $this->matchesRule($actual, $operator, $expected);

            if ($index === 0) {
                $matches = $ruleMatches;
                continue;
            }

            $boolean = (string) ($rule['boolean'] ?? 'and');
            $matches = $boolean === 'or'
                ? ($matches || $ruleMatches)
                : ($matches && $ruleMatches);
        }

        return (bool) $matches;
    }

    private function matchesRule(mixed $actual, string $operator, mixed $expected): bool
    {
        $actualValue = strtolower(trim((string) $actual));

        return match ($operator) {
            'not_equals' => $actualValue !== strtolower(trim((string) $expected)),
            'in' => in_array($actualValue, array_map(fn ($value) => strtolower(trim((string) $value)), (array) $expected), true),
            'not_in' => !in_array($actualValue, array_map(fn ($value) => strtolower(trim((string) $value)), (array) $expected), true),
            default => $actualValue === strtolower(trim((string) $expected)),
        };
    }

    private function sendBirthdayNotifications(Carbon $today): int
    {
        $sent = 0;

        $members = Member::query()
            ->where('is_active', true)
            ->whereNotNull('date_of_birth')
            ->whereMonth('date_of_birth', $today->month)
            ->whereDay('date_of_birth', $today->day)
            ->get();

        foreach ($members as $member) {
            $tenant = app()->bound('tenant') ? app('tenant') : null;

            if (!$tenant || $this->alreadyCreatedToday($member, 'member_birthday', $today)) {
                continue;
            }

            $message = sprintf(
                'Happy Birthday %s! Wishing you a strong, healthy and joyful year ahead from everyone at %s. Keep moving, keep growing!',
                $this->memberName($member),
                $this->tenantName($tenant),
            );

            SendMemberNotificationJob::dispatch(
                (int) $tenant->id,
                $member->id,
                'member_birthday',
                'Happy Birthday',
                $message,
            );

            $sent++;
        }

        return $sent;
    }

    private function sendJoinAnniversaryNotifications(Carbon $today): int
    {
        $sent = 0;

        $members = Member::query()
            ->where('is_active', true)
            ->whereNotNull('joined_date')
            ->whereDate('joined_date', '<', $today->toDateString())
            ->whereMonth('joined_date', $today->month)
            ->whereDay('joined_date', $today->day)
            ->get();

        foreach ($members as $member) {
            $tenant = app()->bound('tenant') ? app('tenant') : null;

            if (!$tenant || $this->alreadyCreatedToday($member, 'member_join_anniversary', $today)) {
                continue;
            }

            $attendanceDays = $this->attendanceDaysInPreviousYear($member, $today);
            $message = sprintf(
                'Happy fitness anniversary %s! You showed up for %d training %s at %s in the last year. That consistency matters. Keep pushing forward!',
                $this->memberName($member),
                $attendanceDays,
                $attendanceDays === 1 ? 'day' : 'days',
                $this->tenantName($tenant),
            );

            SendMemberNotificationJob::dispatch(
                (int) $tenant->id,
                $member->id,
                'member_join_anniversary',
                'Happy fitness anniversary',
                $message,
            );

            $sent++;
        }

        return $sent;
    }

    private function attendanceDaysInPreviousYear(Member $member, Carbon $today): int
    {
        $startDate = $today->copy()->subYear()->addDay()->toDateString();
        $endDate = $today->copy()->subDay()->toDateString();

        return MemberAttendance::query()
            ->where('member_id', $member->id)
            ->whereDate('attended_date', '>=', $startDate)
            ->whereDate('attended_date', '<=', $endDate)
            ->distinct()
            ->pluck('attended_date')
            ->count();
    }

    private function isLatestMembershipForMember(PaymentMembership $membership): bool
    {
        $memberId = $membership->payment?->member_id;

        if (!$memberId) {
            return false;
        }

        $latest = PaymentMembership::query()
            ->whereHas('payment', fn ($query) => $query->where('member_id', $memberId))
            ->whereNotNull('end_date')
            ->orderByDesc('end_date')
            ->orderByDesc('id')
            ->first(['id']);

        return $latest?->id === $membership->id;
    }

    private function alreadyCreated(Member $member, string $type, string $message): bool
    {
        return MemberNotification::query()
            ->where('member_id', $member->id)
            ->where('type', $type)
            ->where('body', $message)
            ->exists();
    }

    private function alreadyCreatedToday(Member $member, string $type, Carbon $today): bool
    {
        return MemberNotification::query()
            ->where('member_id', $member->id)
            ->where('type', $type)
            ->whereDate('created_at', $today->toDateString())
            ->exists();
    }

    private function expiryReminderType(int $offset): string
    {
        return match ($offset) {
            7 => 'membership_expiry_7_days_before',
            2 => 'membership_expiry_2_days_before',
            0 => 'membership_expiry_due_date',
            -2 => 'membership_expiry_2_days_after',
            default => 'membership_expiry_reminder',
        };
    }

    private function memberName(Member $member): string
    {
        return trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) ?: ($member->name ?: 'Member');
    }

    private function tenantName(Tenant $tenant): string
    {
        return trim((string) $tenant->name) ?: 'your fitness center';
    }
}

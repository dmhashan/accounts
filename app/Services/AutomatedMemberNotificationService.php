<?php

namespace App\Services;

use App\Jobs\SendMemberNotificationJob;
use App\Models\Member;
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
    ) {}

    public function sendWelcome(Member $member): void
    {
        $tenant = $member->tenant;

        if (!$tenant) {
            return;
        }

        $config = $this->notificationConfig($member->tenant_id);
        $message = $this->welcomeMessage($member, $tenant, $config);

        SendMemberNotificationJob::dispatch(
            $member->tenant_id,
            $member->id,
            'member_welcome',
            'Welcome to ' . $this->tenantName($tenant),
            $message,
        );
    }

    public function sendPaymentReceipt(MemberPayment $payment): void
    {
        $payment->loadMissing(['member', 'tenant', 'account']);

        if (!$payment->member || !$payment->tenant) {
            return;
        }

        $memberName = $this->memberName($payment->member);
        $tenantName = $this->tenantName($payment->tenant);
        $accountName = $payment->account?->name ?: ($payment->payment_method === 'member_wallet' ? 'Member Wallet' : 'Cash');
        $amount = number_format((float) $payment->amount, 2, '.', ',');
        $paymentDate = $payment->payment_date?->toDateString() ?? now()->toDateString();

        $message = "Payment received! {$memberName} paid {$amount} at {$tenantName} on {$paymentDate} via {$accountName}";

        SendMemberNotificationJob::dispatch(
            $payment->tenant_id,
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
                ->with(['payment.member.tenant'])
                ->get();

            foreach ($memberships as $membership) {
                $payment = $membership->payment;
                $member = $payment?->member;
                $tenant = $member?->tenant;

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
                    $member->tenant_id,
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

        $loginUrl = trim((string) ($config['member_login_url'] ?? ''));

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
            ->where('tenant_id', $member->tenant_id)
            ->where('member_id', $member->id)
            ->where('type', $type)
            ->where('body', $message)
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

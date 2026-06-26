<?php

namespace App\Services;

use App\Models\CompanyAccount;
use App\Models\CompanyAccountTransaction;
use App\Models\Member;
use App\Models\MemberPayment;
use App\Models\PaymentMembership;
use App\Models\PaymentPlan;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly BiometricSyncService $biometric,
        private readonly AutomatedMemberNotificationService $notifications,
    ) {}

    public function meta(int $tenantId): array
    {
        $members = Member::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'name', 'phone_number']);

        $accounts = CompanyAccount::query()
            ->orderBy('name')
            ->withSum('incomingTransfers as incoming_total', 'amount')
            ->withSum('outgoingTransfers as outgoing_total', 'amount')
            ->withSum('transactions as transaction_total', 'amount')
            ->get();

        $plans = PaymentPlan::query()
            ->where('is_active', true)
            ->orderByRaw(PaymentPlan::durationDaysOrderRaw())
            ->get(['id', 'name', 'duration_value', 'duration_unit', 'price']);

        return [
            'members' => $members->map(function (Member $member) {
                $name = trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? ''));

                if ($name === '') {
                    $name = $member->name ?: 'Member';
                }
                $phone = $member->phone_number ?: 'N/A';

                return [
                    'id' => $member->id,
                    'label' => $name . ' (' . $phone . ')',
                    'name' => $name,
                    'phone_number' => $phone,
                ];
            })->values(),
            'accounts' => $accounts->map(fn (CompanyAccount $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'current_balance' => round(
                    (float) $account->opening_balance
                    + (float) ($account->incoming_total ?? 0)
                    + (float) ($account->transaction_total ?? 0)
                    - (float) ($account->outgoing_total ?? 0),
                    2,
                ),
            ])->values(),
            'plans' => $plans->map(fn (PaymentPlan $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'duration_value' => (int) $p->duration_value,
                'duration_unit' => (string) $p->duration_unit,
                'duration_days' => $p->approximateDays(),
                'price' => (float) $p->price,
            ])->values(),
        ];
    }

    public function memberPaymentInfo(Member $member, int $tenantId): array
    {
        $name = trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? ''));

        if ($name === '') {
            $name = $member->name ?: 'Member';
        }

        // Resolve the member's default plan
        $currentPlan = null;

        if ($member->payment_plan_id) {
            $plan = PaymentPlan::find($member->payment_plan_id);

            if ($plan) {
                $currentPlan = [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'duration_value' => (int) $plan->duration_value,
                    'duration_unit' => (string) $plan->duration_unit,
                    'duration_days' => $plan->approximateDays(),
                    'price' => (float) $plan->price,
                ];
            }
        }

        // Find the last membership for this member (latest end_date)
        $lastMembership = PaymentMembership::query()
            ->whereHas('payment', fn ($q) => $q
                ->where('member_id', $member->id),
            )
            ->with(['payment', 'plan'])
            ->whereNotNull('end_date')
            ->latest('end_date')
            ->first();

        $lastPayment = null;
        $nextStartDate = now()->toDateString();

        if ($lastMembership) {
            $lastPayment = [
                'payment_date' => $lastMembership->payment?->payment_date?->toDateString(),
                'end_date' => $lastMembership->end_date?->toDateString(),
            ];
            $nextStartDate = $lastMembership->end_date->copy()->addDay()->toDateString();

            // Fall back to last payment's plan if no default set on member
            if (!$currentPlan && $lastMembership->plan) {
                $p = $lastMembership->plan;
                $currentPlan = [
                    'id' => $p->id,
                    'name' => $p->name,
                    'duration_value' => (int) $p->duration_value,
                    'duration_unit' => (string) $p->duration_unit,
                    'duration_days' => $p->approximateDays(),
                    'price' => (float) $p->price,
                ];
            }
        }

        return [
            'id' => $member->id,
            'member_id' => $member->biometric_member_id,
            'name' => $name,
            'username' => $member->username,
            'phone_number' => $member->phone_number,
            'address' => $member->address,
            'joined_date' => $member->joined_date?->toDateString(),
            'current_plan' => $currentPlan,
            'member_price' => $member->price ? (float) $member->price : null,
            'last_payment' => $lastPayment,
            'next_start_date' => $nextStartDate,
        ];
    }

    public function memberPayments(int $memberId, int $tenantId, int $perPage): array
    {
        $payments = MemberPayment::query()
            ->where('member_id', $memberId)
            ->with(['account:id,name', 'membership.plan:id,name'])
            ->orderBy('payment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'data' => collect($payments->items())->map(fn (MemberPayment $payment) => $this->serialize($payment)),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ];
    }

    public function payments(int $tenantId, int $perPage): array
    {
        $payments = MemberPayment::query()
            ->with([
                'member:id,first_name,last_name,name,phone_number',
                'account:id,name',
                'membership.plan:id,name',
            ])
            ->orderBy('payment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'data' => collect($payments->items())->map(fn (MemberPayment $payment) => $this->serialize($payment)),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ];
    }

    public function showPayment(MemberPayment $payment, int $tenantId): array
    {
        $payment = MemberPayment::query()
            ->with([
                'member:id,first_name,last_name,name,phone_number',
                'account:id,name',
                'membership.plan:id,name',
            ])
            ->find($payment->id);

        if (!$payment) {
            abort(404);
        }

        return $this->serialize($payment);
    }

    public function storePayment(int $tenantId, array $validated): MemberPayment
    {
        $payment = DB::transaction(function () use ($tenantId, $validated) {
            $isWalletPayment = ($validated['payment_method'] ?? 'cash') === 'member_wallet';

            if (!empty($validated['member_id'])) {
                $this->ensureMemberBelongsToTenant((int) $validated['member_id'], $tenantId);
            }

            if ($isWalletPayment) {
                if (empty($validated['member_id'])) {
                    abort(422, 'Please select a member for wallet payment.');
                }
                $member = Member::query()
                    ->lockForUpdate()
                    ->find((int) $validated['member_id']);

                if (!$member) {
                    abort(422, 'Member not found.');
                }

                if ((float) $member->current_balance < (float) $validated['amount']) {
                    abort(422, 'Insufficient wallet balance.');
                }
                $member->update([
                    'current_balance' => (float) $member->current_balance - (float) $validated['amount'],
                ]);
                $accountId = null;
            } else {
                $this->ensureAccountBelongsToTenant((int) $validated['company_account_id'], $tenantId);
                $accountId = $validated['company_account_id'];
            }

            $payment = MemberPayment::create([
                'member_id' => $validated['member_id'] ?? null,
                'company_account_id' => $accountId,
                'payment_method' => $isWalletPayment ? 'member_wallet' : 'cash',
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'reference_number' => filled($validated['reference_number'] ?? null) ? trim((string) $validated['reference_number']) : null,
                'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
            ]);

            $this->syncMembership($payment, $tenantId, $validated);

            if (!$isWalletPayment) {
                $this->syncTransaction($payment, $tenantId);
            }

            return $payment;
        });

        $this->triggerBiometricSync($payment->member_id, $tenantId);
        $this->notifications->sendPaymentReceipt($payment);

        return $payment;
    }

    public function updatePayment(MemberPayment $payment, int $tenantId, array $validated): void
    {
        $oldMemberId = $payment->member_id;

        DB::transaction(function () use ($payment, $tenantId, $validated) {
            $lockedPayment = MemberPayment::query()
                ->lockForUpdate()
                ->find($payment->id);

            if (!$lockedPayment) {
                abort(404);
            }

            // Wallet payments cannot be updated - only cash payments support edit
            if ($lockedPayment->payment_method === 'member_wallet') {
                abort(422, 'Wallet payments cannot be edited.');
            }

            if (!empty($validated['member_id'])) {
                $this->ensureMemberBelongsToTenant((int) $validated['member_id'], $tenantId);
            }
            $this->ensureAccountBelongsToTenant((int) $validated['company_account_id'], $tenantId);

            $lockedPayment->update([
                'member_id' => $validated['member_id'] ?? null,
                'company_account_id' => $validated['company_account_id'],
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'reference_number' => filled($validated['reference_number'] ?? null) ? trim((string) $validated['reference_number']) : null,
                'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
            ]);

            $this->syncMembership($lockedPayment, $tenantId, $validated);
            $this->syncTransaction($lockedPayment, $tenantId);
        });

        $newMemberId = !empty($validated['member_id']) ? (int) $validated['member_id'] : null;

        foreach (array_unique(array_filter([$oldMemberId, $newMemberId])) as $memberId) {
            $this->triggerBiometricSync($memberId, $tenantId);
        }
    }

    public function destroyPayment(MemberPayment $payment, int $tenantId): void
    {
        $lockedPayment = MemberPayment::query()
            ->find($payment->id);

        if (!$lockedPayment) {
            abort(404);
        }

        $memberId = $lockedPayment->member_id;

        // Refund wallet if this was a wallet payment
        if ($lockedPayment->payment_method === 'member_wallet' && $lockedPayment->member_id) {
            $member = Member::query()
                ->lockForUpdate()
                ->find($lockedPayment->member_id);

            if ($member) {
                $member->update([
                    'current_balance' => (float) $member->current_balance + (float) $lockedPayment->amount,
                ]);
            }
        }

        // Delete the associated transaction before deleting the payment
        CompanyAccountTransaction::where('model_name', 'payment')
            ->where('reference_id', $lockedPayment->id)
            ->delete();

        $lockedPayment->delete();

        $this->triggerBiometricSync($memberId, $tenantId);
    }

    private function triggerBiometricSync(?int $memberId, int $tenantId): void
    {
        if (!$memberId) {
            return;
        }

        $member = Member::where('id', $memberId)->first();

        if ($member) {
            $this->biometric->syncMember($member, 'update');
        }
    }

    private function syncMembership(MemberPayment $payment, int $tenantId, array $validated): void
    {
        $planId = !empty($validated['payment_plan_id']) ? (int) $validated['payment_plan_id'] : null;
        $startDate = filled($validated['start_date'] ?? null) ? $validated['start_date'] : null;
        $endDate = filled($validated['end_date'] ?? null) ? $validated['end_date'] : null;

        // Auto-calculate end_date from plan duration if not provided
        if ($planId && $startDate && !$endDate) {
            $plan = PaymentPlan::find($planId);

            if ($plan) {
                $endDate = $plan->endDateFrom($startDate)->toDateString();
            }
        }

        if ($planId || $startDate || $endDate) {
            PaymentMembership::updateOrCreate(
                ['member_payment_id' => $payment->id],
                [
                    'payment_plan_id' => $planId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            );
        } else {
            PaymentMembership::where('member_payment_id', $payment->id)->delete();
        }
    }

    private function syncTransaction(MemberPayment $payment, int $tenantId): void
    {
        $member = $payment->member;
        $memberName = $member
            ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) ?: ($member->name ?: 'Member')
            : 'Member';

        CompanyAccountTransaction::updateOrCreate(
            [
                'model_name' => 'payment',
                'reference_id' => $payment->id,
            ],
            [
                'company_account_id' => $payment->company_account_id,
                'type' => 'payment',
                'amount' => (float) $payment->amount,
                'transaction_date' => $payment->payment_date->toDateString(),
                'reference_number' => $payment->reference_number,
                'notes' => filled($payment->notes) ? $payment->notes : 'Payment: ' . $memberName,
            ],
        );
    }

    private function ensureMemberBelongsToTenant(int $memberId, int $tenantId): void
    {
        $exists = Member::query()
            ->where('id', $memberId)
            ->exists();

        if (!$exists) {
            abort(422, 'Invalid member selection.');
        }
    }

    private function ensureAccountBelongsToTenant(int $accountId, int $tenantId): void
    {
        $exists = CompanyAccount::query()
            ->where('id', $accountId)
            ->exists();

        if (!$exists) {
            abort(422, 'Invalid account selection.');
        }
    }

    private function serialize(MemberPayment $payment): array
    {
        $member = $payment->member;
        $memberName = $member
            ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) ?: ($member->name ?: 'Member')
            : 'Unknown';

        return [
            'id' => $payment->id,
            'member_id' => $payment->member_id,
            'member_name' => $memberName,
            'member_phone' => $member?->phone_number,
            'company_account_id' => $payment->company_account_id,
            'account_name' => $payment->account?->name,
            'payment_plan_id' => $payment->membership?->payment_plan_id,
            'payment_plan_name' => $payment->membership?->plan?->name,
            'payment_method' => $payment->payment_method ?? 'cash',
            'amount' => round((float) $payment->amount, 2),
            'payment_date' => $payment->payment_date?->toDateString(),
            'start_date' => $payment->membership?->start_date?->toDateString(),
            'end_date' => $payment->membership?->end_date?->toDateString(),
            'reference_number' => $payment->reference_number,
            'notes' => $payment->notes,
            'created_at' => optional($payment->created_at)->format('Y-m-d H:i'),
        ];
    }
}

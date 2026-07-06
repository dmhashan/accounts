<?php

namespace App\Services;

use App\Jobs\SyncBiometricMemberJob;
use App\Models\CompanyAccount;
use App\Models\Member;
use App\Models\MemberPayment;
use App\Models\PaymentMembership;
use App\Models\PaymentPlan;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly AutomatedMemberNotificationService $notifications,
        private readonly PaymentMethodService $paymentMethods,
        private readonly PaymentSettlementService $settlements,
    ) {}

    public function meta(int $tenantId): array
    {
        $members = Member::query()
            ->orderBy('name')
            ->get(['id', 'name', 'phone_number']);

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
                $name = trim((string) ($member->name ?? '')) ?: 'Member';
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
            'payment_methods' => $this->paymentMethods->activeMethods($tenantId),
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
        $name = trim((string) ($member->name ?? '')) ?: 'Member';

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
            ->with(['account:id,name', 'paymentMethod:id,name', 'settlement', 'membership.plan:id,name'])
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

    public function payments(int $tenantId, int $perPage, ?string $status = null): array
    {
        $payments = MemberPayment::query()
            ->with([
                'member:id,name,phone_number',
                'account:id,name',
                'paymentMethod:id,name',
                'settlement',
                'membership.plan:id,name',
            ])
            ->when($status === 'outstanding', fn ($query) => $query->where('is_paid', false))
            ->when($status === 'paid', fn ($query) => $query->where('is_paid', true))
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
                'member:id,name,phone_number',
                'account:id,name',
                'paymentMethod:id,name',
                'settlement',
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
            $isPaid = filter_var($validated['is_paid'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $isWalletPayment = $isPaid && (($validated['payment_method'] ?? 'cash') === 'member_wallet');

            if (!empty($validated['member_id'])) {
                $this->ensureMemberBelongsToTenant((int) $validated['member_id'], $tenantId);
            }

            $paymentMethod = null;
            $accountId = null;
            $paymentMethodName = null;

            if ($isPaid) {
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
                    $paymentMethodName = 'member_wallet';
                } else {
                    $paymentMethod = $this->paymentMethods->resolveFromPayload($validated, $tenantId);
                    $accountId = $paymentMethod->company_account_id;
                    $paymentMethodName = $paymentMethod->name;
                }
            }

            $payment = MemberPayment::create([
                'member_id' => $validated['member_id'] ?? null,
                'company_account_id' => $accountId,
                'payment_method_id' => $paymentMethod?->id,
                'payment_method' => $paymentMethodName,
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'reference_number' => filled($validated['reference_number'] ?? null) ? trim((string) $validated['reference_number']) : null,
                'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
                'is_paid' => $isPaid,
                'paid_amount' => $isPaid ? $validated['amount'] : 0.00,
                'balance' => $isPaid ? 0.00 : $validated['amount'],
            ]);

            $this->syncMembership($payment, $tenantId, $validated);

            if ($isPaid && !$isWalletPayment) {
                $this->settlements->syncForPayment($payment->fresh(['member']), $paymentMethod);
            }

            return $payment;
        });

        $this->triggerBiometricSync($payment->member_id, $tenantId);

        if ($payment->is_paid) {
            $this->notifications->sendPaymentReceipt($payment);
        } else {
            $this->notifications->sendPaymentOutstanding($payment);
        }

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
            $paymentMethod = $this->paymentMethods->resolveFromPayload($validated, $tenantId);

            $lockedPayment->update([
                'member_id' => $validated['member_id'] ?? null,
                'company_account_id' => $paymentMethod->company_account_id,
                'payment_method_id' => $paymentMethod->id,
                'payment_method' => $paymentMethod->name,
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'reference_number' => filled($validated['reference_number'] ?? null) ? trim((string) $validated['reference_number']) : null,
                'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
            ]);

            $this->syncMembership($lockedPayment, $tenantId, $validated);
            $this->settlements->syncForPayment($lockedPayment->fresh(['member']), $paymentMethod);
        });

        $newMemberId = !empty($validated['member_id']) ? (int) $validated['member_id'] : null;

        foreach (array_unique(array_filter([$oldMemberId, $newMemberId])) as $memberId) {
            $this->triggerBiometricSync($memberId, $tenantId);
        }
    }

    public function markAsPaid(MemberPayment $payment, int $tenantId, array $validated): MemberPayment
    {
        $paid = DB::transaction(function () use ($payment, $tenantId, $validated) {
            $lockedPayment = MemberPayment::query()
                ->lockForUpdate()
                ->find($payment->id);

            if (!$lockedPayment) {
                abort(404);
            }

            if ($lockedPayment->is_paid) {
                abort(422, 'Payment is already paid.');
            }

            $isWallet = ($validated['payment_method'] ?? null) === 'member_wallet';
            $paymentMethod = null;
            $accountId = null;
            $paymentMethodName = null;

            if ($isWallet) {
                if (!$lockedPayment->member_id) {
                    abort(422, 'Member wallet payment requires a member to be assigned.');
                }

                $member = Member::query()
                    ->lockForUpdate()
                    ->find($lockedPayment->member_id);

                if (!$member) {
                    abort(422, 'Member not found.');
                }

                if ((float) $member->current_balance < (float) $lockedPayment->amount) {
                    abort(422, 'Insufficient member wallet balance.');
                }

                $member->update([
                    'current_balance' => (float) $member->current_balance - (float) $lockedPayment->amount,
                ]);

                $lockedPayment->update([
                    'company_account_id' => null,
                    'payment_method_id' => null,
                    'payment_method' => 'member_wallet',
                    'is_paid' => true,
                    'paid_amount' => (float) $lockedPayment->amount,
                    'balance' => 0.00,
                ]);
            } else {
                $paymentMethod = $this->paymentMethods->resolveFromPayload($validated, $tenantId);
                $accountId = $paymentMethod->company_account_id;
                $paymentMethodName = $paymentMethod->name;

                $lockedPayment->update([
                    'company_account_id' => $accountId,
                    'payment_method_id' => $paymentMethod->id,
                    'payment_method' => $paymentMethodName,
                    'is_paid' => true,
                    'paid_amount' => (float) $lockedPayment->amount,
                    'balance' => 0.00,
                ]);

                $this->settlements->syncForPayment($lockedPayment->fresh(['member']), $paymentMethod);
            }

            return $lockedPayment->fresh();
        });

        $this->triggerBiometricSync($paid->member_id, $tenantId);
        $this->notifications->sendPaymentReceipt($paid);

        return $paid;
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

        $this->settlements->deleteForSource('payment', $lockedPayment->id);

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
            SyncBiometricMemberJob::dispatchForTenant($tenantId, $member->id, 'update');
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
            ? trim((string) ($member->name ?? '')) ?: 'Member'
            : 'Unknown';

        return [
            'id' => $payment->id,
            'member_id' => $payment->member_id,
            'member_name' => $memberName,
            'member_phone' => $member?->phone_number,
            'company_account_id' => $payment->company_account_id,
            'account_name' => $payment->account?->name,
            'payment_method_id' => $payment->payment_method_id,
            'payment_method_name' => $payment->payment_method === 'member_wallet' ? 'Member Wallet' : ($payment->paymentMethod?->name ?? $payment->payment_method),
            'payment_method_color' => $payment->payment_method === 'member_wallet' ? 'emerald' : ($payment->paymentMethod?->color ?? 'slate'),
            'payment_method_icon' => $payment->payment_method === 'member_wallet' ? 'Wallet' : ($payment->paymentMethod?->icon ?? 'CreditCard'),
            'payment_plan_id' => $payment->membership?->payment_plan_id,
            'payment_plan_name' => $payment->membership?->plan?->name,
            'payment_method' => $payment->payment_method ?? 'cash',
            'settlement_status' => $payment->settlement?->status,
            'settlement_gross_amount' => $payment->settlement ? round((float) $payment->settlement->gross_amount, 2) : null,
            'settlement_deduction_amount' => $payment->settlement ? round((float) $payment->settlement->deduction_amount, 2) : null,
            'settlement_net_amount' => $payment->settlement ? round((float) $payment->settlement->net_amount, 2) : null,
            'amount' => round((float) $payment->amount, 2),
            'payment_date' => $payment->payment_date?->toDateString(),
            'start_date' => $payment->membership?->start_date?->toDateString(),
            'end_date' => $payment->membership?->end_date?->toDateString(),
            'reference_number' => $payment->reference_number,
            'notes' => $payment->notes,
            'created_at' => optional($payment->created_at)->format('Y-m-d H:i'),
            'is_paid' => (bool) $payment->is_paid,
            'paid_amount' => round((float) $payment->paid_amount, 2),
            'balance' => round((float) $payment->balance, 2),
        ];
    }
}

<?php

namespace App\Services;

use App\Models\CompanyAccount;
use App\Models\CompanyAccountTransaction;
use App\Models\Member;
use App\Models\MemberPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function meta(int $tenantId): array
    {
        $members = Member::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'name', 'phone_number', 'payment_plan', 'price']);

        $accounts = CompanyAccount::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->withSum('incomingTransfers as incoming_total', 'amount')
            ->withSum('outgoingTransfers as outgoing_total', 'amount')
            ->withSum('transactions as transaction_total', 'amount')
            ->get();

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
                    'payment_plan' => $member->payment_plan,
                    'price' => (float) ($member->price ?? 0),
                ];
            })->values(),
            'accounts' => $accounts->map(fn (CompanyAccount $account) => [
                'id'              => $account->id,
                'name'            => $account->name,
                'current_balance' => round(
                    (float) $account->opening_balance
                    + (float) ($account->incoming_total ?? 0)
                    + (float) ($account->transaction_total ?? 0)
                    - (float) ($account->outgoing_total ?? 0),
                    2
                ),
            ])->values(),
        ];
    }

    public function memberPayments(int $memberId, int $tenantId, int $perPage): array
    {
        $payments = MemberPayment::query()
            ->where('tenant_id', $tenantId)
            ->where('member_id', $memberId)
            ->with(['account:id,name'])
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
            ->where('tenant_id', $tenantId)
            ->with([
                'member:id,first_name,last_name,name,phone_number',
                'account:id,name',
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
            ->where('tenant_id', $tenantId)
            ->with([
                'member:id,first_name,last_name,name,phone_number',
                'account:id,name',
            ])
            ->find($payment->id);

        if (!$payment) {
            abort(404);
        }

        return $this->serialize($payment);
    }

    public function storePayment(int $tenantId, array $validated): MemberPayment
    {
        return DB::transaction(function () use ($tenantId, $validated) {
            $isWalletPayment = ($validated['payment_method'] ?? 'cash') === 'member_wallet';

            if (!empty($validated['member_id'])) {
                $this->ensureMemberBelongsToTenant((int) $validated['member_id'], $tenantId);
            }

            if ($isWalletPayment) {
                if (empty($validated['member_id'])) {
                    abort(422, 'Please select a member for wallet payment.');
                }
                $member = Member::query()
                    ->where('tenant_id', $tenantId)
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
                'tenant_id'          => $tenantId,
                'member_id'          => $validated['member_id'] ?? null,
                'company_account_id' => $accountId,
                'payment_method'     => $isWalletPayment ? 'member_wallet' : 'cash',
                'amount'             => $validated['amount'],
                'payment_date'       => $validated['payment_date'],
                'reference_number'   => filled($validated['reference_number'] ?? null) ? trim((string) $validated['reference_number']) : null,
                'notes'              => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
            ]);

            if (!$isWalletPayment) {
                $this->syncTransaction($payment, $tenantId);
            }

            return $payment;
        });
    }

    public function updatePayment(MemberPayment $payment, int $tenantId, array $validated): void
    {
        DB::transaction(function () use ($payment, $tenantId, $validated) {
            $lockedPayment = MemberPayment::query()
                ->where('tenant_id', $tenantId)
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
                'member_id'          => $validated['member_id'] ?? null,
                'company_account_id' => $validated['company_account_id'],
                'amount'             => $validated['amount'],
                'payment_date'       => $validated['payment_date'],
                'reference_number'   => filled($validated['reference_number'] ?? null) ? trim((string) $validated['reference_number']) : null,
                'notes'              => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
            ]);

            $this->syncTransaction($lockedPayment, $tenantId);
        });
    }

    public function destroyPayment(MemberPayment $payment, int $tenantId): void
    {
        $lockedPayment = MemberPayment::query()
            ->where('tenant_id', $tenantId)
            ->find($payment->id);

        if (!$lockedPayment) {
            abort(404);
        }

        // Refund wallet if this was a wallet payment
        if ($lockedPayment->payment_method === 'member_wallet' && $lockedPayment->member_id) {
            $member = Member::query()
                ->where('tenant_id', $tenantId)
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
    }

    private function syncTransaction(MemberPayment $payment, int $tenantId): void
    {
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
                'tenant_id'          => $tenantId,
                'company_account_id' => $payment->company_account_id,
                'type'               => 'payment',
                'amount'             => (float) $payment->amount,
                'transaction_date'   => $payment->payment_date->toDateString(),
                'reference_number'   => $payment->reference_number,
                'notes'              => filled($payment->notes) ? $payment->notes : 'Payment: ' . $memberName,
            ]
        );
    }

    private function ensureMemberBelongsToTenant(int $memberId, int $tenantId): void
    {
        $exists = Member::query()
            ->where('id', $memberId)
            ->where('tenant_id', $tenantId)
            ->exists();

        if (!$exists) {
            abort(422, 'Invalid member selection.');
        }
    }

    private function ensureAccountBelongsToTenant(int $accountId, int $tenantId): void
    {
        $exists = CompanyAccount::query()
            ->where('id', $accountId)
            ->where('tenant_id', $tenantId)
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
            'payment_method' => $payment->payment_method ?? 'cash',
            'amount' => round((float) $payment->amount, 2),
            'payment_date' => $payment->payment_date?->toDateString(),
            'reference_number' => $payment->reference_number,
            'notes' => $payment->notes,
            'created_at' => optional($payment->created_at)->format('Y-m-d H:i'),
        ];
    }
}

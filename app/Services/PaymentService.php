<?php

namespace App\Services;

use App\Models\CompanyAccount;
use App\Models\CompanyAccountTransaction;
use App\Models\Member;
use App\Models\MemberPayment;
use Illuminate\Support\Facades\DB;

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
            ->get(['id', 'name']);

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
                'id' => $account->id,
                'name' => $account->name,
            ])->values(),
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
            if (!empty($validated['member_id'])) {
                $this->ensureMemberBelongsToTenant((int) $validated['member_id'], $tenantId);
            }
            $this->ensureAccountBelongsToTenant((int) $validated['company_account_id'], $tenantId);

            $payment = MemberPayment::create([
                'tenant_id' => $tenantId,
                'member_id' => $validated['member_id'] ?? null,
                'company_account_id' => $validated['company_account_id'],
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'reference_number' => filled($validated['reference_number'] ?? null) ? trim((string) $validated['reference_number']) : null,
                'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
            ]);

            $this->syncTransaction($payment, $tenantId);

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
            'amount' => round((float) $payment->amount, 2),
            'payment_date' => $payment->payment_date?->toDateString(),
            'reference_number' => $payment->reference_number,
            'notes' => $payment->notes,
            'created_at' => optional($payment->created_at)->format('Y-m-d H:i'),
        ];
    }
}

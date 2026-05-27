<?php

namespace App\Services;

use App\Models\CompanyAccount;
use App\Models\CompanyAccountTransaction;
use App\Models\Member;
use App\Models\MemberPayment;
use App\Models\Sale;
use App\Models\WalletTopup;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function __construct(private readonly AuditService $auditService) {}

    public function meta(int $tenantId): array
    {
        $accounts = CompanyAccount::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->withSum('incomingTransfers as incoming_total', 'amount')
            ->withSum('outgoingTransfers as outgoing_total', 'amount')
            ->withSum('transactions as transaction_total', 'amount')
            ->get();

        return [
            'accounts' => $accounts->map(fn (CompanyAccount $a) => [
                'id' => $a->id,
                'name' => $a->name,
                'current_balance' => round(
                    (float) $a->opening_balance
                    + (float) ($a->incoming_total ?? 0)
                    + (float) ($a->transaction_total ?? 0)
                    - (float) ($a->outgoing_total ?? 0),
                    2,
                ),
            ])->values(),
        ];
    }

    public function topup(Member $member, int $tenantId, array $validated, int $createdBy): WalletTopup
    {
        return DB::transaction(function () use ($member, $tenantId, $validated, $createdBy) {
            $account = CompanyAccount::query()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->find((int) $validated['company_account_id']);

            if (!$account) {
                abort(422, 'Invalid company account selection.');
            }

            $lockedMember = Member::query()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->find($member->id);

            if (!$lockedMember) {
                abort(404);
            }

            $topup = WalletTopup::create([
                'tenant_id' => $tenantId,
                'member_id' => $lockedMember->id,
                'company_account_id' => $account->id,
                'amount' => (float) $validated['amount'],
                'topup_date' => $validated['topup_date'],
                'reference_number' => filled($validated['reference_number'] ?? null) ? trim((string) $validated['reference_number']) : null,
                'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
                'created_by' => $createdBy,
            ]);

            $lockedMember->update([
                'current_balance' => (float) $lockedMember->current_balance + (float) $validated['amount'],
            ]);

            CompanyAccountTransaction::create([
                'tenant_id' => $tenantId,
                'company_account_id' => $account->id,
                'model_name' => 'wallet_topup',
                'reference_id' => $topup->id,
                'type' => 'wallet_topup',
                'amount' => (float) $validated['amount'],
                'transaction_date' => $validated['topup_date'],
                'reference_number' => $topup->reference_number,
                'notes' => 'Wallet top-up for ' . trim(($lockedMember->first_name ?? '') . ' ' . ($lockedMember->last_name ?? '')) ?: $lockedMember->name,
            ]);

            $this->auditService->log($tenantId, 'wallet_topup', $topup, [], [
                'amount' => (float) $validated['amount'],
                'member_id' => $lockedMember->id,
            ]);

            return $topup;
        });
    }

    public function topupHistory(Member $member, int $tenantId, int $perPage): array
    {
        $topups = WalletTopup::query()
            ->where('tenant_id', $tenantId)
            ->where('member_id', $member->id)
            ->with('account:id,name')
            ->orderBy('topup_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'data' => collect($topups->items())->map(fn (WalletTopup $t) => $this->serializeTopup($t)),
            'meta' => [
                'current_page' => $topups->currentPage(),
                'last_page' => $topups->lastPage(),
                'per_page' => $topups->perPage(),
                'total' => $topups->total(),
            ],
        ];
    }

    public function transactions(Member $member, int $tenantId, int $perPage): array
    {
        // Build a unified list of wallet movements
        $topups = WalletTopup::query()
            ->where('tenant_id', $tenantId)
            ->where('member_id', $member->id)
            ->get()
            ->map(fn (WalletTopup $t) => [
                'id' => 'topup_' . $t->id,
                'type' => 'topup',
                'label' => 'Wallet Top-up',
                'amount' => (float) $t->amount,
                'direction' => 'credit',
                'date' => $t->topup_date?->toDateString(),
                'reference' => $t->reference_number,
                'notes' => $t->notes,
                'created_at' => optional($t->created_at)->format('Y-m-d H:i'),
            ]);

        $walletSales = Sale::query()
            ->where('tenant_id', $tenantId)
            ->where('customer_member_id', $member->id)
            ->where('payment_method', 'member_wallet')
            ->whereNull('deleted_at')
            ->get()
            ->map(fn (Sale $s) => [
                'id' => 'sale_' . $s->id,
                'type' => 'sale',
                'label' => 'Sale #' . $s->id,
                'amount' => (float) $s->total_amount,
                'direction' => 'debit',
                'date' => optional($s->created_at)->toDateString(),
                'reference' => $s->reference_number,
                'notes' => null,
                'created_at' => optional($s->created_at)->format('Y-m-d H:i'),
            ]);

        $walletPayments = MemberPayment::query()
            ->where('tenant_id', $tenantId)
            ->where('member_id', $member->id)
            ->where('payment_method', 'member_wallet')
            ->get()
            ->map(fn (MemberPayment $p) => [
                'id' => 'payment_' . $p->id,
                'type' => 'payment',
                'label' => 'Member Payment',
                'amount' => (float) $p->amount,
                'direction' => 'debit',
                'date' => $p->payment_date?->toDateString(),
                'reference' => $p->reference_number,
                'notes' => $p->notes,
                'created_at' => optional($p->created_at)->format('Y-m-d H:i'),
            ]);

        $all = $topups->concat($walletSales)->concat($walletPayments)
            ->sortByDesc('created_at')
            ->values();

        $total = $all->count();
        $page = max(1, (int) request('page', 1));
        $offset = ($page - 1) * $perPage;
        $items = $all->slice($offset, $perPage)->values();
        $lastPage = max(1, (int) ceil($total / $perPage));

        return [
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ];
    }

    public function show(WalletTopup $topup, int $tenantId): array
    {
        if ($topup->tenant_id !== $tenantId) {
            abort(404);
        }

        $topup->load(['member:id,first_name,last_name,name,biometric_member_id,email,phone_number', 'account:id,name', 'createdBy:id,name']);

        $member = $topup->member;
        $memberName = $member
            ? (trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) ?: $member->name)
            : null;

        return [
            ...$this->serializeTopup($topup),
            'created_by_name' => $topup->createdBy?->name,
            'member' => $member ? [
                'id' => $member->id,
                'member_id' => $member->biometric_member_id,
                'name' => $memberName,
                'email' => $member->email,
                'phone' => $member->phone_number,
            ] : null,
        ];
    }

    private function serializeTopup(WalletTopup $topup): array
    {
        return [
            'id' => $topup->id,
            'amount' => round((float) $topup->amount, 2),
            'topup_date' => $topup->topup_date?->toDateString(),
            'reference_number' => $topup->reference_number,
            'notes' => $topup->notes,
            'account_id' => $topup->company_account_id,
            'account_name' => $topup->account?->name,
            'created_at' => optional($topup->created_at)->format('Y-m-d H:i'),
        ];
    }
}

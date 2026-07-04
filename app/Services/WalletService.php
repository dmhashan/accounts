<?php

namespace App\Services;

use App\Models\CompanyAccount;
use App\Models\CompanyAccountTransaction;
use App\Models\Member;
use App\Models\MemberPayment;
use App\Models\Sale;
use App\Models\WalletTopup;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function __construct(private readonly AuditService $auditService) {}

    public function meta(int $tenantId): array
    {
        $accounts = CompanyAccount::query()
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
                ->lockForUpdate()
                ->find((int) $validated['company_account_id']);

            if (!$account) {
                abort(422, 'Invalid company account selection.');
            }

            $lockedMember = Member::query()
                ->lockForUpdate()
                ->find($member->id);

            if (!$lockedMember) {
                abort(404);
            }

            $topup = WalletTopup::create([
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
                'company_account_id' => $account->id,
                'model_name' => 'wallet_topup',
                'reference_id' => $topup->id,
                'type' => 'wallet_topup',
                'amount' => (float) $validated['amount'],
                'transaction_date' => $validated['topup_date'],
                'reference_number' => $topup->reference_number,
                'notes' => 'Wallet top-up for ' . (trim((string) ($lockedMember->name ?? '')) ?: 'Member'),
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
        $topups = WalletTopup::query()
            ->where('member_id', $member->id)
            ->selectRaw('id as source_id')
            ->selectRaw("'topup' as type")
            ->selectRaw('amount as amount')
            ->selectRaw("'credit' as direction")
            ->selectRaw('topup_date as movement_date')
            ->selectRaw('reference_number as reference')
            ->selectRaw('notes as notes')
            ->selectRaw('created_at as sort_at');

        $walletSales = Sale::query()
            ->where('customer_member_id', $member->id)
            ->where('payment_method', 'member_wallet')
            ->whereNull('deleted_at')
            ->selectRaw('id as source_id')
            ->selectRaw("'sale' as type")
            ->selectRaw('total_amount as amount')
            ->selectRaw("'debit' as direction")
            ->selectRaw('DATE(created_at) as movement_date')
            ->selectRaw('reference_number as reference')
            ->selectRaw('NULL as notes')
            ->selectRaw('created_at as sort_at');

        $walletPayments = MemberPayment::query()
            ->where('member_id', $member->id)
            ->where('payment_method', 'member_wallet')
            ->selectRaw('id as source_id')
            ->selectRaw("'payment' as type")
            ->selectRaw('amount as amount')
            ->selectRaw("'debit' as direction")
            ->selectRaw('payment_date as movement_date')
            ->selectRaw('reference_number as reference')
            ->selectRaw('notes as notes')
            ->selectRaw('created_at as sort_at');

        $union = $topups->unionAll($walletSales)->unionAll($walletPayments);
        $page = max(1, (int) request('page', 1));
        $paginator = DB::query()
            ->fromSub($union, 'wallet_movements')
            ->orderByDesc('sort_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => collect($paginator->items())->map(fn ($row) => [
                'id' => $row->type . '_' . $row->source_id,
                'type' => (string) $row->type,
                'label' => $this->walletMovementLabel((string) $row->type, (int) $row->source_id),
                'amount' => (float) $row->amount,
                'direction' => (string) $row->direction,
                'date' => $row->movement_date ? Carbon::parse($row->movement_date)->toDateString() : null,
                'reference' => $row->reference,
                'notes' => $row->notes,
                'created_at' => $row->sort_at ? Carbon::parse($row->sort_at)->format('Y-m-d H:i') : null,
            ]),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    public function show(WalletTopup $topup, int $tenantId): array
    {

        $topup->load(['member:id,name,biometric_member_id,email,phone_number', 'account:id,name', 'createdBy:id,name']);

        $member = $topup->member;
        $memberName = $member
            ? (trim((string) ($member->name ?? '')) ?: 'Member')
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

    private function walletMovementLabel(string $type, int $sourceId): string
    {
        return match ($type) {
            'sale' => 'Sale #' . $sourceId,
            'payment' => 'Member Payment',
            default => 'Wallet Top-up',
        };
    }
}

<?php

namespace App\Services;

use App\Models\CompanyAccount;
use App\Models\CompanyAccountTransaction;
use App\Models\CompanyAccountTransfer;
use App\Models\Expense;
use App\Models\MemberPayment;
use App\Models\Sale;
use App\Models\WalletTopup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class CompanyAccountService
{
    public function meta(int $tenantId): array
    {
        $accounts = $this->accountQuery($tenantId)
            ->orderBy('name')
            ->get();

        return [
            'accounts' => $accounts->map(fn (CompanyAccount $account) => [
                ...$this->serializeAccount($account),
                'label' => $account->name,
            ])->values(),
        ];
    }

    public function accounts(int $tenantId, int $perPage): array
    {
        $accounts = $this->accountQuery($tenantId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'data' => collect($accounts->items())->map(fn (CompanyAccount $account) => $this->serializeAccount($account)),
            'meta' => [
                'current_page' => $accounts->currentPage(),
                'last_page' => $accounts->lastPage(),
                'per_page' => $accounts->perPage(),
                'total' => $accounts->total(),
            ],
        ];
    }

    public function showAccount(CompanyAccount $account, int $tenantId): array
    {
        $account = $this->accountQuery($tenantId)->find($account->id);

        if (!$account) {
            abort(404);
        }

        return $this->serializeAccount($account);
    }

    public function storeAccount(int $tenantId, array $validated): CompanyAccount
    {
        return CompanyAccount::create([
            'tenant_id' => $tenantId,
            'name' => trim($validated['name']),
            'opening_balance' => $validated['opening_balance'],
            'description' => filled($validated['description'] ?? null) ? trim((string) $validated['description']) : null,
        ]);
    }

    public function updateAccount(CompanyAccount $account, int $tenantId, array $validated): void
    {
        $this->ensureAccountTenant($account, $tenantId);

        $account->update([
            'name' => trim($validated['name']),
            'opening_balance' => $validated['opening_balance'],
            'description' => filled($validated['description'] ?? null) ? trim((string) $validated['description']) : null,
        ]);
    }

    public function destroyAccount(CompanyAccount $account, int $tenantId): ?string
    {
        $this->ensureAccountTenant($account, $tenantId);

        $hasTransfers = CompanyAccountTransfer::query()
            ->where('tenant_id', $tenantId)
            ->where(function (Builder $query) use ($account) {
                $query->where('source_account_id', $account->id)
                    ->orWhere('destination_account_id', $account->id);
            })
            ->exists();

        $hasTransactions = CompanyAccountTransaction::query()
            ->where('tenant_id', $tenantId)
            ->where('company_account_id', $account->id)
            ->exists();

        if ($hasTransfers || $hasTransactions) {
            return 'Account cannot be deleted because transaction history exists.';
        }

        $account->delete();

        return null;
    }

    public function transactions(int $tenantId, int $perPage): array
    {
        $transactions = CompanyAccountTransaction::query()
            ->where('tenant_id', $tenantId)
            ->with(['account:id,name'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $items = collect($transactions->items());

        // Collect reference IDs per model type
        $saleIds    = $items->where('model_name', 'sale')->pluck('reference_id')->filter()->unique()->values();
        $expenseIds = $items->where('model_name', 'expense')->pluck('reference_id')->filter()->unique()->values();
        $paymentIds = $items->where('model_name', 'payment')->pluck('reference_id')->filter()->unique()->values();
        $topupIds   = $items->where('model_name', 'wallet_topup')->pluck('reference_id')->filter()->unique()->values();

        // Load related records in bulk
        $sales    = $saleIds->isNotEmpty()
            ? Sale::whereIn('id', $saleIds)->get(['id', 'reference_number', 'customer_name'])->keyBy('id')
            : collect();
        $expenses = $expenseIds->isNotEmpty()
            ? Expense::whereIn('id', $expenseIds)->get(['id', 'category'])->keyBy('id')
            : collect();
        $payments = $paymentIds->isNotEmpty()
            ? MemberPayment::whereIn('id', $paymentIds)
                ->with('member:id,first_name,last_name,name')
                ->get(['id', 'member_id'])
                ->keyBy('id')
            : collect();
        $topups   = $topupIds->isNotEmpty()
            ? WalletTopup::whereIn('id', $topupIds)
                ->with('member:id,member_id,first_name,last_name,name')
                ->get(['id', 'member_id', 'reference_number'])
                ->keyBy('id')
            : collect();

        return [
            'data' => $items->map(function (CompanyAccountTransaction $tx) use ($sales, $expenses, $payments, $topups) {
                $sourceReference = null;
                $customer = null;
                $memberId = null;

                if ($tx->model_name === 'sale' && $tx->reference_id) {
                    $sale = $sales->get($tx->reference_id);
                    $sourceReference = $sale?->reference_number;
                    $customer = $sale?->customer_name;
                } elseif ($tx->model_name === 'expense' && $tx->reference_id) {
                    $expense = $expenses->get($tx->reference_id);
                    $sourceReference = $expense?->category;
                } elseif ($tx->model_name === 'payment' && $tx->reference_id) {
                    $payment = $payments->get($tx->reference_id);
                    if ($payment?->member) {
                        $m = $payment->member;
                        $customer = trim(($m->first_name ?? '') . ' ' . ($m->last_name ?? '')) ?: ($m->name ?? null);
                        $memberId = $m->id;
                    }
                } elseif ($tx->model_name === 'wallet_topup' && $tx->reference_id) {
                    $topup = $topups->get($tx->reference_id);
                    $sourceReference = $topup?->reference_number;
                    $memberId = $topup?->member_id;
                    if ($topup?->member) {
                        $m = $topup->member;
                        $customer = $m->member_id;
                    }
                }

                return [
                    'id'               => $tx->id,
                    'type'             => $tx->type,
                    'model_name'       => $tx->model_name,
                    'reference_id'     => $tx->reference_id,
                    'member_id'        => $memberId,
                    'amount'           => $tx->amount,
                    'transaction_date' => $tx->transaction_date?->toDateString(),
                    'reference_number' => $tx->reference_number,
                    'notes'            => $tx->notes,
                    'account_name'     => $tx->account?->name,
                    'source_reference' => $sourceReference,
                    'customer'         => $customer,
                ];
            }),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page'    => $transactions->lastPage(),
                'per_page'     => $transactions->perPage(),
                'total'        => $transactions->total(),
            ],
        ];
    }

    public function transfers(int $tenantId, int $perPage): array
    {
        $transfers = CompanyAccountTransfer::query()
            ->where('tenant_id', $tenantId)
            ->with([
                'sourceAccount:id,name',
                'destinationAccount:id,name',
            ])
            ->orderBy('transfer_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'data' => collect($transfers->items())->map(fn (CompanyAccountTransfer $transfer) => $this->serializeTransfer($transfer)),
            'meta' => [
                'current_page' => $transfers->currentPage(),
                'last_page' => $transfers->lastPage(),
                'per_page' => $transfers->perPage(),
                'total' => $transfers->total(),
            ],
        ];
    }

    public function showTransfer(CompanyAccountTransfer $transfer, int $tenantId): array
    {
        $transfer = CompanyAccountTransfer::query()
            ->where('tenant_id', $tenantId)
            ->with([
                'sourceAccount:id,name',
                'destinationAccount:id,name',
            ])
            ->find($transfer->id);

        if (!$transfer) {
            abort(404);
        }

        return $this->serializeTransfer($transfer);
    }

    public function storeTransfer(int $tenantId, array $validated): array
    {
        return DB::transaction(function () use ($tenantId, $validated) {
            $accounts = $this->lockAccounts($tenantId, [
                (int) $validated['source_account_id'],
                (int) $validated['destination_account_id'],
            ]);

            $sourceAccount = $accounts->get((int) $validated['source_account_id']);
            $destinationAccount = $accounts->get((int) $validated['destination_account_id']);

            $error = $this->validateTransferAccounts($sourceAccount, $destinationAccount);
            if ($error) {
                return ['error' => $error];
            }

            if (!$this->hasSufficientBalance($sourceAccount, $tenantId, (float) $validated['amount'])) {
                return ['error' => 'Insufficient balance in source account.'];
            }

            $transfer = CompanyAccountTransfer::create([
                'tenant_id' => $tenantId,
                'source_account_id' => $sourceAccount->id,
                'destination_account_id' => $destinationAccount->id,
                'amount' => $validated['amount'],
                'transfer_date' => $validated['transfer_date'],
                'reference_number' => filled($validated['reference_number'] ?? null) ? trim((string) $validated['reference_number']) : null,
                'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
            ]);

            $transfer->load([
                'sourceAccount:id,name',
                'destinationAccount:id,name',
            ]);

            return ['transfer' => $transfer];
        });
    }

    public function updateTransfer(CompanyAccountTransfer $transfer, int $tenantId, array $validated): ?string
    {
        return DB::transaction(function () use ($transfer, $tenantId, $validated) {
            $lockedTransfer = CompanyAccountTransfer::query()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->find($transfer->id);

            if (!$lockedTransfer) {
                abort(404);
            }

            $accounts = $this->lockAccounts($tenantId, [
                $lockedTransfer->source_account_id,
                $lockedTransfer->destination_account_id,
                (int) $validated['source_account_id'],
                (int) $validated['destination_account_id'],
            ]);

            $sourceAccount = $accounts->get((int) $validated['source_account_id']);
            $destinationAccount = $accounts->get((int) $validated['destination_account_id']);

            $error = $this->validateTransferAccounts($sourceAccount, $destinationAccount);
            if ($error) {
                return $error;
            }

            if (!$this->hasSufficientBalance($sourceAccount, $tenantId, (float) $validated['amount'], $lockedTransfer->id)) {
                return 'Insufficient balance in source account.';
            }

            $lockedTransfer->update([
                'source_account_id' => $sourceAccount->id,
                'destination_account_id' => $destinationAccount->id,
                'amount' => $validated['amount'],
                'transfer_date' => $validated['transfer_date'],
                'reference_number' => filled($validated['reference_number'] ?? null) ? trim((string) $validated['reference_number']) : null,
                'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
            ]);

            return null;
        });
    }

    public function destroyTransfer(CompanyAccountTransfer $transfer, int $tenantId): void
    {
        DB::transaction(function () use ($transfer, $tenantId) {
            $lockedTransfer = CompanyAccountTransfer::query()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->find($transfer->id);

            if (!$lockedTransfer) {
                abort(404);
            }

            $this->lockAccounts($tenantId, [
                $lockedTransfer->source_account_id,
                $lockedTransfer->destination_account_id,
            ]);

            $lockedTransfer->delete();
        });
    }

    private function accountQuery(int $tenantId): Builder
    {
        return CompanyAccount::query()
            ->where('tenant_id', $tenantId)
            ->withSum('incomingTransfers as incoming_total', 'amount')
            ->withSum('outgoingTransfers as outgoing_total', 'amount')
            ->withSum('transactions as transaction_total', 'amount');
    }

    private function serializeAccount(CompanyAccount $account): array
    {
        $openingBalance = (float) $account->opening_balance;
        $incomingTotal = (float) ($account->incoming_total ?? 0);
        $outgoingTotal = (float) ($account->outgoing_total ?? 0);
        $transactionTotal = (float) ($account->transaction_total ?? 0);

        return [
            'id' => $account->id,
            'name' => $account->name,
            'opening_balance' => round($openingBalance, 2),
            'current_balance' => round($openingBalance + $incomingTotal + $transactionTotal - $outgoingTotal, 2),
            'description' => $account->description,
            'created_at' => optional($account->created_at)->format('Y-m-d H:i'),
        ];
    }

    private function serializeTransfer(CompanyAccountTransfer $transfer): array
    {
        return [
            'id' => $transfer->id,
            'source_account_id' => $transfer->source_account_id,
            'source_account_name' => $transfer->sourceAccount?->name,
            'destination_account_id' => $transfer->destination_account_id,
            'destination_account_name' => $transfer->destinationAccount?->name,
            'amount' => round((float) $transfer->amount, 2),
            'transfer_date' => optional($transfer->transfer_date)->format('Y-m-d'),
            'reference_number' => $transfer->reference_number,
            'notes' => $transfer->notes,
            'created_at' => optional($transfer->created_at)->format('Y-m-d H:i'),
        ];
    }

    private function ensureAccountTenant(CompanyAccount $account, int $tenantId): void
    {
        if ($account->tenant_id !== $tenantId) {
            abort(404);
        }
    }

    private function lockAccounts(int $tenantId, array $accountIds): Collection
    {
        $accountIds = array_values(array_unique($accountIds));
        sort($accountIds);

        return CompanyAccount::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $accountIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    private function validateTransferAccounts(?CompanyAccount $sourceAccount, ?CompanyAccount $destinationAccount): ?string
    {
        if (!$sourceAccount || !$destinationAccount) {
            return 'Invalid account selection.';
        }

        if ($sourceAccount->id === $destinationAccount->id) {
            return 'Source and destination accounts must be different.';
        }

        return null;
    }

    private function hasSufficientBalance(CompanyAccount $sourceAccount, int $tenantId, float $amount, ?int $excludedTransferId = null): bool
    {
        return $this->accountBalance($sourceAccount, $tenantId, $excludedTransferId) + 0.00001 >= $amount;
    }

    private function accountBalance(CompanyAccount $account, int $tenantId, ?int $excludedTransferId = null): float
    {
        $incomingQuery = CompanyAccountTransfer::query()
            ->where('tenant_id', $tenantId)
            ->where('destination_account_id', $account->id);

        $outgoingQuery = CompanyAccountTransfer::query()
            ->where('tenant_id', $tenantId)
            ->where('source_account_id', $account->id);

        if ($excludedTransferId) {
            $incomingQuery->where('id', '!=', $excludedTransferId);
            $outgoingQuery->where('id', '!=', $excludedTransferId);
        }

        $transactionTotal = CompanyAccountTransaction::query()
            ->where('tenant_id', $tenantId)
            ->where('company_account_id', $account->id)
            ->sum('amount');

        return round(
            (float) $account->opening_balance
            + (float) $incomingQuery->sum('amount')
            + (float) $transactionTotal
            - (float) $outgoingQuery->sum('amount'),
            2
        );
    }
}
<?php

namespace App\Services;

use App\Models\CompanyAccount;
use App\Models\Member;
use App\Models\Sale;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialTransactionService
{
    public function ensureMemberWallet(Member $member, ?int $userId = null): Wallet
    {
        return DB::transaction(function () use ($member, $userId) {
            $wallet = Wallet::query()
                ->where('tenant_id', $member->tenant_id)
                ->where('member_id', $member->id)
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                $wallet = Wallet::create([
                    'tenant_id' => $member->tenant_id,
                    'member_id' => $member->id,
                    'status' => Wallet::STATUS_ACTIVE,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }

            return $this->syncWalletBalanceFromLedger($wallet, $userId);
        });
    }

    public function syncWalletBalance(Wallet $wallet, ?int $userId = null): Wallet
    {
        return DB::transaction(function () use ($wallet, $userId) {
            $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            return $this->syncWalletBalanceFromLedger($wallet, $userId);
        });
    }

    public function syncCompanyAccountBalance(CompanyAccount $account, ?int $userId = null): CompanyAccount
    {
        return DB::transaction(function () use ($account, $userId) {
            $account = CompanyAccount::query()->whereKey($account->id)->lockForUpdate()->firstOrFail();

            return $this->syncCompanyAccountBalanceFromLedger($account, $userId);
        });
    }

    public function recordWalletTransaction(Wallet $wallet, array $payload, ?int $userId = null): Transaction
    {
        return DB::transaction(function () use ($wallet, $payload, $userId) {
            $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            $wallet = $this->syncWalletBalanceFromLedger($wallet, $userId);

            $transactionType = $payload['transaction_type'] ?? null;
            abort_unless(in_array($transactionType, [Transaction::TYPE_CREDIT, Transaction::TYPE_DEBIT], true), 422, 'Invalid transaction type.');

            $status = $payload['status'] ?? Transaction::STATUS_COMPLETED;
            abort_unless(in_array($status, [Transaction::STATUS_PENDING, Transaction::STATUS_COMPLETED, Transaction::STATUS_CANCELLED], true), 422, 'Invalid transaction status.');

            $amount = $this->normalizeAmount($payload['amount'] ?? null);
            $balanceBefore = round((float) $wallet->current_balance, 2);
            $balanceAfter = $balanceBefore;

            if ($status === Transaction::STATUS_COMPLETED) {
                abort_if($wallet->status !== Wallet::STATUS_ACTIVE, 422, 'Wallet is suspended and cannot process transactions.');

                $balanceAfter = $this->calculateBalanceAfter($balanceBefore, $amount, $transactionType);

                if ($transactionType === Transaction::TYPE_DEBIT) {
                    $creditLimit = (float) DB::table('tenants')
                        ->where('id', $wallet->tenant_id)
                        ->value('wallet_credit_limit');

                    abort_if($balanceAfter < -$creditLimit, 422, 'Wallet credit limit exceeded.');
                }
            }

            $transaction = Transaction::create([
                'tenant_id' => $wallet->tenant_id,
                'transaction_reference_type' => Transaction::REFERENCE_WALLET,
                'reference_id' => $wallet->id,
                'amount' => $amount,
                'transaction_type' => $transactionType,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $payload['description'] ?? null,
                'status' => $status,
                'transaction_date' => $this->parseTransactionDate($payload['transaction_date'] ?? null),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            if ($status === Transaction::STATUS_COMPLETED) {
                Wallet::withoutBalanceGuard(function () use ($wallet, $balanceAfter, $userId) {
                    $wallet->forceFill([
                        'current_balance' => $balanceAfter,
                        'updated_by' => $userId,
                    ]);
                    $wallet->save();
                });
            }

            return $transaction;
        });
    }

    public function recordCompanyAccountTransaction(CompanyAccount $account, array $payload, ?int $userId = null): Transaction
    {
        return DB::transaction(function () use ($account, $payload, $userId) {
            $account = CompanyAccount::query()->whereKey($account->id)->lockForUpdate()->firstOrFail();
            $account = $this->syncCompanyAccountBalanceFromLedger($account, $userId);

            $transactionType = $payload['transaction_type'] ?? null;
            abort_unless(in_array($transactionType, [Transaction::TYPE_CREDIT, Transaction::TYPE_DEBIT], true), 422, 'Invalid transaction type.');

            $status = $payload['status'] ?? Transaction::STATUS_COMPLETED;
            abort_unless(in_array($status, [Transaction::STATUS_PENDING, Transaction::STATUS_COMPLETED, Transaction::STATUS_CANCELLED], true), 422, 'Invalid transaction status.');

            $amount = $this->normalizeAmount($payload['amount'] ?? null);
            $balanceBefore = round((float) $account->current_balance, 2);
            $balanceAfter = $balanceBefore;

            if ($status === Transaction::STATUS_COMPLETED) {
                $balanceAfter = $this->calculateBalanceAfter($balanceBefore, $amount, $transactionType);
            }

            $transaction = Transaction::create([
                'tenant_id' => $account->tenant_id,
                'transaction_reference_type' => Transaction::REFERENCE_COMPANY_ACCOUNT,
                'reference_id' => $account->id,
                'amount' => $amount,
                'transaction_type' => $transactionType,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $payload['description'] ?? null,
                'status' => $status,
                'transaction_date' => $this->parseTransactionDate($payload['transaction_date'] ?? null),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            if ($status === Transaction::STATUS_COMPLETED) {
                CompanyAccount::withoutBalanceGuard(function () use ($account, $balanceAfter, $userId) {
                    $account->forceFill([
                        'current_balance' => $balanceAfter,
                        'updated_by' => $userId,
                    ]);
                    $account->save();
                });
            }

            return $transaction;
        });
    }

    public function transferBetweenCompanyAccounts(
        CompanyAccount $sourceAccount,
        CompanyAccount $destinationAccount,
        array $payload,
        ?int $userId = null
    ): array {
        return DB::transaction(function () use ($sourceAccount, $destinationAccount, $payload, $userId) {
            abort_if($sourceAccount->tenant_id !== $destinationAccount->tenant_id, 422, 'Accounts must belong to the same tenant.');
            abort_if($sourceAccount->id === $destinationAccount->id, 422, 'Source and destination accounts must be different.');

            $amount = $this->normalizeAmount($payload['amount'] ?? null);

            $ids = [$sourceAccount->id, $destinationAccount->id];
            sort($ids);

            $lockedAccounts = CompanyAccount::query()
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $source = $lockedAccounts->get($sourceAccount->id);
            $destination = $lockedAccounts->get($destinationAccount->id);

            abort_if(!$source || !$destination, 404, 'Account not found.');

            $source = $this->syncCompanyAccountBalanceFromLedger($source, $userId);
            $destination = $this->syncCompanyAccountBalanceFromLedger($destination, $userId);

            $sourceBalanceBefore = round((float) $source->current_balance, 2);
            $sourceBalanceAfter = $this->calculateBalanceAfter($sourceBalanceBefore, $amount, Transaction::TYPE_DEBIT);

            $destinationBalanceBefore = round((float) $destination->current_balance, 2);
            $destinationBalanceAfter = $this->calculateBalanceAfter($destinationBalanceBefore, $amount, Transaction::TYPE_CREDIT);

            $description = trim((string) ($payload['description'] ?? ''));
            $transactionDate = $this->parseTransactionDate($payload['transaction_date'] ?? null);

            $debitDescription = $description !== ''
                ? 'Transfer to '.$destination->account_name.' - '.$description
                : 'Transfer to '.$destination->account_name;
            $creditDescription = $description !== ''
                ? 'Transfer from '.$source->account_name.' - '.$description
                : 'Transfer from '.$source->account_name;

            $debitTransaction = Transaction::create([
                'tenant_id' => $source->tenant_id,
                'transaction_reference_type' => Transaction::REFERENCE_COMPANY_ACCOUNT,
                'reference_id' => $source->id,
                'amount' => $amount,
                'transaction_type' => Transaction::TYPE_DEBIT,
                'balance_before' => $sourceBalanceBefore,
                'balance_after' => $sourceBalanceAfter,
                'description' => $debitDescription,
                'status' => Transaction::STATUS_COMPLETED,
                'transaction_date' => $transactionDate,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $creditTransaction = Transaction::create([
                'tenant_id' => $destination->tenant_id,
                'transaction_reference_type' => Transaction::REFERENCE_COMPANY_ACCOUNT,
                'reference_id' => $destination->id,
                'amount' => $amount,
                'transaction_type' => Transaction::TYPE_CREDIT,
                'balance_before' => $destinationBalanceBefore,
                'balance_after' => $destinationBalanceAfter,
                'description' => $creditDescription,
                'status' => Transaction::STATUS_COMPLETED,
                'transaction_date' => $transactionDate,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            CompanyAccount::withoutBalanceGuard(function () use ($source, $sourceBalanceAfter, $destination, $destinationBalanceAfter, $userId) {
                $source->forceFill([
                    'current_balance' => $sourceBalanceAfter,
                    'updated_by' => $userId,
                ]);
                $source->save();

                $destination->forceFill([
                    'current_balance' => $destinationBalanceAfter,
                    'updated_by' => $userId,
                ]);
                $destination->save();
            });

            return [
                'debit_transaction' => $debitTransaction,
                'credit_transaction' => $creditTransaction,
                'source_account' => $source->fresh(),
                'destination_account' => $destination->fresh(),
            ];
        });
    }

    public function recordSaleTransaction(Sale $sale, array $payload = [], ?int $userId = null): Transaction
    {
        $status = $payload['status'] ?? Transaction::STATUS_COMPLETED;
        abort_unless(in_array($status, [Transaction::STATUS_PENDING, Transaction::STATUS_COMPLETED, Transaction::STATUS_CANCELLED], true), 422, 'Invalid transaction status.');

        $amount = isset($payload['amount'])
            ? $this->normalizeAmount($payload['amount'])
            : round((float) $sale->paid_amount, 2);

        return Transaction::create([
            'tenant_id' => $sale->tenant_id,
            'transaction_reference_type' => Transaction::REFERENCE_SALE,
            'reference_id' => $sale->id,
            'amount' => $amount,
            'transaction_type' => $payload['transaction_type'] ?? Transaction::TYPE_CREDIT,
            'balance_before' => 0,
            'balance_after' => 0,
            'description' => $payload['description'] ?? 'Sale #'.$sale->id.' recorded',
            'status' => $status,
            'transaction_date' => $this->parseTransactionDate($payload['transaction_date'] ?? null),
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    private function syncWalletBalanceFromLedger(Wallet $wallet, ?int $userId = null): Wallet
    {
        $calculatedBalance = (float) Transaction::query()
            ->where('tenant_id', $wallet->tenant_id)
            ->where('transaction_reference_type', Transaction::REFERENCE_WALLET)
            ->where('reference_id', $wallet->id)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->selectRaw("COALESCE(SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END), 0) as balance")
            ->value('balance');

        $calculatedBalance = round($calculatedBalance, 2);
        $currentBalance = round((float) $wallet->current_balance, 2);

        if (abs($calculatedBalance - $currentBalance) > 0.0001) {
            Wallet::withoutBalanceGuard(function () use ($wallet, $calculatedBalance, $userId) {
                $wallet->forceFill([
                    'current_balance' => $calculatedBalance,
                    'updated_by' => $userId,
                ]);
                $wallet->save();
            });

            $wallet->refresh();
        }

        return $wallet;
    }

    private function syncCompanyAccountBalanceFromLedger(CompanyAccount $account, ?int $userId = null): CompanyAccount
    {
        $calculatedBalance = (float) Transaction::query()
            ->where('tenant_id', $account->tenant_id)
            ->where('transaction_reference_type', Transaction::REFERENCE_COMPANY_ACCOUNT)
            ->where('reference_id', $account->id)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->selectRaw("COALESCE(SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END), 0) as balance")
            ->value('balance');

        $calculatedBalance = round($calculatedBalance, 2);
        $currentBalance = round((float) $account->current_balance, 2);

        if (abs($calculatedBalance - $currentBalance) > 0.0001) {
            CompanyAccount::withoutBalanceGuard(function () use ($account, $calculatedBalance, $userId) {
                $account->forceFill([
                    'current_balance' => $calculatedBalance,
                    'updated_by' => $userId,
                ]);
                $account->save();
            });

            $account->refresh();
        }

        return $account;
    }

    private function calculateBalanceAfter(float $before, float $amount, string $transactionType): float
    {
        if ($transactionType === Transaction::TYPE_CREDIT) {
            return round($before + $amount, 2);
        }

        return round($before - $amount, 2);
    }

    private function normalizeAmount(mixed $amount): float
    {
        $normalized = round((float) $amount, 2);
        abort_if($normalized <= 0, 422, 'Amount must be greater than zero.');

        return $normalized;
    }

    private function parseTransactionDate(mixed $value): Carbon
    {
        if (blank($value)) {
            return now();
        }

        return Carbon::parse($value);
    }
}

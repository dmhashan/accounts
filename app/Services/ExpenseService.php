<?php

namespace App\Services;

use App\Models\CompanyAccount;
use App\Models\CompanyAccountTransaction;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function expenses(int $tenantId, int $perPage): array
    {
        $expenses = Expense::query()
            ->where('tenant_id', $tenantId)
            ->with('account:id,name')
            ->orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'data' => collect($expenses->items())->map(fn (Expense $expense) => $this->serialize($expense)),
            'meta' => [
                'current_page' => $expenses->currentPage(),
                'last_page' => $expenses->lastPage(),
                'per_page' => $expenses->perPage(),
                'total' => $expenses->total(),
            ],
        ];
    }

    public function showExpense(Expense $expense, int $tenantId): array
    {
        $expense = Expense::query()
            ->where('tenant_id', $tenantId)
            ->with('account:id,name')
            ->find($expense->id);

        if (!$expense) {
            abort(404);
        }

        return $this->serialize($expense);
    }

    public function storeExpense(int $tenantId, array $validated): Expense
    {
        return DB::transaction(function () use ($tenantId, $validated) {
            $this->ensureAccountBelongsToTenant((int) $validated['company_account_id'], $tenantId);

            $expense = Expense::create([
                'tenant_id' => $tenantId,
                'company_account_id' => $validated['company_account_id'],
                'category' => trim($validated['category']),
                'amount' => $validated['amount'],
                'expense_date' => $validated['expense_date'],
                'reference_number' => filled($validated['reference_number'] ?? null) ? trim((string) $validated['reference_number']) : null,
                'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
            ]);

            $this->syncTransaction($expense, $tenantId);

            return $expense;
        });
    }

    public function updateExpense(Expense $expense, int $tenantId, array $validated): void
    {
        DB::transaction(function () use ($expense, $tenantId, $validated) {
            $lockedExpense = Expense::query()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->find($expense->id);

            if (!$lockedExpense) {
                abort(404);
            }

            $this->ensureAccountBelongsToTenant((int) $validated['company_account_id'], $tenantId);

            $lockedExpense->update([
                'company_account_id' => $validated['company_account_id'],
                'category' => trim($validated['category']),
                'amount' => $validated['amount'],
                'expense_date' => $validated['expense_date'],
                'reference_number' => filled($validated['reference_number'] ?? null) ? trim((string) $validated['reference_number']) : null,
                'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
            ]);

            $this->syncTransaction($lockedExpense, $tenantId);
        });
    }

    public function destroyExpense(Expense $expense, int $tenantId): void
    {
        $lockedExpense = Expense::query()
            ->where('tenant_id', $tenantId)
            ->find($expense->id);

        if (!$lockedExpense) {
            abort(404);
        }

        // The transaction is deleted via cascadeOnDelete on the FK
        $lockedExpense->delete();
    }

    private function syncTransaction(Expense $expense, int $tenantId): void
    {
        // Expenses are debits: stored as negative amounts so they reduce the account balance
        CompanyAccountTransaction::updateOrCreate(
            ['expense_id' => $expense->id],
            [
                'tenant_id' => $tenantId,
                'company_account_id' => $expense->company_account_id,
                'sale_id' => null,
                'type' => 'expense',
                'amount' => -(float) $expense->amount,
                'transaction_date' => $expense->expense_date->toDateString(),
                'reference_number' => $expense->reference_number,
                'notes' => filled($expense->notes) ? $expense->notes : 'Expense: '.$expense->category,
            ]
        );
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

    private function serialize(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'company_account_id' => $expense->company_account_id,
            'account_name' => $expense->account?->name,
            'category' => $expense->category,
            'amount' => round((float) $expense->amount, 2),
            'expense_date' => $expense->expense_date?->toDateString(),
            'reference_number' => $expense->reference_number,
            'notes' => $expense->notes,
            'created_at' => optional($expense->created_at)->format('Y-m-d H:i'),
        ];
    }
}

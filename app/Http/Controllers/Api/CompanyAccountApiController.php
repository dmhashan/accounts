<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyAccount;
use App\Services\FinancialTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyAccountApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant')->id;
        $perPage = min((int) $request->integer('per_page', 15), 50);

        $accounts = CompanyAccount::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => collect($accounts->items())->map(fn (CompanyAccount $account) => [
                'id' => $account->id,
                'account_name' => $account->account_name,
                'description' => $account->description,
                'current_balance' => (float) $account->current_balance,
                'created_at' => optional($account->created_at)->toIso8601String(),
                'updated_at' => optional($account->updated_at)->toIso8601String(),
                'created_by' => $account->created_by,
                'updated_by' => $account->updated_by,
            ]),
            'meta' => [
                'current_page' => $accounts->currentPage(),
                'last_page' => $accounts->lastPage(),
                'per_page' => $accounts->perPage(),
                'total' => $accounts->total(),
            ],
        ]);
    }

    public function store(Request $request, FinancialTransactionService $financialTransactionService): JsonResponse
    {
        $tenantId = app('tenant')->id;
        $userId = $request->user()->id;

        $validated = $request->validate([
            'account_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('company_accounts')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'initial_balance' => ['nullable', 'numeric', 'min:0'],
        ]);

        $account = CompanyAccount::create([
            'tenant_id' => $tenantId,
            'account_name' => trim($validated['account_name']),
            'description' => $validated['description'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $initialBalance = round((float) ($validated['initial_balance'] ?? 0), 2);

        if ($initialBalance > 0) {
            $financialTransactionService->recordCompanyAccountTransaction($account, [
                'amount' => $initialBalance,
                'transaction_type' => 'credit',
                'description' => 'Opening balance',
                'status' => 'completed',
            ], $userId);

            $account = $account->fresh();
        }

        return response()->json([
            'message' => 'Company account created successfully.',
            'data' => [
                'id' => $account->id,
                'account_name' => $account->account_name,
                'description' => $account->description,
                'current_balance' => (float) $account->current_balance,
            ],
        ], 201);
    }

    public function show(CompanyAccount $companyAccount): JsonResponse
    {
        $this->ensureTenant($companyAccount);

        $recentTransactions = $companyAccount->transactions()
            ->latest('transaction_date')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => [
                'id' => $companyAccount->id,
                'account_name' => $companyAccount->account_name,
                'description' => $companyAccount->description,
                'current_balance' => (float) $companyAccount->current_balance,
                'created_at' => optional($companyAccount->created_at)->toIso8601String(),
                'updated_at' => optional($companyAccount->updated_at)->toIso8601String(),
                'created_by' => $companyAccount->created_by,
                'updated_by' => $companyAccount->updated_by,
                'transactions' => $recentTransactions->map(fn ($transaction) => [
                    'id' => $transaction->id,
                    'amount' => (float) $transaction->amount,
                    'transaction_type' => $transaction->transaction_type,
                    'balance_before' => (float) $transaction->balance_before,
                    'balance_after' => (float) $transaction->balance_after,
                    'description' => $transaction->description,
                    'status' => $transaction->status,
                    'transaction_date' => optional($transaction->transaction_date)->toIso8601String(),
                ])->values(),
            ],
        ]);
    }

    public function update(Request $request, CompanyAccount $companyAccount): JsonResponse
    {
        $this->ensureTenant($companyAccount);
        $tenantId = app('tenant')->id;

        $validated = $request->validate([
            'account_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('company_accounts')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($companyAccount->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $companyAccount->update([
            'account_name' => trim($validated['account_name']),
            'description' => $validated['description'] ?? null,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Company account updated successfully.',
        ]);
    }

    public function storeTransaction(
        Request $request,
        CompanyAccount $companyAccount,
        FinancialTransactionService $financialTransactionService
    ): JsonResponse {
        $this->ensureTenant($companyAccount);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_type' => ['required', 'in:credit,debit'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'in:pending,completed,cancelled'],
            'transaction_date' => ['nullable', 'date'],
        ]);

        $transaction = $financialTransactionService->recordCompanyAccountTransaction($companyAccount, [
            'amount' => $validated['amount'],
            'transaction_type' => $validated['transaction_type'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'completed',
            'transaction_date' => $validated['transaction_date'] ?? null,
        ], $request->user()->id);

        $account = $companyAccount->fresh();

        return response()->json([
            'message' => 'Company account transaction recorded successfully.',
            'data' => [
                'transaction_id' => $transaction->id,
                'current_balance' => (float) $account->current_balance,
            ],
        ], 201);
    }

    public function storeTransfer(Request $request, FinancialTransactionService $financialTransactionService): JsonResponse
    {
        $tenantId = app('tenant')->id;

        $validated = $request->validate([
            'source_account_id' => ['required', 'integer', 'different:destination_account_id', 'exists:company_accounts,id'],
            'destination_account_id' => ['required', 'integer', 'exists:company_accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:2000'],
            'transaction_date' => ['nullable', 'date'],
        ]);

        $sourceAccount = CompanyAccount::query()
            ->where('tenant_id', $tenantId)
            ->find($validated['source_account_id']);
        $destinationAccount = CompanyAccount::query()
            ->where('tenant_id', $tenantId)
            ->find($validated['destination_account_id']);

        abort_if(!$sourceAccount || !$destinationAccount, 422, 'Invalid company account selection.');

        $result = $financialTransactionService->transferBetweenCompanyAccounts(
            $sourceAccount,
            $destinationAccount,
            [
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'transaction_date' => $validated['transaction_date'] ?? null,
            ],
            $request->user()->id
        );

        return response()->json([
            'message' => 'Account transfer completed successfully.',
            'data' => [
                'debit_transaction_id' => $result['debit_transaction']->id,
                'credit_transaction_id' => $result['credit_transaction']->id,
                'source_account_id' => $result['source_account']->id,
                'destination_account_id' => $result['destination_account']->id,
                'source_current_balance' => (float) $result['source_account']->current_balance,
                'destination_current_balance' => (float) $result['destination_account']->current_balance,
            ],
        ], 201);
    }

    private function ensureTenant(CompanyAccount $companyAccount): void
    {
        if ($companyAccount->tenant_id !== app('tenant')->id) {
            abort(404);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyAccount;
use App\Models\CompanyAccountTransfer;
use App\Models\Expense;
use App\Services\CompanyAccountService;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyAccountApiController extends Controller
{
    public function __construct(
        private readonly CompanyAccountService $companyAccountService,
        private readonly ExpenseService $expenseService,
    ) {
    }

    public function meta(): JsonResponse
    {
        return response()->json($this->companyAccountService->meta(app('tenant')->id));
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 10), 50);

        return response()->json($this->companyAccountService->accounts(app('tenant')->id, $perPage));
    }

    public function show(CompanyAccount $account): JsonResponse
    {
        return response()->json([
            'data' => $this->companyAccountService->showAccount($account, app('tenant')->id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = app('tenant')->id;
        $validated = $request->validate($this->accountRules($tenantId));

        $account = $this->companyAccountService->storeAccount($tenantId, $validated);

        return response()->json([
            'message' => 'Account created successfully.',
            'data' => [
                'id' => $account->id,
                'name' => $account->name,
            ],
        ], 201);
    }

    public function update(Request $request, CompanyAccount $account): JsonResponse
    {
        $tenantId = app('tenant')->id;
        $validated = $request->validate($this->accountRules($tenantId, $account));

        $this->companyAccountService->updateAccount($account, $tenantId, $validated);

        return response()->json([
            'message' => 'Account updated successfully.',
        ]);
    }

    public function destroy(CompanyAccount $account): JsonResponse
    {
        $error = $this->companyAccountService->destroyAccount($account, app('tenant')->id);
        if ($error) {
            return response()->json([
                'message' => $error,
            ], 422);
        }

        return response()->json([
            'message' => 'Account deleted successfully.',
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 10), 50);

        return response()->json($this->companyAccountService->transactions(app('tenant')->id, $perPage));
    }

    public function transfers(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 10), 50);

        return response()->json($this->companyAccountService->transfers(app('tenant')->id, $perPage));
    }

    public function showTransfer(CompanyAccountTransfer $transfer): JsonResponse
    {
        return response()->json([
            'data' => $this->companyAccountService->showTransfer($transfer, app('tenant')->id),
        ]);
    }

    public function storeTransfer(Request $request): JsonResponse
    {
        $tenantId = app('tenant')->id;
        $validated = $request->validate($this->transferRules());

        $result = $this->companyAccountService->storeTransfer($tenantId, $validated);
        if (isset($result['error'])) {
            return response()->json([
                'message' => $result['error'],
            ], 422);
        }

        $transfer = $result['transfer'];

        return response()->json([
            'message' => 'Transfer created successfully.',
            'data' => [
                'id' => $transfer->id,
            ],
        ], 201);
    }

    public function updateTransfer(Request $request, CompanyAccountTransfer $transfer): JsonResponse
    {
        $validated = $request->validate($this->transferRules());

        $error = $this->companyAccountService->updateTransfer($transfer, app('tenant')->id, $validated);
        if ($error) {
            return response()->json([
                'message' => $error,
            ], 422);
        }

        return response()->json([
            'message' => 'Transfer updated successfully.',
        ]);
    }

    public function destroyTransfer(CompanyAccountTransfer $transfer): JsonResponse
    {
        $this->companyAccountService->destroyTransfer($transfer, app('tenant')->id);

        return response()->json([
            'message' => 'Transfer deleted successfully.',
        ]);
    }

    private function accountRules(int $tenantId, ?CompanyAccount $account = null): array
    {
        $nameRule = Rule::unique('company_accounts')
            ->where(fn ($query) => $query->where('tenant_id', $tenantId));

        if ($account) {
            $nameRule = $nameRule->ignore($account->id);
        }

        return [
            'name' => ['required', 'string', 'max:255', $nameRule],
            'opening_balance' => ['required', 'numeric'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function transferRules(): array
    {
        return [
            'source_account_id' => ['required', 'integer', 'different:destination_account_id', 'exists:company_accounts,id'],
            'destination_account_id' => ['required', 'integer', 'different:source_account_id', 'exists:company_accounts,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'transfer_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    // ─── Expenses ────────────────────────────────────────────────────────────

    public function expenses(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 10), 50);

        return response()->json($this->expenseService->expenses(app('tenant')->id, $perPage));
    }

    public function showExpense(Expense $expense): JsonResponse
    {
        return response()->json([
            'data' => $this->expenseService->showExpense($expense, app('tenant')->id),
        ]);
    }

    public function storeExpense(Request $request): JsonResponse
    {
        $validated = $request->validate($this->expenseRules());

        $expense = $this->expenseService->storeExpense(app('tenant')->id, $validated);

        return response()->json([
            'message' => 'Expense recorded successfully.',
            'data' => ['id' => $expense->id],
        ], 201);
    }

    public function updateExpense(Request $request, Expense $expense): JsonResponse
    {
        $validated = $request->validate($this->expenseRules());

        $this->expenseService->updateExpense($expense, app('tenant')->id, $validated);

        return response()->json([
            'message' => 'Expense updated successfully.',
        ]);
    }

    public function destroyExpense(Expense $expense): JsonResponse
    {
        $this->expenseService->destroyExpense($expense, app('tenant')->id);

        return response()->json([
            'message' => 'Expense deleted successfully.',
        ]);
    }

    private function expenseRules(): array
    {
        return [
            'company_account_id' => ['required', 'integer', 'exists:company_accounts,id'],
            'category' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'expense_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Wallet;
use App\Services\FinancialTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletApiController extends Controller
{
    public function showByMember(Request $request, Member $member, FinancialTransactionService $financialTransactionService): JsonResponse
    {
        if ($member->tenant_id !== app('tenant')->id) {
            abort(404);
        }

        $wallet = $financialTransactionService->ensureMemberWallet($member, $request->user()->id);
        $creditLimit = (float) app('tenant')->wallet_credit_limit;

        return response()->json([
            'wallet_id' => $wallet->id,
            'member_id' => $member->id,
            'current_balance' => (float) $wallet->current_balance,
            'status' => $wallet->status,
            'credit_limit' => $creditLimit,
            'available_spend' => (float) $wallet->current_balance + $creditLimit,
            'created_at' => optional($wallet->created_at)->toIso8601String(),
            'updated_at' => optional($wallet->updated_at)->toIso8601String(),
            'created_by' => $wallet->created_by,
            'updated_by' => $wallet->updated_by,
        ]);
    }

    public function topUp(Request $request, Member $member, FinancialTransactionService $financialTransactionService): JsonResponse
    {
        if ($member->tenant_id !== app('tenant')->id) {
            abort(404);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:2000'],
            'transaction_date' => ['nullable', 'date'],
        ]);

        $userId = $request->user()->id;
        $wallet = $financialTransactionService->ensureMemberWallet($member, $userId);

        $transaction = $financialTransactionService->recordWalletTransaction($wallet, [
            'amount' => $validated['amount'],
            'transaction_type' => 'credit',
            'description' => $validated['description'] ?? 'Wallet top-up',
            'status' => 'completed',
            'transaction_date' => $validated['transaction_date'] ?? null,
        ], $userId);

        $wallet = $wallet->fresh();

        return response()->json([
            'message' => 'Wallet top-up completed successfully.',
            'data' => [
                'transaction_id' => $transaction->id,
                'wallet_id' => $wallet->id,
                'current_balance' => (float) $wallet->current_balance,
            ],
        ], 201);
    }

    public function storeTransaction(
        Request $request,
        Member $member,
        FinancialTransactionService $financialTransactionService
    ): JsonResponse {
        if ($member->tenant_id !== app('tenant')->id) {
            abort(404);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_type' => ['required', 'in:credit,debit'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'in:pending,completed,cancelled'],
            'transaction_date' => ['nullable', 'date'],
        ]);

        $userId = $request->user()->id;
        $wallet = $financialTransactionService->ensureMemberWallet($member, $userId);

        $transaction = $financialTransactionService->recordWalletTransaction($wallet, [
            'amount' => $validated['amount'],
            'transaction_type' => $validated['transaction_type'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'completed',
            'transaction_date' => $validated['transaction_date'] ?? null,
        ], $userId);

        return response()->json([
            'message' => 'Wallet transaction recorded successfully.',
            'data' => [
                'transaction_id' => $transaction->id,
                'wallet_id' => $wallet->id,
                'current_balance' => (float) $wallet->fresh()->current_balance,
            ],
        ], 201);
    }

    public function updateStatus(Request $request, Wallet $wallet): JsonResponse
    {
        $this->ensureWalletTenant($wallet);

        $validated = $request->validate([
            'status' => ['required', 'in:active,suspended'],
        ]);

        $wallet->update([
            'status' => $validated['status'],
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Wallet status updated successfully.',
            'data' => [
                'wallet_id' => $wallet->id,
                'status' => $wallet->status,
            ],
        ]);
    }

    private function ensureWalletTenant(Wallet $wallet): void
    {
        if ($wallet->tenant_id !== app('tenant')->id) {
            abort(404);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant')->id;
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $transactions = Transaction::query()
            ->where('tenant_id', $tenantId)
            ->when($request->filled('transaction_reference_type'), function ($query) use ($request) {
                $query->where('transaction_reference_type', $request->query('transaction_reference_type'));
            })
            ->when($request->filled('reference_id'), function ($query) use ($request) {
                $query->where('reference_id', (int) $request->query('reference_id'));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->query('status'));
            })
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => collect($transactions->items())->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'transaction_reference_type' => $transaction->transaction_reference_type,
                'reference_id' => $transaction->reference_id,
                'amount' => (float) $transaction->amount,
                'transaction_type' => $transaction->transaction_type,
                'balance_before' => (float) $transaction->balance_before,
                'balance_after' => (float) $transaction->balance_after,
                'description' => $transaction->description,
                'status' => $transaction->status,
                'transaction_date' => optional($transaction->transaction_date)->toIso8601String(),
                'created_at' => optional($transaction->created_at)->toIso8601String(),
                'updated_at' => optional($transaction->updated_at)->toIso8601String(),
                'created_by' => $transaction->created_by,
                'updated_by' => $transaction->updated_by,
            ]),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        $this->ensureTenant($transaction);

        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'in:pending,completed,cancelled'],
            'transaction_date' => ['nullable', 'date'],
        ]);

        $targetStatus = $validated['status'] ?? $transaction->status;

        if (
            in_array($transaction->transaction_reference_type, [Transaction::REFERENCE_COMPANY_ACCOUNT, Transaction::REFERENCE_WALLET], true)
            && $targetStatus !== $transaction->status
        ) {
            abort(422, 'Status changes are not allowed for balance-affecting transactions.');
        }

        $transaction->update([
            'description' => $validated['description'] ?? $transaction->description,
            'status' => $targetStatus,
            'transaction_date' => $validated['transaction_date'] ?? $transaction->transaction_date,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Transaction updated successfully.',
            'data' => [
                'id' => $transaction->id,
                'status' => $transaction->status,
            ],
        ]);
    }

    private function ensureTenant(Transaction $transaction): void
    {
        if ($transaction->tenant_id !== app('tenant')->id) {
            abort(404);
        }
    }
}

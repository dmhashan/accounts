<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyAccount;
use App\Models\PaymentSettlement;
use App\Services\PaymentSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentSettlementApiController extends Controller
{
    public function __construct(
        private readonly PaymentSettlementService $paymentSettlementService,
    ) {}

    public function accountIndex(Request $request, CompanyAccount $account): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 50);
        $status = $request->string('status', 'pending')->toString();

        if (!in_array($status, ['pending', 'confirmed', 'cancelled', 'all'], true)) {
            $status = 'pending';
        }

        return response()->json(
            $this->paymentSettlementService->accountSettlements($account, app('tenant')->id, $status, $perPage),
        );
    }

    public function confirm(Request $request, PaymentSettlement $settlement): JsonResponse
    {
        $validated = $request->validate([
            'transaction_date' => ['nullable', 'date'],
            'confirmation_reference' => ['nullable', 'string', 'max:255'],
            'confirmation_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $settlement = $this->paymentSettlementService->confirm(
            $settlement,
            app('tenant')->id,
            $validated,
            $request->user()?->id,
        );

        return response()->json([
            'message' => 'Payment settlement confirmed successfully.',
            'data' => [
                'id' => $settlement->id,
                'status' => $settlement->status,
            ],
        ]);
    }
}

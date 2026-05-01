<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\WalletTopup;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletApiController extends Controller
{
    public function __construct(private readonly WalletService $walletService)
    {
    }

    public function meta(): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        return response()->json($this->walletService->meta($tenant->id));
    }

    public function showTopup(WalletTopup $topup): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        return response()->json($this->walletService->show($topup, $tenant->id));
    }

    public function topup(Request $request, Member $member): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        if ($member->tenant_id !== $tenant->id) {
            abort(404);
        }

        $validated = $request->validate([
            'company_account_id' => ['required', 'integer'],
            'amount'             => ['required', 'numeric', 'min:0.01'],
            'topup_date'         => ['required', 'date'],
            'reference_number'   => ['nullable', 'string', 'max:255'],
            'notes'              => ['nullable', 'string', 'max:1000'],
        ]);

        $topup = $this->walletService->topup($member, $tenant->id, $validated, $request->user()->id);

        $member->refresh();

        return response()->json([
            'topup'           => $topup,
            'current_balance' => round((float) $member->current_balance, 2),
        ], 201);
    }

    public function topupHistory(Request $request, Member $member): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        if ($member->tenant_id !== $tenant->id) {
            abort(404);
        }

        $perPage = min((int) $request->integer('per_page', 15), 50);

        return response()->json($this->walletService->topupHistory($member, $tenant->id, $perPage));
    }

    public function transactions(Request $request, Member $member): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        if ($member->tenant_id !== $tenant->id) {
            abort(404);
        }

        $perPage = min((int) $request->integer('per_page', 15), 50);

        return response()->json($this->walletService->transactions($member, $tenant->id, $perPage));
    }
}

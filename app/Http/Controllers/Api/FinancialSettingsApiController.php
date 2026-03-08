<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialSettingsApiController extends Controller
{
    public function show(): JsonResponse
    {
        $tenant = app('tenant');

        return response()->json([
            'wallet_credit_limit' => (float) $tenant->wallet_credit_limit,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'wallet_credit_limit' => ['required', 'numeric', 'min:0'],
        ]);

        $tenant->update([
            'wallet_credit_limit' => round((float) $validated['wallet_credit_limit'], 2),
        ]);

        return response()->json([
            'message' => 'Financial settings updated successfully.',
            'wallet_credit_limit' => (float) $tenant->fresh()->wallet_credit_limit,
        ]);
    }
}

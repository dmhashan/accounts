<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentPlan;
use App\Services\PaymentPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentPlanApiController extends Controller
{
    public function __construct(
        private readonly PaymentPlanService $planService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->planService->index(app('tenant')->id));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'duration_days' => 'required|integer|min:1|max:36500',
            'price' => 'required|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $plan = $this->planService->store(app('tenant')->id, $validated);

        return response()->json(['data' => $this->planService->serialize($plan)], 201);
    }

    public function update(Request $request, PaymentPlan $paymentPlan): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'duration_days' => 'required|integer|min:1|max:36500',
            'price' => 'required|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $this->planService->update($paymentPlan, app('tenant')->id, $validated);

        return response()->json(['data' => $this->planService->serialize($paymentPlan->fresh())]);
    }

    public function destroy(Request $request, PaymentPlan $paymentPlan): JsonResponse
    {
        $force = $request->boolean('force');
        $result = $this->planService->destroy($paymentPlan, app('tenant')->id, $force);

        if ($result['blocked']) {
            return response()->json([
                'message' => "This plan has {$result['member_count']} member(s) assigned and cannot be deleted.",
                'member_count' => $result['member_count'],
            ], 422);
        }

        return response()->json(null, 204);
    }
}

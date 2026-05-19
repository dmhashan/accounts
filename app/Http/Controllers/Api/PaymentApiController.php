<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberPayment;
use App\Services\MemberService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentApiController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly MemberService $memberService,
    ) {}

    public function meta(): JsonResponse
    {
        return response()->json($this->paymentService->meta(app('tenant')->id));
    }

    public function memberPayments(Request $request, Member $member): JsonResponse
    {
        $tenantId = app('tenant')->id;
        $this->memberService->ensureTenantMember($member, $tenantId);
        $perPage = min((int) $request->integer('per_page', 15), 50);

        return response()->json($this->paymentService->memberPayments($member->id, $tenantId, $perPage));
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 50);

        return response()->json($this->paymentService->payments(app('tenant')->id, $perPage));
    }

    public function show(MemberPayment $payment): JsonResponse
    {
        return response()->json([
            'data' => $this->paymentService->showPayment($payment, app('tenant')->id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $payment = $this->paymentService->storePayment(app('tenant')->id, $validated);

        return response()->json([
            'message' => 'Payment recorded successfully.',
            'data' => ['id' => $payment->id],
        ], 201);
    }

    public function update(Request $request, MemberPayment $payment): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $this->paymentService->updatePayment($payment, app('tenant')->id, $validated);

        return response()->json([
            'message' => 'Payment updated successfully.',
        ]);
    }

    public function destroy(MemberPayment $payment): JsonResponse
    {
        $this->paymentService->destroyPayment($payment, app('tenant')->id);

        return response()->json([
            'message' => 'Payment deleted successfully.',
        ]);
    }

    private function rules(): array
    {
        return [
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'company_account_id' => ['nullable', 'integer', 'exists:company_accounts,id'],
            'payment_plan_id' => ['nullable', 'integer', 'exists:payment_plans,id'],
            'payment_method' => ['nullable', 'string', 'in:cash,member_wallet'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_date' => ['required', 'date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

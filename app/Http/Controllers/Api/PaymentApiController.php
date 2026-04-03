<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberPayment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentApiController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    public function meta(): JsonResponse
    {
        return response()->json($this->paymentService->meta(app('tenant')->id));
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
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'company_account_id' => ['required', 'integer', 'exists:company_accounts,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Services\PaymentMethodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentMethodApiController extends Controller
{
    public function __construct(
        private readonly PaymentMethodService $paymentMethodService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 50);

        return response()->json($this->paymentMethodService->methods(app('tenant')->id, $perPage));
    }

    public function meta(): JsonResponse
    {
        return response()->json($this->paymentMethodService->meta(app('tenant')->id));
    }

    public function show(PaymentMethod $paymentMethod): JsonResponse
    {
        return response()->json([
            'data' => $this->paymentMethodService->show($paymentMethod, app('tenant')->id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());
        $method = $this->paymentMethodService->store(app('tenant')->id, $validated);

        return response()->json([
            'message' => 'Payment method created successfully.',
            'data' => [
                'id' => $method->id,
                'name' => $method->name,
            ],
        ], 201);
    }

    public function update(Request $request, PaymentMethod $paymentMethod): JsonResponse
    {
        $validated = $request->validate($this->rules($paymentMethod));

        $this->paymentMethodService->update($paymentMethod, app('tenant')->id, $validated);

        return response()->json([
            'message' => 'Payment method updated successfully.',
        ]);
    }

    public function destroy(PaymentMethod $paymentMethod): JsonResponse
    {
        $message = $this->paymentMethodService->destroy($paymentMethod, app('tenant')->id)
            ?: 'Payment method deleted successfully.';

        return response()->json([
            'message' => $message,
        ]);
    }

    private function rules(?PaymentMethod $paymentMethod = null): array
    {
        $nameRule = Rule::unique('payment_methods', 'name');

        if ($paymentMethod) {
            $nameRule = $nameRule->ignore($paymentMethod->id);
        }

        return [
            'name' => ['required', 'string', 'max:255', $nameRule],
            'company_account_id' => ['required', 'integer', 'exists:company_accounts,id'],
            'deduction_type' => ['required', 'string', Rule::in(['none', 'fixed', 'percentage'])],
            'deduction_value' => [
                Rule::requiredIf(fn () => in_array(request('deduction_type'), ['fixed', 'percentage'], true)),
                'nullable',
                'numeric',
                'min:0',
                Rule::when(request('deduction_type') === 'percentage', ['max:100']),
            ],
            'record_deduction_as_expense' => ['nullable', 'boolean'],
            'requires_reconciliation' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

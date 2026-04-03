<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\SaleMetaService;
use App\Services\SaleProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleApiController extends Controller
{
    public function __construct(
        private readonly SaleMetaService $saleMetaService,
        private readonly SaleProcessingService $saleProcessingService,
    ) {
    }

    public function memberWallet(Member $member): JsonResponse
    {
        $this->ensureMemberBelongsToTenant($member);

        return response()->json([
            'data' => [
                'member_id' => $member->id,
                'current_balance' => (float) $member->current_balance,
            ],
        ]);
    }

    public function meta(): JsonResponse
    {
        return response()->json($this->saleMetaService->build(app('tenant')->id));
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 15), 50);
        $status = $request->string('status')->toString();

        $sales = Sale::query()
            ->where('tenant_id', app('tenant')->id)
            ->with(['items.product', 'items.variation'])
            ->when($status === 'outstanding', fn ($query) => $query->where('is_paid', false))
            ->when($status === 'paid', fn ($query) => $query->where('is_paid', true))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => collect($sales->items())->map(fn (Sale $sale) => [
                'id' => $sale->id,
                'customer_name' => $sale->customer_name,
                'customer_type' => $sale->customer_type,
                'payment_method' => $sale->payment_method,
                'reference_number' => $sale->reference_number,
                'total_amount' => (float) $sale->total_amount,
                'paid_amount' => (float) $sale->paid_amount,
                'balance' => (float) $sale->balance,
                'is_paid' => (bool) $sale->is_paid,
                'account_id' => $sale->account_id,
                'created_at' => optional($sale->created_at)->format('d M Y, H:i'),
                'items' => $sale->items->map(fn (SaleItem $item) => [
                    'product_name' => $item->product?->name,
                    'variation_name' => $item->variation?->name,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                ]),
            ]),
            'meta' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->saleRules());
        $sale = $this->saleProcessingService->create(app('tenant')->id, $validated);

        return response()->json([
            'message' => 'Sale completed successfully.',
            'data' => [
                'id' => $sale->id,
            ],
        ], 201);
    }

    public function show(Sale $sale): JsonResponse
    {
        if ($sale->tenant_id !== app('tenant')->id) {
            abort(404);
        }

        $items = $sale->items()->get();

        return response()->json([
            'data' => [
                'id' => $sale->id,
                'customer_name' => $sale->customer_name,
                'customer_member_id' => $sale->customer_member_id,
                'customer_type' => $sale->customer_type,
                'payment_method' => $sale->payment_method,
                'reference_number' => $sale->reference_number,
                'total_amount' => (float) $sale->total_amount,
                'paid_amount' => (float) $sale->paid_amount,
                'balance' => (float) $sale->balance,
                'is_paid' => (bool) $sale->is_paid,
                'account_id' => $sale->account_id,
                'created_at' => optional($sale->created_at)->toDateString(),
                'items' => $items->map(function (SaleItem $item) {
                    return [
                        'id' => $item->id,
                        'product_variation_id' => $item->product_variation_id,
                        'quantity' => $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'subtotal' => (float) $item->subtotal,
                    ];
                }),
            ],
        ]);
    }

    public function update(Sale $sale, Request $request): JsonResponse
    {
        $this->ensureSaleBelongsToTenant($sale);

        $validated = $request->validate($this->saleRules());
        $sale = $this->saleProcessingService->update($sale, app('tenant')->id, $validated);

        return response()->json([
            'message' => 'Sale updated successfully.',
            'data' => [
                'id' => $sale->id,
            ],
        ]);
    }

    public function markAsPaid(Sale $sale, Request $request): JsonResponse
    {
        $this->ensureSaleBelongsToTenant($sale);

        $validated = $request->validate([
            'account_id' => ['required', 'integer', 'exists:company_accounts,id'],
        ]);

        $sale = $this->saleProcessingService->markAsPaid($sale, app('tenant')->id, $validated);

        return response()->json([
            'message' => 'Sale marked as paid successfully.',
            'data' => [
                'id' => $sale->id,
                'is_paid' => (bool) $sale->is_paid,
                'account_id' => $sale->account_id,
            ],
        ]);
    }

    public function destroy(Sale $sale): JsonResponse
    {
        $this->ensureSaleBelongsToTenant($sale);

        $this->saleProcessingService->delete($sale, app('tenant')->id);

        return response()->json([
            'message' => 'Sale deleted successfully.',
        ]);
    }

    private function saleRules(): array
    {
        return [
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_member_id' => ['nullable', 'exists:members,id'],
            'customer_type' => ['required', 'in:local,foreign'],
            'payment_method' => ['required', 'in:cash,bank,card,member_wallet'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'account_id' => ['nullable', 'integer', 'exists:company_accounts,id'],
            'is_paid' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variation_id' => ['required', 'exists:product_variations,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    private function ensureMemberBelongsToTenant(Member $member): void
    {
        if ($member->tenant_id !== app('tenant')->id) {
            abort(404);
        }
    }

    private function ensureSaleBelongsToTenant(Sale $sale): void
    {
        if ($sale->tenant_id !== app('tenant')->id) {
            abort(404);
        }
    }
}

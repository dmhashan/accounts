<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherApiController extends Controller
{
    public function __construct(private readonly VoucherService $voucherService)
    {
    }

    // ── Admin management ──────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $filters = $request->only(['status', 'search', 'per_page']);

        return response()->json($this->voucherService->index($tenant->id, $filters));
    }

    public function store(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'status'      => ['required', 'in:active,inactive'],
            'valid_from'  => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        $voucher = $this->voucherService->store($tenant->id, $validated, $request->user()->id);

        return response()->json(['voucher' => [
            'id'               => $voucher->id,
            'name'             => $voucher->name,
            'uuid'             => $voucher->uuid,
            'amount'           => (float) $voucher->amount,
            'status'           => $voucher->status,
            'valid_from'       => $voucher->valid_from?->toDateString(),
            'valid_until'      => $voucher->valid_until?->toDateString(),
            'created_by'       => $voucher->createdBy ? ['id' => $voucher->createdBy->id, 'name' => $voucher->createdBy->name] : null,
            'redemption_count' => 0,
            'created_at'       => $voucher->created_at?->toISOString(),
        ]], 201);
    }

    public function show(Voucher $voucher): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        if ($voucher->tenant_id !== $tenant->id) {
            abort(404);
        }

        $voucher->load(['createdBy:id,name', 'redemption.member:id,first_name,last_name,name,member_id', 'redemption.redeemedBy:id,name']);

        return response()->json([
            'id'               => $voucher->id,
            'name'             => $voucher->name,
            'uuid'             => $voucher->uuid,
            'amount'           => (float) $voucher->amount,
            'status'           => $voucher->status,
            'valid_from'       => $voucher->valid_from?->toDateString(),
            'valid_until'      => $voucher->valid_until?->toDateString(),
            'created_by'       => $voucher->createdBy ? ['id' => $voucher->createdBy->id, 'name' => $voucher->createdBy->name] : null,
            'created_at'       => $voucher->created_at?->toISOString(),
            'redemption'       => $voucher->redemption ? [
                'id'          => $voucher->redemption->id,
                'member'      => $voucher->redemption->member ? [
                    'id'        => $voucher->redemption->member->id,
                    'name'      => trim(($voucher->redemption->member->first_name ?? '') . ' ' . ($voucher->redemption->member->last_name ?? '')) ?: $voucher->redemption->member->name,
                    'member_id' => $voucher->redemption->member->member_id,
                ] : null,
                'notes'       => $voucher->redemption->notes,
                'redeemed_by' => $voucher->redemption->redeemedBy ? ['id' => $voucher->redemption->redeemedBy->id, 'name' => $voucher->redemption->redeemedBy->name] : null,
                'redeemed_at' => $voucher->redemption->created_at?->toISOString(),
            ] : null,
        ]);
    }

    public function update(Request $request, Voucher $voucher): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'status'      => ['required', 'in:active,inactive'],
            'valid_from'  => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        $voucher = $this->voucherService->update($voucher, $tenant->id, $validated);

        return response()->json(['voucher' => $voucher]);
    }

    public function destroy(Voucher $voucher): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $this->voucherService->destroy($voucher, $tenant->id);

        return response()->json(['message' => 'Voucher deleted.']);
    }

    // ── Member redemption ─────────────────────────────────────────────

    public function redeem(Request $request, Member $member): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        if ($member->tenant_id !== $tenant->id) {
            abort(404);
        }

        $validated = $request->validate([
            'uuid'  => ['required', 'string', 'max:36'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $redemption = $this->voucherService->redeem(
            $member,
            $tenant->id,
            trim($validated['uuid']),
            $validated['notes'] ?? null,
            $request->user()->id,
        );

        $member->refresh();

        return response()->json([
            'redemption'      => $redemption,
            'current_balance' => round((float) $member->current_balance, 2),
        ], 201);
    }

    public function redemptionHistory(Request $request, Member $member): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        if ($member->tenant_id !== $tenant->id) {
            abort(404);
        }

        $perPage = min($request->integer('per_page', 15), 50);

        return response()->json($this->voucherService->redemptionHistory($member, $tenant->id, $perPage));
    }
}

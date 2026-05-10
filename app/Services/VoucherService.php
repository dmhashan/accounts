<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Voucher;
use App\Models\VoucherRedemption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoucherService
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    // ── Admin management ──────────────────────────────────────────────

    public function index(int $tenantId, array $filters = []): array
    {
        $query = Voucher::query()
            ->where('tenant_id', $tenantId)
            ->with('createdBy:id,name')
            ->withExists('redemption as is_redeemed_flag');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('uuid', 'like', $term);
            });
        }

        $perPage = min((int) ($filters['per_page'] ?? 20), 100);
        $paginated = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return [
            'data' => collect($paginated->items())->map(fn (Voucher $v) => $this->serialize($v)),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ];
    }

    public function store(int $tenantId, array $validated, int $createdBy): Voucher
    {
        $voucher = Voucher::create([
            'tenant_id'  => $tenantId,
            'name'       => trim($validated['name']),
            'uuid'       => Str::uuid()->toString(),
            'amount'     => (float) $validated['amount'],
            'status'     => $validated['status'] ?? 'active',
            'valid_from' => $validated['valid_from'] ?? null,
            'valid_until' => $validated['valid_until'] ?? null,
            'created_by' => $createdBy,
        ]);

        $this->auditService->log($tenantId, 'voucher_create', $voucher, [], $this->serialize($voucher));

        return $voucher->load('createdBy:id,name');
    }

    public function update(Voucher $voucher, int $tenantId, array $validated): Voucher
    {
        if ($voucher->tenant_id !== $tenantId) {
            abort(404);
        }

        if ($voucher->isRedeemed()) {
            abort(422, 'Cannot edit a redeemed voucher.');
        }

        $before = $this->serialize($voucher);

        $voucher->update([
            'name'       => trim($validated['name']),
            'amount'     => (float) $validated['amount'],
            'status'     => $validated['status'],
            'valid_from' => $validated['valid_from'] ?? null,
            'valid_until' => $validated['valid_until'] ?? null,
        ]);

        $this->auditService->log($tenantId, 'voucher_update', $voucher, $before, $this->serialize($voucher));

        return $voucher->load('createdBy:id,name');
    }

    public function destroy(Voucher $voucher, int $tenantId): void
    {
        if ($voucher->tenant_id !== $tenantId) {
            abort(404);
        }

        if ($voucher->isRedeemed()) {
            abort(422, 'Cannot delete a redeemed voucher.');
        }

        $this->auditService->log($tenantId, 'voucher_delete', $voucher, $this->serialize($voucher), []);
        $voucher->delete();
    }

    // ── Member redemption ─────────────────────────────────────────────

    public function redeem(Member $member, int $tenantId, string $uuid, ?string $notes, int $redeemedBy): VoucherRedemption
    {
        return DB::transaction(function () use ($member, $tenantId, $uuid, $notes, $redeemedBy) {
            /** @var Voucher|null $voucher */
            $voucher = Voucher::query()
                ->where('tenant_id', $tenantId)
                ->where('uuid', $uuid)
                ->lockForUpdate()
                ->first();

            if (!$voucher) {
                abort(422, 'Voucher not found. Please check the voucher code and try again.');
            }

            if ($voucher->isRedeemed()) {
                abort(422, 'This voucher has already been redeemed.');
            }

            if ($voucher->status === 'inactive') {
                abort(422, 'This voucher is not active.');
            }

            $today = now()->startOfDay();

            if ($voucher->valid_from && $today->lt($voucher->valid_from->startOfDay())) {
                abort(422, 'This voucher is not yet valid. Valid from ' . $voucher->valid_from->toFormattedDateString() . '.');
            }

            if ($voucher->valid_until && $today->gt($voucher->valid_until->endOfDay())) {
                abort(422, 'This voucher has expired (valid until ' . $voucher->valid_until->toFormattedDateString() . ').');
            }

            $lockedMember = Member::query()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->find($member->id);

            if (!$lockedMember) {
                abort(404);
            }

            $redemption = VoucherRedemption::create([
                'tenant_id'   => $tenantId,
                'voucher_id'  => $voucher->id,
                'member_id'   => $lockedMember->id,
                'redeemed_by' => $redeemedBy,
                'notes'       => filled($notes) ? trim($notes) : null,
            ]);

            $voucher->update(['status' => 'redeemed']);

            $lockedMember->update([
                'current_balance' => (float) $lockedMember->current_balance + (float) $voucher->amount,
            ]);

            $this->auditService->log($tenantId, 'voucher_redeem', $redemption, [], [
                'voucher_id'  => $voucher->id,
                'voucher_uuid' => $voucher->uuid,
                'amount'      => (float) $voucher->amount,
                'member_id'   => $lockedMember->id,
            ]);

            return $redemption->load(['voucher', 'member:id,first_name,last_name,name,member_id', 'redeemedBy:id,name']);
        });
    }

    public function redemptionHistory(Member $member, int $tenantId, int $perPage): array
    {
        $paginated = VoucherRedemption::query()
            ->where('tenant_id', $tenantId)
            ->where('member_id', $member->id)
            ->with(['voucher:id,name,uuid,amount', 'redeemedBy:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'data' => collect($paginated->items())->map(fn (VoucherRedemption $r) => $this->serializeRedemption($r)),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ];
    }

    // ── Serializers ───────────────────────────────────────────────────

    private function serialize(Voucher $voucher): array
    {
        return [
            'id'          => $voucher->id,
            'name'        => $voucher->name,
            'uuid'        => $voucher->uuid,
            'amount'      => (float) $voucher->amount,
            'status'      => $voucher->status,
            'valid_from'  => $voucher->valid_from?->toDateString(),
            'valid_until' => $voucher->valid_until?->toDateString(),
            'created_by'  => $voucher->createdBy ? ['id' => $voucher->createdBy->id, 'name' => $voucher->createdBy->name] : null,
            'created_at'  => $voucher->created_at?->toISOString(),
        ];
    }

    private function serializeRedemption(VoucherRedemption $redemption): array
    {
        return [
            'id'          => $redemption->id,
            'voucher'     => $redemption->voucher ? [
                'id'     => $redemption->voucher->id,
                'name'   => $redemption->voucher->name,
                'uuid'   => $redemption->voucher->uuid,
                'amount' => (float) $redemption->voucher->amount,
            ] : null,
            'notes'       => $redemption->notes,
            'redeemed_by' => $redemption->redeemedBy ? ['id' => $redemption->redeemedBy->id, 'name' => $redemption->redeemedBy->name] : null,
            'redeemed_at' => $redemption->created_at?->toISOString(),
        ];
    }
}

<?php

namespace App\Services;

use App\Models\PaymentPlan;

class PaymentPlanService
{
    public function index(int $tenantId): array
    {
        $plans = PaymentPlan::where('tenant_id', $tenantId)
            ->withCount('members')
            ->orderByRaw(PaymentPlan::durationDaysOrderRaw())
            ->orderBy('name')
            ->get();

        return [
            'data' => $plans->map(fn (PaymentPlan $p) => $this->serialize($p))->values(),
        ];
    }

    public function store(int $tenantId, array $validated): PaymentPlan
    {
        return PaymentPlan::create([
            'tenant_id' => $tenantId,
            'name' => trim($validated['name']),
            'duration_value' => (int) $validated['duration_value'],
            'duration_unit' => $validated['duration_unit'],
            'price' => $validated['price'],
            'is_active' => $validated['is_active'] ?? true,
        ]);
    }

    public function update(PaymentPlan $plan, int $tenantId, array $validated): void
    {
        $this->ensureTenant($plan, $tenantId);

        $plan->update([
            'name' => trim($validated['name']),
            'duration_value' => (int) $validated['duration_value'],
            'duration_unit' => $validated['duration_unit'],
            'price' => $validated['price'],
            'is_active' => $validated['is_active'] ?? $plan->is_active,
        ]);
    }

    public function destroy(PaymentPlan $plan, int $tenantId, bool $force = false): array
    {
        $this->ensureTenant($plan, $tenantId);

        $memberCount = $plan->members()->count();

        if ($memberCount > 0 && !$force) {
            return ['blocked' => true, 'member_count' => $memberCount];
        }

        if ($memberCount > 0) {
            // Archive (soft delete) — preserves member assignments and payment history
            $plan->delete();
        } else {
            // No members — permanently remove
            $plan->forceDelete();
        }

        return ['blocked' => false];
    }

    public function serialize(PaymentPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'duration_value' => (int) $plan->duration_value,
            'duration_unit' => (string) $plan->duration_unit,
            'duration_days' => $plan->approximateDays(),
            'price' => (float) $plan->price,
            'is_active' => (bool) $plan->is_active,
            'member_count' => (int) ($plan->members_count ?? 0),
            'created_at' => $plan->created_at?->toDateString(),
        ];
    }

    private function ensureTenant(PaymentPlan $plan, int $tenantId): void
    {
        if ($plan->tenant_id !== $tenantId) {
            abort(404);
        }
    }
}

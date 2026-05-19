<?php

namespace App\Services;

use App\Models\PaymentPlan;

class PaymentPlanService
{
    public function index(int $tenantId): array
    {
        $plans = PaymentPlan::where('tenant_id', $tenantId)
            ->orderBy('duration_days')
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
            'duration_days' => (int) $validated['duration_days'],
            'price' => $validated['price'],
            'is_active' => $validated['is_active'] ?? true,
        ]);
    }

    public function update(PaymentPlan $plan, int $tenantId, array $validated): void
    {
        $this->ensureTenant($plan, $tenantId);

        $plan->update([
            'name' => trim($validated['name']),
            'duration_days' => (int) $validated['duration_days'],
            'price' => $validated['price'],
            'is_active' => $validated['is_active'] ?? $plan->is_active,
        ]);
    }

    public function destroy(PaymentPlan $plan, int $tenantId): void
    {
        $this->ensureTenant($plan, $tenantId);
        $plan->delete();
    }

    public function serialize(PaymentPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'duration_days' => $plan->duration_days,
            'price' => (float) $plan->price,
            'is_active' => (bool) $plan->is_active,
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

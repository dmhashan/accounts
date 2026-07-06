<?php

namespace App\Services;

use App\Models\CompanyAccount;
use App\Models\MemberPayment;
use App\Models\PaymentMethod;
use App\Models\PaymentSettlement;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class PaymentMethodService
{
    public function methods(int $tenantId, int $perPage): array
    {
        $methods = PaymentMethod::query()
            ->with('account:id,name')
            ->orderByDesc('is_active')
            ->orderBy('order')
            ->orderBy('name')
            ->paginate($perPage);

        return [
            'data' => collect($methods->items())->map(fn (PaymentMethod $method) => $this->serialize($method)),
            'meta' => [
                'current_page' => $methods->currentPage(),
                'last_page' => $methods->lastPage(),
                'per_page' => $methods->perPage(),
                'total' => $methods->total(),
            ],
        ];
    }

    public function meta(int $tenantId): array
    {
        return [
            'accounts' => CompanyAccount::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (CompanyAccount $account) => [
                    'id' => $account->id,
                    'name' => $account->name,
                ])
                ->values(),
            'payment_methods' => $this->activeMethods($tenantId),
        ];
    }

    public function activeMethods(int $tenantId): array
    {
        return PaymentMethod::query()
            ->with('account:id,name')
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('name')
            ->get()
            ->map(fn (PaymentMethod $method) => $this->serialize($method))
            ->values()
            ->all();
    }

    public function show(PaymentMethod $method, int $tenantId): array
    {
        $method->load('account:id,name');

        return $this->serialize($method);
    }

    public function store(int $tenantId, array $validated): PaymentMethod
    {
        $this->ensureAccountExists((int) $validated['company_account_id']);
        $payload = $this->payload($validated);

        return PaymentMethod::create($payload);
    }

    public function update(PaymentMethod $method, int $tenantId, array $validated): void
    {
        $this->ensureAccountExists((int) $validated['company_account_id']);
        $method->update($this->payload($validated));
    }

    public function destroy(PaymentMethod $method, int $tenantId): ?string
    {
        $inUse = PaymentSettlement::query()->where('payment_method_id', $method->id)->exists()
            || MemberPayment::query()->where('payment_method_id', $method->id)->exists()
            || Sale::query()->where('payment_method_id', $method->id)->exists();

        if ($inUse) {
            $method->update(['is_active' => false]);

            return 'Payment method is already used, so it was archived instead.';
        }

        $method->delete();

        return null;
    }

    public function resolveFromPayload(array $validated, int $tenantId): PaymentMethod
    {
        if (!empty($validated['payment_method_id'])) {
            $method = PaymentMethod::query()
                ->with('account:id,name')
                ->find((int) $validated['payment_method_id']);

            if (!$method) {
                abort(422, 'Invalid payment method selection.');
            }

            if (!$method->is_active) {
                abort(422, 'Selected payment method is inactive.');
            }

            return $method;
        }

        if (!empty($validated['company_account_id'])) {
            return $this->resolveLegacyAccountMethod((int) $validated['company_account_id']);
        }

        if (!empty($validated['account_id'])) {
            return $this->resolveLegacyAccountMethod((int) $validated['account_id']);
        }

        abort(422, 'Please select a payment method.');
    }

    public function resolveLegacyAccountMethod(int $accountId): PaymentMethod
    {
        $account = CompanyAccount::query()->find($accountId);

        if (!$account) {
            abort(422, 'Invalid account selection.');
        }

        $method = PaymentMethod::query()
            ->where('company_account_id', $account->id)
            ->where('deduction_type', PaymentMethod::DEDUCTION_NONE)
            ->where('requires_reconciliation', false)
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->first();

        if ($method) {
            return $method->load('account:id,name');
        }

        return DB::transaction(function () use ($account) {
            $name = $this->uniqueName($account->name);

            return PaymentMethod::create([
                'company_account_id' => $account->id,
                'name' => $name,
                'deduction_type' => PaymentMethod::DEDUCTION_NONE,
                'deduction_value' => null,
                'record_deduction_as_expense' => true,
                'requires_reconciliation' => false,
                'is_active' => true,
            ])->load('account:id,name');
        });
    }

    public function serialize(PaymentMethod $method): array
    {
        $deductionType = $method->deduction_type ?: PaymentMethod::DEDUCTION_NONE;
        $deductionValue = $method->deduction_value !== null ? (float) $method->deduction_value : null;

        return [
            'id' => $method->id,
            'name' => $method->name,
            'label' => $method->name,
            'company_account_id' => $method->company_account_id,
            'account_name' => $method->account?->name,
            'deduction_type' => $deductionType,
            'deduction_value' => $deductionValue,
            'record_deduction_as_expense' => (bool) $method->record_deduction_as_expense,
            'requires_reconciliation' => (bool) $method->requires_reconciliation,
            'is_active' => (bool) $method->is_active,
            'color' => $method->color ?? 'slate',
            'icon' => $method->icon ?? 'CreditCard',
            'order' => (int) ($method->order ?? 0),
            'created_at' => optional($method->created_at)->format('Y-m-d H:i'),
        ];
    }

    private function payload(array $validated): array
    {
        $deductionType = $validated['deduction_type'] ?? PaymentMethod::DEDUCTION_NONE;
        $deductionValue = $deductionType === PaymentMethod::DEDUCTION_NONE
            ? null
            : (float) $validated['deduction_value'];

        return [
            'company_account_id' => (int) $validated['company_account_id'],
            'name' => trim((string) $validated['name']),
            'deduction_type' => $deductionType,
            'deduction_value' => $deductionValue,
            'record_deduction_as_expense' => (bool) ($validated['record_deduction_as_expense'] ?? false),
            'requires_reconciliation' => (bool) ($validated['requires_reconciliation'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'color' => $validated['color'] ?? 'slate',
            'icon' => $validated['icon'] ?? 'CreditCard',
            'order' => (int) ($validated['order'] ?? 0),
        ];
    }

    private function ensureAccountExists(int $accountId): void
    {
        $exists = CompanyAccount::query()->where('id', $accountId)->exists();

        if (!$exists) {
            abort(422, 'Invalid account selection.');
        }
    }

    private function uniqueName(string $baseName): string
    {
        $name = trim($baseName) !== '' ? trim($baseName) : 'Payment Method';

        if (!PaymentMethod::where('name', $name)->exists()) {
            return $name;
        }

        $suffix = 2;

        while (PaymentMethod::where('name', $name . ' ' . $suffix)->exists()) {
            $suffix++;
        }

        return $name . ' ' . $suffix;
    }
}

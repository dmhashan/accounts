<?php

namespace App\Services;

use App\Models\CompanyAccount;
use App\Models\CompanyAccountTransaction;
use App\Models\MemberPayment;
use App\Models\PaymentMethod;
use App\Models\PaymentSettlement;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentSettlementService
{
    public function syncForPayment(MemberPayment $payment, PaymentMethod $method): PaymentSettlement
    {
        $member = $payment->member;
        $memberName = $member
            ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) ?: ($member->name ?: 'Member')
            : 'Member';

        return $this->syncForSource(
            sourceType: 'payment',
            sourceId: (int) $payment->id,
            method: $method,
            grossAmount: (float) $payment->amount,
            paymentDate: $payment->payment_date?->toDateString() ?? Carbon::today()->toDateString(),
            referenceNumber: $payment->reference_number,
            notes: filled($payment->notes) ? $payment->notes : 'Payment: ' . $memberName,
            mainTransactionType: 'payment',
        );
    }

    public function syncForSale(Sale $sale, PaymentMethod $method, ?string $transactionDate = null): PaymentSettlement
    {
        return $this->syncForSource(
            sourceType: 'sale',
            sourceId: (int) $sale->id,
            method: $method,
            grossAmount: (float) $sale->total_amount,
            paymentDate: $transactionDate ?? optional($sale->created_at)->toDateString() ?? Carbon::today()->toDateString(),
            referenceNumber: $sale->reference_number,
            notes: 'Sale payment for sale #' . $sale->id,
            mainTransactionType: 'sale_payment',
        );
    }

    public function deleteForSource(string $sourceType, int $sourceId): void
    {
        $settlement = PaymentSettlement::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();

        CompanyAccountTransaction::where('model_name', $sourceType)
            ->where('reference_id', $sourceId)
            ->delete();

        if ($settlement) {
            CompanyAccountTransaction::where('model_name', 'payment_deduction')
                ->where('reference_id', $settlement->id)
                ->delete();

            $settlement->delete();
        }
    }

    public function accountSettlements(CompanyAccount $account, int $tenantId, string $status, int $perPage): array
    {
        $query = PaymentSettlement::query()
            ->with(['paymentMethod:id,name', 'account:id,name'])
            ->where('company_account_id', $account->id)
            ->orderBy('payment_date')
            ->orderBy('id');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $settlements = $query->paginate($perPage);
        $items = collect($settlements->items());
        $sourceDetails = $this->sourceDetails($items);

        return [
            'data' => $items->map(fn (PaymentSettlement $settlement) => $this->serialize($settlement, $sourceDetails)),
            'meta' => [
                'current_page' => $settlements->currentPage(),
                'last_page' => $settlements->lastPage(),
                'per_page' => $settlements->perPage(),
                'total' => $settlements->total(),
            ],
        ];
    }

    public function confirm(PaymentSettlement $settlement, int $tenantId, array $validated, ?int $userId): PaymentSettlement
    {
        return DB::transaction(function () use ($settlement, $validated, $userId) {
            $locked = PaymentSettlement::query()
                ->lockForUpdate()
                ->find($settlement->id);

            if (!$locked) {
                abort(404);
            }

            if ($locked->status === PaymentSettlement::STATUS_CONFIRMED) {
                abort(422, 'Settlement is already confirmed.');
            }

            if ($locked->status === PaymentSettlement::STATUS_CANCELLED) {
                abort(422, 'Cancelled settlements cannot be confirmed.');
            }

            $locked->update([
                'status' => PaymentSettlement::STATUS_CONFIRMED,
                'confirmed_transaction_date' => $validated['transaction_date'] ?? Carbon::today()->toDateString(),
                'confirmed_at' => now(),
                'confirmed_by' => $userId,
                'confirmation_reference' => filled($validated['confirmation_reference'] ?? null)
                    ? trim((string) $validated['confirmation_reference'])
                    : $locked->confirmation_reference,
                'confirmation_notes' => filled($validated['confirmation_notes'] ?? null)
                    ? trim((string) $validated['confirmation_notes'])
                    : $locked->confirmation_notes,
            ]);

            $this->createAccountTransactions($locked);

            return $locked->fresh(['paymentMethod:id,name', 'account:id,name']);
        });
    }

    private function syncForSource(
        string $sourceType,
        int $sourceId,
        PaymentMethod $method,
        float $grossAmount,
        string $paymentDate,
        ?string $referenceNumber,
        ?string $notes,
        string $mainTransactionType,
    ): PaymentSettlement {
        return DB::transaction(function () use ($sourceType, $sourceId, $method, $grossAmount, $paymentDate, $referenceNumber, $notes, $mainTransactionType) {
            $deductionAmount = $this->deductionAmount($method, $grossAmount);
            $netAmount = round($grossAmount - $deductionAmount, 2);

            if ($netAmount < 0) {
                abort(422, 'Payment method deduction cannot exceed the payment amount.');
            }

            $existing = PaymentSettlement::query()
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->lockForUpdate()
                ->first();

            $shouldPostNow = !$method->requires_reconciliation;

            $settlement = PaymentSettlement::updateOrCreate(
                [
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                ],
                [
                    'payment_method_id' => $method->id,
                    'company_account_id' => $method->company_account_id,
                    'payment_method_name' => $method->name,
                    'gross_amount' => $grossAmount,
                    'deduction_amount' => $deductionAmount,
                    'net_amount' => $netAmount,
                    'record_deduction_as_expense' => (bool) $method->record_deduction_as_expense,
                    'status' => $shouldPostNow ? PaymentSettlement::STATUS_CONFIRMED : PaymentSettlement::STATUS_PENDING,
                    'payment_date' => $paymentDate,
                    'confirmed_transaction_date' => $shouldPostNow
                        ? ($existing?->confirmed_transaction_date?->toDateString() ?? $paymentDate)
                        : null,
                    'confirmed_at' => $shouldPostNow ? ($existing?->confirmed_at ?? now()) : null,
                    'confirmed_by' => $shouldPostNow ? $existing?->confirmed_by : null,
                    'reference_number' => filled($referenceNumber) ? trim((string) $referenceNumber) : null,
                    'notes' => filled($notes) ? trim((string) $notes) : null,
                ],
            );

            $settlement->setAttribute('main_transaction_type', $mainTransactionType);

            if ($shouldPostNow) {
                $this->createAccountTransactions($settlement, $mainTransactionType);
            } else {
                $this->deleteAccountTransactions($settlement);
            }

            return $settlement;
        });
    }

    private function createAccountTransactions(PaymentSettlement $settlement, ?string $mainTransactionType = null): void
    {
        $mainTransactionType ??= $settlement->source_type === 'sale' ? 'sale_payment' : 'payment';
        $transactionDate = $settlement->confirmed_transaction_date?->toDateString()
            ?? $settlement->payment_date->toDateString();
        $deductionAsExpense = (bool) $settlement->record_deduction_as_expense
            && (float) $settlement->deduction_amount > 0;
        $mainAmount = $deductionAsExpense
            ? (float) $settlement->gross_amount
            : (float) $settlement->net_amount;

        CompanyAccountTransaction::updateOrCreate(
            [
                'model_name' => $settlement->source_type,
                'reference_id' => $settlement->source_id,
            ],
            [
                'company_account_id' => $settlement->company_account_id,
                'type' => $mainTransactionType,
                'amount' => $mainAmount,
                'transaction_date' => $transactionDate,
                'reference_number' => $settlement->confirmation_reference ?: $settlement->reference_number,
                'notes' => $settlement->notes,
            ],
        );

        CompanyAccountTransaction::where('model_name', 'payment_deduction')
            ->where('reference_id', $settlement->id)
            ->delete();

        if ($deductionAsExpense) {
            CompanyAccountTransaction::create([
                'company_account_id' => $settlement->company_account_id,
                'model_name' => 'payment_deduction',
                'reference_id' => $settlement->id,
                'type' => 'payment_deduction',
                'amount' => -((float) $settlement->deduction_amount),
                'transaction_date' => $transactionDate,
                'reference_number' => $settlement->confirmation_reference ?: $settlement->reference_number,
                'notes' => 'Payment method deduction: ' . $settlement->payment_method_name,
            ]);
        }
    }

    private function deleteAccountTransactions(PaymentSettlement $settlement): void
    {
        CompanyAccountTransaction::where('model_name', $settlement->source_type)
            ->where('reference_id', $settlement->source_id)
            ->delete();

        CompanyAccountTransaction::where('model_name', 'payment_deduction')
            ->where('reference_id', $settlement->id)
            ->delete();
    }

    private function deductionAmount(PaymentMethod $method, float $grossAmount): float
    {
        $type = $method->deduction_type ?: PaymentMethod::DEDUCTION_NONE;
        $value = (float) ($method->deduction_value ?? 0);

        if ($type === PaymentMethod::DEDUCTION_FIXED) {
            return round($value, 2);
        }

        if ($type === PaymentMethod::DEDUCTION_PERCENTAGE) {
            return round($grossAmount * $value / 100, 2);
        }

        return 0.0;
    }

    private function sourceDetails($settlements): array
    {
        $saleIds = $settlements->where('source_type', 'sale')->pluck('source_id')->unique()->values();
        $paymentIds = $settlements->where('source_type', 'payment')->pluck('source_id')->unique()->values();

        $sales = $saleIds->isNotEmpty()
            ? Sale::whereIn('id', $saleIds)->get(['id', 'customer_name', 'reference_number'])->keyBy('id')
            : collect();
        $payments = $paymentIds->isNotEmpty()
            ? MemberPayment::whereIn('id', $paymentIds)
                ->with('member:id,first_name,last_name,name')
                ->get(['id', 'member_id', 'reference_number'])
                ->keyBy('id')
            : collect();

        return [
            'sales' => $sales,
            'payments' => $payments,
        ];
    }

    private function serialize(PaymentSettlement $settlement, array $sourceDetails): array
    {
        $sourceLabel = ucfirst($settlement->source_type) . ' #' . $settlement->source_id;
        $sourcePath = null;
        $customer = null;

        if ($settlement->source_type === 'sale') {
            $sale = $sourceDetails['sales']->get($settlement->source_id);
            $sourceLabel = 'Sale #' . $settlement->source_id;
            $sourcePath = '/sales/' . $settlement->source_id;
            $customer = $sale?->customer_name;
        } elseif ($settlement->source_type === 'payment') {
            $payment = $sourceDetails['payments']->get($settlement->source_id);
            $sourceLabel = 'Payment #' . $settlement->source_id;
            $sourcePath = '/accounting/payments/' . $settlement->source_id;

            if ($payment?->member) {
                $m = $payment->member;
                $customer = trim(($m->first_name ?? '') . ' ' . ($m->last_name ?? '')) ?: ($m->name ?? null);
            }
        }

        return [
            'id' => $settlement->id,
            'payment_method_id' => $settlement->payment_method_id,
            'payment_method_name' => $settlement->payment_method_name,
            'company_account_id' => $settlement->company_account_id,
            'account_name' => $settlement->account?->name,
            'source_type' => $settlement->source_type,
            'source_id' => $settlement->source_id,
            'source_label' => $sourceLabel,
            'source_path' => $sourcePath,
            'customer' => $customer,
            'gross_amount' => round((float) $settlement->gross_amount, 2),
            'deduction_amount' => round((float) $settlement->deduction_amount, 2),
            'net_amount' => round((float) $settlement->net_amount, 2),
            'record_deduction_as_expense' => (bool) $settlement->record_deduction_as_expense,
            'status' => $settlement->status,
            'payment_date' => $settlement->payment_date?->toDateString(),
            'confirmed_transaction_date' => $settlement->confirmed_transaction_date?->toDateString(),
            'confirmed_at' => optional($settlement->confirmed_at)->format('Y-m-d H:i'),
            'reference_number' => $settlement->reference_number,
            'confirmation_reference' => $settlement->confirmation_reference,
            'notes' => $settlement->notes,
            'confirmation_notes' => $settlement->confirmation_notes,
        ];
    }
}

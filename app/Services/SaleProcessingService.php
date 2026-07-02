<?php

namespace App\Services;

use App\Jobs\SendMemberNotificationJob;
use App\Models\Member;
use App\Models\PaymentMethod;
use App\Models\ProductVariation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SaleProcessingService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly MemberPortalUrlService $memberPortalUrl,
        private readonly PaymentMethodService $paymentMethods,
        private readonly PaymentSettlementService $settlements,
    ) {}

    public function create(int $tenantId, array $validated): Sale
    {
        $today = Carbon::today()->toDateString();

        $sale = DB::transaction(function () use ($tenantId, $validated, $today) {
            [$saleItems, $total, $itemsPayload] = $this->buildSaleItemsAndTotals($validated, $tenantId, $today);
            $member = $this->resolveMember($validated, $tenantId);
            [$paidAmount, $balance, $isPaid, $accountId, $paymentMethod] = $this->resolvePayment($validated, $member, $total, $tenantId);

            $sale = Sale::create([
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_member_id' => $member?->id,
                'account_id' => $accountId,
                'payment_method_id' => $paymentMethod?->id,
                'customer_type' => $validated['customer_type'],
                'payment_method' => $paymentMethod?->name ?? ($validated['payment_method'] ?? 'cash'),
                'reference_number' => $validated['reference_number'] ?? null,
                'total_amount' => $total,
                'paid_amount' => $paidAmount,
                'balance' => $balance,
                'is_paid' => $isPaid,
            ]);

            $this->replaceSaleItems($sale, $saleItems);
            $this->deductStock($itemsPayload, $tenantId, $today);
            $this->recordAccountTransactionForSale($sale, $tenantId, null, $paymentMethod);

            return $sale;
        });

        if ($sale->is_paid) {
            $this->sendSalePaidNotification($sale);
        } else {
            $this->sendSaleOutstandingNotification($sale);
        }

        return $sale;
    }

    public function update(Sale $sale, int $tenantId, array $validated): Sale
    {
        $this->ensureSaleIsMutable($sale);

        $today = Carbon::today()->toDateString();

        return DB::transaction(function () use ($sale, $tenantId, $validated, $today) {
            $this->restoreStockForExistingItems($sale, $tenantId, $today);
            $this->refundWalletIfNeeded($sale, $tenantId);

            [$saleItems, $total, $itemsPayload] = $this->buildSaleItemsAndTotals($validated, $tenantId, $today);
            $member = $this->resolveMember($validated, $tenantId);
            [$paidAmount, $balance, $isPaid, $accountId, $paymentMethod] = $this->resolvePayment($validated, $member, $total, $tenantId);

            $sale->update([
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_member_id' => $member?->id,
                'account_id' => $accountId,
                'payment_method_id' => $paymentMethod?->id,
                'customer_type' => $validated['customer_type'],
                'payment_method' => $paymentMethod?->name ?? ($validated['payment_method'] ?? 'cash'),
                'reference_number' => $validated['reference_number'] ?? null,
                'total_amount' => $total,
                'paid_amount' => $paidAmount,
                'balance' => $balance,
                'is_paid' => $isPaid,
            ]);

            $this->replaceSaleItems($sale, $saleItems);
            $this->deductStock($itemsPayload, $tenantId, $today);

            return $sale;
        });
    }

    public function delete(Sale $sale, int $tenantId): void
    {
        $this->ensureSaleIsMutable($sale);

        $today = Carbon::today()->toDateString();

        DB::transaction(function () use ($sale, $tenantId, $today) {
            $this->restoreStockForExistingItems($sale, $tenantId, $today);
            $this->refundWalletIfNeeded($sale, $tenantId);

            $sale->delete();
        });
    }

    public function markAsPaid(Sale $sale, int $tenantId, array $validated): Sale
    {
        $paid = DB::transaction(function () use ($sale, $tenantId, $validated) {
            $lockedSale = Sale::query()
                ->lockForUpdate()
                ->find($sale->id);

            if (!$lockedSale) {
                abort(404);
            }

            if ($lockedSale->is_paid) {
                abort(422, 'Sale is already paid.');
            }

            $isWallet = ($validated['payment_method'] ?? null) === 'member_wallet';

            if ($isWallet) {
                if (!$lockedSale->customer_member_id) {
                    abort(422, 'Member wallet payment requires a member to be assigned to this sale.');
                }

                $member = Member::query()
                    ->lockForUpdate()
                    ->find($lockedSale->customer_member_id);

                if (!$member) {
                    abort(422, 'Member not found.');
                }

                if ((float) $member->current_balance < (float) $lockedSale->total_amount) {
                    abort(422, 'Insufficient member wallet balance.');
                }

                $member->update([
                    'current_balance' => (float) $member->current_balance - (float) $lockedSale->total_amount,
                ]);

                $lockedSale->update([
                    'account_id' => null,
                    'payment_method_id' => null,
                    'payment_method' => 'member_wallet',
                    'is_paid' => true,
                    'paid_amount' => (float) $lockedSale->total_amount,
                    'balance' => 0,
                ]);
            } else {
                $paymentMethod = $this->paymentMethods->resolveFromPayload($validated, $tenantId);

                $lockedSale->update([
                    'account_id' => $paymentMethod->company_account_id,
                    'payment_method_id' => $paymentMethod->id,
                    'payment_method' => $paymentMethod->name,
                    'is_paid' => true,
                    'paid_amount' => (float) $lockedSale->total_amount,
                    'balance' => 0,
                ]);

                $this->recordAccountTransactionForSale($lockedSale->fresh(), $tenantId, Carbon::today()->toDateString(), $paymentMethod);
            }

            return $lockedSale->fresh();
        });

        $this->sendSalePaidNotification($paid);

        return $paid;
    }

    private function buildSaleItemsAndTotals(array $validated, int $tenantId, string $today): array
    {
        $itemsPayload = $validated['items'];
        $variationIds = collect($itemsPayload)->pluck('product_variation_id')->unique();

        $variations = ProductVariation::query()
            ->whereIn('id', $variationIds)
            ->with('product')
            ->get()
            ->keyBy('id');

        if ($variations->count() !== $variationIds->count()) {
            abort(422, 'Invalid variation selection.');
        }

        $saleItems = [];
        $total = 0;

        foreach ($itemsPayload as $item) {
            $variation = $variations->get($item['product_variation_id']);
            $quantity = (int) $item['quantity'];

            $stockEntries = $this->saleableStockEntries($variation->id, $today)
                ->lockForUpdate()
                ->get();

            $available = (int) $stockEntries->sum('display_quantity');

            if ($quantity > $available) {
                abort(422, 'Insufficient display stock for ' . $variation->product->name . ' - ' . $variation->name);
            }

            $priceEntry = $stockEntries->first();

            if (!$priceEntry) {
                abort(422, 'No valid stock for ' . $variation->product->name . ' - ' . $variation->name);
            }

            $unitPrice = $validated['customer_type'] === 'local'
                ? $priceEntry->local_selling_price
                : $priceEntry->foreign_selling_price;

            $costTotal = $this->costTotalForQuantity($stockEntries, $quantity);
            $unitCost = $quantity > 0 ? $costTotal / $quantity : 0;
            $subtotal = $unitPrice * $quantity;
            $total += $subtotal;

            $saleItems[] = [
                'product_id' => $variation->product_id,
                'product_variation_id' => $variation->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'unit_cost' => round($unitCost, 4),
                'subtotal' => $subtotal,
                'cost_total' => round($costTotal, 2),
            ];
        }

        return [$saleItems, $total, $itemsPayload];
    }

    private function saleableStockEntries(int $variationId, string $today)
    {
        return StockEntry::query()
            ->where('product_variation_id', $variationId)
            ->where('display_quantity', '>', 0)
            ->where(function ($query) use ($today) {
                $query->whereDate('expiry_date', '>=', $today)
                    ->orWhereNull('expiry_date');
            })
            ->orderBy('expiry_date')
            ->orderBy('id');
    }

    private function costTotalForQuantity($stockEntries, int $quantity): float
    {
        $remaining = $quantity;
        $costTotal = 0.0;

        foreach ($stockEntries as $entry) {
            if ($remaining <= 0) {
                break;
            }

            $deduct = min((int) $entry->display_quantity, $remaining);

            if ($deduct <= 0) {
                continue;
            }

            $costTotal += $deduct * (float) $entry->purchasing_price;
            $remaining -= $deduct;
        }

        return $costTotal;
    }

    private function resolveMember(array $validated, int $tenantId): ?Member
    {
        if (empty($validated['customer_member_id'])) {
            return null;
        }

        $member = Member::query()
            ->lockForUpdate()
            ->find($validated['customer_member_id']);

        if (!$member) {
            abort(422, 'Invalid member selection.');
        }

        return $member;
    }

    private function resolvePayment(array $validated, ?Member $member, float $total, int $tenantId): array
    {
        if (($validated['payment_method'] ?? null) !== 'member_wallet') {
            $usesPaidStatusFlow = array_key_exists('is_paid', $validated);

            if ($usesPaidStatusFlow) {
                $isPaid = (bool) ($validated['is_paid'] ?? false);

                if ($isPaid) {
                    $paymentMethod = $this->paymentMethods->resolveFromPayload($validated, $tenantId);

                    return [$total, 0, true, $paymentMethod->company_account_id, $paymentMethod];
                }

                return [0, 0 - $total, false, null, null];
            }

            $paidAmount = (float) ($validated['paid_amount'] ?? 0);
            $isPaid = $paidAmount + 0.00001 >= $total;

            return [$paidAmount, $paidAmount - $total, $isPaid, null, null];
        }

        if (!$member) {
            abort(422, 'Please select a member for wallet payment.');
        }

        if ((float) $member->current_balance < $total) {
            abort(422, 'Insufficient member wallet balance.');
        }

        $member->update([
            'current_balance' => (float) $member->current_balance - $total,
        ]);

        return [$total, 0, true, null, null];
    }

    private function replaceSaleItems(Sale $sale, array $saleItems): void
    {
        SaleItem::where('sale_id', $sale->id)->delete();

        foreach ($saleItems as $saleItem) {
            $saleItem['sale_id'] = $sale->id;
            SaleItem::create($saleItem);
        }
    }

    private function deductStock(array $itemsPayload, int $tenantId, string $today): void
    {
        foreach ($itemsPayload as $item) {
            $remaining = $item['quantity'];

            $entries = $this->saleableStockEntries((int) $item['product_variation_id'], $today)
                ->lockForUpdate()
                ->get();

            foreach ($entries as $entry) {
                if ($remaining <= 0) {
                    break;
                }

                $deduct = min($entry->display_quantity, $remaining);

                if ($deduct <= 0) {
                    continue;
                }

                $before = [
                    'quantity' => (int) $entry->quantity,
                    'display_quantity' => (int) $entry->display_quantity,
                ];

                $entry->update([
                    'quantity' => $entry->quantity - $deduct,
                    'display_quantity' => $entry->display_quantity - $deduct,
                ]);

                $this->auditService->log($tenantId, 'sale_deducted', $entry, $before, [
                    'quantity' => (int) $entry->quantity,
                    'display_quantity' => (int) $entry->display_quantity,
                    'deducted' => $deduct,
                ]);

                $remaining -= $deduct;
            }
        }
    }

    private function restoreStockForExistingItems(Sale $sale, int $tenantId, string $today): void
    {
        $oldItems = $sale->items()->get();

        foreach ($oldItems as $item) {
            $remaining = $item->quantity;

            $entries = StockEntry::query()
                ->where('product_variation_id', $item->product_variation_id)
                ->where(function ($query) use ($today) {
                    $query->whereDate('expiry_date', '>=', $today)
                        ->orWhereNull('expiry_date');
                })
                ->orderBy('expiry_date')
                ->lockForUpdate()
                ->get();

            foreach ($entries as $entry) {
                if ($remaining <= 0) {
                    break;
                }

                $before = [
                    'quantity' => (int) $entry->quantity,
                    'display_quantity' => (int) $entry->display_quantity,
                ];

                $entry->increment('quantity', $remaining);
                $entry->increment('display_quantity', $remaining);

                $this->auditService->log($tenantId, 'sale_restored', $entry, $before, [
                    'quantity' => (int) $entry->fresh()->quantity,
                    'display_quantity' => (int) $entry->fresh()->display_quantity,
                    'restored' => $remaining,
                ]);

                $remaining = 0;
            }
        }
    }

    private function refundWalletIfNeeded(Sale $sale, int $tenantId): void
    {
        if ($sale->payment_method !== 'member_wallet' || !$sale->customer_member_id) {
            return;
        }

        $oldMember = Member::query()
            ->lockForUpdate()
            ->find($sale->customer_member_id);

        if (!$oldMember) {
            return;
        }

        $oldMember->update([
            'current_balance' => (float) $oldMember->current_balance + (float) $sale->total_amount,
        ]);
    }

    private function ensureSaleIsMutable(Sale $sale): void
    {
        if ($sale->is_paid) {
            abort(422, 'Paid sales cannot be edited or deleted.');
        }
    }

    private function recordAccountTransactionForSale(Sale $sale, int $tenantId, ?string $transactionDate = null, ?PaymentMethod $method = null): void
    {
        if (!$sale->is_paid || !$sale->account_id) {
            return;
        }

        $method ??= $sale->paymentMethod;

        if (!$method) {
            $method = $this->paymentMethods->resolveLegacyAccountMethod((int) $sale->account_id);
        }

        $this->settlements->syncForSale($sale, $method, $transactionDate);
    }

    private function sendSalePaidNotification(Sale $sale): void
    {
        if (!$sale->customer_member_id) {
            return;
        }

        $ref = $sale->reference_number ? ' (Ref: ' . $sale->reference_number . ')' : '';
        $amount = number_format((float) $sale->total_amount, 2);
        $profileUrl = $this->memberPortalUrl->urlForTenant(app('tenant'));

        $title = "Payment Received – LKR {$amount}";
        $body = "Payment received for Sale #{$sale->id}: LKR {$amount}{$ref}. Thank you! View your account: {$profileUrl}";

        SendMemberNotificationJob::dispatch(
            (int) app('tenant')->id,
            $sale->customer_member_id,
            'sale_paid',
            $title,
            $body,
        );
    }

    private function sendSaleOutstandingNotification(Sale $sale): void
    {
        if (!$sale->customer_member_id) {
            return;
        }

        $due = number_format(abs((float) $sale->balance), 2);
        $ref = $sale->reference_number ? ' (Ref: ' . $sale->reference_number . ')' : '';

        // Compute total outstanding before dispatching so the job gets a snapshot
        $totalOutstanding = Sale::where('customer_member_id', $sale->customer_member_id)
            ->where('is_paid', false)
            ->sum('balance');
        $totalDue = number_format(abs((float) $totalOutstanding), 2);

        $profileUrl = $this->memberPortalUrl->urlForTenant(app('tenant'));

        $title = "Outstanding Balance – LKR {$due}";
        $body = "Outstanding for Sale #{$sale->id}: LKR {$due}{$ref}. Total outstanding: LKR {$totalDue}. Please settle soon. View your account: {$profileUrl}";

        SendMemberNotificationJob::dispatch(
            (int) app('tenant')->id,
            $sale->customer_member_id,
            'sale_outstanding',
            $title,
            $body,
        );
    }
}

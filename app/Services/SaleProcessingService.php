<?php

namespace App\Services;

use App\Models\CompanyAccount;
use App\Models\CompanyAccountTransaction;
use App\Models\Member;
use App\Models\ProductVariation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SaleProcessingService
{
    public function create(int $tenantId, array $validated): Sale
    {
        $today = Carbon::today()->toDateString();

        return DB::transaction(function () use ($tenantId, $validated, $today) {
            [$saleItems, $total, $itemsPayload] = $this->buildSaleItemsAndTotals($validated, $tenantId, $today);
            $member = $this->resolveMember($validated, $tenantId);
            [$paidAmount, $balance, $isPaid, $accountId] = $this->resolvePayment($validated, $member, $total, $tenantId);

            $sale = Sale::create([
                'tenant_id' => $tenantId,
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_member_id' => $member?->id,
                'account_id' => $accountId,
                'customer_type' => $validated['customer_type'],
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'total_amount' => $total,
                'paid_amount' => $paidAmount,
                'balance' => $balance,
                'is_paid' => $isPaid,
            ]);

            $this->replaceSaleItems($sale, $saleItems);
            $this->deductStock($itemsPayload, $tenantId, $today);
            $this->recordAccountTransactionForSale($sale, $tenantId);

            return $sale;
        });
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
            [$paidAmount, $balance, $isPaid, $accountId] = $this->resolvePayment($validated, $member, $total, $tenantId);

            $sale->update([
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_member_id' => $member?->id,
                'account_id' => $accountId,
                'customer_type' => $validated['customer_type'],
                'payment_method' => $validated['payment_method'],
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
        return DB::transaction(function () use ($sale, $tenantId, $validated) {
            $lockedSale = Sale::query()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->find($sale->id);

            if (!$lockedSale) {
                abort(404);
            }

            if ($lockedSale->is_paid) {
                abort(422, 'Sale is already paid.');
            }

            $accountId = $this->resolveAccountId($validated, $tenantId);

            $lockedSale->update([
                'account_id' => $accountId,
                'is_paid' => true,
                'paid_amount' => (float) $lockedSale->total_amount,
                'balance' => 0,
            ]);

            $this->recordAccountTransactionForSale($lockedSale, $tenantId, Carbon::today()->toDateString());

            return $lockedSale->fresh();
        });
    }

    private function buildSaleItemsAndTotals(array $validated, int $tenantId, string $today): array
    {
        $itemsPayload = $validated['items'];
        $variationIds = collect($itemsPayload)->pluck('product_variation_id')->unique();

        $variations = ProductVariation::query()
            ->where('tenant_id', $tenantId)
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

            $available = StockEntry::query()
                ->where('tenant_id', $tenantId)
                ->where('product_variation_id', $variation->id)
                ->where(function ($query) use ($today) {
                    $query->whereDate('expiry_date', '>=', $today)
                        ->orWhereNull('expiry_date');
                })
                ->sum('quantity');

            if ($item['quantity'] > $available) {
                abort(422, 'Insufficient stock for '.$variation->product->name.' - '.$variation->name);
            }

            $priceEntry = StockEntry::query()
                ->where('tenant_id', $tenantId)
                ->where('product_variation_id', $variation->id)
                ->where(function ($query) use ($today) {
                    $query->whereDate('expiry_date', '>=', $today)
                        ->orWhereNull('expiry_date');
                })
                ->orderBy('expiry_date')
                ->first();

            if (!$priceEntry) {
                abort(422, 'No valid stock for '.$variation->product->name.' - '.$variation->name);
            }

            $unitPrice = $validated['customer_type'] === 'local'
                ? $priceEntry->local_selling_price
                : $priceEntry->foreign_selling_price;

            $subtotal = $unitPrice * $item['quantity'];
            $total += $subtotal;

            $saleItems[] = [
                'product_id' => $variation->product_id,
                'product_variation_id' => $variation->id,
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
            ];
        }

        return [$saleItems, $total, $itemsPayload];
    }

    private function resolveMember(array $validated, int $tenantId): ?Member
    {
        if (empty($validated['customer_member_id'])) {
            return null;
        }

        $member = Member::query()
            ->where('tenant_id', $tenantId)
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
                    $accountId = $this->resolveAccountId($validated, $tenantId);

                    return [$total, 0, true, $accountId];
                }

                return [0, 0 - $total, false, null];
            }

            $paidAmount = (float) ($validated['paid_amount'] ?? 0);
            $isPaid = $paidAmount + 0.00001 >= $total;

            return [$paidAmount, $paidAmount - $total, $isPaid, null];
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

        return [$total, 0, true, null];
    }

    private function resolveAccountId(array $validated, int $tenantId): int
    {
        if (empty($validated['account_id'])) {
            abort(422, 'Please select a company account for paid sales.');
        }

        $account = CompanyAccount::query()
            ->where('tenant_id', $tenantId)
            ->find((int) $validated['account_id']);

        if (!$account) {
            abort(422, 'Invalid company account selection.');
        }

        return (int) $account->id;
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

            $entries = StockEntry::query()
                ->where('tenant_id', $tenantId)
                ->where('product_variation_id', $item['product_variation_id'])
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

                $deduct = min($entry->quantity, $remaining);
                $entry->update(['quantity' => $entry->quantity - $deduct]);
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
                ->where('tenant_id', $tenantId)
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

                $toRestore = $remaining;
                $entry->increment('quantity', $toRestore);
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
            ->where('tenant_id', $tenantId)
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

    private function recordAccountTransactionForSale(Sale $sale, int $tenantId, ?string $transactionDate = null): void
    {
        if (!$sale->is_paid || !$sale->account_id) {
            return;
        }

        CompanyAccountTransaction::updateOrCreate(
            [
                'sale_id' => $sale->id,
                'type' => 'sale_payment',
            ],
            [
                'tenant_id' => $tenantId,
                'company_account_id' => $sale->account_id,
                'amount' => (float) $sale->total_amount,
                'transaction_date' => $transactionDate ?? optional($sale->created_at)->toDateString() ?? Carbon::today()->toDateString(),
                'reference_number' => $sale->reference_number,
                'notes' => 'Sale payment for sale #'.$sale->id,
            ]
        );
    }
}

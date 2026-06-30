<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\MemberPayment;
use App\Models\SaleItem;
use App\Models\StockEntry;
use Illuminate\Support\Carbon;

class RealProfitReportService
{
    public function build(int $tenantId, ?string $month): array
    {
        $start = $this->resolveMonth($month);
        $end = $start->copy()->endOfMonth();

        $membershipPayments = $this->membershipPayments($start, $end);
        $sales = $this->salesMargin($start, $end);
        $expenses = $this->expenses($start, $end);

        $membershipIncome = $this->roundMoney($membershipPayments['rows']->sum('amount'));
        $expenseTotal = $this->roundMoney($expenses['rows']->sum('amount'));
        $salesProfit = $this->roundMoney($sales['summary']['profit']);
        $realProfit = $this->roundMoney($membershipIncome + $salesProfit - $expenseTotal);

        return [
            'month' => $start->format('Y-m'),
            'month_label' => $start->format('F Y'),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'summary' => [
                'membership_income' => $membershipIncome,
                'membership_count' => $membershipPayments['rows']->count(),
                'sales_revenue' => $this->roundMoney($sales['summary']['revenue']),
                'sales_cost' => $this->roundMoney($sales['summary']['cost']),
                'sales_profit' => $salesProfit,
                'sales_transactions' => $sales['summary']['transactions'],
                'sales_quantity' => $sales['summary']['quantity'],
                'sales_item_lines' => $sales['summary']['lines'],
                'expenses' => $expenseTotal,
                'expense_count' => $expenses['rows']->count(),
                'real_profit' => $realProfit,
                'estimated_cost_items' => $sales['summary']['estimated_cost_items'],
                'missing_cost_items' => $sales['summary']['missing_cost_items'],
            ],
            'membership_payments' => $membershipPayments['data'],
            'sales_items' => $sales['items'],
            'sales_by_product' => $sales['by_product'],
            'expenses' => $expenses['data'],
            'expenses_by_category' => $expenses['by_category'],
        ];
    }

    private function membershipPayments(Carbon $start, Carbon $end): array
    {
        $rows = MemberPayment::query()
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->whereHas('membership')
            ->with([
                'member:id,first_name,last_name,name,phone_number',
                'account:id,name',
                'membership.plan:id,name',
            ])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        return [
            'rows' => $rows,
            'data' => $rows->map(fn (MemberPayment $payment) => [
                'id' => (int) $payment->id,
                'member_name' => $this->memberName($payment),
                'member_phone' => $payment->member?->phone_number,
                'payment_plan_name' => $payment->membership?->plan?->name,
                'payment_method' => $payment->payment_method ?? 'cash',
                'account_name' => $payment->account?->name,
                'amount' => $this->roundMoney((float) $payment->amount),
                'payment_date' => $payment->payment_date?->toDateString(),
                'start_date' => $payment->membership?->start_date?->toDateString(),
                'end_date' => $payment->membership?->end_date?->toDateString(),
                'reference_number' => $payment->reference_number,
            ])->values()->all(),
        ];
    }

    private function salesMargin(Carbon $start, Carbon $end): array
    {
        $items = SaleItem::query()
            ->with([
                'sale:id,customer_name,total_amount,paid_amount,balance,is_paid,created_at',
                'product:id,name',
                'variation:id,name',
            ])
            ->whereHas('sale', fn ($query) => $query->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]))
            ->orderByDesc(
                \App\Models\Sale::query()
                    ->select('created_at')
                    ->whereColumn('sales.id', 'sale_items.sale_id')
                    ->limit(1),
            )
            ->orderByDesc('sale_id')
            ->orderBy('id')
            ->get();

        $costEstimates = $this->costEstimatesForVariations(
            $items->pluck('product_variation_id')->filter()->unique()->values()->all(),
        );

        $rows = $items->map(function (SaleItem $item) use ($costEstimates) {
            $quantity = (int) $item->quantity;
            $revenue = (float) $item->subtotal;
            $costSource = 'exact';
            $unitCost = $item->unit_cost !== null ? (float) $item->unit_cost : null;
            $cost = $item->cost_total !== null ? (float) $item->cost_total : null;

            if ($cost === null) {
                $estimate = $costEstimates[$item->product_variation_id] ?? null;

                if ($estimate !== null) {
                    $unitCost = $estimate;
                    $cost = $estimate * $quantity;
                    $costSource = 'estimated';
                } else {
                    $unitCost = 0.0;
                    $cost = 0.0;
                    $costSource = 'missing';
                }
            }

            $profit = $revenue - $cost;

            return [
                'id' => (int) $item->id,
                'sale_id' => (int) $item->sale_id,
                'sale_date' => $item->sale?->created_at?->toDateString(),
                'customer_name' => $this->saleCustomerName($item),
                'is_paid' => (bool) ($item->sale?->is_paid ?? false),
                'sale_paid_amount' => $this->roundMoney((float) ($item->sale?->paid_amount ?? 0)),
                'sale_balance' => $this->roundMoney((float) ($item->sale?->balance ?? 0)),
                'product_id' => (int) $item->product_id,
                'product_name' => $item->product?->name ?? 'Product',
                'variation_id' => (int) $item->product_variation_id,
                'variation_name' => $item->variation?->name,
                'quantity' => $quantity,
                'unit_price' => $this->roundMoney((float) $item->unit_price),
                'unit_cost' => round((float) $unitCost, 4),
                'revenue' => $this->roundMoney($revenue),
                'cost' => $this->roundMoney($cost),
                'profit' => $this->roundMoney($profit),
                'margin_percent' => $this->marginPercent($profit, $revenue),
                'cost_source' => $costSource,
            ];
        })->values();

        $byProduct = $rows
            ->groupBy('product_id')
            ->map(function ($group) {
                $first = $group->first();
                $revenue = (float) $group->sum('revenue');
                $cost = (float) $group->sum('cost');
                $profit = $revenue - $cost;

                return [
                    'product_id' => $first['product_id'],
                    'product_name' => $first['product_name'],
                    'quantity' => (int) $group->sum('quantity'),
                    'revenue' => $this->roundMoney($revenue),
                    'cost' => $this->roundMoney($cost),
                    'profit' => $this->roundMoney($profit),
                    'margin_percent' => $this->marginPercent($profit, $revenue),
                    'estimated_cost_items' => $group->where('cost_source', 'estimated')->count(),
                    'missing_cost_items' => $group->where('cost_source', 'missing')->count(),
                ];
            })
            ->sortByDesc('profit')
            ->values()
            ->all();

        return [
            'summary' => [
                'transactions' => $items->pluck('sale_id')->unique()->count(),
                'quantity' => (int) $rows->sum('quantity'),
                'lines' => $rows->count(),
                'revenue' => $this->roundMoney($rows->sum('revenue')),
                'cost' => $this->roundMoney($rows->sum('cost')),
                'profit' => $this->roundMoney($rows->sum('profit')),
                'estimated_cost_items' => $rows->where('cost_source', 'estimated')->count(),
                'missing_cost_items' => $rows->where('cost_source', 'missing')->count(),
            ],
            'items' => $rows->all(),
            'by_product' => $byProduct,
        ];
    }

    private function expenses(Carbon $start, Carbon $end): array
    {
        $rows = Expense::query()
            ->with('account:id,name')
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->get();

        $data = $rows->map(fn (Expense $expense) => [
            'id' => (int) $expense->id,
            'expense_date' => $expense->expense_date?->toDateString(),
            'category' => $expense->category,
            'account_name' => $expense->account?->name,
            'amount' => $this->roundMoney((float) $expense->amount),
            'reference_number' => $expense->reference_number,
            'notes' => $expense->notes,
        ])->values();

        $byCategory = $data
            ->groupBy(fn ($expense) => $expense['category'] ?: 'Uncategorized')
            ->map(fn ($group, string $category) => [
                'category' => $category,
                'count' => $group->count(),
                'amount' => $this->roundMoney($group->sum('amount')),
            ])
            ->sortByDesc('amount')
            ->values()
            ->all();

        return [
            'rows' => $rows,
            'data' => $data->all(),
            'by_category' => $byCategory,
        ];
    }

    private function costEstimatesForVariations(array $variationIds): array
    {
        if ($variationIds === []) {
            return [];
        }

        return StockEntry::query()
            ->whereIn('product_variation_id', $variationIds)
            ->get(['product_variation_id', 'quantity', 'purchasing_price'])
            ->groupBy('product_variation_id')
            ->map(function ($entries) {
                $quantity = (float) $entries->sum('quantity');
                $average = (float) $entries->avg('purchasing_price');

                if ($quantity <= 0) {
                    return $average;
                }

                $weightedTotal = $entries->sum(fn (StockEntry $entry) => (float) $entry->quantity * (float) $entry->purchasing_price);

                return $weightedTotal / $quantity;
            })
            ->all();
    }

    private function resolveMonth(?string $month): Carbon
    {
        if (is_string($month) && preg_match('/^(\d{4})-(\d{2})$/', $month, $matches)) {
            $year = (int) $matches[1];
            $monthNumber = (int) $matches[2];

            if ($monthNumber >= 1 && $monthNumber <= 12) {
                return Carbon::create($year, $monthNumber, 1)->startOfMonth();
            }
        }

        return Carbon::today()->startOfMonth();
    }

    private function memberName(MemberPayment $payment): string
    {
        $member = $payment->member;

        if (!$member) {
            return 'Unknown';
        }

        $name = trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? ''));

        return $name !== '' ? $name : ($member->name ?: 'Member');
    }

    private function saleCustomerName(SaleItem $item): string
    {
        $name = trim((string) ($item->sale?->customer_name ?? ''));

        return $name !== '' ? $name : 'Walk-in';
    }

    private function marginPercent(float $profit, float $revenue): float
    {
        if (abs($revenue) < 0.00001) {
            return 0.0;
        }

        return round(($profit / $revenue) * 100, 2);
    }

    private function roundMoney(float|int $value): float
    {
        return round((float) $value, 2);
    }
}

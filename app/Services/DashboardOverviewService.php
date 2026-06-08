<?php

namespace App\Services;

use App\Models\BiometricAccessEvent;
use App\Models\CompanyAccountTransaction;
use App\Models\ProductVariation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;

class DashboardOverviewService
{
    private const LOW_STOCK_THRESHOLD = 5;

    private const SALES_RANGE_TYPES = ['date', 'week', 'month', 'year'];

    private const AUTH_WIDGET_LIMIT = 20;

    private const CASH_FLOW_WIDGET_LIMIT = 5;

    private const TRANSACTION_SOURCE_LABELS = [
        'sale' => 'Sale',
        'payment' => 'Member Payment',
        'wallet_topup' => 'Wallet Top-up',
        'event_registration' => 'Event Registration',
        'expense' => 'Expense',
    ];

    public function __construct(private readonly BiometricSyncService $biometric) {}

    public function build(User $user, Tenant $tenant, ?string $startDate = null, ?string $endDate = null): array
    {
        $tenantId = $tenant->id;
        $today = now()->toDateString();
        $resolvedStartDate = $startDate ?: $today;
        $resolvedEndDate = $endDate ?: $resolvedStartDate;
        $startAt = Carbon::parse($resolvedStartDate)->startOfDay();
        $endAt = Carbon::parse($resolvedEndDate)->endOfDay();
        $rangeLabel = $resolvedStartDate === $resolvedEndDate
            ? $startAt->format('d M Y')
            : $startAt->format('d M Y') . ' - ' . $endAt->format('d M Y');

        return [
            'tenant' => [
                'name' => $tenant->name,
                'id' => $tenant->id,
                'domain' => $tenant->domain,
            ],
            'user' => [
                'name' => $user->name,
                'id' => $user->id,
                'email' => $user->email,
            ],
            'stock_summary' => $this->buildStockSummary($user, $tenantId, $resolvedEndDate),
            'income_expense_summary' => $this->buildIncomeExpenseSummary($user, $tenantId, $startAt, $endAt, $rangeLabel),
            'today_auth_summary' => $this->buildTodayAuthSummary($user, $tenantId, $startAt, $endAt, $rangeLabel),
        ];
    }

    private function buildTodayAuthSummary(User $user, int $tenantId, Carbon $startAt, Carbon $endAt, string $rangeLabel): array
    {
        $canViewTodayAuth = $user->hasPermission('dashboard.view');

        $summary = [
            'can_view' => $canViewTodayAuth,
            'start_date' => $startAt->toDateString(),
            'end_date' => $endAt->toDateString(),
            'range_label' => $rangeLabel,
            'counts' => [
                'total' => 0,
                'success' => 0,
                'payment_expired' => 0,
                'other_failed' => 0,
            ],
            'lists' => [
                'success_attempts' => [],
                'payment_expired' => [],
                'other_failed' => [],
            ],
        ];

        if (!$canViewTodayAuth) {
            return $summary;
        }

        $baseQuery = BiometricAccessEvent::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('event_time', [$startAt, $endAt]);

        $summary['counts']['total'] = (int) (clone $baseQuery)->count();
        $summary['counts']['success'] = (int) (clone $baseQuery)
            ->where('result', 'success')
            ->count();
        $summary['counts']['payment_expired'] = (int) (clone $baseQuery)
            ->where('result', 'failed')
            ->where('fail_reason', 'payment_expired')
            ->count();
        $summary['counts']['other_failed'] = (int) (clone $baseQuery)
            ->where('result', 'failed')
            ->where(function ($query) {
                $query->whereNull('fail_reason')
                    ->orWhere('fail_reason', '!=', 'payment_expired');
            })
            ->count();

        $successRows = (clone $baseQuery)
            ->where('result', 'success')
            ->with('member:id,name,biometric_member_id')
            ->orderByDesc('event_time')
            ->orderByDesc('id')
            ->limit(self::AUTH_WIDGET_LIMIT)
            ->get();

        $paymentExpiredRows = (clone $baseQuery)
            ->where('result', 'failed')
            ->where('fail_reason', 'payment_expired')
            ->with('member:id,name,biometric_member_id')
            ->orderByDesc('event_time')
            ->orderByDesc('id')
            ->limit(self::AUTH_WIDGET_LIMIT)
            ->get();

        $otherFailedRows = (clone $baseQuery)
            ->where('result', 'failed')
            ->where(function ($query) {
                $query->whereNull('fail_reason')
                    ->orWhere('fail_reason', '!=', 'payment_expired');
            })
            ->with('member:id,name,biometric_member_id')
            ->orderByDesc('event_time')
            ->orderByDesc('id')
            ->limit(self::AUTH_WIDGET_LIMIT)
            ->get();

        $summary['lists']['success_attempts'] = $successRows
            ->map(fn (BiometricAccessEvent $event) => $this->mapAuthEventForWidget($event))
            ->values()
            ->all();

        $summary['lists']['payment_expired'] = $paymentExpiredRows
            ->map(fn (BiometricAccessEvent $event) => $this->mapAuthEventForWidget($event))
            ->values()
            ->all();

        $summary['lists']['other_failed'] = $otherFailedRows
            ->map(fn (BiometricAccessEvent $event) => $this->mapAuthEventForWidget($event))
            ->values()
            ->all();

        return $summary;
    }

    private function mapAuthEventForWidget(BiometricAccessEvent $event): array
    {
        return [
            'id' => (int) $event->id,
            'member' => $event->member
                ? [
                    'id' => (int) $event->member->id,
                    'name' => (string) $event->member->name,
                    'biometric_member_id' => (string) ($event->member->biometric_member_id ?? ''),
                ]
                : null,
            'person_name' => (string) ($event->person_name ?? ''),
            'employee_no' => (string) ($event->employee_no ?? ''),
            'auth_method' => (string) ($event->auth_method ?? ''),
            'result' => (string) ($event->result ?? ''),
            'fail_reason' => (string) ($event->fail_reason ?? ''),
            'picture_url' => $this->biometric->accessEventPictureUrl($event->picture_path),
            'event_time' => $event->event_time?->toIso8601String(),
        ];
    }

    public function buildStats(User $user, Tenant $tenant, string $rangeType = 'date', ?string $rangeValue = null): array
    {
        [$normalizedRangeType, $normalizedRangeValue, $startAt, $endAt, $rangeLabel] = $this->resolveSalesRange($rangeType, $rangeValue);

        $canViewSalesSummary = $user->hasPermission('sales.process');

        $stats = [
            'can_view' => $canViewSalesSummary,
            'range_type' => $normalizedRangeType,
            'range_value' => $normalizedRangeValue,
            'range_label' => $rangeLabel,
            'start_date' => $startAt->toDateString(),
            'end_date' => $endAt->toDateString(),
            'transactions' => 0,
            'gross_amount' => 0,
            'paid_amount' => 0,
            'outstanding_amount' => 0,
            'transaction_list' => [],
            'customer_wise_sales' => [],
            'product_wise_sales' => [],
        ];

        if (!$canViewSalesSummary) {
            return $stats;
        }

        return array_merge(
            $stats,
            $this->buildSalesStatsData($tenant->id, $startAt, $endAt),
        );
    }

    private function buildStockSummary(User $user, int $tenantId, string $today): array
    {
        $canViewStockSummary = $user->hasPermission('inventory.manage');

        $stockSummary = [
            'can_view' => $canViewStockSummary,
            'selected_date' => $today,
            'available_units' => 0,
            'tracked_variations' => 0,
            'low_stock_variations' => 0,
            'low_stock_threshold' => self::LOW_STOCK_THRESHOLD,
            'variation_availability' => [],
        ];

        if (!$canViewStockSummary) {
            return $stockSummary;
        }

        $variationAvailability = ProductVariation::query()
            ->where('product_variations.tenant_id', $tenantId)
            ->leftJoin('products', 'products.id', '=', 'product_variations.product_id')
            ->leftJoin('stock_entries', function ($join) use ($tenantId, $today) {
                $join->on('stock_entries.product_variation_id', '=', 'product_variations.id')
                    ->where('stock_entries.tenant_id', $tenantId)
                    ->where(function ($query) use ($today) {
                        $query->whereDate('stock_entries.expiry_date', '>=', $today)
                            ->orWhereNull('stock_entries.expiry_date');
                    });
            })
            ->groupBy('product_variations.id', 'product_variations.name', 'products.name')
            ->orderBy('products.name')
            ->orderBy('product_variations.name')
            ->selectRaw('product_variations.id as variation_id, product_variations.name as variation_name, products.name as product_name, COALESCE(SUM(stock_entries.quantity), 0) as available_quantity')
            ->get()
            ->map(function ($item) {
                $availableQuantity = (int) $item->available_quantity;
                $productName = (string) ($item->product_name ?? 'Product');
                $variationName = (string) $item->variation_name;

                return [
                    'variation_id' => (int) $item->variation_id,
                    'product_name' => $productName,
                    'variation_name' => $variationName,
                    'label' => trim($productName . ' - ' . $variationName),
                    'available_quantity' => $availableQuantity,
                    'is_low_stock' => $availableQuantity <= self::LOW_STOCK_THRESHOLD,
                ];
            })
            ->values();

        $stockSummary['available_units'] = (int) $variationAvailability->sum('available_quantity');
        $stockSummary['tracked_variations'] = $variationAvailability->count();
        $stockSummary['low_stock_variations'] = $variationAvailability
            ->filter(fn ($item) => $item['is_low_stock'])
            ->count();
        $stockSummary['variation_availability'] = $variationAvailability;

        return $stockSummary;
    }

    private function buildIncomeExpenseSummary(
        User $user,
        int $tenantId,
        Carbon $startAt,
        Carbon $endAt,
        string $rangeLabel,
    ): array {
        $canViewAccounts = $user->hasPermission('accounts.manage');

        $summary = [
            'can_view' => $canViewAccounts,
            'start_date' => $startAt->toDateString(),
            'end_date' => $endAt->toDateString(),
            'range_label' => $rangeLabel,
            'income' => 0,
            'expense' => 0,
            'net_movement' => 0,
            'income_count' => 0,
            'expense_count' => 0,
            'recent_transactions' => [],
        ];

        if (!$canViewAccounts) {
            return $summary;
        }

        $baseQuery = CompanyAccountTransaction::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('transaction_date', '>=', $startAt->toDateString())
            ->whereDate('transaction_date', '<=', $endAt->toDateString());

        $incomeQuery = (clone $baseQuery)->where('amount', '>', 0);
        $expenseQuery = (clone $baseQuery)->where('amount', '<', 0);

        $summary['income'] = round((float) (clone $incomeQuery)->sum('amount'), 2);
        $summary['expense'] = round(abs((float) (clone $expenseQuery)->sum('amount')), 2);
        $summary['net_movement'] = round($summary['income'] - $summary['expense'], 2);
        $summary['income_count'] = (int) (clone $incomeQuery)->count();
        $summary['expense_count'] = (int) (clone $expenseQuery)->count();
        $summary['recent_transactions'] = (clone $baseQuery)
            ->with('account:id,name')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(self::CASH_FLOW_WIDGET_LIMIT)
            ->get()
            ->map(fn (CompanyAccountTransaction $transaction) => [
                'id' => (int) $transaction->id,
                'amount' => (float) $transaction->amount,
                'transaction_date' => $transaction->transaction_date?->toDateString(),
                'account_name' => (string) ($transaction->account?->name ?? 'Account'),
                'source_label' => $this->transactionSourceLabel($transaction),
                'source_path' => $this->transactionSourcePath($transaction),
            ])
            ->values()
            ->all();

        return $summary;
    }

    private function transactionSourceLabel(CompanyAccountTransaction $transaction): string
    {
        $source = self::TRANSACTION_SOURCE_LABELS[$transaction->model_name]
            ?? ucwords(str_replace('_', ' ', (string) ($transaction->model_name ?: $transaction->type ?: 'Transaction')));
        $reference = trim((string) ($transaction->reference_number ?? ''));

        return $reference !== '' ? $source . ' · ' . $reference : $source;
    }

    private function transactionSourcePath(CompanyAccountTransaction $transaction): ?string
    {
        if (!$transaction->reference_id) {
            return null;
        }

        return match ($transaction->model_name) {
            'sale' => '/sales/' . $transaction->reference_id,
            'expense' => '/expenses/' . $transaction->reference_id,
            'payment' => '/payments/' . $transaction->reference_id,
            'wallet_topup' => '/wallet-topups/' . $transaction->reference_id,
            default => null,
        };
    }

    private function buildSalesStatsData(int $tenantId, Carbon $startAt, Carbon $endAt): array
    {
        $totals = $this->buildSalesSummaryTotals($tenantId, $startAt, $endAt);

        return [
            'transactions' => $totals['transactions'],
            'gross_amount' => $totals['gross_amount'],
            'paid_amount' => $totals['paid_amount'],
            'outstanding_amount' => $totals['outstanding_amount'],
            'transaction_list' => $this->buildTransactionListForRange($tenantId, $startAt, $endAt),
            'customer_wise_sales' => $this->buildCustomerWiseSalesForRange($tenantId, $startAt, $endAt),
            'product_wise_sales' => $this->buildProductWiseSalesForRange($tenantId, $startAt, $endAt),
        ];
    }

    private function buildSalesSummaryTotals(int $tenantId, Carbon $startAt, Carbon $endAt): array
    {
        $totals = Sale::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startAt, $endAt])
            ->selectRaw('COUNT(*) as transactions, COALESCE(SUM(total_amount), 0) as gross_amount, COALESCE(SUM(paid_amount), 0) as paid_amount')
            ->first();

        $transactions = (int) ($totals->transactions ?? 0);
        $grossAmount = (float) ($totals->gross_amount ?? 0);
        $paidAmount = (float) ($totals->paid_amount ?? 0);

        return [
            'transactions' => $transactions,
            'gross_amount' => $grossAmount,
            'paid_amount' => $paidAmount,
            'outstanding_amount' => $grossAmount - $paidAmount,
        ];
    }

    private function buildTransactionListForRange(int $tenantId, Carbon $startAt, Carbon $endAt): array
    {
        return Sale::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startAt, $endAt])
            ->with(['items:id,sale_id,quantity'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get([
                'id',
                'customer_name',
                'customer_type',
                'payment_method',
                'reference_number',
                'total_amount',
                'paid_amount',
                'balance',
                'created_at',
            ])
            ->map(function (Sale $sale) {
                $customerName = trim((string) ($sale->customer_name ?? ''));

                if ($customerName === '') {
                    $customerName = 'Walk-in';
                }

                return [
                    'sale_id' => (int) $sale->id,
                    'customer_name' => $customerName,
                    'customer_type' => (string) $sale->customer_type,
                    'payment_method' => (string) ($sale->payment_method ?? 'cash'),
                    'reference_number' => $sale->reference_number,
                    'item_lines' => (int) $sale->items->count(),
                    'item_quantity' => (int) $sale->items->sum('quantity'),
                    'total_amount' => (float) $sale->total_amount,
                    'paid_amount' => (float) $sale->paid_amount,
                    'balance' => (float) $sale->balance,
                    'created_at' => $sale->created_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    private function buildCustomerWiseSalesForRange(int $tenantId, Carbon $startAt, Carbon $endAt): array
    {
        $customerNameExpression = "COALESCE(NULLIF(TRIM(customer_name), ''), 'Walk-in')";

        return Sale::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startAt, $endAt])
            ->selectRaw($customerNameExpression . ' as customer_name')
            ->selectRaw('COUNT(*) as transactions')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount')
            ->groupByRaw($customerNameExpression)
            ->orderByRaw('COALESCE(SUM(total_amount), 0) DESC')
            ->orderByRaw($customerNameExpression . ' ASC')
            ->get()
            ->map(function ($item) {
                return [
                    'customer_name' => (string) $item->customer_name,
                    'transactions' => (int) $item->transactions,
                    'total_amount' => (float) $item->total_amount,
                ];
            })
            ->values()
            ->all();
    }

    private function buildProductWiseSalesForRange(int $tenantId, Carbon $startAt, Carbon $endAt): array
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.tenant_id', $tenantId)
            ->whereNull('sales.deleted_at')
            ->whereBetween('sales.created_at', [$startAt, $endAt])
            ->groupBy('sale_items.product_id', 'products.name')
            ->selectRaw("sale_items.product_id as product_id, COALESCE(products.name, 'Product') as product_name")
            ->selectRaw('COUNT(DISTINCT sales.id) as transactions')
            ->selectRaw('COALESCE(SUM(sale_items.quantity), 0) as quantity_sold')
            ->selectRaw('COALESCE(SUM(sale_items.subtotal), 0) as total_amount')
            ->orderByRaw('COALESCE(SUM(sale_items.subtotal), 0) DESC')
            ->orderByRaw("COALESCE(products.name, 'Product') ASC")
            ->get()
            ->map(function ($item) {
                return [
                    'product_id' => (int) $item->product_id,
                    'product_name' => (string) $item->product_name,
                    'transactions' => (int) $item->transactions,
                    'quantity_sold' => (int) $item->quantity_sold,
                    'total_amount' => (float) $item->total_amount,
                ];
            })
            ->values()
            ->all();
    }

    private function resolveSalesRange(string $rangeType, ?string $rangeValue): array
    {
        $normalizedRangeType = in_array($rangeType, self::SALES_RANGE_TYPES, true)
            ? $rangeType
            : 'date';

        return match ($normalizedRangeType) {
            'week' => $this->resolveWeekRange($rangeValue),
            'month' => $this->resolveMonthRange($rangeValue),
            'year' => $this->resolveYearRange($rangeValue),
            default => $this->resolveDateRange($rangeValue),
        };
    }

    private function resolveDateRange(?string $rangeValue): array
    {
        $date = Carbon::today();

        if (is_string($rangeValue) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $rangeValue, $matches)) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];
            $day = (int) $matches[3];

            if (checkdate($month, $day, $year)) {
                $date = Carbon::create($year, $month, $day, 0, 0, 0);
            }
        }

        $startAt = $date->copy()->startOfDay();
        $endAt = $date->copy()->endOfDay();

        return [
            'date',
            $date->toDateString(),
            $startAt,
            $endAt,
            $date->format('d M Y'),
        ];
    }

    private function resolveWeekRange(?string $rangeValue): array
    {
        $today = Carbon::today();
        $isoYear = (int) $today->isoWeekYear;
        $isoWeek = (int) $today->isoWeek;

        if (is_string($rangeValue) && preg_match('/^(\d{4})-W(\d{2})$/', $rangeValue, $matches)) {
            $candidateYear = (int) $matches[1];
            $candidateWeek = (int) $matches[2];

            if ($candidateWeek >= 1 && $candidateWeek <= 53) {
                $candidateDate = Carbon::now()->setISODate($candidateYear, $candidateWeek, 1);

                if ((int) $candidateDate->isoWeekYear === $candidateYear && (int) $candidateDate->isoWeek === $candidateWeek) {
                    $isoYear = $candidateYear;
                    $isoWeek = $candidateWeek;
                }
            }
        }

        $startAt = Carbon::now()->setISODate($isoYear, $isoWeek, 1)->startOfDay();
        $endAt = $startAt->copy()->addDays(6)->endOfDay();

        return [
            'week',
            sprintf('%04d-W%02d', $isoYear, $isoWeek),
            $startAt,
            $endAt,
            $startAt->format('d M Y') . ' - ' . $endAt->format('d M Y'),
        ];
    }

    private function resolveMonthRange(?string $rangeValue): array
    {
        $monthDate = Carbon::today()->startOfMonth();

        if (is_string($rangeValue) && preg_match('/^(\d{4})-(\d{2})$/', $rangeValue, $matches)) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];

            if ($year >= 1970 && $year <= 9999 && $month >= 1 && $month <= 12) {
                $monthDate = Carbon::create($year, $month, 1, 0, 0, 0);
            }
        }

        $startAt = $monthDate->copy()->startOfMonth()->startOfDay();
        $endAt = $monthDate->copy()->endOfMonth()->endOfDay();

        return [
            'month',
            $monthDate->format('Y-m'),
            $startAt,
            $endAt,
            $monthDate->format('F Y'),
        ];
    }

    private function resolveYearRange(?string $rangeValue): array
    {
        $year = Carbon::today()->year;

        if (is_string($rangeValue) && preg_match('/^(\d{4})$/', $rangeValue, $matches)) {
            $candidateYear = (int) $matches[1];

            if ($candidateYear >= 1970 && $candidateYear <= 9999) {
                $year = $candidateYear;
            }
        }

        $startAt = Carbon::create($year, 1, 1, 0, 0, 0)->startOfDay();
        $endAt = Carbon::create($year, 12, 31, 0, 0, 0)->endOfDay();

        return [
            'year',
            (string) $year,
            $startAt,
            $endAt,
            (string) $year,
        ];
    }
}

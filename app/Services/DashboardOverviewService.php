<?php

namespace App\Services;

use App\Models\BiometricAccessEvent;
use App\Models\CompanyAccountTransaction;
use App\Models\EventRegistration;
use App\Models\Member;
use App\Models\MemberPayment;
use App\Models\ProductVariation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WalletTopup;
use Illuminate\Support\Carbon;

class DashboardOverviewService
{
    private const LOW_STOCK_THRESHOLD = 5;

    private const SALES_RANGE_TYPES = ['date', 'week', 'month', 'year', 'date_range'];

    private const AUTH_WIDGET_LIMIT = 20;

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
            ->whereBetween('event_time', [$startAt, $endAt]);

        $summary['counts']['total'] = (int) (clone $baseQuery)->count();
        $summary['counts']['success'] = (int) (clone $baseQuery)
            ->where('result', 'success')
            ->whereNotNull('member_id')
            ->distinct()
            ->count('member_id');
        $summary['counts']['payment_expired'] = (int) (clone $baseQuery)
            ->where('result', 'failed')
            ->where('fail_reason', 'payment_expired')
            ->whereNotNull('member_id')
            ->distinct()
            ->count('member_id');
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

    public function buildStats(
        User $user,
        Tenant $tenant,
        string $rangeType = 'date',
        ?string $rangeValue = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): array {
        [$normalizedRangeType, $normalizedRangeValue, $startAt, $endAt, $rangeLabel] = $this->resolveSalesRange($rangeType, $rangeValue, $startDate, $endDate);

        $canViewSalesSummary = $user->hasPermission('sales.process');
        $canViewAccountSummary = $user->hasPermission('accounts.manage');

        $stats = [
            'can_view' => $canViewSalesSummary || $canViewAccountSummary,
            'can_view_sales' => $canViewSalesSummary,
            'can_view_accounts' => $canViewAccountSummary,
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
            'cash_flow_summary' => [
                'income' => 0,
                'expense' => 0,
                'net_movement' => 0,
                'income_count' => 0,
                'expense_count' => 0,
            ],
            'account_transaction_list' => [],
        ];

        if ($canViewSalesSummary) {
            $stats = array_merge(
                $stats,
                $this->buildSalesStatsData($tenant->id, $startAt, $endAt),
            );
        }

        if ($canViewAccountSummary) {
            $stats['cash_flow_summary'] = $this->buildCashFlowTotals($tenant->id, $startAt, $endAt);
            $stats['account_transaction_list'] = $this->buildAccountTransactionListForRange($tenant->id, $startAt, $endAt);
        }

        return $stats;
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
            ->leftJoin('products', 'products.id', '=', 'product_variations.product_id')
            ->leftJoin('stock_entries', function ($join) use ($today) {
                $join->on('stock_entries.product_variation_id', '=', 'product_variations.id')
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
            'transactions' => [],
        ];

        if (!$canViewAccounts) {
            return $summary;
        }

        $summary = array_merge($summary, $this->buildCashFlowTotals($tenantId, $startAt, $endAt));
        $summary['transactions'] = $this->buildAccountTransactionListForRange($tenantId, $startAt, $endAt);

        return $summary;
    }

    private function buildCashFlowTotals(int $tenantId, Carbon $startAt, Carbon $endAt): array
    {
        $baseQuery = $this->accountTransactionRangeQuery($tenantId, $startAt, $endAt);
        $incomeQuery = (clone $baseQuery)->where('amount', '>', 0);
        $expenseQuery = (clone $baseQuery)->where('amount', '<', 0);
        $income = round((float) (clone $incomeQuery)->sum('amount'), 2);
        $expense = round(abs((float) (clone $expenseQuery)->sum('amount')), 2);

        return [
            'income' => $income,
            'expense' => $expense,
            'net_movement' => round($income - $expense, 2),
            'income_count' => (int) (clone $incomeQuery)->count(),
            'expense_count' => (int) (clone $expenseQuery)->count(),
        ];
    }

    private function buildAccountTransactionListForRange(int $tenantId, Carbon $startAt, Carbon $endAt): array
    {
        $transactions = $this->accountTransactionRangeQuery($tenantId, $startAt, $endAt)
            ->with('account:id,name')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();
        $sourceDetails = $this->buildTransactionSourceDetailsMap($transactions);

        return $transactions
            ->map(fn (CompanyAccountTransaction $transaction) => [
                'id' => (int) $transaction->id,
                'amount' => (float) $transaction->amount,
                'transaction_date' => $transaction->transaction_date?->toDateString(),
                'account_name' => (string) ($transaction->account?->name ?? 'Account'),
                'type' => (string) ($transaction->type ?? ''),
                'reference_number' => $transaction->reference_number,
                'notes' => $transaction->notes,
                'source_label' => $this->transactionSourceLabel(
                    $transaction,
                    $sourceDetails[$this->transactionSourceKey($transaction)] ?? null,
                ),
                'source_path' => $this->transactionSourcePath($transaction),
            ])
            ->values()
            ->all();
    }

    private function buildTransactionSourceDetailsMap($transactions): array
    {
        $referenceIdsByModel = $transactions
            ->filter(fn (CompanyAccountTransaction $transaction) => $transaction->reference_id)
            ->groupBy(fn (CompanyAccountTransaction $transaction) => (string) $transaction->model_name)
            ->map(fn ($modelTransactions) => $modelTransactions->pluck('reference_id')->filter()->unique()->values());

        $membersById = collect();
        $detailsBySourceKey = [];

        $payments = MemberPayment::query()
            ->whereIn('id', $referenceIdsByModel->get('payment', collect()))
            ->with(['member:id,name', 'membership:id,member_payment_id'])
            ->get();

        foreach ($payments as $payment) {
            if ($payment->member) {
                $detailsBySourceKey['payment:' . $payment->id] = [
                    'member_id' => (int) $payment->member->id,
                    'is_membership_payment' => (bool) $payment->membership,
                ];
                $membersById->put($payment->member->id, $payment->member);
            }
        }

        $walletTopups = WalletTopup::query()
            ->whereIn('id', $referenceIdsByModel->get('wallet_topup', collect()))
            ->with('member:id,name')
            ->get();

        foreach ($walletTopups as $walletTopup) {
            if ($walletTopup->member) {
                $detailsBySourceKey['wallet_topup:' . $walletTopup->id] = [
                    'member_id' => (int) $walletTopup->member->id,
                ];
                $membersById->put($walletTopup->member->id, $walletTopup->member);
            }
        }

        $eventRegistrations = EventRegistration::query()
            ->whereIn('id', $referenceIdsByModel->get('event_registration', collect()))
            ->with('member:id,name')
            ->get();

        foreach ($eventRegistrations as $registration) {
            if ($registration->member) {
                $detailsBySourceKey['event_registration:' . $registration->id] = [
                    'member_id' => (int) $registration->member->id,
                ];
                $membersById->put($registration->member->id, $registration->member);
            }
        }

        $sales = Sale::query()
            ->whereIn('id', $referenceIdsByModel->get('sale', collect()))
            ->whereNotNull('customer_member_id')
            ->get(['id', 'customer_member_id']);

        $saleMemberIds = $sales->pluck('customer_member_id')->filter()->unique()->values();

        if ($saleMemberIds->isNotEmpty()) {
            Member::query()
                ->whereIn('id', $saleMemberIds)
                ->get(['id', 'name'])
                ->each(fn (Member $member) => $membersById->put($member->id, $member));
        }

        foreach ($sales as $sale) {
            if ($sale->customer_member_id) {
                $detailsBySourceKey['sale:' . $sale->id] = [
                    'member_id' => (int) $sale->customer_member_id,
                ];
            }
        }

        return collect($detailsBySourceKey)
            ->map(function (array $details) use ($membersById) {
                $member = $membersById->get($details['member_id']);

                return $member
                    ? array_merge($details, [
                        'member' => [
                            'id' => (int) $member->id,
                            'name' => (string) $member->name,
                        ],
                    ])
                    : null;
            })
            ->filter()
            ->all();
    }

    private function accountTransactionRangeQuery(int $tenantId, Carbon $startAt, Carbon $endAt)
    {
        return CompanyAccountTransaction::query()
            ->whereDate('transaction_date', '>=', $startAt->toDateString())
            ->whereDate('transaction_date', '<=', $endAt->toDateString());
    }

    private function transactionSourceLabel(CompanyAccountTransaction $transaction, ?array $sourceDetails = null): string
    {
        $source = self::TRANSACTION_SOURCE_LABELS[$transaction->model_name]
            ?? ucwords(str_replace('_', ' ', (string) ($transaction->model_name ?: $transaction->type ?: 'Transaction')));
        $memberName = $sourceDetails['member']['name'] ?? null;

        if ($memberName) {
            $source = match ($transaction->model_name) {
                'payment' => ($sourceDetails['is_membership_payment'] ?? false) ? "{$memberName}'s Membership Payment" : "{$memberName}'s Payment",
                'sale' => "{$memberName}'s Sale",
                'wallet_topup' => "{$memberName}'s Wallet Top-up",
                'event_registration' => "{$memberName}'s Event Registration",
                default => "{$memberName}'s {$source}",
            };
        }

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
            'expense' => '/accounting/expenses/' . $transaction->reference_id,
            'payment' => '/accounting/payments/' . $transaction->reference_id,
            'wallet_topup' => '/wallet-topups/' . $transaction->reference_id,
            default => null,
        };
    }

    private function transactionSourceKey(CompanyAccountTransaction $transaction): string
    {
        return (string) $transaction->model_name . ':' . (string) $transaction->reference_id;
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

    private function resolveSalesRange(string $rangeType, ?string $rangeValue, ?string $startDate = null, ?string $endDate = null): array
    {
        $normalizedRangeType = in_array($rangeType, self::SALES_RANGE_TYPES, true)
            ? $rangeType
            : 'date';

        return match ($normalizedRangeType) {
            'week' => $this->resolveWeekRange($rangeValue),
            'month' => $this->resolveMonthRange($rangeValue),
            'year' => $this->resolveYearRange($rangeValue),
            'date_range' => $this->resolveCustomDateRange($startDate, $endDate),
            default => $this->resolveDateRange($rangeValue),
        };
    }

    private function resolveCustomDateRange(?string $startDate, ?string $endDate): array
    {
        $start = $this->resolveDateString($startDate) ?? Carbon::today();
        $end = $this->resolveDateString($endDate) ?? $start->copy();

        if ($end->lt($start)) {
            $end = $start->copy();
        }

        $startAt = $start->copy()->startOfDay();
        $endAt = $end->copy()->endOfDay();
        $rangeLabel = $startAt->toDateString() === $endAt->toDateString()
            ? $startAt->format('d M Y')
            : $startAt->format('d M Y') . ' - ' . $endAt->format('d M Y');

        return [
            'date_range',
            $startAt->toDateString() . '_' . $endAt->toDateString(),
            $startAt,
            $endAt,
            $rangeLabel,
        ];
    }

    private function resolveDateString(?string $date): ?Carbon
    {
        if (is_string($date) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];
            $day = (int) $matches[3];

            if (checkdate($month, $day, $year)) {
                return Carbon::create($year, $month, $day, 0, 0, 0);
            }
        }

        return null;
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

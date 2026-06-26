<?php

namespace App\Services;

use App\Models\CompanyAccount;
use App\Models\CompanyAccountTransaction;
use App\Models\CompanyAccountTransfer;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\StockEntry;
use Illuminate\Support\Carbon;

class DailySummaryService
{
    /**
     * Human-friendly labels for the various transaction model_name values.
     */
    private const MODEL_LABELS = [
        'sale' => 'Sales',
        'payment' => 'Member Payments',
        'wallet_topup' => 'Wallet Top-ups',
        'event_registration' => 'Event Registrations',
        'expense' => 'Expenses',
    ];

    public function build(int $tenantId, ?string $date): array
    {
        $day = $this->resolveDate($date);
        $dateStr = $day->toDateString();
        $startOf = $day->copy()->startOfDay();
        $endOf = $day->copy()->endOfDay();

        $accounts = $this->buildAccounts($tenantId, $dateStr, $startOf);
        $income = $this->buildIncome($tenantId, $dateStr);
        $expense = $this->buildExpense($tenantId, $dateStr);
        $stock = $this->buildStock($tenantId, $dateStr, $startOf, $endOf);

        $totals = [
            'opening_balance' => round($accounts->sum('opening_balance'), 2),
            'income' => round($income['total'], 2),
            'expense' => round($expense['total'], 2),
        ];
        $totals['closing_balance'] = round(
            $totals['opening_balance'] + $totals['income'] - $totals['expense'],
            2,
        );
        $totals['net_movement'] = round($totals['income'] - $totals['expense'], 2);
        $totals['stock_on_hand'] = $stock['on_hand_value'];

        return [
            'date' => $dateStr,
            'date_label' => $day->format('d M Y'),
            'is_today' => $dateStr === Carbon::today()->toDateString(),
            'accounts' => $accounts->values()->all(),
            'income' => $income,
            'expense' => $expense,
            'stock' => $stock,
            'totals' => $totals,
        ];
    }

    // ───────────────────────── Accounts ─────────────────────────────────────

    private function buildAccounts(int $tenantId, string $dateStr, Carbon $startOf): \Illuminate\Support\Collection
    {
        $accounts = CompanyAccount::query()
            ->orderBy('name')
            ->get(['id', 'name', 'opening_balance']);

        return $accounts->map(function (CompanyAccount $account) use ($dateStr) {
            // Opening balance = base opening + everything that happened BEFORE this day.
            $priorTx = (float) CompanyAccountTransaction::where('company_account_id', $account->id)
                ->whereDate('transaction_date', '<', $dateStr)
                ->sum('amount');

            $priorIn = (float) CompanyAccountTransfer::where('destination_account_id', $account->id)
                ->whereDate('transfer_date', '<', $dateStr)
                ->sum('amount');

            $priorOut = (float) CompanyAccountTransfer::where('source_account_id', $account->id)
                ->whereDate('transfer_date', '<', $dateStr)
                ->sum('amount');

            $opening = (float) $account->opening_balance + $priorTx + $priorIn - $priorOut;

            // Movements during the day.
            $dayIn = (float) CompanyAccountTransaction::where('company_account_id', $account->id)
                ->whereDate('transaction_date', $dateStr)
                ->where('amount', '>', 0)
                ->sum('amount');

            $dayOut = (float) CompanyAccountTransaction::where('company_account_id', $account->id)
                ->whereDate('transaction_date', $dateStr)
                ->where('amount', '<', 0)
                ->sum('amount'); // negative

            $transferIn = (float) CompanyAccountTransfer::where('destination_account_id', $account->id)
                ->whereDate('transfer_date', $dateStr)
                ->sum('amount');

            $transferOut = (float) CompanyAccountTransfer::where('source_account_id', $account->id)
                ->whereDate('transfer_date', $dateStr)
                ->sum('amount');

            $income = $dayIn + $transferIn;
            $expense = abs($dayOut) + $transferOut;
            $closing = $opening + $income - $expense;

            return [
                'id' => $account->id,
                'name' => $account->name,
                'opening_balance' => round($opening, 2),
                'income' => round($income, 2),
                'expense' => round($expense, 2),
                'transfer_in' => round($transferIn, 2),
                'transfer_out' => round($transferOut, 2),
                'closing_balance' => round($closing, 2),
            ];
        });
    }

    // ───────────────────────── Income ───────────────────────────────────────

    private function buildIncome(int $tenantId, string $dateStr): array
    {
        // Positive transactions grouped by source.
        $grouped = CompanyAccountTransaction::query()
            ->whereDate('transaction_date', $dateStr)
            ->where('amount', '>', 0)
            ->selectRaw('model_name, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('model_name')
            ->get();

        $breakdown = $grouped->map(fn ($row) => [
            'key' => $row->model_name,
            'label' => $this->modelLabel($row->model_name),
            'count' => (int) $row->count,
            'amount' => round((float) $row->total, 2),
        ])->sortByDesc('amount')->values()->all();

        $total = round(array_sum(array_column($breakdown, 'amount')), 2);

        $transactions = CompanyAccountTransaction::with('account:id,name')
            ->whereDate('transaction_date', $dateStr)
            ->where('amount', '>', 0)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($tx) => [
                'id' => $tx->id,
                'account' => $tx->account?->name ?? '—',
                'label' => $this->modelLabel($tx->model_name),
                'reference' => $tx->reference_number,
                'notes' => $tx->notes,
                'amount' => round((float) $tx->amount, 2),
            ])->all();

        return [
            'total' => $total,
            'breakdown' => $breakdown,
            'transactions' => $transactions,
        ];
    }

    // ───────────────────────── Expense ──────────────────────────────────────

    private function buildExpense(int $tenantId, string $dateStr): array
    {
        $grouped = CompanyAccountTransaction::query()
            ->whereDate('transaction_date', $dateStr)
            ->where('amount', '<', 0)
            ->selectRaw('model_name, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('model_name')
            ->get();

        $breakdown = $grouped->map(fn ($row) => [
            'key' => $row->model_name,
            'label' => $this->modelLabel($row->model_name),
            'count' => (int) $row->count,
            'amount' => round(abs((float) $row->total), 2),
        ])->sortByDesc('amount')->values()->all();

        $total = round(array_sum(array_column($breakdown, 'amount')), 2);

        $transactions = CompanyAccountTransaction::with('account:id,name')
            ->whereDate('transaction_date', $dateStr)
            ->where('amount', '<', 0)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($tx) => [
                'id' => $tx->id,
                'account' => $tx->account?->name ?? '—',
                'label' => $this->modelLabel($tx->model_name),
                'reference' => $tx->reference_number,
                'notes' => $tx->notes,
                'amount' => round(abs((float) $tx->amount), 2),
            ])->all();

        return [
            'total' => $total,
            'breakdown' => $breakdown,
            'transactions' => $transactions,
        ];
    }

    // ───────────────────────── Stock ────────────────────────────────────────

    /**
     * Reconstructs per-product stock movement for the day.
     *
     * Live stock (StockEntry.quantity) is mutated on every sale, so the closing
     * balance is anchored to the current on-hand value and rolled back through
     * any movements that happened AFTER the selected day. The opening balance is
     * then derived from the day's net movement (received − sold), mirroring the
     * convention already used by the reconciliation feature.
     */
    private function buildStock(int $tenantId, string $dateStr, Carbon $startOf, Carbon $endOf): array
    {
        // Current live on-hand per product (quantity + cost value).
        $current = StockEntry::query()
            ->selectRaw('product_id, SUM(quantity) as qty, SUM(quantity * purchasing_price) as value')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        // Units sold during the day (immutable from sale_items).
        $soldDay = $this->soldByProduct($tenantId, fn ($q) => $q->whereDate('sales.created_at', $dateStr));
        // Revenue earned from those sold units during the day.
        $revenueDay = $this->revenueByProduct($tenantId, $dateStr);
        // Units received during the day (stock entries created that day).
        $receivedDay = StockEntry::query()
            ->whereDate('created_at', $dateStr)
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        // Movements AFTER the selected day, used to roll the live stock back.
        $soldAfter = $this->soldByProduct($tenantId, fn ($q) => $q->where('sales.created_at', '>', $endOf));
        $receivedAfter = StockEntry::query()
            ->where('created_at', '>', $endOf)
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $movements = [];
        $totals = ['opening' => 0.0, 'received' => 0.0, 'sold' => 0.0, 'closing' => 0.0, 'revenue' => 0.0];

        foreach ($products as $product) {
            $currentQty = (float) ($current[$product->id]->qty ?? 0);
            $sAfter = (float) ($soldAfter[$product->id] ?? 0);
            $rAfter = (float) ($receivedAfter[$product->id] ?? 0);
            $received = (float) ($receivedDay[$product->id] ?? 0);
            $sold = (float) ($soldDay[$product->id] ?? 0);
            $revenue = (float) ($revenueDay[$product->id] ?? 0);

            $closing = $currentQty + $sAfter - $rAfter;
            $opening = $closing - ($received - $sold);

            // Skip products with no stock and no movement on this day.
            if ($opening == 0.0 && $received == 0.0 && $sold == 0.0 && $closing == 0.0) {
                continue;
            }

            $movements[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'opening' => round($opening, 2),
                'received' => round($received, 2),
                'sold' => round($sold, 2),
                'closing' => round($closing, 2),
                'revenue' => round($revenue, 2),
            ];

            $totals['opening'] += $opening;
            $totals['received'] += $received;
            $totals['sold'] += $sold;
            $totals['closing'] += $closing;
            $totals['revenue'] += $revenue;
        }

        return [
            'on_hand_value' => round((float) $current->sum('value'), 2),
            'totals' => [
                'opening' => round($totals['opening'], 2),
                'received' => round($totals['received'], 2),
                'sold' => round($totals['sold'], 2),
                'closing' => round($totals['closing'], 2),
                'revenue' => round($totals['revenue'], 2),
            ],
            'movements' => $movements,
        ];
    }

    private function soldByProduct(int $tenantId, callable $constrain)
    {
        $query = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereNull('sales.deleted_at')
            ->selectRaw('sale_items.product_id, SUM(sale_items.quantity) as qty')
            ->groupBy('sale_items.product_id');

        $constrain($query);

        return $query->pluck('qty', 'product_id');
    }

    private function revenueByProduct(int $tenantId, string $dateStr)
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereNull('sales.deleted_at')
            ->whereDate('sales.created_at', $dateStr)
            ->selectRaw('sale_items.product_id, SUM(sale_items.subtotal) as revenue')
            ->groupBy('sale_items.product_id')
            ->pluck('revenue', 'product_id');
    }

    // ───────────────────────── Helpers ──────────────────────────────────────

    private function modelLabel(?string $modelName): string
    {
        if (blank($modelName)) {
            return 'Other';
        }

        return self::MODEL_LABELS[$modelName]
            ?? ucwords(str_replace('_', ' ', $modelName));
    }

    private function resolveDate(?string $date): Carbon
    {
        if (is_string($date) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)) {
            $year = (int) $m[1];
            $month = (int) $m[2];
            $day = (int) $m[3];

            if (checkdate($month, $day, $year)) {
                return Carbon::create($year, $month, $day, 0, 0, 0);
            }
        }

        return Carbon::today();
    }
}

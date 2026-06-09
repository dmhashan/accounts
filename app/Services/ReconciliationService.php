<?php

namespace App\Services;

use App\Models\CompanyAccount;
use App\Models\CompanyAccountTransaction;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ReconciliationConfig;
use App\Models\ReconciliationEntry;
use App\Models\ReconciliationSession;
use App\Models\Role;
use App\Models\SaleItem;
use App\Models\StockEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReconciliationService
{
    // ───────────────────────── Admin Config ─────────────────────────────────

    public function getAdminConfig(int $tenantId): array
    {
        $roles = Role::withCount('users')->orderBy('name')->get();

        $accounts = CompanyAccount::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $configs = ReconciliationConfig::where('tenant_id', $tenantId)
            ->where('type', 'account')
            ->get()
            ->groupBy('role_id');

        return [
            'roles' => $roles->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'slug' => $r->slug]),
            'accounts' => $accounts->map(fn ($a) => ['id' => $a->id, 'name' => $a->name]),
            'configs' => $configs->map(fn ($group) => $group->map(fn ($c) => [
                'id' => $c->id,
                'role_id' => $c->role_id,
                'type' => $c->type,
                'reference_id' => $c->reference_id,
                'is_active' => $c->is_active,
            ])->values())->toArray(),
        ];
    }

    public function saveAdminConfig(int $tenantId, int $roleId, array $items): void
    {
        DB::transaction(function () use ($tenantId, $roleId, $items) {
            // items: [['type' => 'account', 'reference_id' => int, 'is_active' => bool]]
            // Stock is not configurable — all products are always included, so skip any stock items.
            foreach ($items as $item) {
                if ($item['type'] !== 'account') {
                    continue;
                }
                ReconciliationConfig::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'role_id' => $roleId,
                        'type' => $item['type'],
                        'reference_id' => $item['reference_id'],
                    ],
                    ['is_active' => $item['is_active'] ?? true],
                );
            }
        });
    }

    // ────────────────────────── Session Status ───────────────────────────────

    public function getTodaySession(int $tenantId): ?array
    {
        $session = ReconciliationSession::where('tenant_id', $tenantId)
            ->whereDate('date', Carbon::today()->toDateString())
            ->first();

        return $session ? $this->serializeSession($session) : null;
    }

    // ─────────────────────────── Open Session ────────────────────────────────

    /**
     * Returns the configured items for a given role so the UI can pre-populate
     * the opening form.
     */
    public function getFormConfig(int $tenantId, int $roleId): array
    {
        $configs = ReconciliationConfig::where('tenant_id', $tenantId)
            ->where('role_id', $roleId)
            ->where('type', 'account')
            ->where('is_active', true)
            ->get();

        $accountIds = $configs->pluck('reference_id');

        $accounts = CompanyAccount::whereIn('id', $accountIds)
            ->where('tenant_id', $tenantId)
            ->withSum('incomingTransfers as incoming_total', 'amount')
            ->withSum('outgoingTransfers as outgoing_total', 'amount')
            ->withSum('transactions as transaction_total', 'amount')
            ->get()
            ->map(function ($a) {
                $currentBalance = round(
                    (float) $a->opening_balance
                    + (float) ($a->incoming_total ?? 0)
                    + (float) ($a->transaction_total ?? 0)
                    - (float) ($a->outgoing_total ?? 0),
                    2,
                );

                return ['id' => $a->id, 'name' => $a->name, 'current_value' => $currentBalance];
            });

        // Products with variations → one item per variation (type=stock_variation, reference_id=variation_id).
        // Products with no variations → one item per product    (type=stock,             reference_id=product_id).
        $productList = Product::where('tenant_id', $tenantId)
            ->with('variations:id,product_id,name')
            ->orderBy('name')
            ->get(['id', 'name']);

        $productIds = $productList->pluck('id');
        $variationIds = $productList->flatMap(fn ($p) => $p->variations->pluck('id'));

        // Back-room stock (quantity field)
        $productStockTotals = StockEntry::where('tenant_id', $tenantId)
            ->whereIn('product_id', $productIds)
            ->whereNull('product_variation_id')
            ->selectRaw('product_id, SUM(quantity) as total_qty')
            ->groupBy('product_id')
            ->pluck('total_qty', 'product_id');

        $variationStockTotals = StockEntry::where('tenant_id', $tenantId)
            ->whereIn('product_variation_id', $variationIds)
            ->selectRaw('product_variation_id, SUM(quantity) as total_qty')
            ->groupBy('product_variation_id')
            ->pluck('total_qty', 'product_variation_id');

        // Display stock (display_quantity field)
        $productDisplayTotals = StockEntry::where('tenant_id', $tenantId)
            ->whereIn('product_id', $productIds)
            ->whereNull('product_variation_id')
            ->selectRaw('product_id, SUM(display_quantity) as total_qty')
            ->groupBy('product_id')
            ->pluck('total_qty', 'product_id');

        $variationDisplayTotals = StockEntry::where('tenant_id', $tenantId)
            ->whereIn('product_variation_id', $variationIds)
            ->selectRaw('product_variation_id, SUM(display_quantity) as total_qty')
            ->groupBy('product_variation_id')
            ->pluck('total_qty', 'product_variation_id');

        $stockItems = [];

        foreach ($productList as $product) {
            if ($product->variations->isEmpty()) {
                $stockItems[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'type' => 'stock',
                    'current_value' => round((float) ($productStockTotals[$product->id] ?? 0), 2),
                    'current_display_value' => round((float) ($productDisplayTotals[$product->id] ?? 0), 2),
                ];
            } else {
                foreach ($product->variations as $variation) {
                    $stockItems[] = [
                        'id' => $variation->id,
                        'name' => $product->name . ' – ' . $variation->name,
                        'type' => 'stock_variation',
                        'current_value' => round((float) ($variationStockTotals[$variation->id] ?? 0), 2),
                        'current_display_value' => round((float) ($variationDisplayTotals[$variation->id] ?? 0), 2),
                    ];
                }
            }
        }

        usort($stockItems, fn ($a, $b) => $b['current_value'] <=> $a['current_value']);

        return [
            'accounts' => $accounts->values(),
            'products' => collect($stockItems)->values(),
        ];
    }

    public function openSession(int $tenantId, int $userId, array $entries): array|string
    {
        $today = Carbon::today()->toDateString();

        $existing = ReconciliationSession::where('tenant_id', $tenantId)
            ->whereDate('date', $today)
            ->first();

        if ($existing) {
            return 'A reconciliation session for today already exists.';
        }

        return DB::transaction(function () use ($tenantId, $userId, $today, $entries) {
            $session = ReconciliationSession::create([
                'tenant_id' => $tenantId,
                'date' => $today,
                'status' => 'open',
                'opened_by' => $userId,
            ]);

            foreach ($entries as $entry) {
                ReconciliationEntry::create([
                    'session_id' => $session->id,
                    'type' => $entry['type'],
                    'reference_id' => $entry['reference_id'],
                    'stage' => 'open',
                    'entered_value' => $entry['entered_value'],
                ]);
            }

            return $this->serializeSession($session);
        });
    }

    // ──────────────────────── Close Preview ──────────────────────────────────

    /**
     * Computes expected closing values from system data for each open entry.
     */
    public function getClosePreview(ReconciliationSession $session): array
    {
        $session->load('entries');
        $openEntries = $session->entries->where('stage', 'open');
        $date = $session->date->toDateString();
        $tenantId = $session->tenant_id;

        $items = [];

        foreach ($openEntries as $entry) {
            $systemDelta = $this->calculateSystemDelta($tenantId, $entry->type, $entry->reference_id, $date);

            $label = $this->resolveLabel($tenantId, $entry->type, $entry->reference_id);

            $closeEntry = $session->entries->first(
                fn ($e) => $e->stage === 'close'
                    && $e->type === $entry->type
                    && $e->reference_id === $entry->reference_id,
            );

            $items[] = [
                'type' => $entry->type,
                'reference_id' => $entry->reference_id,
                'label' => $label,
                'opening_value' => (float) $entry->entered_value,
                'system_delta' => $systemDelta,
                'expected_close' => round((float) $entry->entered_value + $systemDelta, 2),
                'actual_close' => $closeEntry ? (float) $closeEntry->entered_value : null,
                'difference' => $closeEntry
                    ? round((float) $closeEntry->entered_value - ((float) $entry->entered_value + $systemDelta), 2)
                    : null,
            ];
        }

        return [
            'session' => $this->serializeSession($session),
            'items' => $items,
        ];
    }

    // ──────────────────────── Close Session ──────────────────────────────────

    public function closeSession(ReconciliationSession $session, int $userId, ?string $reason): array|string
    {
        if ($session->status === 'closed') {
            return 'This session is already closed.';
        }

        $session->load('entries');
        $openEntries = $session->entries->where('stage', 'open');

        // Ensure every open entry has a corresponding saved close entry.
        foreach ($openEntries as $openEntry) {
            $hasClose = $session->entries->contains(
                fn ($e) => $e->stage === 'close'
                    && $e->type === $openEntry->type
                    && $e->reference_id === $openEntry->reference_id,
            );

            if (!$hasClose) {
                return 'Please complete all closing values before confirming.';
            }
        }

        return DB::transaction(function () use ($session, $userId, $reason) {
            $session->update([
                'status' => 'closed',
                'closed_by' => $userId,
                'closed_at' => now(),
                'adjustment_reason' => $reason,
            ]);

            $session->load('entries');

            return $this->getClosePreview($session);
        });
    }

    // ─────────────────────── Save Close Entries ──────────────────────────────

    /**
     * Saves close entries without finalising, so user can "go back" and
     * re-enter values on the close form.
     */
    public function saveCloseEntries(ReconciliationSession $session, array $entries): void
    {
        DB::transaction(function () use ($session, $entries) {
            foreach ($entries as $entry) {
                ReconciliationEntry::updateOrCreate(
                    [
                        'session_id' => $session->id,
                        'type' => $entry['type'],
                        'reference_id' => $entry['reference_id'],
                        'stage' => 'close',
                    ],
                    ['entered_value' => $entry['entered_value']],
                );
            }
        });
    }

    // ─────────────────────────── History ─────────────────────────────────────

    public function history(int $tenantId, int $perPage): array
    {
        $sessions = ReconciliationSession::where('tenant_id', $tenantId)
            ->with(['opener', 'closer'])
            ->orderBy('date', 'desc')
            ->paginate($perPage);

        return [
            'data' => collect($sessions->items())->map(fn ($s) => $this->serializeSession($s)),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
            ],
        ];
    }

    public function showSession(ReconciliationSession $session, int $tenantId): array
    {
        if ($session->tenant_id !== $tenantId) {
            abort(404);
        }

        $session->load(['entries', 'opener', 'closer']);

        return $this->getClosePreview($session);
    }

    // ───────────────────────── Helpers ───────────────────────────────────────

    private function calculateSystemDelta(int $tenantId, string $type, int $referenceId, string $date): float
    {
        if ($type === 'account') {
            // Sum credits minus debits recorded in company_account_transactions on that date
            $credits = (float) CompanyAccountTransaction::where('company_account_id', $referenceId)
                ->where('tenant_id', $tenantId)
                ->whereDate('transaction_date', $date)
                ->where('type', 'credit')
                ->sum('amount');

            $debits = (float) CompanyAccountTransaction::where('company_account_id', $referenceId)
                ->where('tenant_id', $tenantId)
                ->whereDate('transaction_date', $date)
                ->where('type', 'debit')
                ->sum('amount');

            return round($credits - $debits, 2);
        }

        if ($type === 'stock') {
            // Product with no variations
            $sold = (float) SaleItem::whereHas('sale', function ($q) use ($tenantId, $date) {
                $q->where('tenant_id', $tenantId)
                    ->whereDate('created_at', $date)
                    ->whereNull('deleted_at');
            })
                ->where('product_id', $referenceId)
                ->whereNull('product_variation_id')
                ->sum('quantity');

            $received = (float) StockEntry::where('tenant_id', $tenantId)
                ->where('product_id', $referenceId)
                ->whereNull('product_variation_id')
                ->whereDate('created_at', $date)
                ->sum('quantity');

            return round($received - $sold, 2);
        }

        if ($type === 'stock_variation') {
            $sold = (float) SaleItem::whereHas('sale', function ($q) use ($tenantId, $date) {
                $q->where('tenant_id', $tenantId)
                    ->whereDate('created_at', $date)
                    ->whereNull('deleted_at');
            })
                ->where('product_variation_id', $referenceId)
                ->sum('quantity');

            $received = (float) StockEntry::where('tenant_id', $tenantId)
                ->where('product_variation_id', $referenceId)
                ->whereDate('created_at', $date)
                ->sum('quantity');

            return round($received - $sold, 2);
        }

        // Display stock types — no system-tracked movement, delta is always 0
        if ($type === 'stock_display' || $type === 'stock_variation_display') {
            return 0.0;
        }

        return 0.0;
    }

    private function resolveLabel(int $tenantId, string $type, int $referenceId): string
    {
        if ($type === 'account') {
            return CompanyAccount::where('id', $referenceId)
                ->where('tenant_id', $tenantId)
                ->value('name') ?? "Account #{$referenceId}";
        }

        if ($type === 'stock_variation' || $type === 'stock_variation_display') {
            $variation = ProductVariation::with('product:id,name')->find($referenceId);

            if ($variation) {
                $label = $variation->product->name . ' – ' . $variation->name;

                return $type === 'stock_variation_display' ? $label . ' (Display)' : $label;
            }

            return "Variation #{$referenceId}";
        }

        if ($type === 'stock_display') {
            return (Product::where('id', $referenceId)->where('tenant_id', $tenantId)->value('name') ?? "Product #{$referenceId}") . ' (Display)';
        }

        return Product::where('id', $referenceId)
            ->where('tenant_id', $tenantId)
            ->value('name') ?? "Product #{$referenceId}";
    }

    private function serializeSession(ReconciliationSession $session): array
    {
        return [
            'id' => $session->id,
            'date' => $session->date->toDateString(),
            'status' => $session->status,
            'opened_by' => $session->opener?->name ?? $session->opened_by,
            'closed_by' => $session->closer?->name ?? $session->closed_by,
            'closed_at' => $session->closed_at?->toISOString(),
            'adjustment_reason' => $session->adjustment_reason,
            'notes' => $session->notes,
        ];
    }
}

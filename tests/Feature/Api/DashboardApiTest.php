<?php

namespace Tests\Feature\Api;

use App\Models\BiometricAccessEvent;
use App\Models\CompanyAccountTransaction;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Carbon;

class DashboardApiTest extends ApiRouteTestCase
{
    public function testDashboardOverviewRouteReturnsIncomeExpenseSummaryPayload(): void
    {
        $this->actingAsUser([
            'dashboard.widget.cash_flow',
            'dashboard.widget.stock_availability',
        ]);

        $product = $this->createProduct(['name' => 'Protein Powder']);
        $variation = $this->createVariation($product, ['name' => 'Vanilla']);
        $this->createStockEntry($product, $variation, ['quantity' => 12]);

        $todaySale = $this->createSale([
            'customer_name' => 'Today Customer',
            'total_amount' => 250,
            'paid_amount' => 250,
            'balance' => 0,
        ]);
        $todaySale->forceFill([
            'created_at' => now()->startOfDay()->addHours(10),
            'updated_at' => now()->startOfDay()->addHours(10),
        ])->saveQuietly();

        $account = $this->createCompanyAccount();
        CompanyAccountTransaction::create([
            'company_account_id' => $account->id,
            'model_name' => 'sale',
            'reference_id' => $todaySale->id,
            'type' => 'sale',
            'amount' => 250,
            'transaction_date' => now()->toDateString(),
            'reference_number' => 'SALE-TODAY',
        ]);

        $yesterdaySale = $this->createSale([
            'customer_name' => 'Yesterday Customer',
            'total_amount' => 999,
            'paid_amount' => 999,
            'balance' => 0,
        ]);
        $yesterdaySale->forceFill([
            'created_at' => now()->subDay()->startOfDay(),
            'updated_at' => now()->subDay()->startOfDay(),
        ])->saveQuietly();

        $response = $this->getJson('/api/dashboard/overview');

        $response
            ->assertOk()
            ->assertJsonPath('tenant.id', $this->tenant->id)
            ->assertJsonPath('stock_summary.can_view', true)
            ->assertJsonPath('income_expense_summary.can_view', true)
            ->assertJsonPath('income_expense_summary.income', 250)
            ->assertJsonPath('income_expense_summary.expense', 0)
            ->assertJsonPath('income_expense_summary.net_movement', 250)
            ->assertJsonPath('income_expense_summary.transactions.0.source_path', '/sales/' . $todaySale->id);
    }

    public function testDashboardOverviewRouteSupportsASharedDateRange(): void
    {
        $this->actingAsUser([
            'dashboard.widget.cash_flow',
            'dashboard.widget.stock_availability',
        ]);

        $account = $this->createCompanyAccount();
        CompanyAccountTransaction::create([
            'company_account_id' => $account->id,
            'model_name' => 'payment',
            'type' => 'payment',
            'amount' => 400,
            'transaction_date' => now()->subDays(3)->toDateString(),
        ]);
        CompanyAccountTransaction::create([
            'company_account_id' => $account->id,
            'model_name' => 'expense',
            'type' => 'expense',
            'amount' => -125,
            'transaction_date' => now()->subDay()->toDateString(),
        ]);

        $response = $this->getJson(sprintf(
            '/api/dashboard/overview?start_date=%s&end_date=%s',
            now()->subDays(3)->toDateString(),
            now()->subDay()->toDateString(),
        ));

        $response
            ->assertOk()
            ->assertJsonPath('income_expense_summary.income', 400)
            ->assertJsonPath('income_expense_summary.expense', 125)
            ->assertJsonPath('income_expense_summary.net_movement', 275)
            ->assertJsonPath('income_expense_summary.start_date', now()->subDays(3)->toDateString())
            ->assertJsonPath('income_expense_summary.end_date', now()->subDay()->toDateString())
            ->assertJsonCount(2, 'income_expense_summary.transactions')
            ->assertJsonPath('stock_summary.selected_date', now()->subDay()->toDateString());
    }

    public function testDashboardAuthSummaryCountsMembersForSuccessAndPaymentExpiredBuckets(): void
    {
        $this->actingAsUser(['dashboard.widget.auth_details']);

        $memberA = $this->createMember();
        $memberB = $this->createMember();
        $memberExpired = $this->createMember();
        $eventTime = now()->startOfDay()->addHours(9);

        foreach ([$memberA, $memberA, $memberB] as $index => $member) {
            BiometricAccessEvent::create([
                'member_id' => $member->id,
                'biometric_member_id' => $member->biometric_member_id,
                'employee_no' => $member->biometric_member_id,
                'person_name' => $member->name,
                'auth_method' => 'face',
                'result' => 'success',
                'event_time' => $eventTime->copy()->addMinutes($index),
            ]);
        }

        foreach ([10, 11] as $minute) {
            BiometricAccessEvent::create([
                'member_id' => $memberExpired->id,
                'biometric_member_id' => $memberExpired->biometric_member_id,
                'employee_no' => $memberExpired->biometric_member_id,
                'person_name' => $memberExpired->name,
                'auth_method' => 'face',
                'result' => 'failed',
                'fail_reason' => 'payment_expired',
                'event_time' => $eventTime->copy()->addMinutes($minute),
            ]);
        }

        BiometricAccessEvent::create([
            'employee_no' => 'UNKNOWN-1',
            'person_name' => 'Unknown Member',
            'auth_method' => 'face',
            'result' => 'failed',
            'event_time' => $eventTime->copy()->addMinutes(20),
        ]);

        $response = $this->getJson('/api/dashboard/overview');

        $response
            ->assertOk()
            ->assertJsonPath('today_auth_summary.counts.total', 6)
            ->assertJsonPath('today_auth_summary.counts.success', 2)
            ->assertJsonPath('today_auth_summary.counts.payment_expired', 1)
            ->assertJsonPath('today_auth_summary.counts.other_failed', 1)
            ->assertJsonCount(3, 'today_auth_summary.lists.success_attempts')
            ->assertJsonCount(2, 'today_auth_summary.lists.payment_expired');
    }

    public function testDashboardStatsRouteSupportsSelectableDateWeekMonthYearRanges(): void
    {
        $this->actingAsUser([
            'sales.process',
        ]);

        $product = $this->createProduct(['name' => 'Test Product']);
        $variation = $this->createVariation($product, ['name' => 'Default']);

        $dateAnchor = now()->subDays(10)->startOfDay()->addHours(9);
        $weekAnchor = now()->subWeeks(4)->startOfWeek()->addDays(2)->addHours(11);
        $monthAnchor = now()->subMonths(2)->startOfMonth()->addDays(5)->addHours(14);
        $yearAnchor = now()->subYear()->startOfYear()->addMonths(2)->addDays(3)->addHours(15);

        $dateSale = $this->createSaleWithItem($dateAnchor, 'Date Customer', 110, 1, 110, $product->id, $variation->id);
        $weekSale = $this->createSaleWithItem($weekAnchor, 'Week Customer', 220, 2, 110, $product->id, $variation->id);
        $monthSale = $this->createSaleWithItem($monthAnchor, 'Month Customer', 330, 3, 110, $product->id, $variation->id);
        $yearSale = $this->createSaleWithItem($yearAnchor, 'Year Customer', 440, 4, 110, $product->id, $variation->id);

        $dateResponse = $this->getJson('/api/dashboard/stats?range_type=date&range_value=' . $dateAnchor->toDateString());

        $dateResponse
            ->assertOk()
            ->assertJsonPath('range_type', 'date')
            ->assertJsonPath('range_value', $dateAnchor->toDateString())
            ->assertJsonPath('transactions', 1)
            ->assertJsonPath('gross_amount', 110)
            ->assertJsonPath('paid_amount', 110)
            ->assertJsonPath('transaction_list.0.sale_id', $dateSale->id)
            ->assertJsonPath('customer_wise_sales.0.customer_name', 'Date Customer')
            ->assertJsonPath('product_wise_sales.0.quantity_sold', 1);

        $weekValue = sprintf('%04d-W%02d', (int) $weekAnchor->isoWeekYear, (int) $weekAnchor->isoWeek);
        $weekResponse = $this->getJson('/api/dashboard/stats?range_type=week&range_value=' . rawurlencode($weekValue));

        $weekResponse
            ->assertOk()
            ->assertJsonPath('range_type', 'week')
            ->assertJsonPath('range_value', $weekValue)
            ->assertJsonPath('transactions', 1)
            ->assertJsonPath('gross_amount', 220)
            ->assertJsonPath('transaction_list.0.sale_id', $weekSale->id)
            ->assertJsonPath('customer_wise_sales.0.customer_name', 'Week Customer')
            ->assertJsonPath('product_wise_sales.0.quantity_sold', 2);

        $monthValue = $monthAnchor->format('Y-m');
        $monthResponse = $this->getJson('/api/dashboard/stats?range_type=month&range_value=' . rawurlencode($monthValue));

        $monthResponse
            ->assertOk()
            ->assertJsonPath('range_type', 'month')
            ->assertJsonPath('range_value', $monthValue)
            ->assertJsonPath('transactions', 1)
            ->assertJsonPath('gross_amount', 330)
            ->assertJsonPath('transaction_list.0.sale_id', $monthSale->id)
            ->assertJsonPath('customer_wise_sales.0.customer_name', 'Month Customer')
            ->assertJsonPath('product_wise_sales.0.quantity_sold', 3);

        $yearValue = $yearAnchor->format('Y');
        $yearResponse = $this->getJson('/api/dashboard/stats?range_type=year&range_value=' . $yearValue);

        $yearResponse
            ->assertOk()
            ->assertJsonPath('range_type', 'year')
            ->assertJsonPath('range_value', $yearValue)
            ->assertJsonPath('transactions', 1)
            ->assertJsonPath('gross_amount', 440)
            ->assertJsonPath('transaction_list.0.sale_id', $yearSale->id)
            ->assertJsonPath('customer_wise_sales.0.customer_name', 'Year Customer')
            ->assertJsonPath('product_wise_sales.0.quantity_sold', 4);
    }

    public function testDashboardStatsRouteSupportsAccountTransactionsForCustomDateRange(): void
    {
        $this->actingAsUser(['dashboard.widget.cash_flow']);

        $account = $this->createCompanyAccount();
        CompanyAccountTransaction::create([
            'company_account_id' => $account->id,
            'model_name' => 'payment',
            'type' => 'payment',
            'amount' => 500,
            'transaction_date' => now()->subDays(2)->toDateString(),
        ]);
        CompanyAccountTransaction::create([
            'company_account_id' => $account->id,
            'model_name' => 'expense',
            'type' => 'expense',
            'amount' => -175,
            'transaction_date' => now()->subDay()->toDateString(),
        ]);

        $response = $this->getJson(sprintf(
            '/api/dashboard/stats?range_type=date_range&start_date=%s&end_date=%s',
            now()->subDays(2)->toDateString(),
            now()->subDay()->toDateString(),
        ));

        $response
            ->assertOk()
            ->assertJsonPath('range_type', 'date_range')
            ->assertJsonPath('can_view_accounts', true)
            ->assertJsonPath('cash_flow_summary.income', 500)
            ->assertJsonPath('cash_flow_summary.expense', 175)
            ->assertJsonPath('cash_flow_summary.net_movement', 325)
            ->assertJsonCount(2, 'account_transaction_list');
    }

    public function testDashboardStatsRouteAllowsStatisticsReportPermissionWithoutSalesOrAccountsPermissions(): void
    {
        $this->actingAsUser(['reports.statistics']);

        $product = $this->createProduct(['name' => 'Report Product']);
        $variation = $this->createVariation($product, ['name' => 'Default']);
        $sale = $this->createSaleWithItem(now()->startOfDay()->addHours(10), 'Report Customer', 320, 2, 160, $product->id, $variation->id);

        $account = $this->createCompanyAccount();
        CompanyAccountTransaction::create([
            'company_account_id' => $account->id,
            'model_name' => 'sale',
            'reference_id' => $sale->id,
            'type' => 'sale',
            'amount' => 320,
            'transaction_date' => now()->toDateString(),
        ]);

        $response = $this->getJson('/api/dashboard/stats?range_type=date&range_value=' . now()->toDateString());

        $response
            ->assertOk()
            ->assertJsonPath('can_view_sales', true)
            ->assertJsonPath('can_view_accounts', true)
            ->assertJsonPath('transactions', 1)
            ->assertJsonPath('gross_amount', 320)
            ->assertJsonPath('cash_flow_summary.income', 320)
            ->assertJsonCount(1, 'transaction_list')
            ->assertJsonCount(1, 'account_transaction_list');
    }

    private function createSaleWithItem(
        Carbon $createdAt,
        string $customerName,
        float $totalAmount,
        int $quantity,
        float $unitPrice,
        int $productId,
        int $variationId,
    ): Sale {
        $sale = $this->createSale([
            'customer_name' => $customerName,
            'total_amount' => $totalAmount,
            'paid_amount' => $totalAmount,
            'balance' => 0,
        ]);

        $sale->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $productId,
            'product_variation_id' => $variationId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $unitPrice * $quantity,
        ]);

        return $sale;
    }

    public function testDashboardOverviewRouteFiltersByAccounts(): void
    {
        $this->actingAsUser([
            'dashboard.widget.cash_flow',
        ]);

        $accountCash = $this->createCompanyAccount(['name' => 'Cash Account']);
        $accountBank = $this->createCompanyAccount(['name' => 'Bank Account']);

        // Create a cash transaction
        CompanyAccountTransaction::create([
            'company_account_id' => $accountCash->id,
            'model_name' => 'sale',
            'reference_id' => 10,
            'type' => 'sale',
            'amount' => 100,
            'transaction_date' => now()->toDateString(),
        ]);

        // Create a bank transaction
        CompanyAccountTransaction::create([
            'company_account_id' => $accountBank->id,
            'model_name' => 'sale',
            'reference_id' => 11,
            'type' => 'sale',
            'amount' => 200,
            'transaction_date' => now()->toDateString(),
        ]);

        // 1. Check without filter: returns both (100 + 200 = 300)
        $responseAll = $this->getJson('/api/dashboard/overview');
        $responseAll
            ->assertOk()
            ->assertJsonPath('income_expense_summary.income', 300)
            ->assertJsonCount(2, 'income_expense_summary.transactions')
            // Assert accounts list is returned in the response
            ->assertJsonPath('income_expense_summary.accounts.0.name', 'Bank Account')
            ->assertJsonPath('income_expense_summary.accounts.1.name', 'Cash Account');

        // 2. Check filtered by Cash Account only
        $responseCash = $this->getJson('/api/dashboard/overview?account_ids=' . $accountCash->id);
        $responseCash
            ->assertOk()
            ->assertJsonPath('income_expense_summary.income', 100)
            ->assertJsonCount(1, 'income_expense_summary.transactions')
            ->assertJsonPath('income_expense_summary.transactions.0.amount', 100);

        // 3. Check filtered by both Cash and Bank (comma-separated string)
        $responseBoth = $this->getJson('/api/dashboard/overview?account_ids=' . $accountCash->id . ',' . $accountBank->id);
        $responseBoth
            ->assertOk()
            ->assertJsonPath('income_expense_summary.income', 300)
            ->assertJsonCount(2, 'income_expense_summary.transactions');

        // 4. Check filtered by both Cash and Bank (array query parameters)
        $responseBothArray = $this->getJson('/api/dashboard/overview?account_ids[]=' . $accountCash->id . '&account_ids[]=' . $accountBank->id);
        $responseBothArray
            ->assertOk()
            ->assertJsonPath('income_expense_summary.income', 300)
            ->assertJsonCount(2, 'income_expense_summary.transactions');
    }
}

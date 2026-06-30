<?php

namespace Tests\Feature\Api;

use App\Jobs\SendDailySummaryReportJob;
use App\Models\CompanyAccountTransaction;
use App\Models\DailySummaryReport;
use App\Models\Expense;
use App\Models\MemberPayment;
use App\Models\PaymentMembership;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class ReportsApiTest extends ApiRouteTestCase
{
    public function testReportsOverviewRouteReturnsComingSoonPayload(): void
    {
        $this->actingAsUser(['reports.view']);

        $response = $this->getJson('/api/reports/overview');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'coming-soon')
            ->assertJsonCount(4, 'features');
    }

    public function testDailySummaryContainsFinancialData(): void
    {
        $this->actingAsUser(['reports.view']);
        $account = $this->createCompanyAccount(['opening_balance' => 100]);
        CompanyAccountTransaction::create([
            'company_account_id' => $account->id,
            'model_name' => 'payment',
            'type' => 'credit',
            'amount' => 250,
            'transaction_date' => today(),
        ]);

        $this->getJson('/api/reports/daily-summary?date=' . today()->toDateString())
            ->assertOk()
            ->assertJsonCount(1, 'accounts')
            ->assertJsonPath('accounts.0.id', $account->id)
            ->assertJsonPath('income.total', 250)
            ->assertJsonPath('totals.closing_balance', 350);
    }

    public function testRealProfitReportCombinesMembershipSalesMarginAndExpenses(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 10:00:00'));

        try {
            $this->actingAsUser(['reports.view', 'sales.process', 'sales.create']);
            $account = $this->createCompanyAccount(['name' => 'Cash']);
            $member = $this->createMember();
            $plan = $this->createPaymentPlan(['name' => 'Monthly', 'price' => 1200]);

            $membershipPayment = MemberPayment::create([
                'member_id' => $member->id,
                'company_account_id' => $account->id,
                'payment_method' => 'cash',
                'amount' => 1200,
                'payment_date' => '2026-06-05',
            ]);

            PaymentMembership::create([
                'member_payment_id' => $membershipPayment->id,
                'payment_plan_id' => $plan->id,
                'start_date' => '2026-06-05',
                'end_date' => '2026-07-04',
            ]);

            MemberPayment::create([
                'member_id' => $member->id,
                'company_account_id' => $account->id,
                'payment_method' => 'cash',
                'amount' => 500,
                'payment_date' => '2026-06-06',
            ]);

            $product = $this->createProduct(['name' => 'Protein Bar']);
            $variation = $this->createVariation($product, ['name' => 'Chocolate']);
            $this->createStockEntry($product, $variation, [
                'quantity' => 10,
                'display_quantity' => 10,
                'purchasing_price' => 60,
                'local_selling_price' => 100,
            ]);

            $this->postJson('/api/sales', [
                'customer_name' => 'Retail Customer',
                'customer_type' => 'local',
                'payment_method' => 'cash',
                'reference_number' => 'SALE-001',
                'paid_amount' => 0,
                'account_id' => $account->id,
                'is_paid' => true,
                'items' => [
                    ['product_variation_id' => $variation->id, 'quantity' => 2],
                ],
            ])->assertCreated();

            Expense::create([
                'company_account_id' => $account->id,
                'category' => 'Rent',
                'amount' => 300,
                'expense_date' => '2026-06-20',
            ]);

            Expense::create([
                'company_account_id' => $account->id,
                'category' => 'Outside Month',
                'amount' => 999,
                'expense_date' => '2026-05-31',
            ]);

            $response = $this->getJson('/api/reports/real-profit?month=2026-06')
                ->assertOk()
                ->assertJsonPath('month', '2026-06')
                ->assertJsonPath('summary.membership_income', 1200)
                ->assertJsonPath('summary.membership_count', 1)
                ->assertJsonPath('summary.sales_revenue', 200)
                ->assertJsonPath('summary.sales_cost', 120)
                ->assertJsonPath('summary.sales_profit', 80)
                ->assertJsonPath('summary.expenses', 300)
                ->assertJsonPath('summary.real_profit', 980)
                ->assertJsonPath('summary.estimated_cost_items', 0)
                ->assertJsonPath('summary.missing_cost_items', 0)
                ->assertJsonPath('sales_items.0.cost_source', 'exact')
                ->assertJsonPath('sales_by_product.0.product_name', 'Protein Bar')
                ->assertJsonCount(1, 'membership_payments')
                ->assertJsonCount(1, 'expenses');

            $this->assertEqualsWithDelta(980.0, (float) $response->json('summary.real_profit'), 0.001);
        } finally {
            $this->travelBack();
        }
    }

    public function testDailySummaryHistoryShowAndPdf(): void
    {
        $disk = (string) config('filesystems.media_disk', 'public');
        Storage::fake($disk);
        $this->actingAsUser(['reports.view']);
        $report = $this->createReport($this->tenant->id, 'reports/current.pdf');
        Storage::disk($disk)->put($report->pdf_path, '%PDF-current');

        $this->getJson('/api/reports/daily-summary/history')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $report->id);

        $this->getJson('/api/reports/daily-summary/reports/' . $report->id)
            ->assertOk()
            ->assertJsonPath('id', $report->id);
        $this->get('/api/reports/daily-summary/reports/' . $report->id . '/pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function testDailySummaryGenerationStoresTenantFilesAndQueuesTenantJob(): void
    {
        Queue::fake();
        $disk = (string) config('filesystems.media_disk', 'public');
        Storage::fake($disk);
        $this->actingAsUser(['reports.view']);
        $image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL9WQAAAABJRU5ErkJggg==';

        $reportId = (int) $this->postJson('/api/reports/daily-summary/generate', [
            'date' => today()->toDateString(),
            'prepared_by_name' => 'Test Admin',
            'signature' => $image,
            'selfie' => $image,
            'accounts' => [],
            'stock' => [],
        ])->assertCreated()
            ->assertJsonPath('report.prepared_by_name', 'Test Admin')
            ->json('report.id');

        $report = DailySummaryReport::findOrFail($reportId);
        $this->assertStringContainsString($this->tenant->tenant_uuid, $report->signature_path);
        $this->assertStringContainsString($this->tenant->tenant_uuid, $report->selfie_path);
        $this->assertStringContainsString($this->tenant->tenant_uuid, $report->pdf_path);
        Storage::disk($disk)->assertExists($report->signature_path);
        Storage::disk($disk)->assertExists($report->selfie_path);
        Storage::disk($disk)->assertExists($report->pdf_path);
        Queue::assertPushed(
            SendDailySummaryReportJob::class,
            fn (SendDailySummaryReportJob $job) => $this->readPrivate($job, 'tenantId') === $this->tenant->id
                && $this->readPrivate($job, 'reportId') === $report->id,
        );
    }

    private function createReport(int $tenantId, string $pdfPath): DailySummaryReport
    {
        return DailySummaryReport::create([
            'report_date' => today(),
            'prepared_by_name' => 'Admin',
            'system_snapshot' => [],
            'final_snapshot' => [],
            'changes' => [],
            'totals' => [],
            'pdf_path' => $pdfPath,
        ]);
    }

    private function readPrivate(object $object, string $property): mixed
    {
        $reflection = new \ReflectionClass($object);
        $reflectedProperty = $reflection->getProperty($property);
        $reflectedProperty->setAccessible(true);

        return $reflectedProperty->getValue($object);
    }
}

<?php

namespace Tests\Feature\Api;

use App\Jobs\SendDailySummaryReportJob;
use App\Jobs\SendRealProfitReportJob;
use App\Models\CompanyAccountTransaction;
use App\Models\DailySummaryReport;
use App\Models\Expense;
use App\Models\Member;
use App\Models\MemberAttendance;
use App\Models\MemberPayment;
use App\Models\PaymentMembership;
use App\Models\PaymentMethod;
use App\Models\PaymentSettlement;
use App\Models\Sale;
use App\Services\TenantConfigurationService;
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

    public function testMemberAnalysisReportUsesSeparatePermission(): void
    {
        $this->actingAsUser(['reports.member_analysis']);

        $this->getJson('/api/reports/member-analysis/summary')->assertOk();
        $this->getJson('/api/reports/daily-summary')->assertForbidden();
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

    public function testRealProfitReportCombinesMembershipOtherPaymentsSalesMarginAndExpenses(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 10:00:00'));

        try {
            $this->actingAsUser(['reports.view', 'sales.process', 'sales.create']);
            $account = $this->createCompanyAccount(['name' => 'Cash']);
            $method = PaymentMethod::create([
                'name' => 'Card',
                'company_account_id' => $account->id,
                'deduction_type' => 'percentage',
                'deduction_value' => 3,
                'record_deduction_as_expense' => true,
                'requires_reconciliation' => false,
                'is_active' => true,
            ]);
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

            $otherPayment = MemberPayment::create([
                'member_id' => $member->id,
                'company_account_id' => $account->id,
                'payment_method_id' => $method->id,
                'payment_method' => 'cash',
                'amount' => 500,
                'payment_date' => '2026-06-06',
                'reference_number' => 'OTHER-001',
                'notes' => 'Locker rental',
            ]);

            PaymentSettlement::create([
                'payment_method_id' => $method->id,
                'company_account_id' => $account->id,
                'source_type' => 'payment',
                'source_id' => $otherPayment->id,
                'payment_method_name' => $method->name,
                'gross_amount' => 500,
                'deduction_amount' => 15,
                'net_amount' => 485,
                'record_deduction_as_expense' => true,
                'status' => PaymentSettlement::STATUS_CONFIRMED,
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
                ->assertJsonPath('summary.other_payment_income', 500)
                ->assertJsonPath('summary.other_payment_count', 1)
                ->assertJsonPath('summary.total_payment_income', 1700)
                ->assertJsonPath('summary.payment_count', 2)
                ->assertJsonPath('summary.sales_revenue', 200)
                ->assertJsonPath('summary.sales_cost', 120)
                ->assertJsonPath('summary.sales_profit', 80)
                ->assertJsonPath('summary.expenses', 300)
                ->assertJsonPath('summary.payment_deductions', 15)
                ->assertJsonPath('summary.payment_deduction_count', 1)
                ->assertJsonPath('summary.real_profit', 1465)
                ->assertJsonPath('summary.estimated_cost_items', 0)
                ->assertJsonPath('summary.missing_cost_items', 0)
                ->assertJsonPath('sales_items.0.cost_source', 'exact')
                ->assertJsonPath('sales_by_product.0.product_name', 'Protein Bar')
                ->assertJsonPath('other_payments.0.reference_number', 'OTHER-001')
                ->assertJsonPath('other_payments.0.notes', 'Locker rental')
                ->assertJsonCount(1, 'membership_payments')
                ->assertJsonCount(1, 'other_payments')
                ->assertJsonCount(1, 'expenses');

            $this->assertEqualsWithDelta(1465.0, (float) $response->json('summary.real_profit'), 0.001);
        } finally {
            $this->travelBack();
        }
    }

    public function testRealProfitReportPdfDownloadsForSelectedMonth(): void
    {
        $this->actingAsUser(['reports.view']);

        $response = $this->get('/api/reports/real-profit/pdf?month=2026-06')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringContainsString(
            'filename="real-profit-2026-06.pdf"',
            (string) $response->headers->get('Content-Disposition'),
        );
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function testRealProfitReportEmailQueuesAdminDeliveryJob(): void
    {
        Queue::fake();
        $this->actingAsUser(['reports.view']);

        $this->postJson('/api/reports/real-profit/email', ['month' => '2026-06'])
            ->assertAccepted()
            ->assertJsonPath('message', 'Real profit report email queued for administrators.');

        Queue::assertPushed(
            SendRealProfitReportJob::class,
            fn (SendRealProfitReportJob $job) => $this->readPrivate($job, 'tenantId') === $this->tenant->id
                && $this->readPrivate($job, 'month') === '2026-06',
        );
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

    public function testMemberAnalysisFilterOptionsReturnsPaymentPlans(): void
    {
        $this->actingAsUser(['reports.view']);
        $plan = $this->createPaymentPlan(['name' => 'Gold Plan']);

        $this->getJson('/api/reports/member-analysis/filters/options')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $plan->id,
                'name' => 'Gold Plan',
            ]);
    }

    public function testMemberAnalysisFilterRulesUseDatabaseStatusColumns(): void
    {
        $this->actingAsUser(['reports.view']);

        $match = $this->createMember(attributes: [
            'name' => 'Status Match',
            'is_active' => false,
            'is_verified' => false,
            'is_temp' => true,
        ]);
        $this->createMember(attributes: [
            'name' => 'Active Verified Member',
            'is_active' => true,
            'is_verified' => true,
            'is_temp' => false,
        ]);
        $this->createMember(attributes: [
            'name' => 'Inactive Verified Member',
            'is_active' => false,
            'is_verified' => true,
            'is_temp' => true,
        ]);

        $rules = urlencode(json_encode([
            ['field' => 'active', 'operator' => 'eq', 'value' => ['inactive']],
            ['field' => 'verified', 'operator' => 'eq', 'value' => ['unverified']],
            ['field' => 'temp', 'operator' => 'eq', 'value' => ['temp']],
        ], JSON_THROW_ON_ERROR));

        $this->getJson('/api/reports/member-analysis/members?filter_rules=' . $rules)
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.member_id', $match->id)
            ->assertJsonPath('data.0.is_active', false)
            ->assertJsonPath('data.0.is_verified', false)
            ->assertJsonPath('data.0.is_temp', true);
    }

    public function testMemberAnalysisBulkStatusUpdateSetsSelectedMembersActiveOrInactive(): void
    {
        $this->actingAsUser(['reports.view', 'members.edit']);
        $first = $this->createMember(attributes: ['is_active' => false]);
        $second = $this->createMember(attributes: ['is_active' => false]);
        $third = $this->createMember(attributes: ['is_active' => true]);

        $this->patchJson('/api/reports/member-analysis/members/status', [
            'member_ids' => [$first->id, $second->id],
            'status' => 'active',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'active')
            ->assertJsonPath('selected_count', 2)
            ->assertJsonPath('updated_count', 2);

        $this->assertTrue($first->fresh()->is_active);
        $this->assertTrue($second->fresh()->is_active);
        $this->assertTrue($third->fresh()->is_active);

        $this->patchJson('/api/reports/member-analysis/members/status', [
            'member_ids' => [$second->id, $third->id],
            'status' => 'inactive',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'inactive')
            ->assertJsonPath('selected_count', 2)
            ->assertJsonPath('updated_count', 2);

        $this->assertTrue($first->fresh()->is_active);
        $this->assertFalse($second->fresh()->is_active);
        $this->assertFalse($third->fresh()->is_active);
    }

    public function testMemberAnalysisBulkStatusUpdateRequiresMemberEditPermission(): void
    {
        $this->actingAsUser(['reports.view']);
        $member = $this->createMember(attributes: ['is_active' => false]);

        $this->patchJson('/api/reports/member-analysis/members/status', [
            'member_ids' => [$member->id],
            'status' => 'active',
        ])->assertForbidden();

        $this->assertFalse($member->fresh()->is_active);
    }

    public function testMemberAnalysisReturnsExpiryAttendanceAndBiometricSyncCalculations(): void
    {
        $this->travelTo(Carbon::parse('2026-07-04 10:00:00'));

        try {
            $this->actingAsUser(['reports.view']);
            app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
                'biometric.enabled' => '1',
                'biometric.device_maker' => 'hikvision',
                'biometric.device_ip' => 'device.local',
            ]);

            $member = $this->createMember(attributes: [
                'name' => 'Synced Member',
                'joined_date' => '2026-01-01',
                'biometric_last_synced_at' => '2026-07-03 09:30:00',
            ]);
            $this->createMembershipForReport($member, '2026-07-01', '2026-07-10');

            MemberAttendance::create([
                'member_id' => $member->id,
                'attended_date' => '2026-07-01',
            ]);

            $this->getJson('/api/reports/member-analysis/members?search=Synced')
                ->assertOk()
                ->assertJsonPath('meta.total', 1)
                ->assertJsonPath('data.0.member_id', $member->id)
                ->assertJsonPath('data.0.membership_expiry_date', '2026-07-10')
                ->assertJsonPath('data.0.days_until_payment_expiry', 6)
                ->assertJsonPath('data.0.payment_expiry_days', 6)
                ->assertJsonPath('data.0.last_attendance_date', '2026-07-01')
                ->assertJsonPath('data.0.days_since_last_attendance', 3)
                ->assertJsonPath('data.0.last_attendance_days', 3)
                ->assertJsonPath('data.0.biometric_configured', true)
                ->assertJsonPath('data.0.biometric_synced', true)
                ->assertJsonPath('data.0.has_face', false)
                ->assertJsonPath('data.0.has_fingerprint', false)
                ->assertJsonPath('data.0.face_status', 'not_given')
                ->assertJsonPath('data.0.fingerprint_status', 'not_given');
        } finally {
            $this->travelBack();
        }
    }

    public function testMemberAnalysisFilterRulesApplyPlanDateCountBiometricAndOutstandingFilters(): void
    {
        $this->travelTo(Carbon::parse('2026-07-04 10:00:00'));

        try {
            $this->actingAsUser(['reports.view']);
            app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
                'biometric.enabled' => '1',
                'biometric.device_maker' => 'hikvision',
                'biometric.device_ip' => 'device.local',
            ]);

            $member = $this->createMember(attributes: [
                'name' => 'Filter Match',
                'joined_date' => '2026-01-01',
                'biometric_last_synced_at' => null,
            ]);
            $this->createMembershipForReport($member, '2026-07-01', '2026-07-10');

            MemberAttendance::create([
                'member_id' => $member->id,
                'attended_date' => '2026-07-01',
            ]);

            Sale::create([
                'customer_name' => $member->name,
                'customer_member_id' => $member->id,
                'customer_type' => 'local',
                'payment_method' => 'cash',
                'total_amount' => 500,
                'paid_amount' => 100,
                'balance' => -400,
                'is_paid' => false,
            ]);

            $this->createMember(attributes: [
                'name' => 'Filter Miss',
                'joined_date' => '2026-01-01',
                'biometric_last_synced_at' => '2026-07-03 09:30:00',
            ]);

            $rules = urlencode(json_encode([
                ['field' => 'plan', 'operator' => 'eq', 'value' => [(string) $member->payment_plan_id]],
                ['field' => 'payment_expiry_date', 'operator' => 'gt', 'value' => '2026-07-05'],
                ['field' => 'expiry_days', 'operator' => 'lte', 'value' => 6],
                ['field' => 'last_attendance_date', 'operator' => 'lt', 'value' => '2026-07-02'],
                ['field' => 'attendance_days', 'operator' => 'gt', 'value' => 2],
                ['field' => 'attendance_count', 'operator' => 'gte', 'value' => 1],
                ['field' => 'biometric', 'operator' => 'eq', 'value' => ['not_synced']],
                ['field' => 'outstanding', 'operator' => 'gt', 'value' => 300],
            ], JSON_THROW_ON_ERROR));

            $this->getJson('/api/reports/member-analysis/members?filter_rules=' . $rules)
                ->assertOk()
                ->assertJsonPath('meta.total', 1)
                ->assertJsonPath('data.0.member_id', $member->id)
                ->assertJsonPath('data.0.days_until_payment_expiry', 6)
                ->assertJsonPath('data.0.days_since_last_attendance', 3)
                ->assertJsonPath('data.0.attendance_count', 1)
                ->assertJsonPath('data.0.biometric_synced', false)
                ->assertJsonPath('data.0.total_outstanding_amount', 400);
        } finally {
            $this->travelBack();
        }
    }

    public function testMemberAnalysisClassifiesInactiveMembers(): void
    {
        $this->travelTo(Carbon::parse('2026-07-04 10:00:00'));

        try {
            $this->actingAsUser(['reports.view']);
            $member = $this->createMember(attributes: [
                'name' => 'Inactive Member',
                'joined_date' => '2026-01-01',
            ]);

            MemberAttendance::create([
                'member_id' => $member->id,
                'attended_date' => '2026-05-01',
            ]);

            $this->getJson('/api/reports/member-analysis/members?inactive_only=1')
                ->assertOk()
                ->assertJsonPath('meta.total', 1)
                ->assertJsonPath('data.0.member_id', $member->id)
                ->assertJsonPath('data.0.flags.inactive', true)
                ->assertJsonPath('data.0.attendance_status', 'inactive');
        } finally {
            $this->travelBack();
        }
    }

    public function testMemberAnalysisClassifiesPaymentMissedWithGracePeriod(): void
    {
        $this->travelTo(Carbon::parse('2026-07-04 10:00:00'));

        try {
            $this->actingAsUser(['reports.view']);
            $member = $this->createMember(attributes: ['joined_date' => '2026-01-01']);
            $this->createMembershipForReport($member, '2026-05-26', '2026-06-26');

            $this->getJson('/api/reports/member-analysis/members?payment_missed_only=1')
                ->assertOk()
                ->assertJsonPath('meta.total', 1)
                ->assertJsonPath('data.0.member_id', $member->id)
                ->assertJsonPath('data.0.membership_status', 'expired')
                ->assertJsonPath('data.0.flags.payment_missed', true);
        } finally {
            $this->travelBack();
        }
    }

    public function testMemberAnalysisClassifiesOutstandingMembers(): void
    {
        $this->travelTo(Carbon::parse('2026-07-04 10:00:00'));

        try {
            $this->actingAsUser(['reports.view']);
            $member = $this->createMember(attributes: ['joined_date' => '2026-01-01']);

            Sale::create([
                'customer_name' => $member->name,
                'customer_member_id' => $member->id,
                'customer_type' => 'local',
                'payment_method' => 'cash',
                'total_amount' => 500,
                'paid_amount' => 100,
                'balance' => -400,
                'is_paid' => false,
            ]);

            $this->getJson('/api/reports/member-analysis/members?outstanding_only=1')
                ->assertOk()
                ->assertJsonPath('meta.total', 1)
                ->assertJsonPath('data.0.member_id', $member->id)
                ->assertJsonPath('data.0.flags.outstanding', true)
                ->assertJsonPath('data.0.sales_outstanding_amount', 400)
                ->assertJsonPath('data.0.total_outstanding_amount', 400);
        } finally {
            $this->travelBack();
        }
    }

    public function testMemberAnalysisClassifiesPaidButNotAttendingMembers(): void
    {
        $this->travelTo(Carbon::parse('2026-07-04 10:00:00'));

        try {
            $this->actingAsUser(['reports.view']);
            $member = $this->createMember(attributes: ['joined_date' => '2026-01-01']);
            $this->createMembershipForReport($member, '2026-07-01', '2026-07-31');

            MemberAttendance::create([
                'member_id' => $member->id,
                'attended_date' => '2026-06-10',
            ]);

            $this->getJson('/api/reports/member-analysis/members?paid_not_attending_only=1')
                ->assertOk()
                ->assertJsonPath('meta.total', 1)
                ->assertJsonPath('data.0.member_id', $member->id)
                ->assertJsonPath('data.0.membership_status', 'valid')
                ->assertJsonPath('data.0.flags.paid_not_attending', true);
        } finally {
            $this->travelBack();
        }
    }

    public function testMemberAnalysisClassifiesAttendingWithExpiredPaymentMembers(): void
    {
        $this->travelTo(Carbon::parse('2026-07-04 10:00:00'));

        try {
            $this->actingAsUser(['reports.view']);
            $member = $this->createMember(attributes: ['joined_date' => '2026-01-01']);
            $this->createMembershipForReport($member, '2026-06-01', '2026-06-30');

            MemberAttendance::create([
                'member_id' => $member->id,
                'attended_date' => '2026-07-02',
            ]);

            $this->getJson('/api/reports/member-analysis/members?attending_with_expired_payment_only=1')
                ->assertOk()
                ->assertJsonPath('meta.total', 1)
                ->assertJsonPath('data.0.member_id', $member->id)
                ->assertJsonPath('data.0.membership_status', 'expired')
                ->assertJsonPath('data.0.flags.attending_with_expired_payment', true);
        } finally {
            $this->travelBack();
        }
    }

    public function testMemberAnalysisFaceIdAndFingerprintDetailsAndExport(): void
    {
        $this->actingAsUser(['reports.view']);

        $faceMember = $this->createMember(attributes: [
            'name' => 'Face Member',
            'has_face' => true,
            'has_fingerprint' => false,
        ]);

        $fpMember = $this->createMember(attributes: [
            'name' => 'Fingerprint Member',
            'has_face' => false,
            'has_fingerprint' => true,
        ]);

        // Verify JSON response has Face ID and Fingerprint tags/status
        $this->getJson('/api/reports/member-analysis/members?search=Face')
            ->assertOk()
            ->assertJsonPath('data.0.member_id', $faceMember->id)
            ->assertJsonPath('data.0.has_face', true)
            ->assertJsonPath('data.0.has_fingerprint', false)
            ->assertJsonPath('data.0.face_status', 'given')
            ->assertJsonPath('data.0.fingerprint_status', 'not_given');

        // Verify filtering by Face ID status
        $rules = json_encode([['field' => 'face_id', 'operator' => 'eq', 'value' => ['given']]]);
        $this->getJson('/api/reports/member-analysis/members?filter_rules=' . urlencode($rules))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.member_id', $faceMember->id);

        // Verify filtering by Fingerprint status
        $rulesFp = json_encode([['field' => 'fingerprint', 'operator' => 'eq', 'value' => ['given']]]);
        $this->getJson('/api/reports/member-analysis/members?filter_rules=' . urlencode($rulesFp))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.member_id', $fpMember->id);

        // Verify CSV export includes Face ID and Fingerprint headers
        $response = $this->get('/api/reports/member-analysis/export');
        $response->assertOk();
        $this->assertStringContainsString('Face ID', $response->streamedContent());
        $this->assertStringContainsString('Fingerprint', $response->streamedContent());
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

    private function createMembershipForReport(Member $member, string $startDate, string $endDate): PaymentMembership
    {
        $payment = MemberPayment::create([
            'member_id' => $member->id,
            'company_account_id' => null,
            'payment_method' => 'cash',
            'amount' => 1200,
            'payment_date' => $startDate,
        ]);

        return PaymentMembership::create([
            'member_payment_id' => $payment->id,
            'payment_plan_id' => $member->payment_plan_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
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

<?php

namespace Tests\Feature\Api;

use App\Jobs\SendDailySummaryReportJob;
use App\Models\CompanyAccountTransaction;
use App\Models\DailySummaryReport;
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

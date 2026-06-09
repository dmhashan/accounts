<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ImportBiometricAccessEventsJob;
use App\Jobs\SendBulkNotificationJob;
use App\Jobs\SendDailySummaryReportJob;
use App\Jobs\SendFormSubmissionEmailJob;
use App\Jobs\SendMemberNotificationJob;
use App\Models\BulkNotification;
use App\Models\BulkNotificationRecipient;
use App\Models\DailySummaryReport;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\MemberNotification;
use App\Models\Tenant;
use App\Services\BiometricSyncService;
use App\Services\SmsService;
use App\Services\TenantConfigurationService;
use App\Services\TenantMailService;
use Illuminate\Support\Str;
use Tests\Feature\Api\ApiRouteTestCase;

class TenantScopedJobsTest extends ApiRouteTestCase
{
    public function testMemberNotificationJobRejectsMemberFromAnotherTenant(): void
    {
        $otherTenant = $this->createOtherTenant();
        $otherMember = $this->createMember(attributes: ['tenant_id' => $otherTenant->id]);
        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'notifications.inapp.enabled' => '1',
        ]);

        $this->runMemberNotificationJob($this->tenant->id, $otherMember->id);

        $this->assertDatabaseMissing('member_notifications', [
            'tenant_id' => $this->tenant->id,
            'member_id' => $otherMember->id,
        ]);
    }

    public function testBulkNotificationJobIgnoresCrossTenantRecipients(): void
    {
        $member = $this->createMember();
        $otherTenant = $this->createOtherTenant();
        $otherMember = $this->createMember(attributes: ['tenant_id' => $otherTenant->id]);
        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'notifications.inapp.enabled' => '1',
        ]);
        $creator = $this->createUser();

        $notification = BulkNotification::create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $creator->id,
            'name' => 'Tenant notice',
            'message' => 'Current tenant only.',
            'status' => 'processing',
        ]);
        BulkNotificationRecipient::create([
            'bulk_notification_id' => $notification->id,
            'member_id' => $member->id,
            'phone_number' => $member->phone_number,
        ]);
        BulkNotificationRecipient::create([
            'bulk_notification_id' => $notification->id,
            'member_id' => $otherMember->id,
            'phone_number' => $otherMember->phone_number,
        ]);

        $sms = \Mockery::mock(SmsService::class);
        $sms->shouldNotReceive('sendBulk');
        $mail = \Mockery::mock(TenantMailService::class);
        $mail->shouldNotReceive('mailerForTenant');

        (new SendBulkNotificationJob($notification->id, ['in_app']))
            ->handle($sms, $mail, app(TenantConfigurationService::class));

        $this->assertDatabaseHas('member_notifications', [
            'tenant_id' => $this->tenant->id,
            'member_id' => $member->id,
            'type' => 'bulk',
        ]);
        $this->assertDatabaseMissing('member_notifications', [
            'tenant_id' => $this->tenant->id,
            'member_id' => $otherMember->id,
        ]);
        $this->assertSame(1, MemberNotification::count());
        $this->assertSame('sent', $notification->fresh()->status);
    }

    public function testReportAndFormEmailJobsRejectRecordsFromAnotherTenant(): void
    {
        $otherTenant = $this->createOtherTenant();
        $member = $this->createMember();
        $template = FormTemplate::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Health Form',
            'fields' => [],
            'is_active' => true,
        ]);
        $submission = FormSubmission::create([
            'tenant_id' => $this->tenant->id,
            'form_template_id' => $template->id,
            'member_id' => $member->id,
            'responses' => [],
            'pdf_path' => 'forms/submission.pdf',
            'submitted_at' => now(),
        ]);
        $report = DailySummaryReport::create([
            'tenant_id' => $this->tenant->id,
            'report_date' => today(),
            'prepared_by_name' => 'Admin',
            'system_snapshot' => [],
            'final_snapshot' => [],
            'changes' => [],
            'totals' => [],
            'pdf_path' => 'reports/summary.pdf',
        ]);

        $mail = \Mockery::mock(TenantMailService::class);
        $mail->shouldNotReceive('mailerForTenant');

        (new SendFormSubmissionEmailJob($otherTenant->id, $submission->id))->handle($mail);
        (new SendDailySummaryReportJob($otherTenant->id, $report->id))->handle($mail);

        $this->assertDatabaseCount('form_submissions', 1);
        $this->assertDatabaseCount('daily_summary_reports', 1);
    }

    public function testBiometricImportJobBindsItsTenantBeforeCallingService(): void
    {
        $otherTenant = $this->createOtherTenant();
        $this->app->instance('tenant', $otherTenant);

        $biometric = \Mockery::mock(BiometricSyncService::class);
        $biometric->shouldReceive('importDeviceEvents')
            ->once()
            ->withArgs(function (Tenant $tenant, ?string $syncFrom, ?string $syncTo) {
                return $tenant->is($this->tenant)
                    && app('tenant')->is($this->tenant)
                    && $syncFrom === '2026-06-01T00:00:00+00:00'
                    && $syncTo === '2026-06-09T00:00:00+00:00';
            })
            ->andReturn(['imported' => 1, 'skipped' => 0, 'errors' => 0]);

        (new ImportBiometricAccessEventsJob(
            $this->tenant->id,
            '2026-06-01T00:00:00+00:00',
            '2026-06-09T00:00:00+00:00',
        ))->handle($biometric);

        $this->assertTrue(app('tenant')->is($this->tenant));
    }

    private function runMemberNotificationJob(int $tenantId, int $memberId): void
    {
        $sms = \Mockery::mock(SmsService::class);
        $sms->shouldNotReceive('send');
        $mail = \Mockery::mock(TenantMailService::class);
        $mail->shouldNotReceive('mailerForTenant');

        (new SendMemberNotificationJob(
            $tenantId,
            $memberId,
            'test',
            'Test notification',
            'Tenant scoped body',
            ['in_app'],
        ))->handle($sms, $mail, app(TenantConfigurationService::class));
    }

    private function createOtherTenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Other Gym',
            'domain' => 'other-jobs',
            'tenant_uuid' => Str::uuid()->toString(),
        ]);
    }
}

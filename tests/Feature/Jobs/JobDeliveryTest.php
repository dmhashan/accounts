<?php

namespace Tests\Feature\Jobs;

use App\Jobs\RunLegacyCommand;
use App\Jobs\SendBulkNotificationJob;
use App\Jobs\SendDailySummaryReportJob;
use App\Jobs\SendFormSubmissionEmailJob;
use App\Jobs\SendMemberNotificationJob;
use App\Jobs\SendRealProfitReportJob;
use App\Mail\DailySummaryReportMail;
use App\Mail\FormSubmissionMail;
use App\Mail\MemberNotificationMail;
use App\Mail\RealProfitReportMail;
use App\Models\BulkNotification;
use App\Models\BulkNotificationRecipient;
use App\Models\CommandRunLog;
use App\Models\DailySummaryReport;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\MemberNotification;
use App\Services\RealProfitReportService;
use App\Services\SmsService;
use App\Services\TenantConfigurationService;
use App\Services\TenantMailService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Api\ApiRouteTestCase;

class JobDeliveryTest extends ApiRouteTestCase
{
    public function testMemberNotificationJobDeliversEnabledChannels(): void
    {
        Mail::fake();
        $member = $this->createMember();
        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'notifications.sms.enabled' => '1',
            'notifications.whatsapp.enabled' => '1',
            'notifications.email.enabled' => '1',
            'notifications.inapp.enabled' => '1',
        ]);
        $sms = \Mockery::mock(SmsService::class);
        $sms->shouldReceive('sendWhatsappOnly')
            ->once()
            ->with($member->phone_number, 'Delivery body', $this->tenant->id)
            ->andReturnFalse();
        $sms->shouldReceive('sendSmsOnly')
            ->once()
            ->with($member->phone_number, 'Delivery body', $this->tenant->id)
            ->andReturnTrue();

        (new SendMemberNotificationJob(
            $this->tenant->id,
            $member->id,
            'delivery',
            'Delivery title',
            'Delivery body',
        ))->handle($sms, app(TenantMailService::class), app(TenantConfigurationService::class));

        $this->assertDatabaseHas('member_notifications', [
            'member_id' => $member->id,
            'type' => 'delivery',
        ]);
        Mail::assertSent(
            MemberNotificationMail::class,
            fn (MemberNotificationMail $mail) => $mail->notificationTitle === 'Delivery title'
                && $mail->notificationBody === 'Delivery body',
        );
    }

    public function testBulkNotificationJobDeliversAndFailureCallbackUpdatesStatus(): void
    {
        Mail::fake();
        $member = $this->createMember();
        $creator = $this->createUser();
        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'notifications.sms.enabled' => '1',
            'notifications.whatsapp.enabled' => '1',
            'notifications.email.enabled' => '1',
            'notifications.inapp.enabled' => '1',
        ]);
        $notification = BulkNotification::create([
            'created_by' => $creator->id,
            'name' => 'Bulk delivery',
            'message' => 'Bulk body',
            'status' => 'processing',
        ]);
        BulkNotificationRecipient::create([
            'bulk_notification_id' => $notification->id,
            'member_id' => $member->id,
            'phone_number' => $member->phone_number,
        ]);
        $sms = \Mockery::mock(SmsService::class);
        $sms->shouldReceive('sendWhatsappOnly')
            ->once()
            ->with($member->phone_number, 'Bulk body', $this->tenant->id)
            ->andReturnFalse();
        $sms->shouldReceive('sendBulkSmsOnly')
            ->once()
            ->with([$member->phone_number], 'Bulk body', $this->tenant->id)
            ->andReturn(['success' => true, 'campaign_id' => 'campaign-1']);

        $job = new SendBulkNotificationJob($notification->id);
        $job->handle($sms, app(TenantMailService::class), app(TenantConfigurationService::class));

        $this->assertSame('sent', $notification->fresh()->status);
        Mail::assertSent(MemberNotificationMail::class);
        $this->assertDatabaseHas('member_notifications', [
            'member_id' => $member->id,
            'type' => 'bulk',
        ]);

        $job->failed(new \RuntimeException('Queue failed'));
        $this->assertSame('failed', $notification->fresh()->status);
    }

    public function testReportAndFormJobsSendTenantOwnedPdfFiles(): void
    {
        Mail::fake();
        $disk = (string) config('filesystems.media_disk', 'public');
        Storage::fake($disk);
        $adminRole = $this->createRole('admin');
        $admin = $this->createUser(role: $adminRole);
        $member = $this->createMember();
        $template = FormTemplate::create([
            'title' => 'Health Form',
            'fields' => [],
            'is_active' => true,
        ]);
        $submission = FormSubmission::create([
            'form_template_id' => $template->id,
            'member_id' => $member->id,
            'responses' => [],
            'pdf_path' => 'forms/submission.pdf',
            'submitted_at' => now(),
        ]);
        $report = DailySummaryReport::create([
            'report_date' => today(),
            'prepared_by_name' => $admin->name,
            'system_snapshot' => [],
            'final_snapshot' => [],
            'changes' => [['field' => 'cash']],
            'totals' => [],
            'pdf_path' => 'reports/summary.pdf',
        ]);
        Storage::disk($disk)->put($submission->pdf_path, '%PDF-form');
        Storage::disk($disk)->put($report->pdf_path, '%PDF-report');

        $mailService = app(TenantMailService::class);
        (new SendFormSubmissionEmailJob($this->tenant->id, $submission->id))->handle($mailService);
        (new SendDailySummaryReportJob($this->tenant->id, $report->id))->handle($mailService);
        (new SendRealProfitReportJob($this->tenant->id, today()->format('Y-m')))->handle($mailService, app(RealProfitReportService::class));

        Mail::assertSent(
            FormSubmissionMail::class,
            fn (FormSubmissionMail $mail) => $mail->formTitle === 'Health Form'
                && $mail->memberName === $member->name,
        );
        Mail::assertSent(
            DailySummaryReportMail::class,
            fn (DailySummaryReportMail $mail) => $mail->tenantName === $this->tenant->name
                && $mail->changeCount === 1,
        );
        Mail::assertSent(
            RealProfitReportMail::class,
            fn (RealProfitReportMail $mail) => $mail->tenantName === $this->tenant->name
                && $mail->monthLabel === today()->format('F Y'),
        );
    }

    public function testRunLegacyCommandStoresSuccessAndFailureOutput(): void
    {
        Artisan::command('test:legacy-success', function () {
            $this->line('legacy command complete');

            return 0;
        });
        Artisan::command('test:legacy-failure', function () {
            throw new \RuntimeException('legacy command exploded');
        });

        $successLog = $this->createCommandLog('test:legacy-success');
        (new RunLegacyCommand($successLog->id, 'test:legacy-success', []))->handle();
        $this->assertTrue($successLog->fresh()->success);
        $this->assertStringContainsString('legacy command complete', (string) $successLog->fresh()->output);

        $failureLog = $this->createCommandLog('test:legacy-failure');
        (new RunLegacyCommand($failureLog->id, 'test:legacy-failure', []))->handle();
        $this->assertFalse($failureLog->fresh()->success);
        $this->assertSame('legacy command exploded', $failureLog->fresh()->output);
    }

    public function testMissingRecordsAndDisabledDeliveryPathsReturnWithoutSending(): void
    {
        Mail::fake();
        $sms = \Mockery::mock(SmsService::class);
        $sms->shouldNotReceive('send');
        $sms->shouldNotReceive('sendBulk');
        $mail = \Mockery::mock(TenantMailService::class);
        $mail->shouldNotReceive('mailerForTenant');
        $config = app(TenantConfigurationService::class);

        (new SendMemberNotificationJob($this->tenant->id, 999999, 'type', 'title', 'body'))
            ->handle($sms, $mail, $config);
        (new SendBulkNotificationJob(999999))->handle($sms, $mail, $config);
        (new SendFormSubmissionEmailJob($this->tenant->id, 999999))->handle($mail);
        (new SendDailySummaryReportJob($this->tenant->id, 999999))->handle($mail);
        (new SendRealProfitReportJob(999999, today()->format('Y-m')))->handle($mail, app(RealProfitReportService::class));

        $this->assertSame(0, MemberNotification::count());
        Mail::assertNothingSent();
    }

    private function createCommandLog(string $command): CommandRunLog
    {
        return CommandRunLog::create([
            'command' => $command,
            'params' => [],
        ]);
    }
}

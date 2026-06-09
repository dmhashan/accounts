<?php

namespace Tests\Feature\Services;

use App\Mail\DailySummaryReportMail;
use App\Mail\FormSubmissionMail;
use App\Mail\MemberNotificationMail;
use App\Services\SmsService;
use App\Services\TenantConfigurationService;
use App\Services\TenantMailService;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Api\ApiRouteTestCase;

class DeliveryServicesTest extends ApiRouteTestCase
{
    public function testSmsServiceUsesTenantCredentialsForSingleAndBulkMessages(): void
    {
        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'notifications.sms.user_id' => 'tenant-user',
            'notifications.sms.api_key' => 'tenant-key',
            'notifications.sms.sender_id' => 'TENANT',
        ]);
        Http::fake([
            'https://smslenz.lk/api/send-sms' => Http::response([
                'success' => true,
                'data' => ['campaign_id' => 'single-campaign'],
            ]),
            'https://smslenz.lk/api/send-bulk-sms' => Http::response([
                'success' => true,
                'data' => ['campaign_id' => 'bulk-campaign'],
            ]),
        ]);
        $sms = app(SmsService::class);

        $this->assertTrue($sms->send('94700000001', 'Single message', $this->tenant->id));
        $this->assertSame(
            ['success' => true, 'campaign_id' => 'bulk-campaign'],
            $sms->sendBulk(['94700000001', '94700000002'], 'Bulk message', $this->tenant->id),
        );
        Http::assertSentCount(2);
    }

    public function testSmsServiceHandlesMissingCredentialsEmptyContactsAndApiFailures(): void
    {
        config([
            'services.smslenz.user_id' => null,
            'services.smslenz.api_key' => null,
        ]);
        $sms = new SmsService(app(TenantConfigurationService::class));

        $this->assertFalse($sms->send('94700000001', 'No credentials'));
        $this->assertSame(
            ['success' => false, 'campaign_id' => null],
            $sms->sendBulk([], 'No contacts'),
        );

        config([
            'services.smslenz.user_id' => 'env-user',
            'services.smslenz.api_key' => 'env-key',
        ]);
        Http::fake([
            '*' => Http::response(['success' => false], 422),
        ]);
        $sms = new SmsService(app(TenantConfigurationService::class));

        $this->assertFalse($sms->send('94700000001', 'Rejected'));
        $this->assertSame(
            ['success' => false, 'campaign_id' => null],
            $sms->sendBulk(['94700000001'], 'Rejected'),
        );
    }

    public function testTenantMailServiceBuildsFallbackAndTenantSmtpMailers(): void
    {
        $service = app(TenantMailService::class);
        $this->assertSame(app('mailer'), $service->mailerForTenant($this->tenant->id));

        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'notifications.email.smtp_host' => 'smtp.tenant.test',
            'notifications.email.smtp_port' => '2525',
            'notifications.email.smtp_username' => 'tenant-user',
            'notifications.email.smtp_password' => 'tenant-secret',
            'notifications.email.smtp_encryption' => 'none',
            'notifications.email.from_address' => 'mail@tenant.test',
            'notifications.email.from_name' => 'Tenant Mail',
        ]);

        $mailer = $service->mailerForTenant($this->tenant->id);

        $this->assertNotSame(app('mailer'), $mailer);
        $this->assertSame('smtp.tenant.test', config('mail.mailers.tenant_smtp_' . $this->tenant->id . '.host'));
        $this->assertNull(config('mail.mailers.tenant_smtp_' . $this->tenant->id . '.encryption'));
    }

    public function testNotificationMailablesExposeExpectedEnvelopeContentAndAttachments(): void
    {
        $memberMail = new MemberNotificationMail('Notice', 'Body');
        $this->assertSame('Notice', $memberMail->envelope()->subject);
        $this->assertSame('emails.member-notification', $memberMail->content()->view);

        $formMail = new FormSubmissionMail('Health Form', 'Member Name', '%PDF', 'form.pdf');
        $this->assertSame('Health Form', $formMail->envelope()->subject);
        $this->assertSame('emails.form-submission', $formMail->content()->view);
        $this->assertCount(1, $formMail->attachments());

        $reportMail = new DailySummaryReportMail('Gym', '09 Jun 2026', 'Admin', 1, '%PDF', 'summary.pdf');
        $this->assertSame('Daily Summary Report — 09 Jun 2026', $reportMail->envelope()->subject);
        $this->assertSame('emails.daily-summary-report', $reportMail->content()->view);
        $this->assertCount(1, $reportMail->attachments());
    }
}

<?php

namespace Tests\Feature\Services;

use App\Jobs\SendBulkNotificationJob;
use App\Jobs\SendMemberNotificationJob;
use App\Models\BulkNotification;
use App\Models\BulkNotificationRecipient;
use App\Services\SmsService;
use App\Services\TenantConfigurationService;
use App\Services\TenantMailService;
use App\Services\WhatsappService;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Api\ApiRouteTestCase;

class WhatsappNotificationTest extends ApiRouteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.openwa.api_key' => 'test-wa-key',
            'services.openwa.session_id' => 'test-wa-session',
            'services.openwa.base_url' => 'http://localhost:2785',
            'services.smslenz.user_id' => 'test-sms-user',
            'services.smslenz.api_key' => 'test-sms-key',
        ]);
    }

    public function testWhatsappNumberNormalization(): void
    {
        $wa = app(WhatsappService::class);

        $this->assertSame('94771234567@c.us', $wa->formatNumber('94771234567'));
        $this->assertSame('94771234567@c.us', $wa->formatNumber('+94 (77) 123-4567'));
        $this->assertSame('94771234567@c.us', $wa->formatNumber('94771234567@c.us'));
        $this->assertSame('94779600845@c.us', $wa->formatNumber('0779600845'));
        $this->assertSame('94779600845@c.us', $wa->formatNumber('779600845'));
        $this->assertSame('94779600845@c.us', $wa->formatNumber('0779600845@c.us'));
    }

    public function testWhatsappSendsSuccessfully(): void
    {
        Http::fake([
            '*/api/sessions/*/messages/send-text' => Http::response([
                'messageId' => 'wa-msg-123',
                'timestamp' => 1234567890,
            ], 201),
        ]);

        $wa = app(WhatsappService::class);
        $this->assertTrue($wa->send('94771234567', 'Hello World'));

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-API-Key', 'test-wa-key')
                && $request['chatId'] === '94771234567@c.us'
                && $request['text'] === 'Hello World';
        });
    }

    public function testWhatsappFailureFallsBackToSms(): void
    {
        Http::fake([
            '*/api/sessions/*/messages/send-text' => Http::response(['error' => 'Internal server error'], 500),
            'https://smslenz.lk/api/send-sms' => Http::response([
                'success' => true,
                'data' => ['campaign_id' => 'sms-camp-123'],
            ]),
        ]);

        $sms = app(SmsService::class);
        $this->assertTrue($sms->send('94771234567', 'Fallback Test'));

        // Assert both WA request and SMS fallback request were sent
        Http::assertSentCount(2);
    }

    public function testMemberNotificationJobPrioritizesWhatsappBasedOnMemberPreference(): void
    {
        Http::fake([
            '*/api/sessions/*/messages/send-text' => Http::response(['messageId' => 'wa-123'], 201),
        ]);

        $member = $this->createMember(null, [
            'allow_whatsapp' => true,
            'whatsapp_number' => '94771234567',
            'allow_sms' => true,
            'phone_number' => '94700000001',
        ]);

        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'notifications.sms.enabled' => '1',
            'notifications.whatsapp.enabled' => '1',
        ]);

        (new SendMemberNotificationJob(
            $this->tenant->id,
            $member->id,
            'test',
            'Test title',
            'Hello WhatsApp',
        ))->handle(app(SmsService::class), app(TenantMailService::class), app(TenantConfigurationService::class));

        // Since WhatsApp succeeded, no SMS should be sent
        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'send-text') && $request['chatId'] === '94771234567@c.us';
        });
    }

    public function testMemberNotificationJobFallsBackToSmsWhenWhatsappFails(): void
    {
        Http::fake([
            '*/api/sessions/*/messages/send-text' => Http::response(['error' => 'fail'], 500),
            'https://smslenz.lk/api/send-sms' => Http::response([
                'success' => true,
                'data' => ['campaign_id' => 'sms-camp-123'],
            ]),
        ]);

        $member = $this->createMember(null, [
            'allow_whatsapp' => true,
            'whatsapp_number' => '94771234567',
            'allow_sms' => true,
            'phone_number' => '94700000001',
        ]);

        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'notifications.sms.enabled' => '1',
            'notifications.whatsapp.enabled' => '1',
        ]);

        (new SendMemberNotificationJob(
            $this->tenant->id,
            $member->id,
            'test',
            'Test title',
            'Hello Fallback',
        ))->handle(app(SmsService::class), app(TenantMailService::class), app(TenantConfigurationService::class));

        // Both WhatsApp try and SMS fallback should be sent
        Http::assertSentCount(2);
    }

    public function testBulkNotificationJobProcessesPreferencesCorrectly(): void
    {
        Http::fake([
            '*/api/sessions/*/messages/send-text' => Http::response(['messageId' => 'wa-123'], 201),
            'https://smslenz.lk/api/send-bulk-sms' => Http::response([
                'success' => true,
                'data' => ['campaign_id' => 'sms-bulk-123'],
            ]),
        ]);

        $creator = $this->createUser();
        $notification = BulkNotification::create([
            'created_by' => $creator->id,
            'name' => 'Bulk test',
            'message' => 'Bulk msg',
            'status' => 'processing',
        ]);

        // Member 1 allows WhatsApp
        $member1 = $this->createMember(null, [
            'allow_whatsapp' => true,
            'whatsapp_number' => '94771234561',
            'allow_sms' => true,
            'phone_number' => '94700000001',
        ]);

        // Member 2 does not allow WhatsApp, but allows SMS
        $member2 = $this->createMember(null, [
            'allow_whatsapp' => false,
            'allow_sms' => true,
            'phone_number' => '94700000002',
        ]);

        BulkNotificationRecipient::create([
            'bulk_notification_id' => $notification->id,
            'member_id' => $member1->id,
            'phone_number' => $member1->phone_number,
        ]);

        BulkNotificationRecipient::create([
            'bulk_notification_id' => $notification->id,
            'member_id' => $member2->id,
            'phone_number' => $member2->phone_number,
        ]);

        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'notifications.sms.enabled' => '1',
            'notifications.whatsapp.enabled' => '1',
        ]);

        (new SendBulkNotificationJob($notification->id))
            ->handle(app(SmsService::class), app(TenantMailService::class), app(TenantConfigurationService::class));

        // 1 request to WA (Member 1) and 1 request to Bulk SMS (Member 2)
        Http::assertSentCount(2);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'send-text') && $request['chatId'] === '94771234561@c.us';
        });

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'send-bulk-sms') && $request['contacts'] === ['94700000002'];
        });
    }

    public function testWhatsappUsesTenantSpecificCredentials(): void
    {
        config([
            'services.openwa.api_key' => null,
            'services.openwa.session_id' => null,
            'services.openwa.base_url' => null,
        ]);

        Http::fake([
            'http://tenant-wa-host/api/sessions/tenant-wa-session/messages/send-text' => Http::response([
                'messageId' => 'wa-msg-tenant-123',
            ], 201),
        ]);

        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'notifications.whatsapp.enabled' => '1',
            'notifications.whatsapp.api_key' => 'tenant-wa-key',
            'notifications.whatsapp.session_id' => 'tenant-wa-session',
            'notifications.whatsapp.base_url' => 'http://tenant-wa-host',
        ]);

        $wa = app(WhatsappService::class);
        $this->assertTrue($wa->send('94771234567', 'Tenant specific WA message', $this->tenant->id));

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-API-Key', 'tenant-wa-key')
                && $request['chatId'] === '94771234567@c.us'
                && $request['text'] === 'Tenant specific WA message';
        });
    }
}

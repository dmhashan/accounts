<?php

namespace Tests\Feature\Api;

use App\Models\BiometricAccessEvent;
use App\Models\MemberAttendance;
use App\Services\TenantConfigurationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BiometricWebhookTest extends ApiRouteTestCase
{
    private string $token = 'webhook-secret-token-1234567890';

    protected function setUp(): void
    {
        parent::setUp();

        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'biometric.enabled' => '1',
            'biometric.webhook_enabled' => '1',
            'biometric.webhook_token' => $this->token,
            'biometric.device_maker' => 'hikvision',
            'biometric.device_model' => 'DS-K1T320MFWX-B',
        ]);
    }

    public function testSuccessfulAuthenticationMarksAttendanceAndLogsEvent(): void
    {
        $member = $this->createMember(attributes: ['biometric_member_id' => 'MEM-2026-0042']);

        $this->postEvent($this->buildXml('0042', 75, 'Member Tester'))
            ->assertOk();

        $this->assertDatabaseHas('member_attendances', [
            'member_id' => $member->id,
        ]);

        $event = BiometricAccessEvent::first();
        $this->assertNotNull($event);
        $this->assertSame('success', $event->result);
        $this->assertSame('face', $event->auth_method);
        $this->assertSame($member->id, $event->member_id);
    }

    public function testFailedAuthenticationIsLoggedAsAttemptedWithoutAttendance(): void
    {
        $this->postEvent($this->buildXml('9999', 76, 'Unknown'))
            ->assertOk();

        $this->assertSame(0, MemberAttendance::count());

        $event = BiometricAccessEvent::first();
        $this->assertNotNull($event);
        $this->assertSame('failed', $event->result);
        $this->assertSame('face', $event->auth_method);
        $this->assertNull($event->member_id);
    }

    public function testCapturedPictureIsStoredWithTheEvent(): void
    {
        $disk = config('filesystems.media_disk', 'public');
        Storage::fake($disk);

        $member = $this->createMember(attributes: ['biometric_member_id' => 'MEM-2026-0043']);

        $this->call(
            'POST',
            '/api/biometric/events/' . $this->tenant->domain . '?token=' . $this->token,
            ['event_log' => $this->buildXml('0043', 75, 'Member Tester')],
            [],
            ['picture' => UploadedFile::fake()->image('snapshot.jpg')],
            ['CONTENT_TYPE' => 'multipart/form-data'],
        )->assertOk();

        $event = BiometricAccessEvent::where('member_id', $member->id)->first();
        $this->assertNotNull($event);
        $this->assertNotNull($event->picture_path);
        Storage::disk($disk)->assertExists($event->picture_path);
    }

    public function testInvalidTokenIsRejected(): void
    {
        $this->call(
            'POST',
            '/api/biometric/events/' . $this->tenant->domain . '?token=wrong',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/xml'],
            $this->buildXml('0042', 75, 'Member Tester'),
        )->assertStatus(401);

        $this->assertSame(0, BiometricAccessEvent::count());
    }

    private function postEvent(string $xml)
    {
        return $this->call(
            'POST',
            '/api/biometric/events/' . $this->tenant->domain . '?token=' . $this->token,
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/xml'],
            $xml,
        );
    }

    private function buildXml(string $employeeNo, int $minor, string $name): string
    {
        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <EventNotificationAlert version="2.0">
          <eventType>AccessControllerEvent</eventType>
          <dateTime>2026-06-05T10:00:00+05:30</dateTime>
          <AccessControllerEvent>
            <employeeNoString>{$employeeNo}</employeeNoString>
            <eventTime>2026-06-05T10:00:00+05:30</eventTime>
            <minorEventType>{$minor}</minorEventType>
            <name>{$name}</name>
          </AccessControllerEvent>
        </EventNotificationAlert>
        XML;
    }
}

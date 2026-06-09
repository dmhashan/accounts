<?php

namespace Tests\Feature\Api;

use App\Models\BulkNotification;
use App\Models\BulkNotificationRecipient;
use App\Models\Event;
use App\Models\Tenant;
use App\Services\SmsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PublicProfileApiTest extends ApiRouteTestCase
{
    public function testOtpFlowIssuesTenantScopedTokenAndReturnsMemberProfile(): void
    {
        $member = $this->createMember(attributes: ['phone_number' => '0771234567']);
        $sms = \Mockery::mock(SmsService::class);
        $sms->shouldReceive('send')
            ->once()
            ->withArgs(fn (string $phone, string $message) => $phone === '0771234567' && str_contains($message, 'verification code'))
            ->andReturnTrue();
        $this->app->instance(SmsService::class, $sms);

        $this->postJson('/api/public/request-otp', [
            'phone_number' => '0771234567',
        ])->assertOk()
            ->assertJsonPath('message', 'OTP sent successfully.');

        $otp = Cache::get('otp:' . $this->tenant->id . ':0771234567');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp);

        $token = (string) $this->postJson('/api/public/verify-otp', [
            'phone_number' => '0771234567',
            'otp' => $otp,
        ])->assertOk()->json('token');

        $this->assertSame([
            'member_id' => $member->id,
            'tenant_id' => $this->tenant->id,
        ], Cache::get('pp_token:' . $token));

        $this->withHeader('X-PP-Token', $token)
            ->getJson('/api/public/member-profile')
            ->assertOk()
            ->assertJsonPath('meta.name', $member->name)
            ->assertJsonPath('meta.current_balance', 0);

        $this->withHeader('X-PP-Token', $token)
            ->getJson('/api/public/wallet/transactions')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function testPublicProfileTokenCannotCrossTenantBoundary(): void
    {
        $member = $this->createMember();
        $otherTenant = Tenant::create([
            'name' => 'Other Gym',
            'domain' => 'other-public',
            'tenant_uuid' => Str::uuid()->toString(),
        ]);
        $token = Str::uuid()->toString();

        Cache::put('pp_token:' . $token, [
            'member_id' => $member->id,
            'tenant_id' => $otherTenant->id,
        ]);

        $this->withHeader('X-PP-Token', $token)
            ->getJson('/api/public/member-profile')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthorized.');
    }

    public function testPublicEventsNotificationsAndActivityAreTenantScoped(): void
    {
        $member = $this->createMember();
        $token = Str::uuid()->toString();
        Cache::put('pp_token:' . $token, [
            'member_id' => $member->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $event = Event::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Public Event',
            'slug' => 'public-event',
            'start_datetime' => now()->addWeek(),
            'ticket_fee' => 500,
            'additional_ticket_fee' => 100,
            'is_active' => true,
        ]);

        $this->getJson('/api/public/upcoming-events')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $event->id);

        $this->withHeader('X-PP-Token', $token)
            ->postJson('/api/public/event/public-event/register', [
                'name' => $member->name,
                'guests' => [['name' => 'Guest']],
            ])->assertCreated()
            ->assertJsonPath('data.total_fee', 600);

        $this->withHeader('X-PP-Token', $token)
            ->postJson('/api/public/event/public-event/register', [
                'name' => $member->name,
            ])->assertConflict();

        $notification = BulkNotification::create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $this->createUser()->id,
            'name' => 'Member Notice',
            'message' => 'Hello member.',
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        BulkNotificationRecipient::create([
            'bulk_notification_id' => $notification->id,
            'member_id' => $member->id,
            'phone_number' => $member->phone_number,
        ]);

        $this->withHeader('X-PP-Token', $token)
            ->getJson('/api/public/notifications')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $notification->id);

        $this->withHeaders([
            'X-PP-Token' => $token,
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) AppleWebKit Safari/604.1',
        ])->postJson('/api/public/activity', [
            'session_id' => 'session-public-1',
            'event_type' => 'tab_view',
            'screen_width' => 390,
            'screen_height' => 844,
            'metadata' => ['tab' => 'wallet'],
        ])->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('member_activity_logs', [
            'tenant_id' => $this->tenant->id,
            'member_id' => $member->id,
            'session_id' => 'session-public-1',
            'device_type' => 'mobile',
            'browser' => 'Safari',
            'os' => 'iOS',
        ]);
    }
}

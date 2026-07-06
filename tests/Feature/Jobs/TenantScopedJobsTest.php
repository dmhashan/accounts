<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ImportBiometricAccessEventsJob;
use App\Jobs\SendBulkNotificationJob;
use App\Jobs\SendMemberNotificationJob;
use App\Models\BulkNotification;
use App\Models\BulkNotificationRecipient;
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
    public function testMemberNotificationJobCreatesCurrentDatabaseNotification(): void
    {
        $member = $this->createMember();
        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'notifications.inapp.enabled' => '1',
        ]);

        $this->runMemberNotificationJob($this->tenant->id, $member->id);

        $this->assertDatabaseHas('member_notifications', [
            'member_id' => $member->id,
            'type' => 'test',
        ]);
    }

    public function testBulkNotificationJobCreatesInAppNotificationsForRecipients(): void
    {
        $member = $this->createMember();
        $otherMember = $this->createMember();
        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'notifications.inapp.enabled' => '1',
        ]);
        $creator = $this->createUser();

        $notification = BulkNotification::create([
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
            'member_id' => $member->id,
            'type' => 'bulk',
        ]);
        $this->assertDatabaseHas('member_notifications', [
            'member_id' => $otherMember->id,
            'type' => 'bulk',
        ]);
        $this->assertSame(2, MemberNotification::count());
        $this->assertSame('sent', $notification->fresh()->status);
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

    public function testSendAccountAdjustmentNotificationJobDeliversEmailToAdmins(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $account = $this->createCompanyAccount();
        $adminRole = $this->createRole('admin');
        $admin = $this->createUser(attributes: ['email' => 'admin-job@test.com', 'role_id' => $adminRole->id]);

        $details = [
            'account_name' => $account->name,
            'type' => 'credit',
            'amount' => 150.00,
            'reason' => 'Job testing',
            'date' => now()->toDateString(),
            'operator_name' => 'Operator One',
        ];

        (new \App\Jobs\SendAccountAdjustmentNotificationJob(
            $this->tenant->id,
            'created',
            $details,
        ))->handle(app(TenantMailService::class));

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\AccountAdjustmentNotificationMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email) && $mail->action === 'created';
        });
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

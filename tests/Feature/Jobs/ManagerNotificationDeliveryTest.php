<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SendManagerNotificationJob;
use App\Mail\ManagerEventNotificationMail;
use App\Models\ManagerNotificationRecipient;
use App\Models\ManagerNotificationSetting;
use App\Services\SmsService;
use App\Services\Tenancy\TenantDatabaseManager;
use App\Services\TenantMailService;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Api\ApiRouteTestCase;

class ManagerNotificationDeliveryTest extends ApiRouteTestCase
{
    public function testEnabledEventIsDeliveredToConfiguredEmailAndLogged(): void
    {
        Mail::fake();
        ManagerNotificationSetting::query()->create([
            'tenant_id' => $this->tenant->id,
            'email_enabled' => true,
            'sms_enabled' => false,
            'email_events' => ['expense.created'],
            'sms_events' => [],
        ]);
        ManagerNotificationRecipient::query()->create([
            'tenant_id' => $this->tenant->id,
            'channel' => 'email',
            'label' => 'Finance',
            'destination' => 'finance@example.com',
            'normalized_destination' => 'finance@example.com',
        ]);
        $sms = \Mockery::mock(SmsService::class);
        $sms->shouldNotReceive('send');

        (new SendManagerNotificationJob(
            $this->tenant->id,
            'event-uuid-1',
            'expense.created',
            'Expense #45 was added.',
            ['Amount' => 'LKR 25.00'],
            'Manager',
        ))->handle(app(TenantDatabaseManager::class), app(TenantMailService::class), $sms);

        Mail::assertSent(ManagerEventNotificationMail::class, fn (ManagerEventNotificationMail $mail) => $mail->eventLabel === 'Expense added'
            && $mail->envelope()->subject === 'Expense added'
            && $mail->summary === 'Expense #45 was added.'
            && $mail->details['Amount'] === 'LKR 25.00',
        );
        $this->assertDatabaseHas('manager_notification_deliveries', [
            'tenant_id' => $this->tenant->id,
            'event_uuid' => 'event-uuid-1',
            'event_key' => 'expense.created',
            'channel' => 'email',
            'destination' => 'finance@example.com',
            'status' => 'sent',
        ]);
    }

    public function testUnselectedEventIsNotSentOrLogged(): void
    {
        Mail::fake();
        ManagerNotificationSetting::query()->create([
            'tenant_id' => $this->tenant->id,
            'email_enabled' => true,
            'sms_enabled' => false,
            'email_events' => ['payment.created'],
            'sms_events' => [],
        ]);
        ManagerNotificationRecipient::query()->create([
            'tenant_id' => $this->tenant->id,
            'channel' => 'email',
            'label' => null,
            'destination' => 'finance@example.com',
            'normalized_destination' => 'finance@example.com',
        ]);

        (new SendManagerNotificationJob(
            $this->tenant->id,
            'event-uuid-2',
            'expense.created',
            'Expense #45 was added.',
            [],
            null,
        ))->handle(
            app(TenantDatabaseManager::class),
            app(TenantMailService::class),
            \Mockery::mock(SmsService::class),
        );

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('manager_notification_deliveries', ['event_uuid' => 'event-uuid-2']);
    }

    public function testSmsDeliveryFailureIsRecordedAndCanSucceedOnRetry(): void
    {
        ManagerNotificationSetting::query()->create([
            'tenant_id' => $this->tenant->id,
            'email_enabled' => false,
            'sms_enabled' => true,
            'email_events' => [],
            'sms_events' => ['stock.created'],
        ]);
        ManagerNotificationRecipient::query()->create([
            'tenant_id' => $this->tenant->id,
            'channel' => 'sms',
            'label' => 'Owner',
            'destination' => '+94771234567',
            'normalized_destination' => '+94771234567',
        ]);
        $sms = \Mockery::mock(SmsService::class);
        $sms->shouldReceive('send')
            ->twice()
            ->withArgs(fn (string $number, string $message, int $tenantId): bool => $number === '+94771234567'
                && str_contains($message, 'Stock entry added')
                && !str_contains(strtolower($message), 'member')
                && $tenantId === $this->tenant->id,
            )
            ->andReturn(false, true);

        $job = new SendManagerNotificationJob(
            $this->tenant->id,
            'event-uuid-3',
            'stock.created',
            'Stock entry #9 was added.',
            ['Quantity' => 10],
            null,
        );

        try {
            $job->handle(app(TenantDatabaseManager::class), app(TenantMailService::class), $sms);
            $this->fail('A failed SMS send should trigger the queue retry.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('SMS provider did not confirm delivery.', $exception->getMessage());
        }

        $this->assertDatabaseHas('manager_notification_deliveries', [
            'event_uuid' => 'event-uuid-3',
            'channel' => 'sms',
            'destination' => '+94771234567',
            'status' => 'failed',
            'attempts' => 1,
        ]);

        $job->handle(app(TenantDatabaseManager::class), app(TenantMailService::class), $sms);

        $this->assertDatabaseHas('manager_notification_deliveries', [
            'event_uuid' => 'event-uuid-3',
            'channel' => 'sms',
            'destination' => '+94771234567',
            'status' => 'sent',
            'attempts' => 2,
        ]);
    }
}

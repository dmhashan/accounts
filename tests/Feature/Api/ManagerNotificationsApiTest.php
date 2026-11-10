<?php

namespace Tests\Feature\Api;

use App\Models\ManagerNotificationRecipient;
use App\Models\Tenant;
use Illuminate\Support\Str;

class ManagerNotificationsApiTest extends ApiRouteTestCase
{
    public function testManagerCanConfigureChannelsEventsAndManageRecipients(): void
    {
        $this->actingAsUser(['settings.configuration']);

        $this->getJson('/api/settings/manager-notifications')
            ->assertOk()
            ->assertJsonPath('data.settings.email_enabled', false)
            ->assertJsonPath('data.settings.sms_enabled', false)
            ->assertJsonFragment(['key' => 'display.released', 'label' => 'Stock released to display', 'group' => 'Display']);

        $this->putJson('/api/settings/manager-notifications', [
            'email_enabled' => true,
            'sms_enabled' => true,
            'email_events' => ['payment.created', 'expense.deleted'],
            'sms_events' => ['stock.updated', 'display.released'],
        ])->assertOk()
            ->assertJsonPath('data.email_enabled', true)
            ->assertJsonPath('data.sms_events.1', 'display.released');

        $this->putJson('/api/settings/manager-notifications', [
            'email_events' => ['payments.all'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email_events.0']);

        $emailId = $this->postJson('/api/settings/manager-notifications/recipients', [
            'channel' => 'email',
            'label' => 'Finance manager',
            'destination' => ' Finance@Example.com ',
        ])->assertCreated()
            ->assertJsonPath('data.destination', 'finance@example.com')
            ->json('data.id');

        $smsId = $this->postJson('/api/settings/manager-notifications/recipients', [
            'channel' => 'sms',
            'label' => 'Owner',
            'destination' => '+94 77-123-4567',
        ])->assertCreated()
            ->assertJsonPath('data.destination', '+94771234567')
            ->json('data.id');

        $this->postJson('/api/settings/manager-notifications/recipients', [
            'channel' => 'email',
            'destination' => 'FINANCE@example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['destination']);

        $this->postJson('/api/settings/manager-notifications/recipients', [
            'channel' => 'sms',
            'destination' => '0771234567',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['destination']);

        $this->putJson('/api/settings/manager-notifications/recipients/' . $emailId, [
            'label' => 'Accounts manager',
            'destination' => 'accounts@example.com',
        ])->assertOk()
            ->assertJsonPath('data.label', 'Accounts manager');

        $this->getJson('/api/settings/manager-notifications')
            ->assertOk()
            ->assertJsonPath('data.recipients.0.destination', 'accounts@example.com');

        $this->deleteJson('/api/settings/manager-notifications/recipients/' . $smsId)
            ->assertOk();
        $this->assertDatabaseMissing('manager_notification_recipients', ['id' => $smsId]);
        $this->assertDatabaseHas('manager_notification_recipients', [
            'id' => $emailId,
            'tenant_id' => $this->tenant->id,
            'destination' => 'accounts@example.com',
        ]);
    }

    public function testSettingsAndRecipientEndpointsRequireSettingsPermission(): void
    {
        $this->actingAsUser(['dashboard.view']);

        $this->getJson('/api/settings/manager-notifications')->assertForbidden();
        $this->postJson('/api/settings/manager-notifications/recipients', [
            'channel' => 'email',
            'destination' => 'manager@example.com',
        ])->assertForbidden();
    }

    public function testRecipientOperationsAreScopedToTheCurrentTenant(): void
    {
        $this->actingAsUser(['settings.manage']);
        $otherTenant = Tenant::create([
            'name' => 'Other Gym',
            'domain' => 'other-gym',
            'tenant_uuid' => Str::uuid()->toString(),
            'use_custom_landing_page' => false,
        ]);
        $otherRecipient = ManagerNotificationRecipient::query()->create([
            'tenant_id' => $otherTenant->id,
            'channel' => 'email',
            'label' => 'Private',
            'destination' => 'private@example.com',
            'normalized_destination' => 'private@example.com',
        ]);

        $this->getJson('/api/settings/manager-notifications')
            ->assertOk()
            ->assertJsonMissing(['destination' => 'private@example.com']);
        $this->deleteJson('/api/settings/manager-notifications/recipients/' . $otherRecipient->id)
            ->assertNotFound();

        $this->assertDatabaseHas('manager_notification_recipients', ['id' => $otherRecipient->id]);
    }
}

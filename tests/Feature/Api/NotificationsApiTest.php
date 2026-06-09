<?php

namespace Tests\Feature\Api;

use App\Jobs\SendBulkNotificationJob;
use App\Models\BulkNotification;
use App\Models\Tenant;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

class NotificationsApiTest extends ApiRouteTestCase
{
    public function testDraftNotificationCrudOnlyIncludesMembersFromCurrentTenant(): void
    {
        $user = $this->actingAsUser(['notifications.send']);
        $member = $this->createMember();
        $otherTenant = Tenant::create([
            'name' => 'Other Gym',
            'domain' => 'other-notifications',
            'tenant_uuid' => Str::uuid()->toString(),
        ]);
        $otherMember = $this->createMember(attributes: ['tenant_id' => $otherTenant->id]);

        $response = $this->postJson('/api/notifications', [
            'name' => 'Membership Notice',
            'message' => 'Your membership is ready.',
            'member_ids' => [$member->id, $otherMember->id],
        ])->assertCreated()
            ->assertJsonPath('name', 'Membership Notice');

        $notificationId = (int) $response->json('id');

        $this->assertDatabaseHas('bulk_notifications', [
            'id' => $notificationId,
            'tenant_id' => $this->tenant->id,
            'created_by' => $user->id,
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('bulk_notification_recipients', [
            'bulk_notification_id' => $notificationId,
            'member_id' => $member->id,
        ]);
        $this->assertDatabaseMissing('bulk_notification_recipients', [
            'bulk_notification_id' => $notificationId,
            'member_id' => $otherMember->id,
        ]);

        $this->getJson('/api/notifications?search=Membership')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.recipients_count', 1);

        $this->putJson('/api/notifications/' . $notificationId, [
            'name' => 'Updated Notice',
            'message' => 'Updated message.',
            'member_ids' => [$member->id],
        ])->assertOk()
            ->assertJsonPath('name', 'Updated Notice');

        $this->deleteJson('/api/notifications/' . $notificationId)->assertNoContent();
        $this->assertDatabaseMissing('bulk_notifications', ['id' => $notificationId]);
    }

    public function testSendingNotificationQueuesJobAndLocksTheDraft(): void
    {
        Queue::fake();
        $this->actingAsUser(['notifications.send']);
        $member = $this->createMember();

        $notificationId = (int) $this->postJson('/api/notifications', [
            'name' => 'Queued Notice',
            'message' => 'This will be sent.',
            'member_ids' => [$member->id],
        ])->assertCreated()->json('id');

        $this->postJson('/api/notifications/' . $notificationId . '/send')
            ->assertOk()
            ->assertJsonPath('recipient_count', 1);

        Queue::assertPushed(SendBulkNotificationJob::class);
        $this->assertDatabaseHas('bulk_notifications', [
            'id' => $notificationId,
            'status' => 'processing',
        ]);

        $this->putJson('/api/notifications/' . $notificationId, [
            'name' => 'Changed',
            'message' => 'Changed',
            'member_ids' => [$member->id],
        ])->assertUnprocessable();

        $this->deleteJson('/api/notifications/' . $notificationId)->assertUnprocessable();
        $this->postJson('/api/notifications/' . $notificationId . '/send')->assertUnprocessable();
    }

    public function testNotificationFromAnotherTenantIsForbidden(): void
    {
        $user = $this->actingAsUser(['notifications.send']);
        $otherTenant = Tenant::create([
            'name' => 'Other Gym',
            'domain' => 'other-drafts',
            'tenant_uuid' => Str::uuid()->toString(),
        ]);
        $notification = BulkNotification::create([
            'tenant_id' => $otherTenant->id,
            'created_by' => $user->id,
            'name' => 'Private Notice',
            'message' => 'Other tenant only.',
            'status' => 'draft',
        ]);

        $this->getJson('/api/notifications/' . $notification->id)->assertForbidden();
        $this->deleteJson('/api/notifications/' . $notification->id)->assertForbidden();
    }
}

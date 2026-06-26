<?php

namespace Tests\Feature\Api;

use App\Jobs\SendBulkNotificationJob;
use Illuminate\Support\Facades\Queue;

class NotificationsApiTest extends ApiRouteTestCase
{
    public function testDraftNotificationCrudIncludesSelectedMembers(): void
    {
        $user = $this->actingAsUser(['notifications.send']);
        $member = $this->createMember();

        $response = $this->postJson('/api/notifications', [
            'name' => 'Membership Notice',
            'message' => 'Your membership is ready.',
            'member_ids' => [$member->id],
        ])->assertCreated()
            ->assertJsonPath('name', 'Membership Notice');

        $notificationId = (int) $response->json('id');

        $this->assertDatabaseHas('bulk_notifications', [
            'id' => $notificationId,
            'created_by' => $user->id,
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('bulk_notification_recipients', [
            'bulk_notification_id' => $notificationId,
            'member_id' => $member->id,
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
}

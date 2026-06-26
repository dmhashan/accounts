<?php

namespace Tests\Feature\Console;

use App\Jobs\SendMemberNotificationJob;
use App\Models\MemberNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Api\ApiRouteTestCase;

class AutomatedNotificationCommandsTest extends ApiRouteTestCase
{
    public function testMilestoneCommandQueuesEligibleMembersForTheCurrentDatabase(): void
    {
        Queue::fake();
        $today = today();
        $member = $this->createMember(attributes: [
            'date_of_birth' => $today->copy()->subYears(25)->toDateString(),
        ]);
        $otherMember = $this->createMember(attributes: [
            'date_of_birth' => $today->copy()->subYears(30)->toDateString(),
        ]);

        $this->assertSame(0, Artisan::call('notifications:member-milestones'));

        Queue::assertPushed(SendMemberNotificationJob::class, 2);
        Queue::assertPushed(
            SendMemberNotificationJob::class,
            fn (SendMemberNotificationJob $job) => $this->readPrivate($job, 'tenantId') === $this->tenant->id
                && $this->readPrivate($job, 'memberId') === $member->id,
        );
        Queue::assertPushed(
            SendMemberNotificationJob::class,
            fn (SendMemberNotificationJob $job) => $this->readPrivate($job, 'tenantId') === $this->tenant->id
                && $this->readPrivate($job, 'memberId') === $otherMember->id,
        );
    }

    public function testMilestoneCommandDeduplicatesPerMemberAndTenant(): void
    {
        Queue::fake();
        $today = today();
        $member = $this->createMember(attributes: [
            'date_of_birth' => $today->copy()->subYears(25)->toDateString(),
        ]);
        MemberNotification::create([
            'member_id' => $member->id,
            'type' => 'member_birthday',
            'title' => 'Happy Birthday',
            'body' => 'Already queued today.',
        ]);

        $this->assertSame(0, Artisan::call('notifications:member-milestones'));

        Queue::assertNothingPushed();
    }

    private function readPrivate(object $object, string $property): mixed
    {
        $reflection = new \ReflectionClass($object);
        $reflectedProperty = $reflection->getProperty($property);
        $reflectedProperty->setAccessible(true);

        return $reflectedProperty->getValue($object);
    }
}

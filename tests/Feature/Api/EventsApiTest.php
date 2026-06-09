<?php

namespace Tests\Feature\Api;

use App\Models\Event;
use App\Models\Tenant;
use Illuminate\Support\Str;

class EventsApiTest extends ApiRouteTestCase
{
    public function testEventCrudGeneratesUniqueSlugsAndIsTenantScoped(): void
    {
        $this->actingAsUser(['events.manage']);

        $firstId = (int) $this->postJson('/api/events', $this->eventPayload())
            ->assertCreated()
            ->json('data.id');
        $secondId = (int) $this->postJson('/api/events', $this->eventPayload())
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseHas('events', ['id' => $firstId, 'slug' => 'summer-camp']);
        $this->assertDatabaseHas('events', ['id' => $secondId, 'slug' => 'summer-camp-1']);

        $this->getJson('/api/events?search=Summer')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/events/' . $firstId)
            ->assertOk()
            ->assertJsonPath('id', $firstId)
            ->assertJsonPath('name', 'Summer Camp');

        $this->putJson('/api/events/' . $firstId, $this->eventPayload([
            'name' => 'Updated Camp',
            'slug' => 'updated-camp',
        ]))->assertOk();

        $this->assertDatabaseHas('events', ['id' => $firstId, 'slug' => 'updated-camp']);

        $otherTenant = Tenant::create([
            'name' => 'Other Gym',
            'domain' => 'other-events',
            'tenant_uuid' => Str::uuid()->toString(),
        ]);
        $otherEvent = $this->createEvent(['tenant_id' => $otherTenant->id]);

        $this->getJson('/api/events/' . $otherEvent->id)->assertForbidden();

        $this->deleteJson('/api/events/' . $secondId)
            ->assertOk()
            ->assertJsonPath('message', 'Event deleted successfully.');
    }

    public function testRegistrationFeePaymentAndAttendanceAreRecordedOnce(): void
    {
        $this->actingAsUser(['events.manage']);
        $event = $this->createEvent([
            'ticket_fee' => 1000,
            'additional_ticket_fee' => 500,
        ]);
        $member = $this->createMember();
        $account = $this->createCompanyAccount();

        $registrationId = (int) $this->postJson('/api/events/' . $event->id . '/registrations', [
            'member_id' => $member->id,
            'name' => 'Member Tester',
            'email' => 'member@example.com',
            'guests' => [
                ['name' => 'Guest One'],
                ['name' => 'Guest Two'],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.total_fee', 2000)
            ->json('data.id');

        $this->postJson('/api/events/' . $event->id . '/registrations/' . $registrationId . '/mark-paid', [
            'account_id' => $account->id,
        ])->assertOk()
            ->assertJsonPath('data.is_paid', true);

        $this->assertDatabaseHas('company_account_transactions', [
            'tenant_id' => $this->tenant->id,
            'company_account_id' => $account->id,
            'model_name' => 'event_registration',
            'reference_id' => $registrationId,
            'type' => 'credit',
            'amount' => 2000,
        ]);

        $this->postJson('/api/events/' . $event->id . '/registrations/' . $registrationId . '/mark-paid', [
            'account_id' => $account->id,
        ])->assertUnprocessable();

        $this->putJson('/api/events/' . $event->id . '/registrations/' . $registrationId, [
            'name' => 'Changed Name',
        ])->assertUnprocessable();

        $this->deleteJson('/api/events/' . $event->id . '/registrations/' . $registrationId)
            ->assertUnprocessable();

        $this->postJson('/api/events/' . $event->id . '/registrations/' . $registrationId . '/mark-attendance')
            ->assertOk()
            ->assertJsonPath('data.is_attended', true);

        $this->postJson('/api/events/' . $event->id . '/registrations/' . $registrationId . '/mark-attendance')
            ->assertUnprocessable();

        $this->getJson('/api/events/' . $event->id . '/registrations')
            ->assertOk()
            ->assertJsonPath('meta.attended_total', 1)
            ->assertJsonPath('meta.attended_members', 1)
            ->assertJsonPath('meta.attended_guests', 2);
    }

    public function testMemberCannotBeRegisteredTwiceAndCrossTenantMemberIsRejected(): void
    {
        $this->actingAsUser(['events.manage']);
        $event = $this->createEvent();
        $member = $this->createMember();

        $payload = [
            'member_id' => $member->id,
            'name' => $member->name,
        ];

        $this->postJson('/api/events/' . $event->id . '/registrations', $payload)->assertCreated();
        $this->postJson('/api/events/' . $event->id . '/registrations', $payload)->assertConflict();

        $otherTenant = Tenant::create([
            'name' => 'Other Gym',
            'domain' => 'other-members',
            'tenant_uuid' => Str::uuid()->toString(),
        ]);
        $otherMember = $this->createMember(attributes: ['tenant_id' => $otherTenant->id]);

        $this->postJson('/api/events/' . $event->id . '/registrations', [
            'member_id' => $otherMember->id,
            'name' => $otherMember->name,
        ])->assertNotFound();
    }

    private function createEvent(array $attributes = []): Event
    {
        return Event::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Event',
            'slug' => 'test-event-' . Str::random(5),
            'start_datetime' => now()->addWeek(),
            'end_datetime' => now()->addWeek()->addHours(2),
            'venue' => 'Test Hall',
            'ticket_fee' => 100,
            'additional_ticket_fee' => 50,
            'is_active' => true,
        ], $attributes));
    }

    private function eventPayload(array $attributes = []): array
    {
        return array_merge([
            'name' => 'Summer Camp',
            'slug' => 'summer-camp',
            'start_datetime' => now()->addWeek()->toISOString(),
            'end_datetime' => now()->addWeek()->addHours(2)->toISOString(),
            'venue' => 'Main Hall',
            'ticket_fee' => 1000,
            'additional_ticket_fee' => 500,
            'is_active' => true,
        ], $attributes);
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\MemberBodyMeasurement;
use App\Services\TenantConfigurationService;

class MemberBodyMeasurementsApiTest extends ApiRouteTestCase
{
    public function testBodyMeasurementMandatoryFieldsAreRequired(): void
    {
        $this->actingAsUser(['users.view', 'users.edit']);
        $member = $this->createMember();

        $this->postJson('/api/members/' . $member->id . '/body-measurements', [
            'measurements' => [],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['weight', 'height', 'measurement_date']);
    }

    public function testBodyMeasurementsCanBeRecordedAndListedWithConfiguredFields(): void
    {
        $this->actingAsUser(['users.view', 'users.edit']);
        $member = $this->createMember();

        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            TenantConfigurationService::BODY_MEASUREMENT_FIELDS_KEY => json_encode([
                [
                    'key' => 'chest',
                    'label' => 'Upper Chest',
                    'enabled' => true,
                    'sort_order' => 10,
                    'built_in' => true,
                ],
                [
                    'key' => 'left_arm',
                    'label' => 'Left Arm',
                    'enabled' => false,
                    'sort_order' => 20,
                    'built_in' => true,
                ],
                [
                    'key' => 'shoulder',
                    'label' => 'Shoulder',
                    'enabled' => true,
                    'sort_order' => 30,
                    'built_in' => false,
                ],
            ]),
        ]);

        $this->postJson('/api/members/' . $member->id . '/body-measurements', [
            'weight' => 72.5,
            'height' => 175,
            'measurement_date' => now()->subDays(8)->toDateString(),
            'measurements' => [
                'chest' => 38.25,
                'left_arm' => 12.75,
                'shoulder' => 44,
            ],
            'notes' => 'Initial measurement',
        ])
            ->assertCreated()
            ->assertJsonPath('data.measurements.chest', 38.25)
            ->assertJsonPath('data.measurements.shoulder', 44);

        $record = MemberBodyMeasurement::sole();
        $this->assertEquals([
            'chest' => 38.25,
            'shoulder' => 44.0,
        ], $record->measurements);

        $this->postJson('/api/members/' . $member->id . '/body-measurements', [
            'weight' => 71,
            'height' => 175,
            'measurement_date' => now()->subDay()->toDateString(),
            'measurements' => [
                'chest' => 37.5,
                'shoulder' => 43.5,
            ],
        ])->assertCreated();

        $response = $this->getJson('/api/members/' . $member->id . '/body-measurements')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('latest.weight', 71)
            ->assertJsonPath('previous.weight', 72.5);

        $fieldKeys = collect($response->json('fields'))->pluck('key');
        $this->assertTrue($fieldKeys->contains('chest'));
        $this->assertTrue($fieldKeys->contains('shoulder'));
        $this->assertFalse($fieldKeys->contains('left_arm'));
    }

    public function testBodyMeasurementsCanBeUpdatedAndDeleted(): void
    {
        $this->actingAsUser(['users.view', 'users.edit']);
        $member = $this->createMember();
        $record = MemberBodyMeasurement::create([
            'member_id' => $member->id,
            'weight' => 80,
            'height' => 180,
            'measurement_date' => now()->subDays(2)->toDateString(),
            'measurements' => ['chest' => 40],
            'notes' => 'Before update',
        ]);

        $this->putJson('/api/members/' . $member->id . '/body-measurements/' . $record->id, [
            'weight' => 79.25,
            'height' => 180.5,
            'measurement_date' => now()->subDay()->toDateString(),
            'measurements' => ['chest' => 39.5],
            'notes' => 'After update',
        ])
            ->assertOk()
            ->assertJsonPath('data.weight', 79.25)
            ->assertJsonPath('data.measurements.chest', 39.5);

        $this->assertDatabaseHas('member_body_measurements', [
            'id' => $record->id,
            'notes' => 'After update',
        ]);

        $this->deleteJson('/api/members/' . $member->id . '/body-measurements/' . $record->id)
            ->assertOk();

        $this->assertDatabaseMissing('member_body_measurements', [
            'id' => $record->id,
        ]);
    }
}

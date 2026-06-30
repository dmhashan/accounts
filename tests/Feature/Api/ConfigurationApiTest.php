<?php

namespace Tests\Feature\Api;

use App\Services\TenantConfigurationService;

class ConfigurationApiTest extends ApiRouteTestCase
{
    public function testTenantAppearanceSettingsCanBeUpdated(): void
    {
        $this->actingAsUser(['settings.manage']);

        $this->putJson('/api/settings/configuration', [
            'general.color_theme' => 'forest',
            'general.color_mode' => 'dark',
        ])
            ->assertOk()
            ->assertJsonFragment([
                'general.color_theme' => 'forest',
                'general.color_mode' => 'dark',
            ]);

        $this->assertDatabaseHas('tenant_configurations', [
            'key' => 'general.color_theme',
            'value' => 'forest',
        ]);
    }

    public function testTenantAppearanceSettingsRejectUnknownValues(): void
    {
        $this->actingAsUser(['settings.manage']);

        $this->putJson('/api/settings/configuration', [
            'general.color_theme' => 'neon-rainbow',
            'general.color_mode' => 'midnight',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'general.color_theme',
                'general.color_mode',
            ]);
    }

    public function testBodyMeasurementFieldsHaveDefaultsAndCanBeConfigured(): void
    {
        $this->actingAsUser(['settings.manage']);

        $defaultData = $this->getJson('/api/settings/configuration')
            ->assertOk()
            ->json('data');
        $defaultConfig = $defaultData[TenantConfigurationService::BODY_MEASUREMENT_FIELDS_KEY];

        $defaultFields = json_decode($defaultConfig, true);
        $this->assertContains('Chest', array_column($defaultFields, 'label'));
        $this->assertContains('2" Above Navel', array_column($defaultFields, 'label'));

        $configuredFields = [
            [
                'key' => 'chest',
                'label' => 'Upper Chest',
                'enabled' => true,
                'sort_order' => 30,
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
                'sort_order' => 10,
                'built_in' => false,
            ],
            [
                'key' => 'weight',
                'label' => 'Weight',
                'enabled' => true,
                'sort_order' => 1,
                'built_in' => false,
            ],
        ];

        $storedData = $this->putJson('/api/settings/configuration', [
            TenantConfigurationService::BODY_MEASUREMENT_FIELDS_KEY => json_encode($configuredFields),
        ])
            ->assertOk()
            ->json('data');
        $storedConfig = $storedData[TenantConfigurationService::BODY_MEASUREMENT_FIELDS_KEY];

        $storedFields = collect(json_decode($storedConfig, true))->keyBy('key');

        $this->assertSame('Upper Chest', $storedFields['chest']['label']);
        $this->assertFalse($storedFields['left_arm']['enabled']);
        $this->assertSame('Shoulder', $storedFields['shoulder']['label']);
        $this->assertFalse($storedFields->has('weight'));
    }
}

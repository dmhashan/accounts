<?php

namespace Tests\Feature\Api;

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
}

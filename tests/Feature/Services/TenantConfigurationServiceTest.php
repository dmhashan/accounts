<?php

namespace Tests\Feature\Services;

use App\Services\TenantConfigurationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Api\ApiRouteTestCase;

class TenantConfigurationServiceTest extends ApiRouteTestCase
{
    public function testItSupportsIsolatedTenantConfigurationTablesWithoutTenantId(): void
    {
        Schema::dropIfExists('tenant_configurations');
        Schema::create('tenant_configurations', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('title', 255);
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $service = app(TenantConfigurationService::class);

        $updated = $service->updateBatch($this->tenant->id, [
            'general.color_theme' => 'forest',
            'notifications.sms.enabled' => '1',
        ]);

        $this->assertSame('forest', $updated['general.color_theme']);
        $this->assertSame('1', $service->all($this->tenant->id)['notifications.sms.enabled']);
        $this->assertDatabaseHas('tenant_configurations', [
            'key' => 'general.color_theme',
            'value' => 'forest',
        ]);
    }
}

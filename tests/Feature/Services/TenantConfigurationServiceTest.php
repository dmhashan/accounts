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

    public function testConfigurableMemberAndBiometricIdGeneration(): void
    {
        $service = app(TenantConfigurationService::class);
        $service->updateBatch($this->tenant->id, [
            'member.id_prefix' => 'MEM-',
            'member.id_next_number' => '100',
            'member.id_padding' => '4',
            'biometric.id_same_as_member_id' => '0',
            'biometric.id_prefix' => 'BIO-',
            'biometric.id_next_number' => '500',
            'biometric.id_padding' => '4',
        ]);

        $next = \App\Models\Member::generateNextIds($this->tenant->id);

        $this->assertSame('MEM-0100', $next['next_member_id']);
        $this->assertSame('BIO-0500', $next['next_biometric_id']);
    }

    public function testBackfillMemberAndBiometricIdConfigurationsMigration(): void
    {
        $this->createMember(attributes: ['biometric_member_id' => 'GYM-0010']);
        $this->createMember(attributes: ['biometric_member_id' => 'GYM-0042']);

        $migration = require database_path('migrations/tenant/2026_08_04_210000_backfill_member_and_biometric_id_configurations.php');
        $migration->up();

        $this->assertDatabaseHas('tenant_configurations', [
            'key' => 'member.id_prefix',
            'value' => 'GYM-',
        ]);
        $this->assertDatabaseHas('tenant_configurations', [
            'key' => 'member.id_next_number',
            'value' => '43',
        ]);
        $this->assertDatabaseHas('tenant_configurations', [
            'key' => 'member.id_padding',
            'value' => '4',
        ]);
    }

    public function testNumericPrefixGenerationSequenceDoesNotRepeatPrefix(): void
    {
        $service = app(TenantConfigurationService::class);
        $service->updateBatch($this->tenant->id, [
            'member.id_prefix' => '1993',
            'member.id_next_number' => '1',
            'member.id_padding' => '4',
        ]);

        $next1 = \App\Models\Member::generateNextIds($this->tenant->id);
        $this->assertSame('19930001', $next1['next_member_id']);

        $m1 = $this->createMember();
        $this->assertSame('19930001', $m1->biometric_member_id);
        $this->assertSame('2', $service->all($this->tenant->id)['member.id_next_number']);

        $m2 = $this->createMember();
        $this->assertSame('19930002', $m2->biometric_member_id);
        $this->assertSame('3', $service->all($this->tenant->id)['member.id_next_number']);

        $next3 = \App\Models\Member::generateNextIds($this->tenant->id);
        $this->assertSame('19930003', $next3['next_member_id']);
    }
}

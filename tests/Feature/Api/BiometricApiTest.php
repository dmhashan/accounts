<?php

namespace Tests\Feature\Api;

use App\Models\BiometricAccessEvent;
use App\Models\BiometricSyncLog;
use App\Models\Tenant;
use App\Services\BiometricSyncService;
use App\Services\TenantConfigurationService;
use Illuminate\Support\Str;

class BiometricApiTest extends ApiRouteTestCase
{
    public function testDeviceActionsAlwaysUseTheCurrentTenantAndClampDoorNumber(): void
    {
        $this->actingAsUser(['settings.manage']);
        $biometric = \Mockery::mock(BiometricSyncService::class);
        $this->app->instance(BiometricSyncService::class, $biometric);

        $biometric->shouldReceive('testConnection')
            ->once()
            ->withArgs(fn (Tenant $tenant) => $tenant->is($this->tenant))
            ->andReturn(['success' => true]);
        $biometric->shouldReceive('syncAllMembers')
            ->once()
            ->withArgs(fn (Tenant $tenant) => $tenant->is($this->tenant))
            ->andReturn(['message' => 'Synced.', 'synced' => 3, 'failed' => 1]);
        $biometric->shouldReceive('unlockDoor')
            ->once()
            ->withArgs(fn (Tenant $tenant, int $doorNo) => $tenant->is($this->tenant) && $doorNo === 1)
            ->andReturn(['success' => true]);
        $biometric->shouldReceive('keepDoorUnlocked')
            ->once()
            ->withArgs(fn (Tenant $tenant, int $doorNo) => $tenant->is($this->tenant) && $doorNo === 2)
            ->andReturn(['success' => true]);
        $biometric->shouldReceive('closeDoor')
            ->once()
            ->withArgs(fn (Tenant $tenant, int $doorNo) => $tenant->is($this->tenant) && $doorNo === 1)
            ->andReturn(['success' => true]);
        $biometric->shouldReceive('keepDoorClosed')
            ->once()
            ->withArgs(fn (Tenant $tenant, int $doorNo) => $tenant->is($this->tenant) && $doorNo === 3)
            ->andReturn(['success' => true]);
        $biometric->shouldReceive('getDoorStatus')
            ->once()
            ->withArgs(fn (Tenant $tenant, int $doorNo) => $tenant->is($this->tenant) && $doorNo === 4)
            ->andReturn(['success' => true, 'state' => 'closed', 'source' => 'device']);

        $this->postJson('/api/settings/biometric/test-connection')->assertOk();
        $this->postJson('/api/settings/biometric/sync-all')
            ->assertOk()
            ->assertJsonPath('data.synced', 3)
            ->assertJsonPath('data.failed', 1);
        $this->postJson('/api/settings/biometric/unlock', ['door_no' => 0])->assertOk();
        $this->postJson('/api/settings/biometric/keep-unlock', ['door_no' => 2])->assertOk();
        $this->postJson('/api/settings/biometric/close', ['door_no' => -5])->assertOk();
        $this->postJson('/api/settings/biometric/keep-close', ['door_no' => 3])->assertOk();
        $this->getJson('/api/settings/biometric/door-status?door_no=4')
            ->assertOk()
            ->assertJsonPath('state', 'closed')
            ->assertJsonPath('source', 'device');
    }

    public function testWebhookManagementUsesCurrentTenantConfiguration(): void
    {
        $this->actingAsUser(['settings.manage']);
        $biometric = \Mockery::mock(BiometricSyncService::class);
        $this->app->instance(BiometricSyncService::class, $biometric);

        $biometric->shouldReceive('configureWebhook')
            ->once()
            ->withArgs(fn (Tenant $tenant) => $tenant->is($this->tenant))
            ->andReturn(['success' => true]);
        $biometric->shouldReceive('getWebhookConfig')
            ->once()
            ->withArgs(fn (Tenant $tenant) => $tenant->is($this->tenant))
            ->andReturn([
                'success' => true,
                'data' => [
                    'HttpHostNotification' => [
                        'ipAddress' => 'api.example.test',
                        'portNo' => 443,
                        'url' => '/api/biometric/events/test-gym',
                        'protocolType' => 'HTTPS',
                    ],
                ],
            ]);

        $token = (string) $this->postJson('/api/settings/biometric/webhook/generate-token')
            ->assertOk()
            ->json('token');

        $this->assertSame(48, strlen($token));
        $this->assertSame(
            $token,
            app(TenantConfigurationService::class)->all($this->tenant->id)['biometric.webhook_token'],
        );

        $this->postJson('/api/settings/biometric/webhook/configure')
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->getJson('/api/settings/biometric/webhook/status')
            ->assertOk()
            ->assertJsonPath('config.ip', 'api.example.test')
            ->assertJsonPath('config.protocol', 'HTTPS');
    }

    public function testRecentLogsAndAccessEventsAreTenantScopedAndFiltered(): void
    {
        $this->actingAsUser(['settings.manage']);
        $otherTenant = Tenant::create([
            'name' => 'Other Gym',
            'domain' => 'other-biometric',
            'tenant_uuid' => Str::uuid()->toString(),
        ]);
        $member = $this->createMember();

        BiometricSyncLog::create([
            'tenant_id' => $this->tenant->id,
            'member_id' => $member->id,
            'biometric_member_id' => $member->biometric_member_id,
            'direction' => 'up',
            'action' => 'manual_sync',
            'status' => 'failed',
            'error_message' => 'Device offline',
            'synced_at' => now(),
            'created_at' => now(),
        ]);
        BiometricSyncLog::create([
            'tenant_id' => $otherTenant->id,
            'direction' => 'up',
            'action' => 'manual_sync',
            'status' => 'failed',
            'synced_at' => now(),
            'created_at' => now(),
        ]);

        BiometricAccessEvent::create([
            'tenant_id' => $this->tenant->id,
            'member_id' => $member->id,
            'employee_no' => $member->biometric_member_id,
            'person_name' => $member->name,
            'auth_method' => 'face',
            'result' => 'failed',
            'event_time' => now(),
            'created_at' => now(),
        ]);
        BiometricAccessEvent::create([
            'tenant_id' => $otherTenant->id,
            'person_name' => 'Other Member',
            'auth_method' => 'face',
            'result' => 'success',
            'event_time' => now(),
            'created_at' => now(),
        ]);

        $this->getJson('/api/settings/biometric/recent-logs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('failed_count', 1)
            ->assertJsonPath('data.0.member.id', $member->id);

        $this->getJson('/api/settings/biometric/access-events?result=failed')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('counts.all', 1)
            ->assertJsonPath('counts.failed', 1)
            ->assertJsonPath('counts.success', 0)
            ->assertJsonPath('data.0.person_name', $member->name);
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\BiometricAccessEvent;
use App\Models\BiometricSyncLog;
use App\Models\Member;
use App\Models\Tenant;
use App\Services\BiometricSyncService;
use App\Services\TenantConfigurationService;

class BiometricApiTest extends ApiRouteTestCase
{
    public function testMemberBiometricStatusIsAvailableWithoutSettingsPermission(): void
    {
        $this->actingAsUser(['members.view']);

        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'biometric.enabled' => '1',
            'biometric.device_maker' => 'hikvision',
            'biometric.device_ip' => '192.0.2.10',
            'biometric.sync_members' => '1',
            'biometric.access_control' => '1',
        ]);

        $this->getJson('/api/members/biometric-status')
            ->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.configured', true)
            ->assertJsonPath('data.sync_members', true)
            ->assertJsonPath('data.access_control', true);

        $this->getJson('/api/settings/configuration')->assertForbidden();
    }

    public function testMemberBiometricReadEndpointsUseMemberViewPermission(): void
    {
        $member = $this->createMember();

        BiometricSyncLog::create([
            'member_id' => $member->id,
            'biometric_member_id' => $member->biometric_member_id,
            'direction' => 'up',
            'action' => 'manual_sync',
            'status' => 'success',
            'synced_at' => now(),
            'created_at' => now(),
        ]);

        $this->actingAsUser(['members.view']);

        $this->getJson("/api/members/{$member->id}/biometric-logs")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'manual_sync');

        $this->postJson("/api/members/{$member->id}/biometric-sync")->assertForbidden();
    }

    public function testMemberBiometricActionsUseMemberEditPermissionWithoutSettingsPermission(): void
    {
        $this->actingAsUser(['members.view', 'members.edit']);
        $member = $this->createMember();

        $biometric = \Mockery::mock(BiometricSyncService::class);
        $this->app->instance(BiometricSyncService::class, $biometric);

        $biometric->shouldReceive('syncMember')
            ->once()
            ->withArgs(fn (Member $actual, string $action) => $actual->is($member) && $action === 'manual_sync')
            ->andReturnNull();

        $this->postJson("/api/members/{$member->id}/biometric-sync")
            ->assertOk()
            ->assertJsonPath('message', 'Member synced to biometric device.');
    }

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
        $member = $this->createMember();

        BiometricSyncLog::create([
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
            'direction' => 'up',
            'action' => 'manual_sync',
            'status' => 'success',
            'synced_at' => now(),
            'created_at' => now(),
        ]);

        BiometricAccessEvent::create([
            'member_id' => $member->id,
            'employee_no' => $member->biometric_member_id,
            'person_name' => $member->name,
            'auth_method' => 'face',
            'result' => 'failed',
            'event_time' => now(),
            'created_at' => now(),
        ]);
        BiometricAccessEvent::create([
            'person_name' => 'Other Member',
            'auth_method' => 'face',
            'result' => 'success',
            'event_time' => now(),
            'created_at' => now(),
        ]);

        $this->getJson('/api/settings/biometric/recent-logs')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('failed_count', 1)
            ->assertJsonPath('data.0.member.id', $member->id);

        $this->getJson('/api/settings/biometric/access-events?result=failed')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('counts.all', 2)
            ->assertJsonPath('counts.failed', 1)
            ->assertJsonPath('counts.success', 1)
            ->assertJsonPath('data.0.person_name', $member->name);
    }

    public function testFailedJobsCanBeRetriedAndDropped(): void
    {
        $this->actingAsUser(['settings.manage']);

        // Mock queue.failer
        $failer = \Mockery::mock(\Illuminate\Queue\Failed\FailedJobProviderInterface::class);
        $this->app->instance('queue.failer', $failer);

        $dummyJob = (object) [
            'id' => '12345',
            'connection' => 'database',
            'queue' => 'biometric',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\SyncBiometricMemberJob',
                'tenant_domain' => $this->tenant->domain,
                'data' => [
                    'commandName' => 'App\\Jobs\\SyncBiometricMemberJob',
                    'command' => serialize(new \stdClass),
                ],
            ]),
            'failed_at' => now()->toDateTimeString(),
            'exception' => 'Some exception',
        ];

        $otherDummyJob = (object) [
            'id' => '67890',
            'connection' => 'database',
            'queue' => 'biometric',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\SyncBiometricMemberJob',
                'tenant_domain' => 'other-tenant.com',
                'data' => [
                    'commandName' => 'App\\Jobs\\SyncBiometricMemberJob',
                    'command' => serialize(new \stdClass),
                ],
            ]),
            'failed_at' => now()->toDateTimeString(),
            'exception' => 'Some exception',
        ];

        // 1. Test queue status retrieves failed jobs
        $failer->shouldReceive('all')->once()->andReturn([$dummyJob, $otherDummyJob]);

        $this->getJson('/api/settings/biometric/queue-status')
            ->assertOk()
            ->assertJsonPath('failed_count', 1)
            ->assertJsonPath('failed.0.id', '12345');

        // 2. Test retrying job
        $failer->shouldReceive('find')->with('12345')->once()->andReturn($dummyJob);
        \Illuminate\Support\Facades\Artisan::shouldReceive('call')
            ->once()
            ->with('queue:retry', ['id' => ['12345']])
            ->andReturn(0);

        $this->postJson('/api/settings/biometric/failed-jobs/12345/retry')
            ->assertOk()
            ->assertJsonPath('message', 'Failed biometric job requeued.');

        // 3. Test dropping job
        $failer->shouldReceive('find')->with('12345')->once()->andReturn($dummyJob);
        $failer->shouldReceive('forget')->with('12345')->once()->andReturn(true);

        $this->deleteJson('/api/settings/biometric/failed-jobs/12345')
            ->assertOk()
            ->assertJsonPath('message', 'Failed biometric job dropped.');

        // 4. Test dropping non-existent job
        $failer->shouldReceive('find')->with('99999')->once()->andReturn(null);

        $this->deleteJson('/api/settings/biometric/failed-jobs/99999')
            ->assertStatus(404)
            ->assertJsonPath('message', 'Failed biometric job not found.');

        // 5. Test dropping other tenant's job
        $failer->shouldReceive('find')->with('67890')->once()->andReturn($otherDummyJob);

        $this->deleteJson('/api/settings/biometric/failed-jobs/67890')
            ->assertStatus(404)
            ->assertJsonPath('message', 'Failed biometric job not found.');
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\BiometricAccessEvent;
use App\Models\BiometricDeviceCommand;
use App\Models\MemberAttendance;
use App\Services\BiometricSyncService;
use App\Services\TenantConfigurationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ZktecoAdmsTest extends ApiRouteTestCase
{
    private string $deviceSn = 'NYU7252300323';

    protected function setUp(): void
    {
        parent::setUp();

        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'biometric.enabled' => '1',
            'biometric.sync_members' => '1',
            'biometric.device_maker' => 'zkteco',
            'biometric.device_model' => 'SenseFace 2a',
            'biometric.device_sn' => $this->deviceSn,
            'biometric.zk_fingerprint_alg' => '13',
            'biometric.zk_face_alg' => '4',
            'biometric.adms_delay' => '10',
        ]);
    }

    public function testAdmsHandshakeReturnsConfigOptions(): void
    {
        $response = $this->get("/iclock/cdata?SN={$this->deviceSn}&options=all");

        $response->assertOk();
        $this->assertStringContainsString("GET OPTION FROM: {$this->deviceSn}", $response->getContent());
        $this->assertStringContainsString('Delay=10', $response->getContent());
        $this->assertStringContainsString('ServerVersion=3.1.1', $response->getContent());
    }

    public function testMemberSyncQueuesCommandAndGetRequestReturnsIt(): void
    {
        $member = $this->createMember(attributes: [
            'name' => 'John Gymgoer',
            'biometric_member_id' => 'MEM-2026-0042',
        ]);

        app(BiometricSyncService::class)->syncMember($member, 'create');

        $this->assertDatabaseHas('biometric_device_commands', [
            'device_sn' => $this->deviceSn,
            'member_id' => $member->id,
            'status' => 'pending',
        ]);

        $response = $this->get("/iclock/getrequest?SN={$this->deviceSn}");
        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('DATA USER PIN=0042', $content);
        $this->assertStringContainsString('Name=John Gymgoer', $content);

        // Command status should now be 'sent'
        $command = BiometricDeviceCommand::where('member_id', $member->id)->first();
        $this->assertSame('sent', $command->status);
    }

    public function testDeviceCmdAcknowledgesExecution(): void
    {
        $member = $this->createMember(attributes: [
            'biometric_member_id' => 'MEM-2026-0042',
        ]);

        $cmd = BiometricDeviceCommand::create([
            'device_sn' => $this->deviceSn,
            'command_type' => 'DATA USER',
            'command_string' => "DATA USER PIN=0042\tName={$member->name}\tPri=0",
            'status' => 'sent',
            'member_id' => $member->id,
            'biometric_member_id' => $member->biometric_member_id,
            'action' => 'create',
        ]);

        $response = $this->call(
            'POST',
            "/iclock/devicecmd?SN={$this->deviceSn}",
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain'],
            "ID={$cmd->id}&Return=0&CMD=DATA USER",
        );

        $response->assertOk();
        $this->assertStringContainsString('OK', $response->getContent());

        $cmd->refresh();
        $this->assertSame('executed', $cmd->status);
        $this->assertSame(0, $cmd->return_code);
        $this->assertNotNull($cmd->executed_at);

        $member->refresh();
        $this->assertNotNull($member->biometric_last_synced_at);
    }

    public function testAttlogIngestsFaceScanAndMarksAttendance(): void
    {
        $member = $this->createMember(attributes: [
            'biometric_member_id' => 'MEM-2026-0042',
        ]);

        // Face scan (verifyType = 15)
        $rawLog = "0042\t2026-08-12 10:30:00\t0\t15\t0\t0\t0\r\n";

        $response = $this->call(
            'POST',
            "/iclock/cdata?SN={$this->deviceSn}&table=ATTLOG",
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain'],
            $rawLog,
        );

        $response->assertOk();
        $this->assertStringContainsString('OK: 1', $response->getContent());

        // Assert attendance marked
        $attendance = MemberAttendance::where('member_id', $member->id)->first();
        $this->assertNotNull($attendance);
        $this->assertStringStartsWith('2026-08-12', (string) $attendance->attended_date);

        // Assert access event logged
        $event = BiometricAccessEvent::where('member_id', $member->id)->first();
        $this->assertNotNull($event);
        $this->assertSame('success', $event->result);
        $this->assertSame('face', $event->auth_method);
        $this->assertSame('0042', $event->employee_no);
    }

    public function testAttlogIngestsFingerprintScan(): void
    {
        $member = $this->createMember(attributes: [
            'biometric_member_id' => 'MEM-2026-0045',
        ]);

        // Fingerprint scan (verifyType = 1)
        $rawLog = "0045\t2026-08-12 11:15:00\t0\t1\t0\t0\t0\r\n";

        $response = $this->call(
            'POST',
            "/iclock/cdata?SN={$this->deviceSn}&table=ATTLOG",
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain'],
            $rawLog,
        );

        $response->assertOk();

        $event = BiometricAccessEvent::where('member_id', $member->id)->first();
        $this->assertNotNull($event);
        $this->assertSame('success', $event->result);
        $this->assertSame('fingerprint', $event->auth_method);
    }

    public function testFdataStoresSnapshotAndLinksToRecentEvent(): void
    {
        $disk = config('filesystems.media_disk', 'public');
        Storage::fake($disk);

        $member = $this->createMember(attributes: [
            'biometric_member_id' => 'MEM-2026-0042',
        ]);

        // Push attendance log first
        $this->call(
            'POST',
            "/iclock/cdata?SN={$this->deviceSn}&table=ATTLOG",
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain'],
            "0042\t2026-08-12 10:30:00\t0\t15\t0\t0\t0",
        )->assertOk();

        // Push snapshot
        $this->call(
            'POST',
            "/iclock/fdata?SN={$this->deviceSn}&table=ATTPHOTO&PIN=0042",
            [],
            [],
            ['file' => UploadedFile::fake()->image('scan.jpg')],
            ['CONTENT_TYPE' => 'multipart/form-data'],
        )->assertOk();

        $event = BiometricAccessEvent::where('member_id', $member->id)->first();
        $this->assertNotNull($event);
        $this->assertNotNull($event->picture_path);
        Storage::disk($disk)->assertExists($event->picture_path);
    }

    public function testTestConnectionEndpointWithZkteco(): void
    {
        $this->actingAsUser(['settings.manage', 'settings.biometric']);

        // When offline (no heartbeat)
        $response = $this->postJson('/api/settings/biometric/test-connection');
        $response->assertStatus(422);
        $this->assertFalse($response->json('success'));

        // Ping to record heartbeat
        $this->get("/iclock/cdata?SN={$this->deviceSn}&options=all")->assertOk();

        // Now test connection should succeed
        $response = $this->postJson('/api/settings/biometric/test-connection');
        $response->assertOk();
        $this->assertTrue($response->json('success'));
    }

    public function testAdmsStatusEndpoint(): void
    {
        $this->actingAsUser(['settings.manage', 'settings.biometric']);

        $response = $this->getJson('/api/settings/biometric/adms-status');
        $response->assertOk();
        $this->assertSame($this->deviceSn, $response->json('data.sn'));
        $this->assertSame('SenseFace 2a', $response->json('data.model'));
        $this->assertSame('ZKFinger VX13.0', $response->json('data.algorithms.fingerprint'));
        $this->assertSame('ZKFace VX4.0 (Visible Light)', $response->json('data.algorithms.face'));
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\BiometricAccessEvent;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Services\BiometricSyncService;
use App\Services\TenantConfigurationService;
use Illuminate\Support\Facades\Http;

class EmployeesBiometricTest extends ApiRouteTestCase
{
    public function testCanDefineAndClearBiometricIdViaDedicatedEndpoint(): void
    {
        $this->actingAsUser(['employees.manage']);

        $employee = Employee::create([
            'name' => 'Kasun Silva',
            'status' => 'active',
            'joined_date' => '2026-01-01',
            'pay_method' => 'daily',
            'daily_rate' => 1500,
        ]);

        $this->assertNull($employee->biometric_id);

        // Define Biometric ID
        $response = $this->putJson("/api/employees/{$employee->id}/biometric-id", [
            'biometric_id' => 'EMP-1001',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.biometric_id', 'EMP-1001')
            ->assertJsonPath('message', 'Biometric ID updated successfully.');

        $employee->refresh();
        $this->assertSame('EMP-1001', $employee->biometric_id);

        // Clear Biometric ID
        $clearResponse = $this->putJson("/api/employees/{$employee->id}/biometric-id", [
            'biometric_id' => '',
        ]);

        $clearResponse->assertOk()
            ->assertJsonPath('data.biometric_id', null)
            ->assertJsonPath('message', 'Biometric ID removed successfully.');

        $employee->refresh();
        $this->assertNull($employee->biometric_id);
    }

    public function testBiometricIdMustBeUniqueAcrossEmployees(): void
    {
        $this->actingAsUser(['employees.manage']);

        Employee::create([
            'name' => 'First Employee',
            'biometric_id' => 'BIO-001',
            'status' => 'active',
            'joined_date' => '2026-01-01',
            'pay_method' => 'daily',
            'daily_rate' => 1000,
        ]);

        $second = Employee::create([
            'name' => 'Second Employee',
            'status' => 'active',
            'joined_date' => '2026-01-01',
            'pay_method' => 'daily',
            'daily_rate' => 1000,
        ]);

        // Attempting to use existing biometric_id should fail validation
        $response = $this->putJson("/api/employees/{$second->id}/biometric-id", [
            'biometric_id' => 'BIO-001',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['biometric_id']);

        // Updating with its own current biometric_id should pass
        $second->update(['biometric_id' => 'BIO-002']);
        $this->putJson("/api/employees/{$second->id}/biometric-id", [
            'biometric_id' => 'BIO-002',
        ])->assertOk();
    }

    public function testCanSetBiometricIdViaStandardEmployeeEndpoints(): void
    {
        $this->actingAsUser(['employees.manage']);

        // Create with biometric_id
        $createResponse = $this->postJson('/api/employees', [
            'name' => 'Sunil Perera',
            'biometric_id' => '1042',
            'employment_type' => 'full_time',
            'status' => 'active',
            'joined_date' => '2026-02-01',
            'pay_method' => 'daily',
            'daily_rate' => 2000,
        ]);

        $createResponse->assertCreated();
        $employeeId = $createResponse->json('data.id');

        $this->assertDatabaseHas('employees', [
            'id' => $employeeId,
            'biometric_id' => '1042',
        ]);

        // Show endpoint includes biometric_id and biometric_last_synced_at
        $showResponse = $this->getJson("/api/employees/{$employeeId}");
        $showResponse->assertOk()
            ->assertJsonPath('data.biometric_id', '1042')
            ->assertJsonPath('data.biometric_last_synced_at', null);

        // Update via standard PUT
        $this->putJson("/api/employees/{$employeeId}", [
            'name' => 'Sunil Perera Updated',
            'biometric_id' => '1043',
            'employment_type' => 'full_time',
            'status' => 'active',
            'joined_date' => '2026-02-01',
            'pay_method' => 'daily',
            'daily_rate' => 2000,
        ])->assertOk();

        $this->assertDatabaseHas('employees', [
            'id' => $employeeId,
            'biometric_id' => '1043',
        ]);
    }

    public function testDeviceInfoReturnsNotAssignedWhenNoBiometricId(): void
    {
        $this->actingAsUser(['employees.manage']);

        $employee = Employee::create([
            'name' => 'No Bio Employee',
            'status' => 'active',
            'joined_date' => '2026-01-01',
            'pay_method' => 'daily',
            'daily_rate' => 1000,
        ]);

        $response = $this->getJson("/api/employees/{$employee->id}/biometric-device-info");

        $response->assertOk()
            ->assertJsonPath('not_assigned', true)
            ->assertJsonPath('connection_failed', false);
    }

    public function testDeviceInfoAndSyncWithZktecoDriver(): void
    {
        $this->actingAsUser(['employees.manage']);

        // Configure ZKTeco ADMS device for tenant
        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'biometric.enabled' => '1',
            'biometric.device_maker' => 'zkteco',
            'biometric.device_model' => 'SenseFace 2a',
            'biometric.device_sn' => 'SF2A-TEST-001',
        ]);

        $employee = Employee::create([
            'name' => 'Kamal Silva',
            'biometric_id' => '2005',
            'gender' => 'male',
            'status' => 'active',
            'joined_date' => '2026-01-15',
            'pay_method' => 'daily',
            'daily_rate' => 1200,
        ]);

        // Device info returns structured person record
        $infoResponse = $this->getJson("/api/employees/{$employee->id}/biometric-device-info");
        $infoResponse->assertOk()
            ->assertJsonPath('connection_failed', false)
            ->assertJsonPath('not_assigned', false)
            ->assertJsonPath('person.employee_no', '2005')
            ->assertJsonPath('person.name', 'Kamal Silva')
            ->assertJsonPath('person.valid_enabled', true);

        // Sync to device enqueues command and updates last_synced_at
        $syncResponse = $this->postJson("/api/employees/{$employee->id}/biometric-sync");
        $syncResponse->assertOk()
            ->assertJsonPath('message', 'Employee sync queued for device.');

        $employee->refresh();
        $this->assertNotNull($employee->biometric_last_synced_at);

        $this->assertDatabaseHas('biometric_device_commands', [
            'device_sn' => 'SF2A-TEST-001',
            'biometric_member_id' => '2005',
            'status' => 'pending',
        ]);
    }

    public function testDeviceInfoAndSyncWithHikvisionDriver(): void
    {
        $this->actingAsUser(['employees.manage']);

        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'biometric.enabled' => '1',
            'biometric.device_maker' => 'hikvision',
            'biometric.device_model' => 'DS-K1T671',
            'biometric.device_ip' => '192.168.1.100',
            'biometric.device_port' => '80',
            'biometric.device_username' => 'admin',
            'biometric.device_password' => 'secret123',
        ]);

        $employee = Employee::create([
            'name' => 'Ruwan Bandara',
            'biometric_id' => '3001',
            'gender' => 'male',
            'status' => 'active',
            'joined_date' => '2026-01-01',
            'pay_method' => 'daily',
            'daily_rate' => 1500,
        ]);

        // Mock Hikvision ISAPI responses
        Http::fake([
            '*ISAPI/AccessControl/UserInfo/Search*' => Http::response([
                'UserInfoSearch' => [
                    'numOfMatches' => 1,
                    'UserInfo' => [
                        [
                            'employeeNo' => '3001',
                            'name' => 'Ruwan Bandara',
                            'userType' => 'normal',
                            'gender' => 'male',
                            'doorRight' => '1',
                            'Valid' => [
                                'enable' => true,
                                'beginTime' => '2026-01-01T00:00:00',
                                'endTime' => '2035-12-31T23:59:59',
                            ],
                            'numOfFace' => 1,
                            'numOfFP' => 2,
                            'numOfCard' => 1,
                        ],
                    ],
                ],
            ], 200),
            '*ISAPI/AccessControl/CardInfo/Search*' => Http::response([
                'CardInfoSearch' => [
                    'CardInfo' => [
                        ['cardNo' => 'CARD-9988'],
                    ],
                ],
            ], 200),
            '*ISAPI/AccessControl/UserInfo/Record*' => Http::response([
                'statusCode' => 1,
                'statusString' => 'OK',
            ], 200),
        ]);

        $infoResponse = $this->getJson("/api/employees/{$employee->id}/biometric-device-info");

        $infoResponse->assertOk()
            ->assertJsonPath('person.employee_no', '3001')
            ->assertJsonPath('person.name', 'Ruwan Bandara')
            ->assertJsonPath('person.door_right', '1')
            ->assertJsonPath('person.valid_enabled', true)
            ->assertJsonPath('face.enrolled', true)
            ->assertJsonPath('face.count', 1)
            ->assertJsonPath('fingerprint.enrolled', true)
            ->assertJsonPath('fingerprint.count', 2)
            ->assertJsonPath('card.assigned', true)
            ->assertJsonPath('card.card_numbers.0', 'CARD-9988');

        // Sync to Hikvision device
        $syncResponse = $this->postJson("/api/employees/{$employee->id}/biometric-sync");
        $syncResponse->assertOk()
            ->assertJsonPath('message', 'Employee synced to biometric device.');

        $employee->refresh();
        $this->assertNotNull($employee->biometric_last_synced_at);
    }

    public function testEmployeeAttendanceMarkedFromBiometricAccessEvent(): void
    {
        $employee = Employee::create([
            'name' => 'Anura Kumara',
            'biometric_id' => '5001',
            'status' => 'active',
            'joined_date' => '2026-01-01',
            'pay_method' => 'daily',
            'daily_rate' => 2500,
        ]);

        $service = app(BiometricSyncService::class);

        // Simulate incoming face authentication event (minor code 75 = face success)
        $eventPayload = [
            'employeeNoString' => '5001',
            'time' => '2026-10-24T08:30:00',
            'minor' => 75,
            'name' => 'Anura Kumara',
        ];

        $service->handleIncomingEvent($this->tenant, $eventPayload);

        // Verify BiometricAccessEvent was recorded
        $this->assertDatabaseHas('biometric_access_events', [
            'employee_no' => '5001',
            'result' => 'success',
            'auth_method' => 'face',
        ]);

        // Verify EmployeeAttendance was automatically recorded
        $attendance = EmployeeAttendance::where('employee_id', $employee->id)->first();
        $this->assertNotNull($attendance);
        $this->assertSame('2026-10-24', $attendance->attendance_date->toDateString());
        $this->assertSame('present', $attendance->status);
        $this->assertSame('08:30', $attendance->check_in_at);
    }
}

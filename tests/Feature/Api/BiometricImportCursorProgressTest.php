<?php

namespace Tests\Feature\Api;

use App\Services\BiometricSyncService;
use App\Services\TenantConfigurationService;
use Illuminate\Support\Facades\Http;

class BiometricImportCursorProgressTest extends ApiRouteTestCase
{
    public function testCursorAdvancesToLastProcessedAuthEventOnPartialFailure(): void
    {
        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'biometric.enabled' => '1',
            'biometric.device_maker' => 'hikvision',
            'biometric.device_model' => 'DS-K1T320MFWX-B',
            'biometric.device_ip' => 'device.local',
            'biometric.device_port' => '80',
            'biometric.device_username' => 'admin',
            'biometric.device_password' => 'secret',
            'biometric.access_events_sync_from' => '2026-06-05T00:00',
        ]);

        Http::fake([
            '*device.local*/ISAPI/AccessControl/AcsEvent?format=json' => Http::sequence()
                ->push([
                    'statusCode' => 1,
                    'AcsEvent' => [
                        'responseStatusStrg' => 'MORE',
                        'numOfMatches' => 1,
                        'InfoList' => [
                            [
                                'employeeNoString' => '0001',
                                'time' => '2026-06-05T10:35:00+05:30',
                                'minor' => 75,
                                'name' => 'Member One',
                            ],
                        ],
                    ],
                ], 200)
                ->push([
                    'statusCode' => 6,
                    'statusString' => 'Invalid Content',
                ], 400),
        ]);

        $result = app(BiometricSyncService::class)->importDeviceEvents(
            $this->tenant,
            '2026-06-05T00:00:00+05:30',
            '2026-06-05T23:59:00+05:30',
        );

        $this->assertSame(1, $result['errors']);

        $all = app(TenantConfigurationService::class)->all($this->tenant->id);
        $this->assertSame('2026-06-05T10:35', $all['biometric.access_events_sync_from']);
    }
}

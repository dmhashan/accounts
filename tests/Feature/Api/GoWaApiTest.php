<?php

namespace Tests\Feature\Api;

use App\Models\Member;
use Illuminate\Support\Facades\Http;

class GoWaApiTest extends ApiRouteTestCase
{
    public function testCanSaveAndRetrieveGoWaConfiguration(): void
    {
        $this->actingAsUser(['settings.configuration', 'settings.manage']);

        $goWaGroups = [
            [
                'group_id' => '120363023456789012@g.us',
                'rules' => [
                    ['boolean' => 'and', 'field' => 'gender', 'value' => 'male'],
                ],
            ],
        ];

        $this->putJson('/api/settings/configuration', [
            'general.gowa_enabled' => '1',
            'general.gowa_url' => 'http://76.13.212.71:32769',
            'general.gowa_api_key' => 'secret_key_123',
            'general.gowa_session_id' => 'device_1',
            'general.gowa_groups' => json_encode($goWaGroups),
        ])->assertOk();

        $data = $this->getJson('/api/settings/configuration')
            ->assertOk()
            ->json('data');

        $this->assertSame('1', $data['general.gowa_enabled']);
        $this->assertSame('http://76.13.212.71:32769', $data['general.gowa_url']);
        $this->assertSame('secret_key_123', $data['general.gowa_api_key']);
        $this->assertSame('device_1', $data['general.gowa_session_id']);
    }

    public function testTestConnectionEndpointWithMockedGoWaAppInfo(): void
    {
        $this->actingAsUser(['settings.configuration', 'settings.manage']);

        Http::fake([
            'http://76.13.212.71:32769/app/info' => Http::response([
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'version' => 'v9.0.0',
                    'device_os_name' => 'GOWA',
                    'max_file_size' => 52428800,
                    'max_image_size' => 20971520,
                    'max_video_size' => 104857600,
                ],
            ], 200),
            'http://76.13.212.71:32769/devices' => Http::response([
                'code' => 'SUCCESS',
                'results' => [
                    ['id' => 'device_1', 'state' => 'connected', 'jid' => '94779998888@s.whatsapp.net'],
                ],
            ], 200),
        ]);

        $this->postJson('/api/settings/gowa/test-connection', [
            'url' => 'http://76.13.212.71:32769',
            'api_key' => 'secret',
            'session_id' => 'device_1',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', "Connected to GoWA server (v9.0.0, GOWA). WhatsApp device 'device_1' is ACTIVE (connected).");
    }

    public function testCompareGroupEvaluatesRulesAndComparesWithGoWaParticipants(): void
    {
        $this->actingAsUser(['settings.configuration', 'settings.manage']);

        $this->createMember(null, [
            'name' => 'John Doe',
            'gender' => 'male',
            'phone_number' => '0771112222',
            'is_active' => true,
        ]);

        $this->createMember(null, [
            'name' => 'Jane Smith',
            'gender' => 'female',
            'phone_number' => '0773334444',
            'is_active' => true,
        ]);

        Http::fake([
            'http://76.13.212.71:32769/group/info*' => Http::response([
                'code' => 200,
                'data' => [
                    'participants' => [
                        ['id' => '94771112222@s.whatsapp.net'], // John Doe already in group
                        ['id' => '94779998888@s.whatsapp.net'], // Non-system member in group
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/settings/gowa/groups/compare', [
            'url' => 'http://76.13.212.71:32769',
            'api_key' => 'secret',
            'session_id' => 'device_1',
            'group' => [
                'group_id' => '120363023456789012@g.us',
                'rules' => [
                    ['boolean' => 'and', 'field' => 'gender', 'value' => 'male'],
                ],
            ],
        ])->assertOk();

        $response->assertJsonPath('success', true)
            ->assertJsonPath('matching_system_count', 1)
            ->assertJsonPath('gowa_participants_count', 2)
            ->assertJsonPath('to_add_count', 0)
            ->assertJsonPath('to_remove_count', 1);
    }

    public function testSyncGroupPerformsBulkAddAndRemove(): void
    {
        $this->actingAsUser(['settings.configuration', 'settings.manage']);

        Http::fake([
            'http://76.13.212.71:32769/devices' => Http::response([
                'code' => 'SUCCESS',
                'results' => [['id' => 'device_1', 'state' => 'connected', 'jid' => '94779998888@s.whatsapp.net']],
            ], 200),
            'http://76.13.212.71:32769/user/check*' => Http::response([
                'code' => 'SUCCESS',
                'results' => ['is_registered' => true],
            ], 200),
            'http://76.13.212.71:32769/group/invite-link*' => Http::response([
                'code' => 'SUCCESS',
                'results' => ['invite_link' => 'https://chat.whatsapp.com/TEST_INVITE'],
            ], 200),
            'http://76.13.212.71:32769/send/message' => Http::response([
                'code' => 'SUCCESS',
                'message' => 'Message sent',
            ], 200),
        ]);

        $this->postJson('/api/settings/gowa/groups/sync', [
            'url' => 'http://76.13.212.71:32769',
            'api_key' => 'secret',
            'session_id' => 'device_1',
            'group_id' => '120363023456789012@g.us',
            'action' => 'add',
            'phones' => ['0771112222'],
            'async' => false,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('added.0', '0771112222');
    }

    public function testSyncGroupDispatchesJobWhenAsyncOrLargePayload(): void
    {
        $this->actingAsUser(['settings.configuration', 'settings.manage']);

        \Illuminate\Support\Facades\Queue::fake();

        $phones = array_map(fn ($i) => "07710000{$i}", range(10, 30));

        $this->postJson('/api/settings/gowa/groups/sync', [
            'url' => 'http://76.13.212.71:32769',
            'api_key' => 'secret',
            'session_id' => 'device_1',
            'group_id' => '120363023456789012@g.us',
            'action' => 'add',
            'phones' => $phones,
        ])->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonPath('async', true)
            ->assertJsonPath('queued_count', count($phones));

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SyncGoWaGroupJob::class);
    }

    public function testGroupAdminsAreProtectedFromRemoval(): void
    {
        $this->actingAsUser(['settings.configuration', 'settings.manage']);

        Http::fake([
            'http://76.13.212.71:32769/group/info*' => Http::response([
                'code' => 200,
                'results' => [
                    'Participants' => [
                        [
                            'PhoneNumber' => '94779998888@s.whatsapp.net',
                            'IsAdmin' => true,
                        ],
                        [
                            'PhoneNumber' => '94776665555@s.whatsapp.net',
                            'IsAdmin' => false,
                        ],
                    ],
                ],
            ], 200),
            'http://76.13.212.71:32769/group/participants/remove' => Http::response(['code' => 200, 'message' => 'success'], 200),
        ]);

        $service = app(\App\Services\GoWaService::class);
        $result = $service->removeParticipants('http://76.13.212.71:32769', '120363023456789012@g.us', ['0779998888', '0776665555'], 'secret', 'device_1');

        $this->assertContains('0776665555', $result['removed']);
        $this->assertNotContains('0779998888', $result['removed']);
        $this->assertEquals('Cannot remove group admin', $result['failed'][0]['reason']);
    }

    public function testAddParticipantsSendsGroupInviteLinkMessage(): void
    {
        Http::fake([
            'http://76.13.212.71:32769/devices' => Http::response([
                'code' => 'SUCCESS',
                'results' => [['id' => 'device_1', 'state' => 'connected', 'jid' => '94779998888@s.whatsapp.net']],
            ], 200),
            'http://76.13.212.71:32769/user/check*' => Http::response([
                'code' => 'SUCCESS',
                'results' => ['is_registered' => true],
            ], 200),
            'http://76.13.212.71:32769/group/invite-link*' => Http::response([
                'code' => 'SUCCESS',
                'results' => ['invite_link' => 'https://chat.whatsapp.com/TEST_INVITE'],
            ], 200),
            'http://76.13.212.71:32769/send/message' => Http::response([
                'code' => 'SUCCESS',
                'message' => 'Message sent',
            ], 200),
        ]);

        $service = app(\App\Services\GoWaService::class);
        $result = $service->addParticipants('http://76.13.212.71:32769', '120363023456789012@g.us', ['+94771234567'], 'secret', 'device_1');

        $this->assertTrue($result['success']);
        $this->assertContains('+94771234567', $result['added']);
        $this->assertEquals('https://chat.whatsapp.com/TEST_INVITE', $result['invite_link']);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return $request->url() === 'http://76.13.212.71:32769/send/message'
                && str_contains($request['message'], 'https://chat.whatsapp.com/TEST_INVITE');
        });
    }
}

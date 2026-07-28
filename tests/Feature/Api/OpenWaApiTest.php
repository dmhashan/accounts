<?php

namespace Tests\Feature\Api;

use App\Models\Member;
use Illuminate\Support\Facades\Http;

class OpenWaApiTest extends ApiRouteTestCase
{
    public function testCanSaveAndRetrieveOpenWaConfiguration(): void
    {
        $this->actingAsUser(['settings.configuration', 'settings.manage']);

        $openWaGroups = [
            [
                'group_id' => '120363023456789012@g.us',
                'rules' => [
                    ['boolean' => 'and', 'field' => 'gender', 'value' => 'male'],
                ],
            ],
        ];

        $this->putJson('/api/settings/configuration', [
            'general.openwa_enabled' => '1',
            'general.openwa_url' => 'http://openwa.test:8080',
            'general.openwa_api_key' => 'secret_key_123',
            'general.openwa_session_id' => 'default',
            'general.openwa_groups' => json_encode($openWaGroups),
        ])->assertOk();

        $data = $this->getJson('/api/settings/configuration')
            ->assertOk()
            ->json('data');

        $this->assertSame('1', $data['general.openwa_enabled']);
        $this->assertSame('http://openwa.test:8080', $data['general.openwa_url']);
        $this->assertSame('secret_key_123', $data['general.openwa_api_key']);
        $this->assertSame('default', $data['general.openwa_session_id']);
    }

    public function testTestConnectionEndpointWithMockedHttp(): void
    {
        $this->actingAsUser(['settings.configuration', 'settings.manage']);

        Http::fake([
            'http://openwa.test:8080/health' => Http::response(['status' => 'ONLINE'], 200),
        ]);

        $this->postJson('/api/settings/openwa/test-connection', [
            'url' => 'http://openwa.test:8080',
            'api_key' => 'secret',
            'session_id' => 'default',
        ])->assertOk()
            ->assertJsonPath('success', true);
    }

    public function testCompareGroupEvaluatesRulesAndComparesWithOpenWaParticipants(): void
    {
        $this->actingAsUser(['settings.configuration', 'settings.manage']);

        $this->createMember(null, [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'male',
            'phone_number' => '0771112222',
            'is_active' => true,
        ]);

        $this->createMember(null, [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'gender' => 'female',
            'phone_number' => '0773334444',
            'is_active' => true,
        ]);

        Http::fake([
            'http://openwa.test:8080/api/default/getGroupMembers' => Http::response([
                'response' => [
                    ['id' => '94771112222@c.us'], // John Doe already in group
                    ['id' => '94779998888@c.us'], // Non-system member in group
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/settings/openwa/groups/compare', [
            'url' => 'http://openwa.test:8080',
            'api_key' => 'secret',
            'session_id' => 'default',
            'group' => [
                'group_id' => '120363023456789012@g.us',
                'rules' => [
                    ['boolean' => 'and', 'field' => 'gender', 'value' => 'male'],
                ],
            ],
        ])->assertOk();

        $response->assertJsonPath('success', true)
            ->assertJsonPath('matching_system_count', 1)
            ->assertJsonPath('openwa_participants_count', 2)
            ->assertJsonPath('to_add_count', 0)
            ->assertJsonPath('to_remove_count', 1);
    }

    public function testSyncGroupPerformsBulkAddAndRemove(): void
    {
        $this->actingAsUser(['settings.configuration', 'settings.manage']);

        Http::fake([
            'http://openwa.test:8080/api/default/addParticipant' => Http::response(['success' => true], 200),
        ]);

        $this->postJson('/api/settings/openwa/groups/sync', [
            'url' => 'http://openwa.test:8080',
            'api_key' => 'secret',
            'session_id' => 'default',
            'group_id' => '120363023456789012@g.us',
            'action' => 'add',
            'phones' => ['0771112222'],
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('added.0', '0771112222');
    }
}

<?php

namespace Tests\Feature\Api;

use App\Services\WhatsApp\WhatsAppClientInterface;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\Facades\Http;

class WhatsAppApiTest extends ApiRouteTestCase
{
    public function testCanSaveAndRetrieveWhatsAppComponentConfig(): void
    {
        $this->actingAsUser(['settings.configuration', 'settings.manage']);

        $this->putJson('/api/settings/whatsapp/config', [
            'enabled' => true,
            'url' => 'http://76.13.212.71:32769',
            'api_key' => 'secret_key_123',
            'session_id' => 'device_1',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.url', 'http://76.13.212.71:32769');

        $data = $this->getJson('/api/settings/whatsapp/config')
            ->assertOk()
            ->json('data');

        $this->assertTrue($data['enabled']);
        $this->assertSame('http://76.13.212.71:32769', $data['url']);
        $this->assertSame('secret_key_123', $data['api_key']);
        $this->assertSame('device_1', $data['session_id']);
    }

    public function testTestConnectionEndpoint(): void
    {
        $this->actingAsUser(['settings.configuration', 'settings.manage']);

        Http::fake([
            'http://76.13.212.71:32769/app/info' => Http::response([
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'version' => 'v9.0.0',
                    'device_os_name' => 'GOWA',
                ],
            ], 200),
            'http://76.13.212.71:32769/devices/device_1/status' => Http::response([
                'code' => 'SUCCESS',
                'results' => [
                    'is_connected' => true,
                    'is_logged_in' => true,
                    'device_id' => 'device_1',
                ],
            ], 200),
        ]);

        $this->postJson('/api/settings/whatsapp/test-connection', [
            'url' => 'http://76.13.212.71:32769',
            'api_key' => 'secret',
            'session_id' => 'device_1',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', "Connected to GoWA server (v9.0.0, GOWA). WhatsApp device 'device_1' is ACTIVE (connected).");
    }

    public function testGetMessagesEndpointReturnsParsedOpenApi9Schema(): void
    {
        $this->actingAsUser(['settings.configuration', 'settings.manage']);

        // Set tenant config
        $this->putJson('/api/settings/whatsapp/config', [
            'enabled' => true,
            'url' => 'http://76.13.212.71:32769',
            'api_key' => 'secret_key_123',
            'session_id' => 'device_1',
        ])->assertOk();

        $member = $this->createMember(null, [
            'name' => 'Kasun Perera',
            'phone_number' => '0771234567',
            'whatsapp_number' => '0771234567',
            'allow_whatsapp' => true,
        ]);

        // OpenAPI 9.0 ChatMessagesResponse schema
        Http::fake([
            'http://76.13.212.71:32769/chat/94771234567@s.whatsapp.net/messages*' => Http::response([
                'code' => 'SUCCESS',
                'message' => 'Success get chat messages',
                'results' => [
                    'data' => [
                        [
                            'id' => 'msg_1',
                            'chat_jid' => '94771234567@s.whatsapp.net',
                            'sender_jid' => '94771234567@s.whatsapp.net',
                            'sender_display_name' => 'Kasun Perera',
                            'content' => 'Hi, what is my membership status?',
                            'timestamp' => '2026-08-13T10:00:00Z',
                            'is_from_me' => false,
                            'media_type' => null,
                        ],
                        [
                            'id' => 'msg_2',
                            'chat_jid' => '94771234567@s.whatsapp.net',
                            'sender_jid' => 'me',
                            'content' => 'Hello Kasun, your membership is active until Dec 2026.',
                            'timestamp' => '2026-08-13T10:01:00Z',
                            'is_from_me' => true,
                            'media_type' => null,
                        ],
                    ],
                    'pagination' => [
                        'limit' => 50,
                        'offset' => 0,
                        'total' => 2,
                    ],
                ],
            ], 200),
            'http://76.13.212.71:32769/devices' => Http::response([
                'results' => [
                    ['id' => 'device_1', 'state' => 'connected', 'jid' => '94779998888@s.whatsapp.net'],
                ],
            ], 200),
        ]);

        $res = $this->getJson("/api/settings/whatsapp/messages?member_id={$member->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $messages = $res->json('messages');
        $this->assertCount(2, $messages);
        $this->assertSame('Hi, what is my membership status?', $messages[0]['message']);
        $this->assertFalse($messages[0]['from_me']);
        $this->assertSame('Kasun Perera', $messages[0]['sender_display_name']);
        $this->assertSame('Hello Kasun, your membership is active until Dec 2026.', $messages[1]['message']);
        $this->assertTrue($messages[1]['from_me']);
    }

    public function testSendMessageEndpointDispatchesMessage(): void
    {
        $this->actingAsUser(['settings.configuration', 'settings.manage']);

        // Set tenant config
        $this->putJson('/api/settings/whatsapp/config', [
            'enabled' => true,
            'url' => 'http://76.13.212.71:32769',
            'api_key' => 'secret_key_123',
            'session_id' => 'device_1',
        ])->assertOk();

        $member = $this->createMember(null, [
            'name' => 'Nimal Silva',
            'phone_number' => '0779991111',
            'allow_whatsapp' => true,
        ]);

        // OpenAPI 9.0 SendResponse schema
        Http::fake([
            'http://76.13.212.71:32769/send/message' => Http::response([
                'code' => 'SUCCESS',
                'message' => 'Success',
                'results' => [
                    'message_id' => '3EB0B430B6F8F1D0E053AC120E0A9E5C',
                    'status' => 'success',
                ],
            ], 200),
            'http://76.13.212.71:32769/devices' => Http::response([
                'results' => [
                    ['id' => 'device_1', 'state' => 'connected', 'jid' => '94779998888@s.whatsapp.net'],
                ],
            ], 200),
        ]);

        $this->postJson('/api/settings/whatsapp/send', [
            'member_id' => $member->id,
            'message' => 'Welcome to our fitness center!',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Message sent successfully.')
            ->assertJsonPath('data.id', '3EB0B430B6F8F1D0E053AC120E0A9E5C');
    }

    public function testCheckUserEndpoint(): void
    {
        $this->actingAsUser(['settings.configuration', 'settings.manage']);

        $this->putJson('/api/settings/whatsapp/config', [
            'enabled' => true,
            'url' => 'http://76.13.212.71:32769',
        ])->assertOk();

        Http::fake([
            'http://76.13.212.71:32769/user/check*' => Http::response([
                'code' => 'SUCCESS',
                'message' => 'Success check user',
                'results' => [
                    'is_on_whatsapp' => true,
                ],
            ], 200),
            'http://76.13.212.71:32769/devices' => Http::response([
                'results' => [['id' => 'dev_1']],
            ], 200),
        ]);

        $this->getJson('/api/settings/whatsapp/check-user?phone=0771234567')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('on_whatsapp', true);
    }

    public function testGetQrCodeEndpoint(): void
    {
        $this->actingAsUser(['settings.configuration', 'settings.manage']);

        Http::fake([
            'http://76.13.212.71:32769/devices/device_1/login' => Http::response([
                'code' => 'SUCCESS',
                'results' => [
                    'device_id' => 'device_1',
                    'qr_link' => 'http://76.13.212.71:32769/statics/images/qrcode/scan-qr-123.png',
                    'qr_duration' => 30,
                ],
            ], 200),
        ]);

        $this->getJson('/api/settings/whatsapp/qr-code?url=http://76.13.212.71:32769&session_id=device_1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('qr_link', 'http://76.13.212.71:32769/statics/images/qrcode/scan-qr-123.png');
    }

    public function testWhatsAppServiceDriverCanBeExtendedAndSwapped(): void
    {
        /** @var WhatsAppService $service */
        $service = app(WhatsAppService::class);

        $mockCustomDriver = new class implements WhatsAppClientInterface
        {
            public function testConnection(array $config): array
            {
                return ['success' => true, 'message' => 'Custom driver connected'];
            }

            public function getDeviceStatus(array $config): array
            {
                return ['connected' => true, 'device_id' => 'mock_dev'];
            }

            public function getMessages(string $number, int $limit = 50, array $options = []): array
            {
                return ['success' => true, 'phone' => $number, 'messages' => []];
            }

            public function sendMessage(string $number, string $message, array $options = []): array
            {
                return ['success' => true, 'message' => 'Mock sent: ' . $message];
            }

            public function sendMedia(string $number, string $mediaUrl, string $caption = '', string $mediaType = 'image', array $options = []): array
            {
                return ['success' => true, 'message' => 'Mock media sent'];
            }

            public function checkUser(string $number, array $options = []): array
            {
                return ['success' => true, 'on_whatsapp' => true];
            }

            public function getUserAvatar(string $number, array $options = []): array
            {
                return ['success' => true, 'url' => 'http://example.com/avatar.jpg'];
            }

            public function getUserInfo(string $number, array $options = []): array
            {
                return ['success' => true, 'info' => ['status' => 'online']];
            }

            public function getLoginQr(array $config = []): array
            {
                return ['success' => true, 'qr_link' => 'http://example.com/qr.png'];
            }

            public function markAsRead(string $messageId, string $number, array $options = []): array
            {
                return ['success' => true];
            }

            public function sendChatPresence(string $number, string $action = 'start', array $options = []): array
            {
                return ['success' => true];
            }
        };

        $service->extend('custom_provider', $mockCustomDriver);

        $result = $service->send('0771234567', 'Test message', ['driver' => 'custom_provider']);

        $this->assertTrue($result['success']);
        $this->assertSame('Mock sent: Test message', $result['message']);
    }
}

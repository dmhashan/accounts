<?php

namespace App\Services\WhatsApp\Drivers;

use App\Services\WhatsApp\WhatsAppClientInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoWaDriver implements WhatsAppClientInterface
{
    /**
     * Test connection to the GoWA server instance.
     */
    public function testConnection(array $config): array
    {
        $url = rtrim($config['url'] ?? '', '/');
        $apiKey = $config['api_key'] ?? null;
        $sessionId = $config['session_id'] ?? null;

        if (empty($url)) {
            return [
                'success' => false,
                'message' => 'GoWA server URL is required.',
            ];
        }

        try {
            $response = $this->httpClient($apiKey, $sessionId)
                ->timeout(8)
                ->get("{$url}/app/info");

            if ($response->successful()) {
                $body = $response->json();
                $data = $body['results'] ?? $body['data'] ?? $body['response'] ?? $body['result'] ?? $body;
                $version = $data['version'] ?? 'v9.0.0';
                $osName = $data['device_os_name'] ?? $data['os'] ?? 'GOWA';

                $devStatus = $this->getDeviceStatus($config);
                $deviceState = $devStatus['state'];
                $deviceId = $devStatus['device_id'];
                $isConnected = $devStatus['connected'];

                if (!$isConnected && $deviceId) {
                    return [
                        'success' => false,
                        'message' => "GoWA server is online ({$version}), but WhatsApp device '{$deviceId}' is NOT PAIRED ({$deviceState}). Please scan QR code in your GoWA dashboard to connect WhatsApp.",
                        'data' => $data,
                        'device_state' => $deviceState,
                    ];
                }

                return [
                    'success' => true,
                    'message' => "Connected to GoWA server ({$version}, {$osName}). WhatsApp device '{$deviceId}' is ACTIVE ({$deviceState}).",
                    'data' => $data,
                    'device_state' => $deviceState,
                ];
            }

            // Fallback health check
            $healthResp = $this->httpClient($apiKey, $sessionId)
                ->timeout(8)
                ->get("{$url}/health");

            if ($healthResp->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connected to GoWA server successfully.',
                    'data' => $healthResp->json() ?? [],
                ];
            }

            return [
                'success' => false,
                'message' => 'GoWA server returned error status: ' . ($response->status() ?: $healthResp->status()),
            ];
        } catch (\Throwable $e) {
            Log::warning('GoWaDriver testConnection failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Failed to reach GoWA server: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Inspect WhatsApp device connection status on GoWA server.
     * Checks GET /app/status or GET /devices/{device_id}/status and falls back to GET /devices.
     */
    public function getDeviceStatus(array $config): array
    {
        $url = rtrim($config['url'] ?? '', '/');
        $apiKey = $config['api_key'] ?? null;
        $sessionId = $config['session_id'] ?? null;
        $deviceId = $this->resolveDeviceId($url, $apiKey, $sessionId);

        // 1. Check GET /devices/{device_id}/status (OpenAPI 9.0)
        if (!empty($deviceId)) {
            try {
                $resp = $this->httpClient($apiKey, $deviceId)->timeout(5)->get("{$url}/devices/{$deviceId}/status");

                if ($resp->successful()) {
                    $body = $resp->json();
                    $res = $body['results'] ?? [];
                    $isConnected = (bool) ($res['is_connected'] ?? false) && (bool) ($res['is_logged_in'] ?? false);
                    $state = $isConnected ? 'connected' : 'disconnected';

                    return [
                        'connected' => $isConnected,
                        'device_id' => $deviceId,
                        'state' => $state,
                        'jid' => null,
                    ];
                }
            } catch (\Throwable $e) {
                Log::debug('GoWaDriver /devices/{id}/status check error', ['error' => $e->getMessage()]);
            }
        }

        // 2. Check GET /app/status (OpenAPI 9.0)
        try {
            $resp = $this->httpClient($apiKey, $deviceId)->timeout(5)->get("{$url}/app/status");

            if ($resp->successful()) {
                $body = $resp->json();
                $res = $body['results'] ?? $body['data'] ?? [];

                if (isset($res['is_connected']) || isset($res['is_logged_in'])) {
                    $isConnected = (bool) ($res['is_connected'] ?? false) && (bool) ($res['is_logged_in'] ?? false);
                    $jid = $res['jid'] ?? null;
                    $state = $isConnected ? 'connected' : (!empty($jid) ? 'connecting' : 'not paired yet');

                    return [
                        'connected' => $isConnected,
                        'device_id' => $res['device_id'] ?? $deviceId,
                        'state' => $state,
                        'jid' => $jid,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::debug('GoWaDriver /app/status check failed, falling back', ['error' => $e->getMessage()]);
        }

        // 3. Fallback check: GET /devices
        try {
            $resp = $this->httpClient($apiKey, $deviceId)->timeout(5)->get("{$url}/devices");

            if ($resp->successful()) {
                $devices = $resp->json()['results'] ?? $resp->json()['data'] ?? [];

                if (is_array($devices) && !empty($devices)) {
                    foreach ($devices as $dev) {
                        if (($dev['id'] ?? '') === $deviceId || empty($deviceId)) {
                            $st = strtolower($dev['state'] ?? '');
                            $hasJid = !empty($dev['jid']);
                            $isConnected = ($st === 'connected' || $st === 'logged_in') && $hasJid;
                            $statusLabel = $isConnected ? 'connected' : ($hasJid ? $dev['state'] : 'not paired yet');

                            return [
                                'connected' => $isConnected,
                                'device_id' => $dev['id'] ?? $deviceId,
                                'state' => $statusLabel,
                                'jid' => $dev['jid'] ?? null,
                            ];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::debug('GoWaDriver getDeviceStatus fallback failed', ['error' => $e->getMessage()]);
        }

        return ['connected' => true, 'device_id' => $deviceId, 'state' => 'unknown', 'jid' => null];
    }

    /**
     * Read chat messages for a specific phone number or JID via GET /chat/{chat_jid}/messages.
     * Compliant with OpenAPI 9.0 ChatMessagesResponse and ChatMessage schema.
     */
    public function getMessages(string $number, int $limit = 50, array $options = []): array
    {
        $url = rtrim($options['url'] ?? '', '/');
        $apiKey = $options['api_key'] ?? null;
        $sessionId = $options['session_id'] ?? null;
        $sessionId = $this->resolveDeviceId($url, $apiKey, $sessionId);
        $jid = $this->formatForGoWa($number);
        $offset = (int) ($options['offset'] ?? 0);

        if (empty($url)) {
            return [
                'success' => false,
                'phone' => $number,
                'jid' => $jid,
                'messages' => [],
                'message' => 'GoWA server URL is not configured.',
            ];
        }

        $queryParams = [
            'limit' => $limit,
            'offset' => $offset,
        ];

        if (!empty($options['search'])) {
            $queryParams['search'] = $options['search'];
        }

        if (!empty($options['media_only'])) {
            $queryParams['media_only'] = 'true';
        }

        try {
            // Attempt 1: Raw JID path
            $response = $this->httpClient($apiKey, $sessionId)
                ->timeout(10)
                ->get("{$url}/chat/{$jid}/messages", $queryParams);

            if (!$response->successful()) {
                // Attempt 2: URL encoded JID path (%40)
                $encodedJid = urlencode($jid);
                $response = $this->httpClient($apiKey, $sessionId)
                    ->timeout(10)
                    ->get("{$url}/chat/{$encodedJid}/messages", $queryParams);
            }

            if (!$response->successful()) {
                // Attempt 3: Query parameter fallback
                $fallbackParams = array_merge($queryParams, ['chat_jid' => $jid]);
                $response = $this->httpClient($apiKey, $sessionId)
                    ->timeout(10)
                    ->get("{$url}/chat/messages", $fallbackParams);
            }

            if ($response->successful()) {
                $body = $response->json();
                $rawList = $body['results']['data'] ?? $body['results'] ?? $body['data'] ?? (is_array($body) && isset($body[0]) ? $body : []);

                $messages = [];

                if (is_array($rawList)) {
                    foreach ($rawList as $item) {
                        $parsed = $this->normalizeMessageItem($item, $jid);

                        if ($parsed !== null) {
                            $messages[] = $parsed;
                        }
                    }
                }

                // Sort messages chronologically (oldest first, newest last)
                usort($messages, fn ($a, $b) => ($a['timestamp'] ?? 0) <=> ($b['timestamp'] ?? 0));

                return [
                    'success' => true,
                    'phone' => $number,
                    'jid' => $jid,
                    'messages' => $messages,
                    'pagination' => $body['results']['pagination'] ?? null,
                ];
            }

            return [
                'success' => true,
                'phone' => $number,
                'jid' => $jid,
                'messages' => [],
                'message' => 'No messages found or chat storage is not active on GoWA server.',
            ];
        } catch (\Throwable $e) {
            Log::warning('GoWaDriver getMessages error', ['number' => $number, 'error' => $e->getMessage()]);

            return [
                'success' => false,
                'phone' => $number,
                'jid' => $jid,
                'messages' => [],
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send a text message via POST /send/message (OpenAPI 9.0 SendResponse).
     */
    public function sendMessage(string $number, string $message, array $options = []): array
    {
        $url = rtrim($options['url'] ?? '', '/');
        $apiKey = $options['api_key'] ?? null;
        $sessionId = $options['session_id'] ?? null;
        $sessionId = $this->resolveDeviceId($url, $apiKey, $sessionId);
        $formatted = $this->formatForGoWa($number);

        if (empty($url)) {
            return [
                'success' => false,
                'message' => 'GoWA server URL is not configured.',
            ];
        }

        try {
            $payload = [
                'phone' => $formatted,
                'message' => $message,
            ];

            if (!empty($options['reply_message_id'])) {
                $payload['reply_message_id'] = $options['reply_message_id'];
            }

            if (!empty($options['mentions']) && is_array($options['mentions'])) {
                $payload['mentions'] = $options['mentions'];
            }

            if (isset($options['is_forwarded'])) {
                $payload['is_forwarded'] = (bool) $options['is_forwarded'];
            }

            if (isset($options['duration'])) {
                $payload['duration'] = (int) $options['duration'];
            }

            $response = $this->httpClient($apiKey, $sessionId)
                ->timeout(10)
                ->post("{$url}/send/message", $payload);

            if ($response->successful()) {
                $data = $response->json();
                $messageId = $data['results']['message_id'] ?? $data['results']['id'] ?? $data['id'] ?? uniqid('gowa_msg_');

                return [
                    'success' => true,
                    'message' => 'Message sent successfully.',
                    'data' => [
                        'id' => $messageId,
                        'phone' => $number,
                        'jid' => $formatted,
                        'message' => $message,
                        'from_me' => true,
                        'timestamp' => time(),
                        'raw' => $data,
                    ],
                ];
            }

            $errBody = $response->json();

            return [
                'success' => false,
                'message' => $errBody['message'] ?? ('HTTP error ' . $response->status()),
            ];
        } catch (\Throwable $e) {
            Log::warning('GoWaDriver sendMessage error', ['number' => $number, 'error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send media (image/document/file/audio/video) via multipart/form-data as required by GoWA.
     * Compliant with OpenAPI 9.0 (/send/image, /send/file, /send/audio, /send/video).
     */
    public function sendMedia(string $number, string $mediaUrl, string $caption = '', string $mediaType = 'image', array $options = []): array
    {
        $url = rtrim($options['url'] ?? '', '/');
        $apiKey = $options['api_key'] ?? null;
        $sessionId = $options['session_id'] ?? null;
        $sessionId = $this->resolveDeviceId($url, $apiKey, $sessionId);
        $formatted = $this->formatForGoWa($number);

        $endpointMap = [
            'image' => "{$url}/send/image",
            'file' => "{$url}/send/file",
            'document' => "{$url}/send/file",
            'audio' => "{$url}/send/audio",
            'video' => "{$url}/send/video",
            'sticker' => "{$url}/send/sticker",
        ];

        $endpoint = $endpointMap[$mediaType] ?? "{$url}/send/image";
        $fileParamName = ($mediaType === 'file' || $mediaType === 'document') ? 'file' : $mediaType;

        try {
            $client = $this->httpClient($apiKey, $sessionId)->timeout(25)->asMultipart();

            $fields = [
                'phone' => $formatted,
                'caption' => $caption,
            ];

            // Local file attachment
            if (file_exists($mediaUrl) && is_readable($mediaUrl)) {
                $filename = basename($mediaUrl);
                $fileContent = file_get_contents($mediaUrl);
                $client = $client->attach($fileParamName, $fileContent, $filename);
            } elseif (filter_var($mediaUrl, FILTER_VALIDATE_URL)) {
                // Check if GoWA supports remote URL field (image_url, file_url, audio_url, video_url)
                $urlFieldMap = [
                    'image' => 'image_url',
                    'file' => 'file_url',
                    'document' => 'file_url',
                    'audio' => 'audio_url',
                    'video' => 'video_url',
                    'sticker' => 'sticker_url',
                ];
                $urlFieldName = $urlFieldMap[$mediaType] ?? 'image_url';
                $fields[$urlFieldName] = $mediaUrl;
            }

            if ($mediaType === 'image' && isset($options['view_once'])) {
                $fields['view_once'] = $options['view_once'] ? 'true' : 'false';
            }

            if ($mediaType === 'audio' && isset($options['ptt'])) {
                $fields['ptt'] = $options['ptt'] ? 'true' : 'false';
            }

            if (!empty($options['reply_message_id'])) {
                $fields['reply_message_id'] = $options['reply_message_id'];
            }

            $response = $client->post($endpoint, $fields);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Media message sent successfully.',
                    'data' => $response->json(),
                ];
            }

            $errBody = $response->json();

            return [
                'success' => false,
                'message' => $errBody['message'] ?? ('HTTP error ' . $response->status()),
            ];
        } catch (\Throwable $e) {
            Log::warning('GoWaDriver sendMedia error', ['number' => $number, 'error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if a phone number is registered on WhatsApp via GET /user/check.
     * Compliant with OpenAPI 9.0 UserCheckResponse schema (is_on_whatsapp).
     */
    public function checkUser(string $number, array $options = []): array
    {
        $url = rtrim($options['url'] ?? '', '/');
        $apiKey = $options['api_key'] ?? null;
        $sessionId = $options['session_id'] ?? null;
        $sessionId = $this->resolveDeviceId($url, $apiKey, $sessionId);
        $formatted = $this->formatForGoWa($number);

        try {
            $response = $this->httpClient($apiKey, $sessionId)
                ->timeout(8)
                ->get("{$url}/user/check", [
                    'phone' => $formatted,
                ]);

            if ($response->successful()) {
                $body = $response->json();
                $data = $body['results'] ?? [];
                $isOnWhatsApp = (bool) ($data['is_on_whatsapp'] ?? $data['on_whatsapp'] ?? true);

                return [
                    'success' => true,
                    'on_whatsapp' => $isOnWhatsApp,
                    'jid' => $data['jid'] ?? $formatted,
                ];
            }

            return [
                'success' => false,
                'on_whatsapp' => false,
                'message' => $response->json()['message'] ?? 'Failed to check WhatsApp registration.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'on_whatsapp' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get user profile avatar via GET /user/avatar.
     * Compliant with OpenAPI 9.0 UserAvatarResponse schema.
     */
    public function getUserAvatar(string $number, array $options = []): array
    {
        $url = rtrim($options['url'] ?? '', '/');
        $apiKey = $options['api_key'] ?? null;
        $sessionId = $options['session_id'] ?? null;
        $sessionId = $this->resolveDeviceId($url, $apiKey, $sessionId);
        $formatted = $this->formatForGoWa($number);

        try {
            $params = ['phone' => $formatted];

            if (isset($options['is_preview'])) {
                $params['is_preview'] = $options['is_preview'] ? 'true' : 'false';
            }

            $response = $this->httpClient($apiKey, $sessionId)
                ->timeout(8)
                ->get("{$url}/user/avatar", $params);

            if ($response->successful()) {
                $body = $response->json();
                $data = $body['results'] ?? [];

                return [
                    'success' => true,
                    'url' => $data['url'] ?? null,
                    'id' => $data['id'] ?? null,
                ];
            }

            return [
                'success' => false,
                'url' => null,
                'message' => $response->json()['message'] ?? 'No avatar found.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'url' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get user profile info via GET /user/info.
     * Compliant with OpenAPI 9.0 UserInfoResponse schema.
     */
    public function getUserInfo(string $number, array $options = []): array
    {
        $url = rtrim($options['url'] ?? '', '/');
        $apiKey = $options['api_key'] ?? null;
        $sessionId = $options['session_id'] ?? null;
        $sessionId = $this->resolveDeviceId($url, $apiKey, $sessionId);
        $formatted = $this->formatForGoWa($number);

        try {
            $response = $this->httpClient($apiKey, $sessionId)
                ->timeout(8)
                ->get("{$url}/user/info", ['phone' => $formatted]);

            if ($response->successful()) {
                $body = $response->json();

                return [
                    'success' => true,
                    'info' => $body['results'] ?? [],
                ];
            }

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Failed to fetch user info.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get login QR code for device pairing.
     * Compliant with OpenAPI 9.0 GET /devices/{device_id}/login or GET /app/login.
     */
    public function getLoginQr(array $config = []): array
    {
        $url = rtrim($config['url'] ?? '', '/');
        $apiKey = $config['api_key'] ?? null;
        $sessionId = $config['session_id'] ?? null;

        if (empty($url)) {
            return ['success' => false, 'message' => 'GoWA server URL is required.'];
        }

        try {
            $endpoint = !empty($sessionId) ? "{$url}/devices/{$sessionId}/login" : "{$url}/app/login";
            $response = $this->httpClient($apiKey, $sessionId)->timeout(8)->get($endpoint);

            if ($response->successful()) {
                $body = $response->json();
                $res = $body['results'] ?? [];

                return [
                    'success' => true,
                    'qr_link' => $res['qr_link'] ?? null,
                    'qr_duration' => (int) ($res['qr_duration'] ?? 30),
                ];
            }

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Failed to get QR code.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Mark a message as read via POST /message/{message_id}/read.
     */
    public function markAsRead(string $messageId, string $number, array $options = []): array
    {
        $url = rtrim($options['url'] ?? '', '/');
        $apiKey = $options['api_key'] ?? null;
        $sessionId = $options['session_id'] ?? null;
        $sessionId = $this->resolveDeviceId($url, $apiKey, $sessionId);
        $formatted = $this->formatForGoWa($number);

        try {
            $response = $this->httpClient($apiKey, $sessionId)
                ->timeout(8)
                ->post("{$url}/message/{$messageId}/read", ['phone' => $formatted]);

            return [
                'success' => $response->successful(),
                'message' => $response->json()['message'] ?? 'Status updated.',
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send typing / presence indicator via POST /send/chat-presence.
     */
    public function sendChatPresence(string $number, string $action = 'start', array $options = []): array
    {
        $url = rtrim($options['url'] ?? '', '/');
        $apiKey = $options['api_key'] ?? null;
        $sessionId = $options['session_id'] ?? null;
        $sessionId = $this->resolveDeviceId($url, $apiKey, $sessionId);
        $formatted = $this->formatForGoWa($number);

        try {
            $response = $this->httpClient($apiKey, $sessionId)
                ->timeout(5)
                ->post("{$url}/send/chat-presence", [
                    'phone' => $formatted,
                    'action' => in_array($action, ['start', 'stop']) ? $action : 'start',
                ]);

            return [
                'success' => $response->successful(),
                'message' => $response->json()['message'] ?? 'Presence updated.',
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Normalize individual message object from OpenAPI 9.0 ChatMessage payload.
     */
    private function normalizeMessageItem(mixed $item, string $targetJid): ?array
    {
        if (!is_array($item)) {
            return null;
        }

        $id = (string) ($item['id'] ?? $item['key']['id'] ?? uniqid('msg_'));
        $fromMe = (bool) ($item['is_from_me'] ?? $item['from_me'] ?? $item['fromMe'] ?? $item['key']['fromMe'] ?? false);

        // Process timestamp (string ISO 8601 or integer epoch)
        $rawTs = $item['timestamp'] ?? $item['created_at'] ?? $item['message_timestamp'] ?? time();

        if (is_string($rawTs) && !is_numeric($rawTs)) {
            $timestamp = strtotime($rawTs) ?: time();
        } elseif (is_numeric($rawTs) && $rawTs > 10000000000) {
            $timestamp = (int) round($rawTs / 1000);
        } else {
            $timestamp = (int) $rawTs;
        }

        $sender = $fromMe ? 'me' : ($item['sender_jid'] ?? $item['sender'] ?? $item['remote_jid'] ?? $targetJid);
        $senderDisplayName = $item['sender_display_name'] ?? null;
        $text = $this->extractMessageText($item);

        return [
            'id' => $id,
            'from_me' => $fromMe,
            'sender' => $sender,
            'sender_display_name' => $senderDisplayName,
            'message' => $text,
            'type' => $item['media_type'] ?? $item['type'] ?? 'text',
            'media_url' => $item['url'] ?? null,
            'filename' => $item['filename'] ?? null,
            'reactions' => $item['reactions'] ?? [],
            'timestamp' => $timestamp,
            'status' => $fromMe ? 'sent' : 'received',
        ];
    }

    /**
     * Extract clean text content from heterogeneous WhatsApp message payloads.
     * Covers OpenAPI 9.0 'content' field as well as nested GoWA structures.
     */
    private function extractMessageText(array $item): string
    {
        // OpenAPI 9.0 ChatMessage text field is 'content'
        if (!empty($item['content'])) {
            return (string) $item['content'];
        }

        if (isset($item['message'])) {
            $msg = $item['message'];

            if (is_string($msg)) {
                return $msg;
            }

            if (is_array($msg)) {
                if (!empty($msg['conversation'])) {
                    return (string) $msg['conversation'];
                }

                if (!empty($msg['extendedTextMessage']['text'])) {
                    return (string) $msg['extendedTextMessage']['text'];
                }

                if (!empty($msg['imageMessage']['caption'])) {
                    return (string) $msg['imageMessage']['caption'];
                }

                if (!empty($msg['documentMessage']['caption'])) {
                    return (string) $msg['documentMessage']['caption'];
                }

                if (!empty($msg['videoMessage']['caption'])) {
                    return (string) $msg['videoMessage']['caption'];
                }

                if (!empty($msg['text'])) {
                    return (string) $msg['text'];
                }
            }
        }

        if (!empty($item['text'])) {
            return (string) $item['text'];
        }

        if (!empty($item['body'])) {
            return (string) $item['body'];
        }

        if (!empty($item['caption'])) {
            return (string) $item['caption'];
        }

        return '';
    }

    /**
     * Resolve valid GoWA device ID.
     */
    public function resolveDeviceId(string $url, ?string $apiKey = null, ?string $sessionId = null): ?string
    {
        try {
            $resp = $this->httpClient($apiKey)->timeout(5)->get(rtrim($url, '/') . '/devices');

            if ($resp->successful()) {
                $devices = $resp->json()['results'] ?? $resp->json()['data'] ?? [];

                if (is_array($devices) && !empty($devices)) {
                    if (!empty($sessionId)) {
                        foreach ($devices as $dev) {
                            if (($dev['id'] ?? '') === $sessionId) {
                                return $sessionId;
                            }
                        }
                    }

                    foreach ($devices as $dev) {
                        if (($dev['state'] ?? '') === 'connected' && !empty($dev['id'])) {
                            return $dev['id'];
                        }
                    }

                    if (!empty($devices[0]['id'])) {
                        return $devices[0]['id'];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::debug('Failed to resolve GoWA device ID', ['error' => $e->getMessage()]);
        }

        return $sessionId;
    }

    /**
     * Normalize phone number:
     * - Removes non-digits.
     * - If starts with '0' (local format), prefixes with '94'.
     * - If already in international format (e.g. 94..., 62..., 44..., 1...), retains it.
     */
    public function normalizePhone(?string $number): ?string
    {
        if (empty($number)) {
            return null;
        }

        $parts = explode('@', $number);
        $clean = preg_replace('/\D/', '', $parts[0]);

        if (empty($clean)) {
            return null;
        }

        if (str_starts_with($clean, '0')) {
            $clean = '94' . substr($clean, 1);
        }

        return $clean;
    }

    /**
     * Format phone for GoWA API (e.g. 94771234567@s.whatsapp.net).
     * If already contains @ (e.g. group @g.us), preserves it.
     */
    public function formatForGoWa(?string $number): string
    {
        if (empty($number)) {
            return '';
        }

        if (str_contains($number, '@')) {
            return $number;
        }

        $normalized = $this->normalizePhone($number);

        return $normalized ? "{$normalized}@s.whatsapp.net" : (string) $number;
    }

    /**
     * Prepare Guzzle HTTP client with appropriate auth & device headers.
     */
    private function httpClient(?string $apiKey = null, ?string $sessionId = null): PendingRequest
    {
        $client = Http::acceptJson()->asJson();

        if (!empty($apiKey)) {
            $trimmedKey = trim($apiKey);

            if (str_starts_with(strtolower($trimmedKey), 'basic ')) {
                $client = $client->withHeaders(['Authorization' => $trimmedKey]);
            } elseif (str_contains($trimmedKey, ':')) {
                [$u, $p] = explode(':', $trimmedKey, 2);
                $client = $client->withBasicAuth($u, $p);
            } elseif (str_starts_with(strtolower($trimmedKey), 'bearer ')) {
                $client = $client->withToken(substr($trimmedKey, 7));
            } else {
                $client = $client->withToken($trimmedKey)
                    ->withHeaders([
                        'X-API-Key' => $trimmedKey,
                        'X-Api-Key' => $trimmedKey,
                        'X-Admin-Secret' => $trimmedKey,
                    ]);
            }
        }

        if (!empty($sessionId)) {
            $client = $client->withHeaders([
                'X-Device-Id' => $sessionId,
                'session_id' => $sessionId,
                'session' => $sessionId,
            ]);
        }

        return $client;
    }
}

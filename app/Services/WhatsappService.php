<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    private readonly ?string $envApiKey;

    private readonly ?string $envSessionId;

    private readonly ?string $envBaseUrl;

    private readonly int $timeout;

    public function __construct(
        private readonly TenantConfigurationService $tenantConfig,
    ) {
        $this->envApiKey = config('services.openwa.api_key');
        $this->envSessionId = config('services.openwa.session_id');
        $this->envBaseUrl = config('services.openwa.base_url') ? rtrim((string) config('services.openwa.base_url'), '/') : null;
        $this->timeout = (int) config('services.openwa.timeout', 15);
    }

    /**
     * Resolve WhatsApp credentials for a tenant.
     * Tenant-stored values take precedence; env values are used as fallback.
     *
     * @return array{apiKey: string|null, sessionId: string|null, baseUrl: string|null}
     */
    private function credentials(?int $tenantId): array
    {
        Log::debug('WhatsappService: Resolving credentials.', ['tenantId' => $tenantId]);

        if ($tenantId !== null) {
            $cfg = $this->tenantConfig->all($tenantId);

            return [
                'apiKey' => ($cfg['notifications.whatsapp.api_key'] ?? '') ?: $this->envApiKey,
                'sessionId' => ($cfg['notifications.whatsapp.session_id'] ?? '') ?: $this->envSessionId,
                'baseUrl' => ($cfg['notifications.whatsapp.base_url'] ?? '') ? rtrim((string) $cfg['notifications.whatsapp.base_url'], '/') : $this->envBaseUrl,
            ];
        }

        return [
            'apiKey' => $this->envApiKey,
            'sessionId' => $this->envSessionId,
            'baseUrl' => $this->envBaseUrl,
        ];
    }

    /**
     * Clean and format phone number for WhatsApp.
     */
    public function formatNumber(string $number): string
    {
        // Remove @c.us if present
        if (str_ends_with($number, '@c.us')) {
            $number = substr($number, 0, -5);
        }

        // Strip non-digits
        $clean = preg_replace('/\D/', '', $number);

        // Normalize Sri Lankan local format to international format
        if (str_starts_with($clean, '0')) {
            $clean = '94' . substr($clean, 1);
        } elseif (strlen($clean) === 9 && str_starts_with($clean, '7')) {
            $clean = '94' . $clean;
        }

        return "{$clean}@c.us";
    }

    /**
     * Send a WhatsApp text message. Returns true on success, false on failure.
     */
    public function send(string $contact, string $message, ?int $tenantId = null): bool
    {
        ['apiKey' => $apiKey, 'sessionId' => $sessionId, 'baseUrl' => $baseUrl] = $this->credentials($tenantId);

        if (!$apiKey || !$sessionId || !$baseUrl) {
            Log::warning('WhatsappService: API key, session ID, or base URL is not configured.');

            return false;
        }

        $chatId = $this->formatNumber($contact);
        $url = "{$baseUrl}/api/sessions/{$sessionId}/messages/send-text";

        Log::debug('WhatsappService: Attempting to send WhatsApp message.', [
            'url' => $url,
            'chatId' => $chatId,
            'apiKeyMasked' => substr((string) $apiKey, 0, 4) . '...',
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-Key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->post($url, [
                    'chatId' => $chatId,
                    'text' => $message,
                ]);

            if ($response->successful()) {
                $body = $response->json();
                Log::info('WhatsappService: Message sent.', [
                    'contact' => $contact,
                    'messageId' => $body['messageId'] ?? null,
                ]);

                return true;
            }

            Log::warning('WhatsappService: API returned non-success.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('WhatsappService: Send failed.', [
                'contact' => $contact,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Send bulk WhatsApp messages. Returns arrays of succeeded and failed contacts.
     *
     * @param  string[]  $contacts
     * @return array{succeeded: string[], failed: string[]}
     */
    public function sendBulk(array $contacts, string $message, ?int $tenantId = null): array
    {
        $succeeded = [];
        $failed = [];

        foreach ($contacts as $contact) {
            if ($this->send($contact, $message, $tenantId)) {
                $succeeded[] = $contact;
            } else {
                $failed[] = $contact;
            }
        }

        return [
            'succeeded' => $succeeded,
            'failed' => $failed,
        ];
    }
}

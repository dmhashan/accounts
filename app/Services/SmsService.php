<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private const API_URL = 'https://smslenz.lk/api/send-sms';

    private const BULK_API_URL = 'https://smslenz.lk/api/send-bulk-sms';

    /** Fallback credentials read from .env / config */
    private readonly ?string $envUserId;

    private readonly ?string $envApiKey;

    private readonly string $envSenderId;

    public function __construct(
        private readonly TenantConfigurationService $tenantConfig,
        private readonly WhatsappService $whatsappService,
    ) {
        $this->envUserId = config('services.smslenz.user_id');
        $this->envApiKey = config('services.smslenz.api_key');
        $this->envSenderId = config('services.smslenz.sender_id', 'SMSlenzDEMO');
    }

    /**
     * Send a notification trying WhatsApp first, and falling back to SMS if it fails.
     */
    public function send(string $contact, string $message, ?int $tenantId = null): bool
    {
        if ($this->sendWhatsappOnly($contact, $message, $tenantId)) {
            return true;
        }

        return $this->sendSmsOnly($contact, $message, $tenantId);
    }

    /**
     * Send bulk notifications trying WhatsApp first, and falling back to SMS for failed ones.
     */
    public function sendBulk(array $contacts, string $message, ?int $tenantId = null): array
    {
        if (empty($contacts)) {
            return ['success' => false, 'campaign_id' => null];
        }

        $result = $this->whatsappService->sendBulk($contacts, $message, $tenantId);

        if (!empty($result['failed'])) {
            return $this->sendBulkSmsOnly($result['failed'], $message, $tenantId);
        }

        return ['success' => true, 'campaign_id' => null];
    }

    /**
     * Send message via WhatsApp only.
     */
    public function sendWhatsappOnly(string $contact, string $message, ?int $tenantId = null): bool
    {
        return $this->whatsappService->send($contact, $message, $tenantId);
    }

    /**
     * Send message via SMS only.
     */
    public function sendSmsOnly(string $contact, string $message, ?int $tenantId = null): bool
    {
        ['userId' => $userId, 'apiKey' => $apiKey, 'senderId' => $senderId] = $this->credentials($tenantId);

        if (!$userId || !$apiKey) {
            Log::warning('SmsService: SMSLENZ_USER_ID or SMSLENZ_API_KEY is not configured.');

            return false;
        }

        try {
            $response = Http::asForm()->post(self::API_URL, [
                'user_id' => $userId,
                'api_key' => $apiKey,
                'sender_id' => $senderId,
                'contact' => $contact,
                'message' => $message,
            ]);

            $body = $response->json();

            if ($response->successful() && ($body['success'] ?? false)) {
                Log::info('SmsService: SMS sent.', [
                    'contact' => $contact,
                    'campaign_id' => $body['data']['campaign_id'] ?? null,
                ]);

                return true;
            }

            Log::warning('SmsService: SMS API returned non-success.', [
                'status' => $response->status(),
                'body' => $body,
            ]);
        } catch (\Throwable $e) {
            Log::error('SmsService: SMS send failed.', [
                'contact' => $contact,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Send bulk messages via SMS only.
     */
    public function sendBulkSmsOnly(array $contacts, string $message, ?int $tenantId = null): array
    {
        ['userId' => $userId, 'apiKey' => $apiKey, 'senderId' => $senderId] = $this->credentials($tenantId);

        if (!$userId || !$apiKey) {
            Log::warning('SmsService: SMSLENZ_USER_ID or SMSLENZ_API_KEY is not configured.');

            return ['success' => false, 'campaign_id' => null];
        }

        if (empty($contacts)) {
            return ['success' => false, 'campaign_id' => null];
        }

        try {
            $response = Http::post(self::BULK_API_URL, [
                'user_id' => $userId,
                'api_key' => $apiKey,
                'sender_id' => $senderId,
                'contacts' => $contacts,
                'message' => $message,
            ]);

            $body = $response->json();

            if ($response->successful() && ($body['success'] ?? false)) {
                Log::info('SmsService: Bulk SMS sent.', [
                    'recipient_count' => count($contacts),
                    'campaign_id' => $body['data']['campaign_id'] ?? null,
                ]);

                return ['success' => true, 'campaign_id' => $body['data']['campaign_id'] ?? null];
            }

            Log::warning('SmsService: Bulk SMS API returned non-success.', [
                'status' => $response->status(),
                'body' => $body,
            ]);
        } catch (\Throwable $e) {
            Log::error('SmsService: Bulk SMS send failed.', ['error' => $e->getMessage()]);
        }

        return ['success' => false, 'campaign_id' => null];
    }

    /**
     * Resolve SMS credentials for a tenant.
     * Tenant-stored values take precedence; env values are used as fallback.
     *
     * @return array{userId: string|null, apiKey: string|null, senderId: string}
     */
    private function credentials(?int $tenantId): array
    {
        if ($tenantId !== null) {
            $cfg = $this->tenantConfig->all($tenantId);

            return [
                'userId' => ($cfg['notifications.sms.user_id'] ?? '') ?: $this->envUserId,
                'apiKey' => ($cfg['notifications.sms.api_key'] ?? '') ?: $this->envApiKey,
                'senderId' => ($cfg['notifications.sms.sender_id'] ?? '') ?: $this->envSenderId,
            ];
        }

        return [
            'userId' => $this->envUserId,
            'apiKey' => $this->envApiKey,
            'senderId' => $this->envSenderId,
        ];
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private const API_URL = 'https://smslenz.lk/api/send-sms';

    private ?string $userId;
    private ?string $apiKey;
    private string $senderId;

    public function __construct()
    {
        $this->userId = config('services.smslenz.user_id');
        $this->apiKey = config('services.smslenz.api_key');
        $this->senderId = config('services.smslenz.sender_id', 'SMSlenzDEMO');
    }

    /**
     * Send an SMS message. Returns true on success, false on failure.
     * Failures are logged but never throw — SMS sending must not block core flows.
     */
    public function send(string $contact, string $message): bool
    {
        if (!$this->userId || !$this->apiKey) {
            Log::warning('SmsService: SMSLENZ_USER_ID or SMSLENZ_API_KEY is not configured.');
            return false;
        }

        try {
            $response = Http::asForm()->post(self::API_URL, [
                'user_id' => $this->userId,
                'api_key' => $this->apiKey,
                'sender_id' => $this->senderId,
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
}

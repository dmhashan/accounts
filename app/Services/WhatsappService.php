<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    private readonly ?string $apiKey;

    private readonly ?string $sessionId;

    private readonly ?string $baseUrl;

    private readonly int $timeout;

    public function __construct()
    {
        $this->apiKey = config('services.openwa.api_key');
        $this->sessionId = config('services.openwa.session_id');
        $this->baseUrl = config('services.openwa.base_url') ? rtrim((string) config('services.openwa.base_url'), '/') : null;
        $this->timeout = (int) config('services.openwa.timeout', 5);
    }

    /**
     * Clean and format phone number for WhatsApp.
     */
    public function formatNumber(string $number): string
    {
        // Strip non-digits
        $clean = preg_replace('/\D/', '', $number);

        // If it already ends with @c.us, keep it, else append @c.us
        if (str_ends_with($number, '@c.us')) {
            return $number;
        }

        return "{$clean}@c.us";
    }

    /**
     * Send a WhatsApp text message. Returns true on success, false on failure.
     */
    public function send(string $contact, string $message): bool
    {
        if (!$this->apiKey || !$this->sessionId || !$this->baseUrl) {
            Log::warning('WhatsappService: API key, session ID, or base URL is not configured.');

            return false;
        }

        $chatId = $this->formatNumber($contact);
        $url = "{$this->baseUrl}/api/sessions/{$this->sessionId}/messages/send-text";

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
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
    public function sendBulk(array $contacts, string $message): array
    {
        $succeeded = [];
        $failed = [];

        foreach ($contacts as $contact) {
            if ($this->send($contact, $message)) {
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

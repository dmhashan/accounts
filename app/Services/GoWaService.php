<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoWaService
{
    /**
     * Test connection to GoWA server instance via GET /app/info.
     */
    public function testConnection(string $url, ?string $apiKey = null, ?string $sessionId = null): array
    {
        $url = rtrim($url, '/');

        try {
            // First check GoWA official endpoint GET /app/info
            $response = $this->httpClient($apiKey, $sessionId)
                ->timeout(8)
                ->get("{$url}/app/info");

            if ($response->successful()) {
                $body = $response->json();
                $data = $body['results'] ?? $body['data'] ?? $body['response'] ?? $body['result'] ?? $body;
                $version = $data['version'] ?? 'v9.0.0';
                $osName = $data['device_os_name'] ?? $data['os'] ?? 'GOWA';

                return [
                    'success' => true,
                    'message' => "Connected to GoWA server ({$version}, {$osName}).",
                    'data' => $data,
                ];
            }

            // Fallback check with /health or /getHOST
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
            Log::warning('GoWA connection test failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Failed to reach GoWA server: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get participants in a GoWA group via GET /group/info or GET /group/members.
     */
    public function getGroupParticipants(string $url, string $groupId, ?string $apiKey = null, ?string $sessionId = null): array
    {
        $url = rtrim($url, '/');
        $sessionId = $this->resolveDeviceId($url, $apiKey, $sessionId);

        try {
            // Primary GoWA endpoint: GET /group/info?group_id={groupId}
            $response = $this->httpClient($apiKey, $sessionId)
                ->timeout(10)
                ->get("{$url}/group/info", [
                    'group_id' => $groupId,
                ]);

            if (!$response->successful()) {
                // Alternative GoWA endpoint: GET /group/members?group_id={groupId}
                $response = $this->httpClient($apiKey, $sessionId)
                    ->timeout(10)
                    ->get("{$url}/group/members", [
                        'group_id' => $groupId,
                    ]);
            }

            if (!$response->successful()) {
                // Legacy fallback: POST /api/{session}/getGroupMembers
                $session = $sessionId ?: 'default';
                $response = $this->httpClient($apiKey, $sessionId)
                    ->timeout(10)
                    ->post("{$url}/api/{$session}/getGroupMembers", [
                        'groupId' => $groupId,
                    ]);
            }

            if ($response->successful()) {
                $body = $response->json();
                $data = $body['results'] ?? $body['data'] ?? $body['response'] ?? $body['result'] ?? $body;

                $rawList = [];

                if (is_array($data)) {
                    $rawList = $data['participants'] ?? $data['Participants'] ?? $data['members'] ?? $data['Members'] ?? (isset($data[0]) ? $data : []);
                }

                $participants = [];

                if (is_array($rawList)) {
                    foreach ($rawList as $item) {
                        $phone = null;

                        if (is_string($item)) {
                            $phone = $item;
                        } elseif (is_array($item)) {
                            $phone = $item['PhoneNumber'] ?? $item['phone_number'] ?? $item['phone'] ?? $item['id']['_serialized'] ?? $item['id'] ?? $item['user'] ?? (isset($item['JID']) && str_contains($item['JID'], '@s.whatsapp.net') ? $item['JID'] : null) ?? $item['jid'] ?? null;
                        }

                        if ($phone) {
                            $normalized = $this->normalizePhone($phone);

                            if ($normalized) {
                                $participants[] = [
                                    'raw' => $phone,
                                    'normalized' => $normalized,
                                ];
                            }
                        }
                    }
                }

                return [
                    'success' => true,
                    'participants' => $participants,
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to fetch GoWA group participants (HTTP ' . $response->status() . ')',
                'participants' => [],
            ];
        } catch (\Throwable $e) {
            Log::error('GoWA getGroupParticipants error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Error fetching group participants: ' . $e->getMessage(),
                'participants' => [],
            ];
        }
    }

    /**
     * Add participant(s) to a GoWA group via POST /group/participants.
     */
    public function addParticipants(string $url, string $groupId, array $phones, ?string $apiKey = null, ?string $sessionId = null): array
    {
        $url = rtrim($url, '/');
        $sessionId = $this->resolveDeviceId($url, $apiKey, $sessionId);
        $added = [];
        $failed = [];
        $validPhones = [];

        foreach ($phones as $p) {
            $norm = $this->normalizePhone($p);

            // Valid international phones (like 94771234567) are 10 to 15 digits
            if (!$norm || strlen($norm) < 10 || strlen($norm) > 15) {
                $failed[] = ['phone' => $p, 'reason' => 'Invalid phone number format'];
            } else {
                $validPhones[] = $p;
            }
        }

        // Process valid phones in chunks (10 at a time) to prevent one bad number from breaking a giant batch,
        // and avoid huge sequential fallback loops that trigger 504 Gateway Timeouts.
        $chunks = array_chunk($validPhones, 10);

        foreach ($chunks as $chunk) {
            $formattedPhones = array_map(fn ($p) => $this->formatForGoWa($p), $chunk);

            try {
                $response = $this->httpClient($apiKey, $sessionId)
                    ->timeout(12)
                    ->post("{$url}/group/participants", [
                        'group_id' => $groupId,
                        'participants' => $formattedPhones,
                    ]);

                if ($response->successful()) {
                    $body = $response->json();
                    $results = $body['results'] ?? [];

                    if (is_array($results) && count($results) === count($chunk)) {
                        foreach ($chunk as $idx => $phone) {
                            $resItem = $results[$idx] ?? [];
                            $status = $resItem['status'] ?? 'success';

                            if ($status === 'success') {
                                $added[] = $phone;
                            } else {
                                $failed[] = ['phone' => $phone, 'reason' => $resItem['message'] ?? 'Failed to add participant'];
                            }
                        }
                    } else {
                        foreach ($chunk as $phone) {
                            $added[] = $phone;
                        }
                    }
                    continue;
                }
            } catch (\Throwable $e) {
                Log::warning('GoWA addParticipants chunk failed', ['error' => $e->getMessage()]);
            }

            // Fallback per-phone add for numbers in this chunk only
            foreach ($chunk as $phone) {
                $formatted = $this->formatForGoWa($phone);

                try {
                    $resp = $this->httpClient($apiKey, $sessionId)
                        ->timeout(5)
                        ->post("{$url}/group/participants", [
                            'group_id' => $groupId,
                            'participants' => [$formatted],
                        ]);

                    if ($resp->successful()) {
                        $body = $resp->json();
                        $results = $body['results'] ?? [];
                        $firstRes = $results[0] ?? [];
                        $status = $firstRes['status'] ?? 'success';

                        if ($status === 'success' || empty($results)) {
                            $added[] = $phone;
                        } else {
                            $failed[] = ['phone' => $phone, 'reason' => $firstRes['message'] ?? 'Failed to add participant'];
                        }
                    } else {
                        $errBody = $resp->json();
                        $errMsg = $errBody['message'] ?? ('HTTP ' . $resp->status());
                        $failed[] = ['phone' => $phone, 'reason' => $errMsg];
                    }
                } catch (\Throwable $e) {
                    $failed[] = ['phone' => $phone, 'reason' => $e->getMessage()];
                }
            }
        }

        return [
            'success' => count($failed) === 0 || count($added) > 0,
            'added' => $added,
            'failed' => $failed,
        ];
    }

    /**
     * Remove participant(s) from a GoWA group via POST /group/participants/remove.
     */
    public function removeParticipants(string $url, string $groupId, array $phones, ?string $apiKey = null, ?string $sessionId = null): array
    {
        $url = rtrim($url, '/');
        $sessionId = $this->resolveDeviceId($url, $apiKey, $sessionId);
        $removed = [];
        $failed = [];
        $validPhones = [];

        foreach ($phones as $p) {
            $norm = $this->normalizePhone($p);

            if (!$norm || strlen($norm) < 10 || strlen($norm) > 15) {
                $failed[] = ['phone' => $p, 'reason' => 'Invalid phone number format'];
            } else {
                $validPhones[] = $p;
            }
        }

        // Process valid phones in chunks (10 at a time) to prevent timeouts and isolate failures.
        $chunks = array_chunk($validPhones, 10);

        foreach ($chunks as $chunk) {
            $formattedPhones = array_map(fn ($p) => $this->formatForGoWa($p), $chunk);

            try {
                $response = $this->httpClient($apiKey, $sessionId)
                    ->timeout(12)
                    ->post("{$url}/group/participants/remove", [
                        'group_id' => $groupId,
                        'participants' => $formattedPhones,
                    ]);

                if ($response->successful()) {
                    $body = $response->json();
                    $results = $body['results'] ?? [];

                    if (is_array($results) && count($results) === count($chunk)) {
                        foreach ($chunk as $idx => $phone) {
                            $resItem = $results[$idx] ?? [];
                            $status = $resItem['status'] ?? 'success';

                            if ($status === 'success') {
                                $removed[] = $phone;
                            } else {
                                $failed[] = ['phone' => $phone, 'reason' => $resItem['message'] ?? 'Failed to remove participant'];
                            }
                        }
                    } else {
                        foreach ($chunk as $phone) {
                            $removed[] = $phone;
                        }
                    }
                    continue;
                }
            } catch (\Throwable $e) {
                Log::warning('GoWA removeParticipants chunk failed', ['error' => $e->getMessage()]);
            }

            // Fallback per-phone remove for numbers in this chunk only
            foreach ($chunk as $phone) {
                $formatted = $this->formatForGoWa($phone);

                try {
                    $resp = $this->httpClient($apiKey, $sessionId)
                        ->timeout(5)
                        ->post("{$url}/group/participants/remove", [
                            'group_id' => $groupId,
                            'participants' => [$formatted],
                        ]);

                    if ($resp->successful()) {
                        $body = $resp->json();
                        $results = $body['results'] ?? [];
                        $firstRes = $results[0] ?? [];
                        $status = $firstRes['status'] ?? 'success';

                        if ($status === 'success' || empty($results)) {
                            $removed[] = $phone;
                        } else {
                            $failed[] = ['phone' => $phone, 'reason' => $firstRes['message'] ?? 'Failed to remove participant'];
                        }
                    } else {
                        $errBody = $resp->json();
                        $errMsg = $errBody['message'] ?? ('HTTP ' . $resp->status());
                        $failed[] = ['phone' => $phone, 'reason' => $errMsg];
                    }
                } catch (\Throwable $e) {
                    $failed[] = ['phone' => $phone, 'reason' => $e->getMessage()];
                }
            }
        }

        return [
            'success' => count($failed) === 0 || count($removed) > 0,
            'removed' => $removed,
            'failed' => $failed,
        ];
    }

    /**
     * Evaluate system members matching a group rule set.
     */
    public function evaluateMatchingMembers(array $groupRuleConfig): Collection
    {
        $rules = $groupRuleConfig['rules'] ?? [];

        if (empty($rules)) {
            return collect();
        }

        $query = Member::query();

        $query->where(function ($mainQuery) use ($rules) {
            foreach ($rules as $index => $rule) {
                $boolean = strtolower($rule['boolean'] ?? 'and') === 'or' ? 'or' : 'and';
                $field = $rule['field'] ?? 'gender';
                $value = $rule['value'] ?? '';

                $clause = function ($q) use ($field, $value) {
                    switch ($field) {
                        case 'gender':
                            $q->where('gender', $value);
                            break;
                        case 'payment_plan_id':
                            if ($value !== '') {
                                $q->where('payment_plan_id', $value);
                            }
                            break;
                        case 'is_active':
                            $q->where('is_active', (bool) ($value === '1' || $value === 'true' || $value === true));
                            break;
                        case 'is_verified':
                            $q->where('is_verified', (bool) ($value === '1' || $value === 'true' || $value === true));
                            break;
                        case 'is_temp':
                            $q->where('is_temp', (bool) ($value === '1' || $value === 'true' || $value === true));
                            break;
                        case 'email':
                            $q->where('email', 'like', "%{$value}%");
                            break;
                        case 'address':
                            $q->where('address', 'like', "%{$value}%");
                            break;
                        default:
                            if (!empty($field)) {
                                $q->where($field, $value);
                            }
                    }
                };

                if ($index === 0 || $boolean === 'and') {
                    $mainQuery->where($clause);
                } else {
                    $mainQuery->orWhere($clause);
                }
            }
        });

        return $query->get(['id', 'name', 'phone_number', 'whatsapp_number', 'allow_whatsapp'])
            ->map(function ($m) {
                $waPhone = $m->whatsapp_number ?: $m->phone_number;

                return [
                    'id' => $m->id,
                    'name' => (string) $m->name,
                    'phone' => $waPhone,
                    'normalized_phone' => $this->normalizePhone($waPhone),
                    'allow_whatsapp' => (bool) $m->allow_whatsapp,
                ];
            })
            ->filter(fn ($m) => !empty($m['normalized_phone']));
    }

    /**
     * Compare system members matching rules against GoWA group participants.
     */
    public function compareMembers(array $groupRuleConfig, string $url, ?string $apiKey = null, ?string $sessionId = null): array
    {
        $groupId = $groupRuleConfig['group_id'] ?? '';

        if (empty($groupId)) {
            return [
                'success' => false,
                'message' => 'Group ID / JID is required for comparison.',
            ];
        }

        $systemMembers = $this->evaluateMatchingMembers($groupRuleConfig);
        $gowaResult = $this->getGroupParticipants($url, $groupId, $apiKey, $sessionId);

        if (!$gowaResult['success']) {
            return [
                'success' => false,
                'message' => $gowaResult['message'] ?? 'Failed to get GoWA group members.',
                'matching_system_members' => $systemMembers->values()->toArray(),
                'gowa_participants' => [],
                'to_add' => [],
                'to_remove' => [],
            ];
        }

        $gowaParticipants = $gowaResult['participants'];
        $gowaNormalizedMap = [];

        foreach ($gowaParticipants as $p) {
            $gowaNormalizedMap[$p['normalized']] = $p['raw'];
        }

        $systemNormalizedMap = [];

        foreach ($systemMembers as $m) {
            $systemNormalizedMap[$m['normalized_phone']] = $m;
        }

        // System members to add (in system matching list, not in GoWA group)
        $toAdd = [];

        foreach ($systemMembers as $m) {
            if (!isset($gowaNormalizedMap[$m['normalized_phone']])) {
                $toAdd[] = $m;
            }
        }

        // GoWA members to remove (in GoWA group, not in system matching list)
        $toRemove = [];

        foreach ($gowaParticipants as $p) {
            if (!isset($systemNormalizedMap[$p['normalized']])) {
                $toRemove[] = [
                    'raw_phone' => $p['raw'],
                    'normalized_phone' => $p['normalized'],
                ];
            }
        }

        return [
            'success' => true,
            'group_id' => $groupId,
            'matching_system_count' => $systemMembers->count(),
            'gowa_participants_count' => count($gowaParticipants),
            'to_add_count' => count($toAdd),
            'to_remove_count' => count($toRemove),
            'matching_system_members' => $systemMembers->values()->toArray(),
            'gowa_participants' => array_values($gowaParticipants),
            'to_add' => $toAdd,
            'to_remove' => $toRemove,
        ];
    }

    /**
     * Create pre-configured HTTP client for GoWA API requests.
     */
    private function httpClient(?string $apiKey = null, ?string $sessionId = null)
    {
        $client = Http::acceptJson();

        if (!empty($apiKey)) {
            if (str_starts_with($apiKey, 'Basic ') || str_starts_with($apiKey, 'Bearer ')) {
                $client = $client->withHeaders([
                    'Authorization' => $apiKey,
                    'api_key' => $apiKey,
                ]);
            } elseif (str_contains($apiKey, ':')) {
                [$user, $pass] = explode(':', $apiKey, 2);
                $client = $client->withBasicAuth($user, $pass)
                    ->withHeaders([
                        'api_key' => $pass,
                        'X-Api-Key' => $pass,
                    ]);
            } else {
                $client = $client->withBasicAuth('admin', $apiKey)
                    ->withHeaders([
                        'api_key' => $apiKey,
                        'X-Api-Key' => $apiKey,
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

    /**
     * Resolve valid GoWA device ID if a phone JID or empty session ID was provided.
     */
    public function resolveDeviceId(string $url, ?string $apiKey = null, ?string $sessionId = null): ?string
    {
        if (!empty($sessionId) && !str_contains($sessionId, '@')) {
            return $sessionId;
        }

        try {
            $resp = $this->httpClient($apiKey)->timeout(5)->get(rtrim($url, '/') . '/devices');

            if ($resp->successful()) {
                $devices = $resp->json()['results'] ?? [];

                if (is_array($devices) && !empty($devices)) {
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
     * Normalize phone number to digits only (e.g. 94771234567).
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
     * Format phone for GoWA API (e.g. 94771234567@s.whatsapp.net or 94771234567@c.us).
     */
    public function formatForGoWa(?string $number): string
    {
        $normalized = $this->normalizePhone($number);

        return $normalized ? "{$normalized}@s.whatsapp.net" : (string) $number;
    }
}

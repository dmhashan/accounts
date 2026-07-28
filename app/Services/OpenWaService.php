<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenWaService
{
    /**
     * Test connection to OpenWA server instance.
     */
    public function testConnection(string $url, ?string $apiKey = null, ?string $sessionId = null): array
    {
        $url = rtrim($url, '/');
        $session = $sessionId ?: 'default';

        try {
            $response = $this->httpClient($apiKey)
                ->timeout(8)
                ->get("{$url}/health");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connected to OpenWA server successfully.',
                    'data' => $response->json() ?? [],
                ];
            }

            // Fallback check with session check endpoint
            $sessionResp = $this->httpClient($apiKey)
                ->timeout(8)
                ->post("{$url}/api/{$session}/check-connection-state");

            if ($sessionResp->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connected to OpenWA session successfully.',
                    'data' => $sessionResp->json() ?? [],
                ];
            }

            return [
                'success' => false,
                'message' => 'OpenWA server returned error status: ' . ($response->status() ?: $sessionResp->status()),
            ];
        } catch (\Throwable $e) {
            Log::warning('OpenWA connection test failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Failed to reach OpenWA server: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get participants in an OpenWA group.
     */
    public function getGroupParticipants(string $url, string $groupId, ?string $apiKey = null, ?string $sessionId = null): array
    {
        $url = rtrim($url, '/');
        $session = $sessionId ?: 'default';

        try {
            // Try standard OpenWA endpoint getGroupMembers / getGroupMembersId
            $response = $this->httpClient($apiKey)
                ->timeout(10)
                ->post("{$url}/api/{$session}/getGroupMembers", [
                    'groupId' => $groupId,
                ]);

            if (!$response->successful()) {
                $response = $this->httpClient($apiKey)
                    ->timeout(10)
                    ->post("{$url}/api/{$session}/getGroupMembersId", [
                        'groupId' => $groupId,
                    ]);
            }

            if ($response->successful()) {
                $data = $response->json();

                // Extract participant list / objects
                $rawList = [];

                if (is_array($data)) {
                    $rawList = isset($data['response']) ? $data['response'] : (isset($data['result']) ? $data['result'] : $data);
                }

                $participants = [];

                if (is_array($rawList)) {
                    foreach ($rawList as $item) {
                        $phone = null;

                        if (is_string($item)) {
                            $phone = $item;
                        } elseif (is_array($item)) {
                            $phone = $item['id']['_serialized'] ?? $item['id'] ?? $item['user'] ?? $item['phone'] ?? null;
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
                'message' => 'Failed to fetch OpenWA group participants (HTTP ' . $response->status() . ')',
                'participants' => [],
            ];
        } catch (\Throwable $e) {
            Log::error('OpenWA getGroupParticipants error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Error fetching group participants: ' . $e->getMessage(),
                'participants' => [],
            ];
        }
    }

    /**
     * Add participant(s) to an OpenWA group.
     */
    public function addParticipants(string $url, string $groupId, array $phones, ?string $apiKey = null, ?string $sessionId = null): array
    {
        $url = rtrim($url, '/');
        $session = $sessionId ?: 'default';
        $added = [];
        $failed = [];

        foreach ($phones as $phone) {
            $formattedPhone = $this->formatForOpenWa($phone);

            try {
                $response = $this->httpClient($apiKey)
                    ->timeout(10)
                    ->post("{$url}/api/{$session}/addParticipant", [
                        'groupId' => $groupId,
                        'participant' => $formattedPhone,
                    ]);

                if ($response->successful()) {
                    $added[] = $phone;
                } else {
                    $failed[] = [
                        'phone' => $phone,
                        'reason' => 'HTTP ' . $response->status(),
                    ];
                }
            } catch (\Throwable $e) {
                $failed[] = [
                    'phone' => $phone,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => count($failed) === 0,
            'added' => $added,
            'failed' => $failed,
        ];
    }

    /**
     * Remove participant(s) from an OpenWA group.
     */
    public function removeParticipants(string $url, string $groupId, array $phones, ?string $apiKey = null, ?string $sessionId = null): array
    {
        $url = rtrim($url, '/');
        $session = $sessionId ?: 'default';
        $removed = [];
        $failed = [];

        foreach ($phones as $phone) {
            $formattedPhone = $this->formatForOpenWa($phone);

            try {
                $response = $this->httpClient($apiKey)
                    ->timeout(10)
                    ->post("{$url}/api/{$session}/removeParticipant", [
                        'groupId' => $groupId,
                        'participant' => $formattedPhone,
                    ]);

                if ($response->successful()) {
                    $removed[] = $phone;
                } else {
                    $failed[] = [
                        'phone' => $phone,
                        'reason' => 'HTTP ' . $response->status(),
                    ];
                }
            } catch (\Throwable $e) {
                $failed[] = [
                    'phone' => $phone,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => count($failed) === 0,
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

        // Evaluate rule items
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

        return $query->get(['id', 'first_name', 'last_name', 'phone_number', 'whatsapp_number', 'allow_whatsapp'])
            ->map(function ($m) {
                $waPhone = $m->whatsapp_number ?: $m->phone_number;

                return [
                    'id' => $m->id,
                    'name' => trim($m->first_name . ' ' . $m->last_name),
                    'phone' => $waPhone,
                    'normalized_phone' => $this->normalizePhone($waPhone),
                    'allow_whatsapp' => (bool) $m->allow_whatsapp,
                ];
            })
            ->filter(fn ($m) => !empty($m['normalized_phone']));
    }

    /**
     * Compare system members matching rules against OpenWA group participants.
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
        $openwaResult = $this->getGroupParticipants($url, $groupId, $apiKey, $sessionId);

        if (!$openwaResult['success']) {
            return [
                'success' => false,
                'message' => $openwaResult['message'] ?? 'Failed to get OpenWA group members.',
                'matching_system_members' => $systemMembers->values()->toArray(),
                'openwa_participants' => [],
                'to_add' => [],
                'to_remove' => [],
            ];
        }

        $openwaParticipants = $openwaResult['participants'];
        $openwaNormalizedMap = [];

        foreach ($openwaParticipants as $p) {
            $openwaNormalizedMap[$p['normalized']] = $p['raw'];
        }

        $systemNormalizedMap = [];

        foreach ($systemMembers as $m) {
            $systemNormalizedMap[$m['normalized_phone']] = $m;
        }

        // System members to add (in system matching list, not in OpenWA group)
        $toAdd = [];

        foreach ($systemMembers as $m) {
            if (!isset($openwaNormalizedMap[$m['normalized_phone']])) {
                $toAdd[] = $m;
            }
        }

        // OpenWA members to remove (in OpenWA group, not in system matching list)
        $toRemove = [];

        foreach ($openwaParticipants as $p) {
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
            'openwa_participants_count' => count($openwaParticipants),
            'to_add_count' => count($toAdd),
            'to_remove_count' => count($toRemove),
            'matching_system_members' => $systemMembers->values()->toArray(),
            'openwa_participants' => array_values($openwaParticipants),
            'to_add' => $toAdd,
            'to_remove' => $toRemove,
        ];
    }

    /**
     * Create pre-configured HTTP client for OpenWA API requests.
     */
    private function httpClient(?string $apiKey = null)
    {
        $client = Http::acceptJson();

        if (!empty($apiKey)) {
            $client = $client->withHeaders([
                'api_key' => $apiKey,
                'Authorization' => "Bearer {$apiKey}",
            ]);
        }

        return $client;
    }

    /**
     * Normalize phone number to digits only (e.g. 94771234567).
     */
    public function normalizePhone(?string $number): ?string
    {
        if (empty($number)) {
            return null;
        }

        // Remove suffix like @c.us or @g.us
        $parts = explode('@', $number);
        $clean = preg_replace('/\D/', '', $parts[0]);

        if (empty($clean)) {
            return null;
        }

        // Standardize local 077... -> 9477...
        if (str_starts_with($clean, '0')) {
            $clean = '94' . substr($clean, 1);
        }

        return $clean;
    }

    /**
     * Format phone for OpenWA API (e.g. 94771234567@c.us).
     */
    public function formatForOpenWa(?string $number): string
    {
        $normalized = $this->normalizePhone($number);

        return $normalized ? "{$normalized}@c.us" : (string) $number;
    }
}

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

                $devStatus = $this->getDeviceStatus($url, $apiKey, $sessionId);
                $deviceState = $devStatus['state'];
                $deviceId = $devStatus['device_id'];
                $isConnected = $devStatus['connected'];

                if (!$isConnected && $deviceId) {
                    return [
                        'success' => false,
                        'message' => "GoWA server is online ({$version}), but WhatsApp device '{$deviceId}' is NOT PAIRED ({$deviceState}). Please scan QR code in your GoWA dashboard (http://76.13.212.71:32769/#/) to connect WhatsApp.",
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
     * Inspect WhatsApp device connection status on GoWA server.
     */
    public function getDeviceStatus(string $url, ?string $apiKey = null, ?string $sessionId = null): array
    {
        $url = rtrim($url, '/');
        $deviceId = $this->resolveDeviceId($url, $apiKey, $sessionId);

        try {
            $resp = $this->httpClient($apiKey, $sessionId)->timeout(5)->get("{$url}/devices");

            if ($resp->successful()) {
                $devices = $resp->json()['results'] ?? [];

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
            Log::debug('GoWA getDeviceStatus failed', ['error' => $e->getMessage()]);
        }

        return ['connected' => true, 'device_id' => $deviceId, 'state' => 'unknown', 'jid' => null];
    }

    /**
     * Get participants in a GoWA group via GET /group/info or GET /group/members.
     */
    public function getGroupParticipants(string $url, string $groupId, ?string $apiKey = null, ?string $sessionId = null): array
    {
        $url = rtrim($url, '/');
        $sessionId = $this->resolveDeviceId($url, $apiKey, $sessionId);

        $devStatus = $this->getDeviceStatus($url, $apiKey, $sessionId);

        if (!$devStatus['connected']) {
            return [
                'success' => false,
                'message' => "WhatsApp device '{$devStatus['device_id']}' is DISCONNECTED ({$devStatus['state']}) on GoWA server. Please click Pair in your GoWA dashboard to connect WhatsApp.",
                'participants' => [],
            ];
        }

        $primaryErrorMessage = null;

        try {
            // Primary GoWA endpoint: GET /group/info?group_id={groupId}
            $response = $this->httpClient($apiKey, $sessionId)
                ->timeout(10)
                ->get("{$url}/group/info", [
                    'group_id' => $groupId,
                ]);

            if (!$response->successful()) {
                $errBody = $response->json();
                $primaryErrorMessage = $errBody['message'] ?? null;

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
                                $isAdmin = false;

                                if (is_array($item)) {
                                    $isAdmin = (bool) (
                                        ($item['IsAdmin'] ?? false) ||
                                        ($item['is_admin'] ?? false) ||
                                        ($item['IsSuperAdmin'] ?? false) ||
                                        ($item['is_super_admin'] ?? false)
                                    );
                                }

                                $participants[] = [
                                    'raw' => $phone,
                                    'normalized' => $normalized,
                                    'is_admin' => $isAdmin,
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

            $errBody = $response->json();
            $errMsg = $primaryErrorMessage ?? $errBody['message'] ?? ('HTTP ' . $response->status());

            return [
                'success' => false,
                'message' => 'Failed to fetch GoWA group participants: ' . $errMsg,
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
     * Check if a phone number has a registered WhatsApp account.
     */
    public function checkUserRegistered(string $url, string $phone, ?string $apiKey = null, ?string $sessionId = null): bool
    {
        $url = rtrim($url, '/');
        $formatted = $this->formatForGoWa($phone);
        $cleanDigits = $this->normalizePhone($phone);

        try {
            $response = $this->httpClient($apiKey, $sessionId)
                ->timeout(6)
                ->get("{$url}/user/check", [
                    'phone' => $formatted,
                ]);

            if (!$response->successful() && $cleanDigits) {
                $response = $this->httpClient($apiKey, $sessionId)
                    ->timeout(6)
                    ->get("{$url}/user/check", [
                        'phone' => $cleanDigits,
                    ]);
            }

            if ($response->successful()) {
                $body = $response->json();
                $data = $body['results'] ?? $body['data'] ?? $body;

                if (is_array($data)) {
                    if (isset($data['is_registered'])) {
                        return (bool) $data['is_registered'];
                    }

                    if (isset($data['registered'])) {
                        return (bool) $data['registered'];
                    }

                    if (isset($data['on_whatsapp'])) {
                        return (bool) $data['on_whatsapp'];
                    }

                    if (isset($data['is_valid'])) {
                        return (bool) $data['is_valid'];
                    }
                }

                $code = strtoupper($body['code'] ?? '');

                return $code === 'SUCCESS' || $code === '200' || !empty($data);
            }
        } catch (\Throwable $e) {
            Log::warning('GoWA checkUserRegistered error', ['phone' => $phone, 'error' => $e->getMessage()]);
        }

        return true;
    }

    /**
     * Add participant(s) to a GoWA group one by one, checking WhatsApp account existence first.
     */
    public function addParticipants(string $url, string $groupId, array $phones, ?string $apiKey = null, ?string $sessionId = null): array
    {
        $url = rtrim($url, '/');
        $sessionId = $this->resolveDeviceId($url, $apiKey, $sessionId);

        $devStatus = $this->getDeviceStatus($url, $apiKey, $sessionId);

        if (!$devStatus['connected']) {
            return [
                'success' => false,
                'added' => [],
                'failed' => array_map(fn ($p) => [
                    'phone' => $p,
                    'reason' => "WhatsApp device '{$devStatus['device_id']}' is DISCONNECTED ({$devStatus['state']}) on GoWA server. Please pair/scan QR code in your GoWA dashboard.",
                ], $phones),
            ];
        }

        $added = [];
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

        // Retrieve group invite link
        $inviteLink = $this->getGroupInviteLink($url, $groupId, $apiKey, $sessionId);

        if (!$inviteLink) {
            return [
                'success' => false,
                'added' => [],
                'failed' => array_map(fn ($p) => [
                    'phone' => $p,
                    'reason' => 'Failed to retrieve WhatsApp Group Invite Link from GoWA server.',
                ], $validPhones),
            ];
        }

        // Send group invite link via direct WhatsApp message ONE BY ONE
        foreach ($validPhones as $index => $phone) {
            if ($index > 0) {
                sleep(2); // 2s humanized delay between direct messages
            }

            // Check if phone number has a registered WhatsApp account
            $isRegistered = $this->checkUserRegistered($url, $phone, $apiKey, $sessionId);

            if (!$isRegistered) {
                $failed[] = ['phone' => $phone, 'reason' => 'Phone number does not have a registered WhatsApp account'];
                Log::info('GoWA addParticipants: Skipped non-WhatsApp number', ['phone' => $phone]);
                continue;
            }

            $message = "Hi! You are invited to join our WhatsApp group. Please click this link to join: {$inviteLink}";
            $sendRes = $this->sendMessage($url, $phone, $message, $apiKey, $sessionId);

            if ($sendRes['success'] ?? false) {
                $added[] = $phone;
                Log::info('GoWA addParticipants: Sent group invite link PM', ['phone' => $phone, 'invite_link' => $inviteLink]);
            } else {
                $failed[] = [
                    'phone' => $phone,
                    'reason' => $sendRes['message'] ?? 'Failed to send WhatsApp invite message via PM',
                ];
            }
        }

        return [
            'success' => count($added) > 0 || count($failed) === 0,
            'added' => $added,
            'failed' => $failed,
            'invite_link' => $inviteLink,
        ];
    }

    /**
     * Send a direct WhatsApp text message via POST /send/message.
     */
    public function sendMessage(string $url, string $phone, string $message, ?string $apiKey = null, ?string $sessionId = null): array
    {
        $url = rtrim($url, '/');
        $sessionId = $this->resolveDeviceId($url, $apiKey, $sessionId);
        $formatted = $this->formatForGoWa($phone);

        try {
            $response = $this->httpClient($apiKey, $sessionId)
                ->timeout(10)
                ->post("{$url}/send/message", [
                    'phone' => $formatted,
                    'message' => $message,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Direct WhatsApp message sent successfully.',
                    'data' => $response->json(),
                ];
            }

            $errBody = $response->json();

            return [
                'success' => false,
                'message' => $errBody['message'] ?? ('HTTP ' . $response->status()),
            ];
        } catch (\Throwable $e) {
            Log::warning('GoWA sendMessage error', ['phone' => $phone, 'error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get WhatsApp group invite link via GET /group/invite-link?group_id={groupId}.
     */
    public function getGroupInviteLink(string $url, string $groupId, ?string $apiKey = null, ?string $sessionId = null): ?string
    {
        $url = rtrim($url, '/');
        $sessionId = $this->resolveDeviceId($url, $apiKey, $sessionId);

        try {
            $response = $this->httpClient($apiKey, $sessionId)
                ->timeout(8)
                ->get("{$url}/group/invite-link", [
                    'group_id' => $groupId,
                ]);

            if ($response->successful()) {
                $body = $response->json();
                $results = $body['results'] ?? $body['data'] ?? [];

                return $results['invite_link'] ?? $results['url'] ?? null;
            }
        } catch (\Throwable $e) {
            Log::warning('GoWA getGroupInviteLink failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Check if an error message/status indicates a WhatsApp privacy restriction.
     */
    private function isPrivacyError(string $message, int $statusCode = 0): bool
    {
        $lower = strtolower($message);

        return $statusCode === 403
            || $statusCode === 408
            || str_contains($lower, 'privacy')
            || str_contains($lower, 'invite')
            || str_contains($lower, 'cannot add')
            || str_contains($lower, 'can\'t add')
            || str_contains($lower, 'setting')
            || str_contains($lower, 'permission')
            || str_contains($lower, 'restrict');
    }

    /**
     * Remove participant(s) from a GoWA group via POST /group/participants/remove.
     * Protects group admins from being removed.
     */
    public function removeParticipants(string $url, string $groupId, array $phones, ?string $apiKey = null, ?string $sessionId = null): array
    {
        $url = rtrim($url, '/');
        $sessionId = $this->resolveDeviceId($url, $apiKey, $sessionId);
        $removed = [];
        $failed = [];
        $validPhones = [];

        // Fetch current group participants to identify and protect admins
        $gowaResult = $this->getGroupParticipants($url, $groupId, $apiKey, $sessionId);
        $adminMap = [];

        if ($gowaResult['success']) {
            foreach ($gowaResult['participants'] as $gp) {
                if (!empty($gp['is_admin'])) {
                    $adminMap[$gp['normalized']] = true;
                }
            }
        }

        foreach ($phones as $p) {
            $norm = $this->normalizePhone($p);

            if (!$norm || strlen($norm) < 10 || strlen($norm) > 15) {
                $failed[] = ['phone' => $p, 'reason' => 'Invalid phone number format'];
            } elseif (isset($adminMap[$norm])) {
                $failed[] = ['phone' => $p, 'reason' => 'Cannot remove group admin'];
                Log::info('GoWA removeParticipants: Protected group admin from removal', ['phone' => $p]);
            } else {
                $validPhones[] = $p;
            }
        }

        // Process removals in small chunks (3 at a time with 3s delay)
        $chunks = array_chunk($validPhones, 3);

        foreach ($chunks as $index => $chunk) {
            if ($index > 0) {
                sleep(3);
            }

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
            foreach ($chunk as $fIdx => $phone) {
                if ($fIdx > 0) {
                    sleep(2);
                }

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

        // GoWA members to remove (in GoWA group, not in system matching list, and not an admin)
        $toRemoveRaw = [];

        foreach ($gowaParticipants as $p) {
            // Protect group admins from being flagged for removal
            if (!empty($p['is_admin'])) {
                continue;
            }

            if (!isset($systemNormalizedMap[$p['normalized']])) {
                $toRemoveRaw[] = [
                    'raw_phone' => $p['raw'],
                    'normalized_phone' => $p['normalized'],
                ];
            }
        }

        // Look up member contact details in system database for non-matching participants using 9-digit suffix matching
        $toRemoveRaw = $toRemoveRaw;
        $suffixes = [];

        foreach ($toRemoveRaw as $item) {
            $digits = preg_replace('/\D/', '', explode('@', $item['normalized_phone'])[0]);

            if (strlen($digits) >= 7) {
                $suffixes[] = substr($digits, -9);
            }
        }
        $suffixes = array_values(array_unique(array_filter($suffixes)));

        $lookupBySuffix = [];

        if (!empty($suffixes)) {
            // Query ALL members in database (not restricted to active members)
            $matchedDbMembers = Member::query()
                ->where(function ($q) use ($suffixes) {
                    foreach ($suffixes as $suf) {
                        $q->orWhere('phone_number', 'like', "%{$suf}")
                            ->orWhere('whatsapp_number', 'like', "%{$suf}");
                    }
                })
                ->get(['id', 'name', 'phone_number', 'whatsapp_number']);

            foreach ($matchedDbMembers as $dbMember) {
                $p1Digits = preg_replace('/\D/', '', (string) $dbMember->phone_number);
                $p2Digits = preg_replace('/\D/', '', (string) $dbMember->whatsapp_number);

                if (strlen($p1Digits) >= 7) {
                    $s1 = substr($p1Digits, -9);
                    $lookupBySuffix[$s1] = [
                        'id' => $dbMember->id,
                        'name' => (string) $dbMember->name,
                        'phone' => $dbMember->phone_number,
                    ];
                }

                if (strlen($p2Digits) >= 7) {
                    $s2 = substr($p2Digits, -9);
                    $lookupBySuffix[$s2] = [
                        'id' => $dbMember->id,
                        'name' => (string) $dbMember->name,
                        'phone' => $dbMember->whatsapp_number,
                    ];
                }
            }
        }

        $toRemove = [];

        foreach ($toRemoveRaw as $item) {
            $norm = $item['normalized_phone'];
            $digits = preg_replace('/\D/', '', explode('@', $norm)[0]);
            $suf = strlen($digits) >= 7 ? substr($digits, -9) : $digits;

            $memberInfo = $lookupBySuffix[$suf] ?? null;

            $toRemove[] = [
                'raw_phone' => $item['raw_phone'],
                'normalized_phone' => $norm,
                'member_id' => $memberInfo['id'] ?? null,
                'name' => $memberInfo['name'] ?? null,
                'phone' => $memberInfo['phone'] ?? null,
                'is_system_member' => !empty($memberInfo),
            ];
        }

        $inviteLink = $this->getGroupInviteLink($url, $groupId, $apiKey, $sessionId);

        return [
            'success' => true,
            'group_id' => $groupId,
            'invite_link' => $inviteLink,
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
     * Resolve valid GoWA device ID if a phone JID or invalid session ID was provided.
     */
    public function resolveDeviceId(string $url, ?string $apiKey = null, ?string $sessionId = null): ?string
    {
        try {
            $resp = $this->httpClient($apiKey)->timeout(5)->get(rtrim($url, '/') . '/devices');

            if ($resp->successful()) {
                $devices = $resp->json()['results'] ?? [];

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

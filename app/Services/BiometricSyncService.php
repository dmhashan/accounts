<?php

namespace App\Services;

use App\Models\BiometricSyncLog;
use App\Models\Member;
use App\Models\MemberAttendance;
use App\Models\PaymentMembership;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * BiometricSyncService
 *
 * Orchestrates all biometric device synchronisation for a tenant.
 *
 * - Up-sync  : member create / update / delete → pushed to device
 * - Down-sync: attendance events pulled from device → stored in member_attendances
 *
 * All errors are caught and logged — this service never throws.
 */
class BiometricSyncService
{
    // Map of supported device makers → driver class
    private const DRIVERS = [
        'hikvision' => HikvisionService::class,
    ];

    /**
     * Hikvision Value Series (K1T320 family) does NOT implement the remote
     * fingerprint-enrolment trigger (PUT /ISAPI/AccessControl/Fingerprint/SetUp).
     * Attempting it returns HTTP 400 / subStatusCode "methodNotAllowed".
     * Fingerprint enrolment on these devices must be done physically at the terminal.
     */
    private const FINGERPRINT_SETUP_UNSUPPORTED_MODELS = [
        'DS-K1T320', // Value Series — all variants
    ];

    public function __construct(
        private readonly TenantConfigurationService $config,
        private readonly MediaStorageService $media,
    ) {}

    // -------------------------------------------------------------------------
    // Feature-flag helpers
    // -------------------------------------------------------------------------

    public function isEnabled(int $tenantId): bool
    {
        return $this->cfg($tenantId, 'biometric.enabled') === '1';
    }

    public function isMemberSyncEnabled(int $tenantId): bool
    {
        return $this->isEnabled($tenantId) && $this->cfg($tenantId, 'biometric.sync_members') === '1';
    }

    public function isAttendanceSyncEnabled(int $tenantId): bool
    {
        return $this->isEnabled($tenantId) && $this->cfg($tenantId, 'biometric.sync_attendance') === '1';
    }

    public function isAccessControlEnabled(int $tenantId): bool
    {
        return $this->isEnabled($tenantId) && $this->cfg($tenantId, 'biometric.access_control') === '1';
    }

    public function getGracePeriodDays(int $tenantId): int
    {
        return (int) ($this->cfg($tenantId, 'biometric.grace_period_days') ?? 0);
    }

    // -------------------------------------------------------------------------
    // Up-sync: member → device
    // -------------------------------------------------------------------------

    /**
     * Sync a single member to the device.
     *
     * @param  string  $action  create | update | delete | manual_sync
     */
    public function syncMember(Member $member, string $action): void
    {
        $tenantId = $member->tenant_id;

        try {
            // Manual sync only requires the device to be enabled (not auto-sync toggle)
            $allowed = $action === 'manual_sync'
                ? $this->isEnabled($tenantId)
                : $this->isMemberSyncEnabled($tenantId);

            if (!$allowed) {
                Log::debug('BiometricSyncService: sync skipped — not enabled', [
                    'member_id' => $member->id,
                    'action' => $action,
                    'is_enabled' => $this->isEnabled($tenantId),
                    'sync_members' => $this->isMemberSyncEnabled($tenantId),
                ]);

                return;
            }

            $allConfig = $this->config->all($tenantId);

            Log::debug('BiometricSyncService: config loaded', [
                'member_id' => $member->id,
                'action' => $action,
                'device_maker' => $allConfig['biometric.device_maker'] ?? '(none)',
                'device_ip' => $allConfig['biometric.device_ip'] ?? '(none)',
                'device_port' => $allConfig['biometric.device_port'] ?? '(none)',
                'biometric_member_id' => $member->biometric_member_id,
            ]);

            $driver = $this->buildDriver($allConfig);

            if (!$driver) {
                Log::debug('BiometricSyncService: sync skipped — driver could not be built (check device_maker and device_ip)', [
                    'member_id' => $member->id,
                    'device_maker' => $allConfig['biometric.device_maker'] ?? '',
                    'device_ip' => $allConfig['biometric.device_ip'] ?? '',
                ]);

                return;
            }

            $maker = $allConfig['biometric.device_maker'] ?? '';
            $model = $allConfig['biometric.device_model'] ?? '';

            // Skip if no biometric ID assigned to this member
            if (empty($member->biometric_member_id)) {
                Log::debug('BiometricSyncService: sync skipped — member has no biometric_member_id', [
                    'member_id' => $member->id,
                    'action' => $action,
                ]);

                return;
            }

            Log::debug('BiometricSyncService: executing sync', [
                'member_id' => $member->id,
                'member_name' => $member->name,
                'biometric_member_id' => $member->biometric_member_id,
                'action' => $action,
                'device' => "{$maker} {$model}",
            ]);

            if ($action === 'delete') {
                $result = $driver->deletePerson($member->biometric_member_id);
                $payload = ['employeeNo' => $member->biometric_member_id];
            } else {
                $accessControl = ($allConfig['biometric.access_control'] ?? '0') === '1';
                $graceDays = (int) ($allConfig['biometric.grace_period_days'] ?? 0);
                $personPayload = $this->buildPersonPayload($member, $accessControl, $graceDays);
                $payload = $personPayload;

                $alreadyExistsCodes = ['deviceUserAlreadyExist', 'employeeNoAlreadyExist'];
                $notExistCodes = ['employeeNoNotExist', 'noSuchUser'];

                if ($action === 'update') {
                    // For update: try updatePerson first, fall back to addPerson if not on device yet
                    $result = $driver->updatePerson($personPayload);

                    Log::debug('BiometricSyncService: updatePerson result', [
                        'success' => $result['success'],
                        'message' => $result['message'],
                        'subStatusCode' => $result['data']['subStatusCode'] ?? null,
                    ]);

                    if (!$result['success'] && isset($result['data']['subStatusCode'])
                        && in_array($result['data']['subStatusCode'], $notExistCodes, true)) {
                        $result = $driver->addPerson($personPayload);
                        Log::debug('BiometricSyncService: addPerson result (after not-exists on update)', [
                            'success' => $result['success'],
                            'message' => $result['message'],
                        ]);
                    }
                } else {
                    // For create / manual_sync: try addPerson first, fall back to updatePerson if already on device
                    $result = $driver->addPerson($personPayload);

                    Log::debug('BiometricSyncService: addPerson result', [
                        'success' => $result['success'],
                        'message' => $result['message'],
                        'subStatusCode' => $result['data']['subStatusCode'] ?? null,
                    ]);

                    if (!$result['success'] && isset($result['data']['subStatusCode'])
                        && in_array($result['data']['subStatusCode'], $alreadyExistsCodes, true)) {
                        $result = $driver->updatePerson($personPayload);
                        Log::debug('BiometricSyncService: updatePerson result (after already-exists)', [
                            'success' => $result['success'],
                            'message' => $result['message'],
                        ]);
                    }
                }
            }

            $status = $result['success'] ? 'success' : 'failed';

            Log::debug('BiometricSyncService: sync complete', [
                'member_id' => $member->id,
                'action' => $action,
                'status' => $status,
                'message' => $result['message'],
                'response' => $result['data'],
            ]);

            $this->writeLog([
                'tenant_id' => $tenantId,
                'biometric_member_id' => $member->id,
                'direction' => 'up',
                'action' => $action,
                'status' => $status,
                'device_maker' => $maker,
                'device_model' => $model,
                'payload' => $payload,
                'response' => $result['data'],
                'error_message' => $result['success'] ? null : $result['message'],
            ]);

            if ($result['success'] && $action !== 'delete') {
                $member->timestamps = false;
                $member->update(['biometric_last_synced_at' => now()]);
                $member->timestamps = true;
            }
        } catch (\Throwable $e) {
            Log::error('BiometricSyncService::syncMember error', [
                'member_id' => $member->id,
                'action' => $action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Sync all active non-temp members to the device.
     * Returns count of synced/failed.
     */
    public function syncAllMembers(Tenant $tenant): array
    {
        $synced = 0;
        $failed = 0;

        if (!$this->isMemberSyncEnabled($tenant->id)) {
            return ['synced' => 0, 'failed' => 0, 'message' => 'Member sync is disabled.'];
        }

        $members = Member::where('tenant_id', $tenant->id)
            ->where('is_temp', false)
            ->get();

        foreach ($members as $member) {
            $this->syncMember($member, 'manual_sync');

            // Check the latest log for this member
            $log = BiometricSyncLog::where('tenant_id', $tenant->id)
                ->where('biometric_member_id', $member->id)
                ->latest('created_at')
                ->first();

            if ($log && $log->status === 'success') {
                $synced++;
            } else {
                $failed++;
            }
        }

        return ['synced' => $synced, 'failed' => $failed, 'message' => "Synced {$synced} members, {$failed} failed."];
    }

    // -------------------------------------------------------------------------
    // Down-sync: device → attendance records
    // -------------------------------------------------------------------------

    /**
     * Pull attendance events from the device for the last 24 h and persist them.
     */
    public function pullAttendance(Tenant $tenant): array
    {
        $tenantId = $tenant->id;
        $created = 0;
        $errors = 0;

        try {
            if (!$this->isAttendanceSyncEnabled($tenantId)) {
                return ['created' => 0, 'errors' => 0, 'message' => 'Attendance sync is disabled.'];
            }

            $allConfig = $this->config->all($tenantId);
            $driver = $this->buildDriver($allConfig);

            if (!$driver) {
                return ['created' => 0, 'errors' => 0, 'message' => 'No device configured.'];
            }

            $maker = $allConfig['biometric.device_maker'] ?? '';
            $model = $allConfig['biometric.device_model'] ?? '';
            $startTime = now()->subHours(25)->format('Y-m-d\TH:i:s');
            $endTime = now()->format('Y-m-d\TH:i:s');

            $offset = 0;
            $maxResults = 50;
            $hasMore = true;

            while ($hasMore) {
                $result = $driver->getAttendanceEvents($startTime, $endTime, $offset, $maxResults);

                if (!$result['success']) {
                    $this->writeLog([
                        'tenant_id' => $tenantId,
                        'biometric_member_id' => null,
                        'direction' => 'down',
                        'action' => 'attendance',
                        'status' => 'failed',
                        'device_maker' => $maker,
                        'device_model' => $model,
                        'payload' => ['startTime' => $startTime, 'endTime' => $endTime],
                        'response' => $result['data'],
                        'error_message' => $result['message'],
                    ]);
                    $errors++;
                    break;
                }

                $acsEvent = $result['data']['AcsEvent'] ?? [];
                $infoList = $acsEvent['InfoList'] ?? [];
                $morePages = ($acsEvent['responseStatusStrg'] ?? 'OK') === 'MORE';

                foreach ($infoList as $event) {
                    try {
                        $this->persistAttendanceEvent($tenant, $event);
                        $created++;
                    } catch (\Throwable $e) {
                        Log::warning('Biometric attendance event persist error', ['event' => $event, 'error' => $e->getMessage()]);
                        $errors++;
                    }
                }

                $numMatches = $acsEvent['numOfMatches'] ?? count($infoList);
                $offset += $numMatches;
                $hasMore = $morePages && $numMatches > 0;
            }

            $this->writeLog([
                'tenant_id' => $tenantId,
                'biometric_member_id' => null,
                'direction' => 'down',
                'action' => 'attendance',
                'status' => $errors === 0 ? 'success' : 'failed',
                'device_maker' => $maker,
                'device_model' => $model,
                'payload' => ['startTime' => $startTime, 'endTime' => $endTime],
                'response' => ['created' => $created, 'errors' => $errors],
                'error_message' => $errors > 0 ? "{$errors} event(s) failed to persist." : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('BiometricSyncService::pullAttendance error', ['tenant_id' => $tenantId, 'error' => $e->getMessage()]);
            $errors++;
        }

        return ['created' => $created, 'errors' => $errors, 'message' => "Created {$created} attendance records, {$errors} errors."];
    }

    // -------------------------------------------------------------------------
    // Connection test (for settings UI)
    // -------------------------------------------------------------------------

    public function testConnection(Tenant $tenant): array
    {
        $allConfig = $this->config->all($tenant->id);
        $driver = $this->buildDriver($allConfig);
        $maker = $allConfig['biometric.device_maker'] ?? '';
        $model = $allConfig['biometric.device_model'] ?? '';

        if (!$driver) {
            return ['success' => false, 'message' => 'No device maker configured.'];
        }

        $result = $driver->testConnection();

        $this->writeLog([
            'tenant_id' => $tenant->id,
            'biometric_member_id' => null,
            'direction' => 'up',
            'action' => 'test',
            'status' => $result['success'] ? 'success' : 'failed',
            'device_maker' => $maker,
            'device_model' => $model,
            'payload' => [],
            'response' => $result['data'],
            'error_message' => $result['success'] ? null : $result['message'],
        ]);

        return $result;
    }

    /**
     * Trigger fingerprint enrolment on the device for a member.
     * Returns ['success' => bool, 'message' => string].
     */
    public function setupMemberFingerprint(Member $member): array
    {
        $allConfig = $this->config->all($member->tenant_id);
        $driver = $this->buildDriver($allConfig);

        if (!$driver) {
            return ['success' => false, 'message' => 'Device not configured.'];
        }

        $employeeNo = $member->biometric_member_id;

        if (!$employeeNo) {
            return ['success' => false, 'message' => 'No biometric ID assigned to this member.'];
        }

        $model = $allConfig['biometric.device_model'] ?? '';

        if ($this->modelLacksRemoteFingerprintSetup($model)) {
            return [
                'success' => false,
                'message' => "Remote fingerprint enrolment is not supported on {$model}. "
                    . 'Enrol the fingerprint directly at the terminal (device screen → Personnel).',
            ];
        }

        return $driver->setupFingerprint($employeeNo);
    }

    // -------------------------------------------------------------------------
    // Device record query
    // -------------------------------------------------------------------------

    /**
     * Query the device for the current record of a member.
     *
     * Returns one of:
     *   ['connection_failed' => true, ...]
     *   ['not_assigned'      => true, ...]
     *   ['not_found'         => true, ...]
     *   ['person' => [...], 'face' => [...], 'fingerprint' => [...], 'card' => [...]]
     */
    public function getMemberDeviceInfo(Member $member): array
    {
        $tenantId = $member->tenant_id;
        $allConfig = $this->config->all($tenantId);
        $driver = $this->buildDriver($allConfig);

        if (!$driver) {
            return ['connection_failed' => true, 'not_assigned' => false, 'not_found' => false, 'message' => 'Device not configured.'];
        }

        $employeeNo = $member->biometric_member_id;

        if (!$employeeNo) {
            return ['connection_failed' => false, 'not_assigned' => true, 'not_found' => false, 'message' => 'No biometric ID assigned to this member.'];
        }

        // ── Person info ───────────────────────────────────────────────────────
        $personResult = $driver->getUserInfo($employeeNo);

        if (!$personResult['success']) {
            $msg = strtolower($personResult['message'] ?? '');

            if (str_contains($msg, 'connection') || str_contains($msg, 'failed')) {
                return ['connection_failed' => true, 'not_assigned' => false, 'not_found' => false, 'message' => $personResult['message']];
            }

            return ['connection_failed' => false, 'not_assigned' => false, 'not_found' => true, 'message' => 'Member not found on device.'];
        }

        $userInfoSearch = $personResult['data']['UserInfoSearch'] ?? [];
        $numMatches = (int) ($userInfoSearch['numOfMatches'] ?? 0);
        $userInfoList = $userInfoSearch['UserInfo'] ?? [];

        // Handle device wrapping a single object (not an array)
        if ($numMatches > 0 && !empty($userInfoList) && !isset($userInfoList[0])) {
            $userInfoList = [$userInfoList];
        }

        if ($numMatches === 0 || empty($userInfoList)) {
            return ['connection_failed' => false, 'not_assigned' => false, 'not_found' => true, 'message' => 'Member not found on device.'];
        }

        $person = (array) $userInfoList[0];
        $valid = $person['Valid'] ?? [];

        // ── Credential counts from UserInfo (avoids separate search endpoints
        //    that many device models return notSupport for) ───────────────────
        $faceCount = (int) ($person['numOfFace'] ?? 0);
        $fpCount = (int) ($person['numOfFP'] ?? 0);
        $cardCount = (int) ($person['numOfCard'] ?? 0);
        $faceUrl = ($person['faceURL'] ?? '') ?: null;

        // ── Card numbers — only query when the device reports cards assigned ──
        $cardNumbers = [];

        if ($cardCount > 0) {
            $cardResult = $driver->getCardInfo($employeeNo);

            if ($cardResult['success']) {
                $cardSearch = $cardResult['data']['CardInfoSearch'] ?? [];
                $cardList = $cardSearch['CardInfo'] ?? [];

                if (!empty($cardList)) {
                    $cardArr = isset($cardList[0]) ? $cardList : [$cardList];
                    $cardNumbers = array_values(array_filter(array_map(fn ($c) => $c['cardNo'] ?? '', $cardArr)));
                }
            }
        }

        return [
            'connection_failed' => false,
            'not_assigned' => false,
            'not_found' => false,
            'fingerprint_setup_supported' => !$this->modelLacksRemoteFingerprintSetup($allConfig['biometric.device_model'] ?? ''),
            'person' => [
                'employee_no' => $person['employeeNo'] ?? $employeeNo,
                'name' => $person['name'] ?? '',
                'gender' => $person['gender'] ?? null,
                'user_type' => $person['userType'] ?? null,
                'valid_enabled' => isset($valid['enable']) ? (bool) $valid['enable'] : null,
                'valid_begin' => $valid['beginTime'] ?? null,
                'valid_end' => $valid['endTime'] ?? null,
            ],
            'face' => ['enrolled' => $faceCount > 0, 'count' => $faceCount, 'face_url' => $faceUrl],
            'fingerprint' => ['enrolled' => $fpCount > 0, 'count' => $fpCount],
            'card' => ['assigned' => $cardCount > 0, 'count' => $cardCount, 'card_numbers' => $cardNumbers],
        ];
    }

    /**
     * Upload the enrolled face image from the device as the member's profile photo.
     * Only uploads when the member has no existing photo.
     * Returns ['success', 'profile_photo_url'] on success.
     */
    public function uploadFaceAsAvatar(Member $member): array
    {
        if ($member->profile_photo_path) {
            return ['success' => false, 'message' => 'Member already has a profile photo.'];
        }

        $imageResult = $this->getMemberFaceImage($member);

        if (!$imageResult['success'] || $imageResult['body'] === '') {
            return ['success' => false, 'message' => 'Could not retrieve face image from device.'];
        }

        $extension = str_contains($imageResult['content_type'], 'png') ? 'png' : 'jpg';
        $filename = 'member-avatars/face_' . $member->id . '_' . Str::random(8) . '.' . $extension;

        $path = $this->media->storeContent($imageResult['body'], $filename);

        $member->update(['profile_photo_path' => $path]);

        return [
            'success' => true,
            'profile_photo_url' => $this->media->url($path),
        ];
    }

    /**
     * Proxy the enrolled face image for a member from the device.
     * Returns ['success', 'body', 'content_type'] for streaming to the browser.
     */
    public function getMemberFaceImage(Member $member): array
    {
        $tenantId = $member->tenant_id;
        $allConfig = $this->config->all($tenantId);
        $driver = $this->buildDriver($allConfig);

        if (!$driver || !$member->biometric_member_id) {
            return ['success' => false, 'body' => '', 'content_type' => ''];
        }

        $employeeNo = $this->extractEmployeeNo($member->biometric_member_id);
        $personResult = $driver->getUserInfo($employeeNo);

        if (!$personResult['success']) {
            return ['success' => false, 'body' => '', 'content_type' => ''];
        }

        $userInfoList = $personResult['data']['UserInfoSearch']['UserInfo'] ?? [];

        if (!empty($userInfoList) && !isset($userInfoList[0])) {
            $userInfoList = [$userInfoList];
        }

        if (empty($userInfoList)) {
            return ['success' => false, 'body' => '', 'content_type' => ''];
        }

        $faceUrl = ((array) $userInfoList[0])['faceURL'] ?? null;

        if (!$faceUrl) {
            return ['success' => false, 'body' => '', 'content_type' => ''];
        }

        return $driver->proxyImage($faceUrl);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build the person payload for the device from a Member model.
     */
    private function buildPersonPayload(Member $member, bool $accessControl, int $graceDays): array
    {
        // Always calculate validity from membership data (never use far-future Long-Term fallback).
        $validUntil = $this->getMemberValidUntil($member, $graceDays);

        // beginTime: use the member's joined date (start of day).
        $beginTime = $member->joined_date
            ? $member->joined_date->startOfDay()->format('Y-m-d\TH:i:s')
            : now()->startOfDay()->format('Y-m-d\TH:i:s');

        // endTime: use membership end + grace if available; otherwise expire end of joined date.
        // (no membership → access expires at end of the day they joined)
        $endTime = $validUntil
            ? $validUntil->format('Y-m-d\TH:i:s')
            : ($member->joined_date
                ? $member->joined_date->copy()->endOfDay()->format('Y-m-d\TH:i:s')
                : now()->endOfDay()->format('Y-m-d\TH:i:s'));

        return [
            'employeeNo' => $this->extractEmployeeNo($member->biometric_member_id),
            'name' => mb_substr($member->name, 0, 32),
            'userType' => 'normal',
            'gender' => $member->gender === 'female' ? 'female' : 'male',
            'Valid' => [
                'enable' => true,
                'beginTime' => $beginTime,
                'endTime' => $endTime,
            ],
            'doorRight' => '1',
            'RightPlan' => [
                ['doorNo' => 1, 'planTemplateNo' => '1'],
            ],
        ];
    }

    /**
     * Find the furthest membership end date for a member, plus grace period.
     */
    private function getMemberValidUntil(Member $member, int $graceDays): ?Carbon
    {
        $latestMembership = PaymentMembership::whereHas('payment', function ($q) use ($member) {
            $q->where('member_id', $member->id);
        })
            ->orderByDesc('end_date')
            ->first();

        if (!$latestMembership) {
            return null;
        }

        return Carbon::parse($latestMembership->end_date)->addDays($graceDays)->endOfDay();
    }

    /**
     * Persist a single attendance event from the device as a MemberAttendance record.
     * Ignores duplicates (same member + same date).
     */
    private function persistAttendanceEvent(Tenant $tenant, array $event): void
    {
        $employeeNo = $event['employeeNoString'] ?? null;
        $eventTime = $event['time'] ?? null;

        if (!$employeeNo || !$eventTime) {
            return;
        }

        // Only record successful access events (minor 75 = face OK, 38 = card OK, 113 = fingerprint OK)
        $minor = (int) ($event['minor'] ?? 0);

        if (!in_array($minor, [75, 38, 113], true)) {
            return;
        }

        $member = Member::where('tenant_id', $tenant->id)
            ->where('biometric_member_id', 'like', '%-' . str_pad($employeeNo, 4, '0', STR_PAD_LEFT))
            ->first();

        if (!$member) {
            return;
        }

        $attendedDate = Carbon::parse($eventTime)->toDateString();

        MemberAttendance::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'member_id' => $member->id,
                'attended_date' => $attendedDate,
            ],
            [
                'username' => $member->username ?? $member->biometric_member_id,
            ],
        );
    }

    /**
     * Extract the 4-digit numeric suffix from a MEM-YYYY-XXXX member ID.
     * This is used as the HikVision device's employeeNo (must be numeric).
     */
    private function extractEmployeeNo(string $biometricMemberId): string
    {
        // Extract trailing digits, e.g. MEM-2026-0042 → '0042'
        if (preg_match('/-([0-9]+)$/', $biometricMemberId, $m)) {
            return str_pad((int) $m[1], 4, '0', STR_PAD_LEFT);
        }

        // Fallback: strip non-numeric characters
        return preg_replace('/[^0-9]/', '', $biometricMemberId);
    }

    /**
     * Returns true when the configured device model is known NOT to support the
     * remote fingerprint-enrolment trigger via ISAPI.
     */
    private function modelLacksRemoteFingerprintSetup(string $model): bool
    {
        foreach (self::FINGERPRINT_SETUP_UNSUPPORTED_MODELS as $prefix) {
            if (str_starts_with(strtoupper($model), strtoupper($prefix))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build the device driver for the given config array.
     */
    private function buildDriver(array $config): ?object
    {
        $maker = $config['biometric.device_maker'] ?? '';

        if (!isset(self::DRIVERS[$maker])) {
            return null;
        }

        $ip = $config['biometric.device_ip'] ?? '';
        $port = (int) ($config['biometric.device_port'] ?? 80);
        $username = $config['biometric.device_username'] ?? 'admin';
        $password = $config['biometric.device_password'] ?? '';

        if (!$ip) {
            return null;
        }

        return new HikvisionService($ip, $port, $username, $password);
    }

    /**
     * Get a single config value for a tenant.
     */
    private function cfg(int $tenantId, string $key): string
    {
        return $this->config->all($tenantId)[$key] ?? '';
    }

    private function writeLog(array $data): void
    {
        BiometricSyncLog::create(array_merge($data, ['synced_at' => now()]));
    }
}

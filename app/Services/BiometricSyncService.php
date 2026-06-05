<?php

namespace App\Services;

use App\Models\BiometricAccessEvent;
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

    /**
     * Access-control minor event codes that represent a SUCCESSFUL authentication,
     * mapped to the credential method used. A successful event marks attendance.
     * (Device/firmware dependent — extend as needed.)
     */
    private const SUCCESS_AUTH_MINORS = [
        75 => 'face',
        38 => 'card',
        113 => 'fingerprint',
    ];

    /**
     * Access-control minor event codes that represent a FAILED authentication
     * attempt, mapped to the credential method used. A failed event is logged as
     * "attempted" (no attendance). (Device/firmware dependent — extend as needed.)
     */
    private const FAILED_AUTH_MINORS = [
        76 => 'face',         // face authentication failed
        77 => 'face',         // face anti-spoofing / liveness failed
        39 => 'card',         // card authentication failed / no access right
        114 => 'fingerprint', // fingerprint authentication failed
        22 => 'password',     // password authentication failed
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
                'member_id' => $member->id,
                'biometric_member_id' => $member->biometric_member_id,
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
                ->where('member_id', $member->id)
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
    // Real-time webhook: configure device + handle incoming events
    // -------------------------------------------------------------------------

    /**
     * Configure the biometric device to push real-time access events to our webhook URL.
     *
     * Requires `biometric.webhook_server_host` and `biometric.webhook_token` to be set.
     * Returns ['success' => bool, 'message' => string].
     */
    public function configureWebhook(Tenant $tenant): array
    {
        $tenantId = $tenant->id;

        if (!$this->isEnabled($tenantId)) {
            return ['success' => false, 'message' => 'Biometric integration is disabled.'];
        }

        $allConfig = $this->config->all($tenantId);
        $driver = $this->buildDriver($allConfig);

        if (!$driver) {
            return ['success' => false, 'message' => 'No device configured.'];
        }

        $serverHost = $allConfig['biometric.webhook_server_host'] ?? '';
        $serverPort = (int) ($allConfig['biometric.webhook_server_port'] ?? 80);
        $token = $allConfig['biometric.webhook_token'] ?? '';

        if (!$serverHost) {
            return ['success' => false, 'message' => 'Webhook server host is not set. Enter the IP or hostname of this server reachable from the device.'];
        }

        if (!$token) {
            return ['success' => false, 'message' => 'No webhook token. Generate one first.'];
        }

        $path = '/api/biometric/events/' . $tenant->domain . '?token=' . $token;
        $maker = $allConfig['biometric.device_maker'] ?? '';
        $model = $allConfig['biometric.device_model'] ?? '';
        $result = $driver->configureHttpNotification($serverHost, $serverPort, $path);

        $this->writeLog([
            'tenant_id' => $tenantId,
            'member_id' => null,
            'biometric_member_id' => null,
            'direction' => 'up',
            'action' => 'webhook_configure',
            'status' => $result['success'] ? 'success' : 'failed',
            'device_maker' => $maker,
            'device_model' => $model,
            'payload' => ['server_host' => $serverHost, 'server_port' => $serverPort, 'path' => $path],
            'response' => $result['data'],
            'error_message' => $result['success'] ? null : $result['message'],
        ]);

        return $result;
    }

    /**
     * Read the current HTTP notification host config from the device.
     * Returns the raw result from the driver.
     */
    public function getWebhookConfig(Tenant $tenant): array
    {
        $allConfig = $this->config->all($tenant->id);
        $driver = $this->buildDriver($allConfig);

        if (!$driver) {
            return ['success' => false, 'message' => 'No device configured.', 'data' => []];
        }

        return $driver->getHttpNotificationConfig();
    }

    /**
     * Process a single real-time access event received via the webhook endpoint.
     *
     * Records the authentication event (success or failed) together with the
     * snapshot the device captured at that moment, then marks attendance when the
     * authentication succeeded.
     */
    public function handleIncomingEvent(Tenant $tenant, array $event): void
    {
        $employeeNo = $event['employeeNoString'] ?? null;
        $member = $this->resolveMemberByEmployeeNo($tenant, $employeeNo);

        // Core: record the authentication event + picture and mark attendance.
        $this->recordAccessEvent($tenant, $event, $member);

        // Best-effort raw debug trail on the sync-log table (never blocks the event).
        try {
            $allConfig = $this->config->all($tenant->id);

            $logEvent = $event;
            unset($logEvent['picture_bytes'], $logEvent['picture_content_type']);

            $this->writeLog([
                'tenant_id' => $tenant->id,
                'member_id' => $member?->id,
                'biometric_member_id' => $member?->biometric_member_id,
                'direction' => 'down',
                'action' => 'webhook_event',
                'status' => 'success',
                'device_maker' => $allConfig['biometric.device_maker'] ?? '',
                'device_model' => $allConfig['biometric.device_model'] ?? '',
                'payload' => null,
                'response' => $logEvent,
                'error_message' => null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Biometric webhook sync-log write failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Resolve a viewable URL for a stored access-event snapshot.
     */
    public function accessEventPictureUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        try {
            return $this->media->url($path);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Persist a real-time authentication event with its captured picture.
     *
     * - Successful authentications are stored with result `success` and also mark
     *   the member as present for the day (MemberAttendance).
     * - Failed authentications are stored with result `failed` (i.e. "attempted").
     * - Non-authentication device events (door, tamper, heartbeat) are ignored.
     *
     * Returns true when a new event row was created, false when the event was
     * ignored (non-auth) or already recorded (duplicate).
     */
    private function recordAccessEvent(Tenant $tenant, array $event, ?Member $member): bool
    {
        $minor = (int) ($event['minor'] ?? 0);
        $classification = $this->classifyAuthEvent($minor);

        if (!$classification) {
            return false;
        }

        $method = $classification['method'];
        $result = $classification['result'];

        $eventTime = !empty($event['time']) ? Carbon::parse((string) $event['time']) : now();
        $employeeNo = $this->normaliseEmployeeNo($event['employeeNoString'] ?? null);
        $raw = $event;
        unset($raw['picture_bytes'], $raw['picture_content_type']);

        // De-duplicate: the same scan may arrive via both the real-time webhook
        // and a later manual device import. Match on tenant + person + type + time.
        if (!empty($event['time'])) {
            $existingQuery = BiometricAccessEvent::where('tenant_id', $tenant->id)
                ->where('minor_code', $minor)
                ->where('event_time', $eventTime);

            $employeeNo
                ? $existingQuery->where('employee_no', $employeeNo)
                : $existingQuery->whereNull('employee_no');

            $existing = $existingQuery->first();

            if ($existing) {
                // Backfill missing identifiers on existing events so earlier rows
                // created without member resolution can be corrected later.
                $shouldSave = false;

                if (!$existing->member_id && $member) {
                    $existing->member_id = $member->id;
                    $shouldSave = true;
                }

                if (!$existing->biometric_member_id) {
                    $existing->biometric_member_id = $member?->biometric_member_id ?: $employeeNo;
                    $shouldSave = $existing->biometric_member_id !== null || $shouldSave;
                }

                if (!$existing->employee_no && $employeeNo) {
                    $existing->employee_no = $employeeNo;
                    $shouldSave = true;
                }

                if (!$existing->person_name) {
                    $resolvedName = $event['name'] ?? $member?->name;

                    if ($resolvedName) {
                        $existing->person_name = $resolvedName;
                        $shouldSave = true;
                    }
                }

                if ($existing->raw === null && !empty($raw)) {
                    $existing->raw = $raw;
                    $shouldSave = true;
                }

                if (!$existing->picture_path) {
                    $existingPicturePath = $this->storeEventPicture($tenant, $event);

                    if ($existingPicturePath) {
                        $existing->picture_path = $existingPicturePath;
                        $shouldSave = true;
                    }
                }

                if ($shouldSave) {
                    $existing->save();
                }

                if ($result === 'success' && $member) {
                    $this->markMemberAttendance($tenant, $member, $eventTime, $existing->id);
                }

                return false;
            }
        }

        $picturePath = $this->storeEventPicture($tenant, $event);

        $createdEvent = BiometricAccessEvent::create([
            'tenant_id' => $tenant->id,
            'member_id' => $member?->id,
            'biometric_member_id' => $member?->biometric_member_id ?: $employeeNo,
            'employee_no' => $employeeNo,
            'person_name' => $event['name'] ?? $member?->name,
            'auth_method' => $method,
            'result' => $result,
            'minor_code' => $minor,
            'picture_path' => $picturePath,
            'event_time' => $eventTime,
            'raw' => $raw,
        ]);

        if ($result === 'success' && $member) {
            $this->markMemberAttendance($tenant, $member, $eventTime, $createdEvent->id);
        }

        return true;
    }

    /**
     * Mark a member as attended on the event date while preventing duplicate rows.
     */
    private function markMemberAttendance(Tenant $tenant, Member $member, Carbon $eventTime, ?int $biometricAccessEventId = null): void
    {
        $attendedDate = $eventTime->toDateString();

        $attendance = MemberAttendance::where('tenant_id', $tenant->id)
            ->where('member_id', $member->id)
            ->where('attended_date', $attendedDate)
            ->first();

        if ($attendance) {
            $shouldSave = false;

            if (!$attendance->username) {
                $attendance->username = $member->username ?? $member->biometric_member_id;
                $shouldSave = true;
            }

            if (!$attendance->biometric_access_event_id && $biometricAccessEventId) {
                $attendance->biometric_access_event_id = $biometricAccessEventId;
                $shouldSave = true;
            }

            if ($shouldSave) {
                $attendance->save();
            }

            return;
        }

        MemberAttendance::create([
            'tenant_id' => $tenant->id,
            'member_id' => $member->id,
            'biometric_access_event_id' => $biometricAccessEventId,
            'legacy_uuid' => null,
            'legacy_member_id' => null,
            'username' => $member->username ?? $member->biometric_member_id,
            'attended_date' => $attendedDate,
        ]);
    }

    /**
     * Import the full access-event history currently held on the device and store
     * each authentication (success/failed) as a BiometricAccessEvent with its
     * captured snapshot. Successful events also mark attendance. Already-recorded
     * events are skipped. Intended to run inside a queued job.
     *
     * @return array{imported: int, skipped: int, errors: int, message: string}
     */
    public function importDeviceEvents(Tenant $tenant, ?string $syncFrom = null, ?string $syncTo = null): array
    {
        $tenantId = $tenant->id;
        $imported = 0;
        $skipped = 0;
        $errors = 0;

        try {
            if (!$this->isEnabled($tenantId)) {
                return ['imported' => 0, 'skipped' => 0, 'errors' => 0, 'message' => 'Biometric integration is disabled.'];
            }

            $allConfig = $this->config->all($tenantId);
            $driver = $this->buildDriver($allConfig);

            if (!$driver) {
                return ['imported' => 0, 'skipped' => 0, 'errors' => 0, 'message' => 'No device configured.'];
            }

            $maker = $allConfig['biometric.device_maker'] ?? '';
            $model = $allConfig['biometric.device_model'] ?? '';

            // Sync window defaults to [configured cursor, now]. If no cursor exists,
            // use a wide 1-year fallback to bootstrap initial imports.
            $configuredFrom = (string) ($allConfig['biometric.access_events_sync_from'] ?? '');
            $syncFromIso = $syncFrom ?: ($configuredFrom !== '' ? $configuredFrom : now()->subYear()->toIso8601String());
            $syncToIso = $syncTo ?: now()->toIso8601String();

            $syncFromAt = Carbon::parse($syncFromIso);
            $syncToAt = Carbon::parse($syncToIso);

            if ($syncFromAt->gt($syncToAt)) {
                return ['imported' => 0, 'skipped' => 0, 'errors' => 1, 'message' => 'Invalid sync window: sync_from is after sync_to.'];
            }

            // HikVision requires a timezone offset on AcsEvent search times.
            $startTime = $syncFromAt->format('Y-m-d\TH:i:sP');
            $endTime = $syncToAt->format('Y-m-d\TH:i:sP');

            $offset = 0;
            $maxResults = 50;
            $hasMore = true;
            $lastProcessedAuthAt = null;

            while ($hasMore) {
                $result = $driver->getAccessEvents($startTime, $endTime, $offset, $maxResults);

                if (!$result['success']) {
                    $errors++;
                    break;
                }

                $acsEvent = $result['data']['AcsEvent'] ?? [];
                $infoList = $acsEvent['InfoList'] ?? [];
                $morePages = ($acsEvent['responseStatusStrg'] ?? 'OK') === 'MORE';

                foreach ($infoList as $info) {
                    try {
                        $event = [
                            'employeeNoString' => $info['employeeNoString'] ?? null,
                            'time' => $info['time'] ?? null,
                            'minor' => (int) ($info['minor'] ?? 0),
                            'name' => $info['name'] ?? null,
                            'picture_url' => $info['pictureURL'] ?? null,
                        ];

                        // Track cursor by the last processed authentication event
                        // so partial imports can resume safely from progress.
                        if ($this->classifyAuthEvent((int) $event['minor']) !== null && !empty($event['time'])) {
                            try {
                                $eventAt = Carbon::parse((string) $event['time']);

                                if (!$lastProcessedAuthAt || $eventAt->gt($lastProcessedAuthAt)) {
                                    $lastProcessedAuthAt = $eventAt;
                                }
                            } catch (\Throwable) {
                                // Ignore invalid event timestamps for cursor tracking.
                            }
                        }

                        $member = $this->resolveMemberByEmployeeNo($tenant, $event['employeeNoString']);

                        $this->recordAccessEvent($tenant, $event, $member) ? $imported++ : $skipped++;
                    } catch (\Throwable $e) {
                        Log::warning('Biometric device event import error', ['event' => $info ?? null, 'error' => $e->getMessage()]);
                        $errors++;
                    }
                }

                $numMatches = $acsEvent['numOfMatches'] ?? count($infoList);
                $offset += $numMatches;
                $hasMore = $morePages && $numMatches > 0;
            }

            $this->writeLogSafely([
                'tenant_id' => $tenantId,
                'member_id' => null,
                'biometric_member_id' => null,
                'direction' => 'down',
                'action' => 'attendance',
                'status' => $errors === 0 ? 'success' : 'failed',
                'device_maker' => $maker,
                'device_model' => $model,
                'payload' => ['startTime' => $startTime, 'endTime' => $endTime],
                'response' => ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors],
                'error_message' => $errors > 0 ? "{$errors} event(s) failed to import." : null,
            ]);

            $cursorToPersist = $lastProcessedAuthAt;

            if (!$cursorToPersist && $errors === 0) {
                // No auth events were processed but the full window completed;
                // advancing to sync_to avoids re-scanning the same empty window.
                $cursorToPersist = $syncToAt;
            }

            if ($cursorToPersist) {
                $this->config->updateBatch($tenantId, [
                    'biometric.access_events_sync_from' => $cursorToPersist->format('Y-m-d\TH:i'),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('BiometricSyncService::importDeviceEvents error', ['tenant_id' => $tenantId, 'error' => $e->getMessage()]);
            $errors++;
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => "Imported {$imported} event(s), skipped {$skipped}, {$errors} error(s).",
        ];
    }

    /**
     * Classify an access-control minor event code into a credential method and a
     * success/failed result. Returns null for events that are not authentication
     * attempts (door control, tamper, heartbeat, etc.).
     *
     * @return array{method: string, result: string}|null
     */
    private function classifyAuthEvent(int $minor): ?array
    {
        if (isset(self::SUCCESS_AUTH_MINORS[$minor])) {
            return ['method' => self::SUCCESS_AUTH_MINORS[$minor], 'result' => 'success'];
        }

        if (isset(self::FAILED_AUTH_MINORS[$minor])) {
            return ['method' => self::FAILED_AUTH_MINORS[$minor], 'result' => 'failed'];
        }

        return null;
    }

    /**
     * Store the snapshot captured by the device at authentication time.
     *
     * Uses the inline image pushed in the multipart body when available, and
     * otherwise falls back to fetching the device's pictureURL with digest auth.
     * Returns the stored media path or null when no picture is available.
     */
    private function storeEventPicture(Tenant $tenant, array $event): ?string
    {
        try {
            $bytes = $event['picture_bytes'] ?? null;
            $contentType = $event['picture_content_type'] ?? 'image/jpeg';

            if (!$bytes && !empty($event['picture_url'])) {
                $driver = $this->buildDriver($this->config->all($tenant->id));

                if ($driver) {
                    $img = $driver->proxyImage($event['picture_url']);

                    if ($img['success'] && $img['body'] !== '') {
                        $bytes = $img['body'];
                        $contentType = $img['content_type'] ?: 'image/jpeg';
                    }
                }
            }

            if (!$bytes) {
                return null;
            }

            $ext = str_contains((string) $contentType, 'png') ? 'png' : 'jpg';
            $filename = 'biometric-events/' . now()->format('Y/m/d')
                . '/evt_' . now()->timestamp . '_' . Str::random(8) . '.' . $ext;

            return $this->media->storeContent($bytes, $filename);
        } catch (\Throwable $e) {
            Log::warning('Biometric event picture store failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Resolve a member from the device's employeeNo (the 4-digit suffix of the
     * biometric_member_id). Returns null when the number is empty or unmatched.
     */
    private function resolveMemberByEmployeeNo(Tenant $tenant, ?string $employeeNo): ?Member
    {
        $employeeNo = $this->normaliseEmployeeNo($employeeNo);

        if (!$employeeNo) {
            return null;
        }

        $candidates = [$employeeNo];

        if (ctype_digit($employeeNo)) {
            $unpadded = ltrim($employeeNo, '0');
            $unpadded = $unpadded === '' ? '0' : $unpadded;
            $padded4 = str_pad($unpadded, 4, '0', STR_PAD_LEFT);

            $candidates[] = $unpadded;
            $candidates[] = $padded4;
        }

        $candidates = array_values(array_unique(array_filter($candidates, static fn ($v) => $v !== '')));

        $query = Member::where('tenant_id', $tenant->id)
            ->whereNotNull('biometric_member_id')
            ->where(function ($q) use ($candidates, $employeeNo) {
                $q->whereIn('biometric_member_id', $candidates);

                if (ctype_digit($employeeNo)) {
                    $unpadded = ltrim($employeeNo, '0');
                    $unpadded = $unpadded === '' ? '0' : $unpadded;
                    $q->orWhere('biometric_member_id', 'like', '%-' . str_pad($unpadded, 4, '0', STR_PAD_LEFT));
                }
            });

        $matches = $query->get();

        if ($matches->isEmpty()) {
            return null;
        }

        foreach ($candidates as $candidate) {
            $exact = $matches->firstWhere('biometric_member_id', $candidate);

            if ($exact) {
                return $exact;
            }
        }

        return $matches->first();
    }

    private function normaliseEmployeeNo(?string $employeeNo): ?string
    {
        $employeeNo = trim((string) $employeeNo);

        return $employeeNo === '' ? null : $employeeNo;
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
            'member_id' => null,
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
     * Send a one-time unlock command to the configured biometric device.
     */
    public function unlockDoor(Tenant $tenant, int $doorNo = 1): array
    {
        return $this->runDoorControl($tenant, $doorNo, 'unlock', 'Door unlocked.', fn (HikvisionService $driver) => $driver->unlockDoor($doorNo));
    }

    /**
     * Set the door to always-open mode on the configured biometric device.
     */
    public function keepDoorUnlocked(Tenant $tenant, int $doorNo = 1): array
    {
        return $this->runDoorControl($tenant, $doorNo, 'keep_unlock', 'Door set to keep-unlocked mode.', fn (HikvisionService $driver) => $driver->keepDoorUnlocked($doorNo));
    }

    /**
     * Send a one-time close command to the configured biometric device.
     */
    public function closeDoor(Tenant $tenant, int $doorNo = 1): array
    {
        return $this->runDoorControl($tenant, $doorNo, 'close', 'Door closed.', fn (HikvisionService $driver) => $driver->closeDoor($doorNo));
    }

    /**
     * Set the door to always-closed mode on the configured biometric device.
     */
    public function keepDoorClosed(Tenant $tenant, int $doorNo = 1): array
    {
        return $this->runDoorControl($tenant, $doorNo, 'keep_close', 'Door set to keep-closed mode.', fn (HikvisionService $driver) => $driver->keepDoorClosed($doorNo));
    }

    /**
     * Read current door mode for UI decisions.
     * Returns state: keep_unlock | keep_close | unknown
     */
    public function getDoorStatus(Tenant $tenant, int $doorNo = 1): array
    {
        if (!$this->isEnabled($tenant->id)) {
            return ['success' => false, 'state' => 'unknown', 'message' => 'Biometric integration is disabled.'];
        }

        $allConfig = $this->config->all($tenant->id);
        $driver = $this->buildDriver($allConfig);

        if ($driver) {
            $device = $driver->getDoorStatus($doorNo);
            $deviceState = $device['data']['state'] ?? 'unknown';

            if ($device['success'] && in_array($deviceState, ['keep_unlock', 'keep_close'], true)) {
                return ['success' => true, 'state' => $deviceState, 'source' => 'device', 'message' => 'OK'];
            }
        }

        $latest = BiometricSyncLog::query()
            ->where('tenant_id', $tenant->id)
            ->where('direction', 'up')
            ->where('status', 'success')
            ->whereIn('action', ['keep_unlock', 'keep_close', 'unlock', 'close'])
            ->latest('created_at')
            ->first();

        if ($latest && $latest->action === 'keep_unlock') {
            return ['success' => true, 'state' => 'keep_unlock', 'source' => 'log', 'message' => 'OK'];
        }

        if ($latest && $latest->action === 'keep_close') {
            return ['success' => true, 'state' => 'keep_close', 'source' => 'log', 'message' => 'OK'];
        }

        if ($latest && in_array($latest->action, ['unlock', 'close'], true)) {
            return ['success' => true, 'state' => 'unknown', 'source' => 'log', 'message' => 'OK'];
        }

        return ['success' => true, 'state' => 'unknown', 'source' => 'fallback', 'message' => 'Unknown'];
    }

    /**
     * Shared door-control execution path with audit log writing.
     */
    private function runDoorControl(Tenant $tenant, int $doorNo, string $action, string $okMessage, callable $command): array
    {
        if (!$this->isEnabled($tenant->id)) {
            return ['success' => false, 'message' => 'Biometric integration is disabled.'];
        }

        $allConfig = $this->config->all($tenant->id);
        $driver = $this->buildDriver($allConfig);
        $maker = $allConfig['biometric.device_maker'] ?? '';
        $model = $allConfig['biometric.device_model'] ?? '';

        if (!$driver) {
            return ['success' => false, 'message' => 'No device configured.'];
        }

        $result = $command($driver);

        $this->writeLog([
            'tenant_id' => $tenant->id,
            'member_id' => null,
            'biometric_member_id' => null,
            'direction' => 'up',
            'action' => $action,
            'status' => $result['success'] ? 'success' : 'failed',
            'device_maker' => $maker,
            'device_model' => $model,
            'payload' => ['door_no' => $doorNo],
            'response' => $result['data'],
            'error_message' => $result['success'] ? null : $result['message'],
        ]);

        if ($result['success']) {
            $result['message'] = $okMessage;
        }

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

    /**
     * Write a sync log without ever throwing. The action-enum extensions are
     * MySQL-only migrations, so a sync-log write must never block core logic.
     */
    private function writeLogSafely(array $data): void
    {
        try {
            $this->writeLog($data);
        } catch (\Throwable $e) {
            Log::warning('Biometric sync-log write failed', ['error' => $e->getMessage()]);
        }
    }
}

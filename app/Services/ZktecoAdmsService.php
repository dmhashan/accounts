<?php

namespace App\Services;

use App\Models\BiometricDeviceCommand;
use App\Models\BiometricSyncLog;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * ZktecoAdmsService
 *
 * Handles ZKTeco ADMS (Automatic Data Master Server / Cloud Push Protocol).
 *
 * Operates as a reverse-push protocol driver:
 * - When server wants to push a user/command, it enqueues into `biometric_device_commands`.
 * - The device polls `/iclock/getrequest?SN={SN}` and executes pending commands.
 * - The device reports execution result via `/iclock/devicecmd?SN={SN}`.
 * - Real-time scans arrive at `/iclock/cdata?SN={SN}&table=ATTLOG`.
 * - Snapshots arrive at `/iclock/fdata?SN={SN}`.
 */
class ZktecoAdmsService
{
    public function __construct(
        private readonly TenantConfigurationService $config,
        private readonly MediaStorageService $media,
    ) {}

    /**
     * Build the standard ADMS handshake / config response text for GET /iclock/cdata.
     */
    public function buildConfigResponse(string $sn, array $query = []): string
    {
        $tenant = app('tenant');
        $tenantId = $tenant ? $tenant->id : 0;
        $allConfig = $tenantId ? $this->config->all($tenantId) : [];

        $delay = (int) ($allConfig['biometric.adms_delay'] ?? 10);

        if ($delay < 3) {
            $delay = 10;
        }

        $lines = [
            "GET OPTION FROM: {$sn}",
            'Stamp=9999',
            'OpStamp=0',
            'PhotoStamp=0',
            'ErrorDelay=60',
            "Delay={$delay}",
            'TransTimes=00:00;14:05',
            'TransInterval=1',
            "TransFlag=TransData AttLog\tOpLog\tAttPhoto\tEnrollFP\tEnrollUser\tFPImag\tFACE\tUserPic\tBioPhoto",
            'TimeZone=0',
            'Realtime=1',
            'Encrypt=0',
            'ServerVersion=3.1.1',
        ];

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Record device heartbeat and optionally update device metadata.
     */
    public function recordHeartbeat(string $sn, array $options = []): void
    {
        $tenant = app('tenant');

        if (!$tenant) {
            return;
        }

        $updates = [
            'biometric.last_heartbeat_at' => now()->toIso8601String(),
        ];

        if (!empty($options)) {
            $existingJson = $this->config->all($tenant->id)['biometric.device_info_json'] ?? '{}';
            $existing = json_decode($existingJson, true) ?: [];
            $merged = array_merge($existing, $options, [
                'sn' => $sn,
                'last_seen_at' => now()->toIso8601String(),
            ]);

            $updates['biometric.device_info_json'] = json_encode($merged);
        }

        $this->config->updateBatch($tenant->id, $updates);
    }

    /**
     * Check if a device is online based on heartbeat recency (<= 60s threshold).
     */
    public function isDeviceOnline(string $sn, int $thresholdSeconds = 60): bool
    {
        $tenant = app('tenant');

        if (!$tenant) {
            return false;
        }

        $allConfig = $this->config->all($tenant->id);
        $lastHeartbeat = $allConfig['biometric.last_heartbeat_at'] ?? '';

        if (!$lastHeartbeat) {
            return false;
        }

        try {
            $lastAt = Carbon::parse($lastHeartbeat);

            return $lastAt->diffInSeconds(now()) <= $thresholdSeconds;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get device status summary for settings and status endpoints.
     */
    public function getDeviceStatus(string $sn): array
    {
        $tenant = app('tenant');

        if (!$tenant) {
            return ['online' => false, 'message' => 'Tenant not active.'];
        }

        $allConfig = $this->config->all($tenant->id);
        $configuredSn = $allConfig['biometric.device_sn'] ?? '';
        $model = $allConfig['biometric.device_model'] ?? 'SenseFace 2a';
        $fpAlg = $allConfig['biometric.zk_fingerprint_alg'] ?? '13';
        $faceAlg = $allConfig['biometric.zk_face_alg'] ?? '4';
        $lastHeartbeat = $allConfig['biometric.last_heartbeat_at'] ?? null;
        $infoJson = $allConfig['biometric.device_info_json'] ?? '{}';
        $info = json_decode($infoJson, true) ?: [];

        $isOnline = $this->isDeviceOnline($sn ?: $configuredSn);

        return [
            'online' => $isOnline,
            'sn' => $configuredSn,
            'model' => $model,
            'algorithms' => [
                'fingerprint' => "ZKFinger VX{$fpAlg}.0",
                'face' => "ZKFace VX{$faceAlg}.0 (Visible Light)",
            ],
            'last_heartbeat_at' => $lastHeartbeat,
            'device_info' => $info,
            'pending_commands_count' => BiometricDeviceCommand::where('status', 'pending')->count(),
        ];
    }

    /**
     * Queue adding or updating a member on the ZKTeco device.
     */
    public function queueAddOrUpdateMember(Member $member, string $action = 'create', bool $accessControl = false, int $graceDays = 0): array
    {
        $tenant = app('tenant');

        if (!$tenant) {
            return ['success' => false, 'message' => 'Tenant context missing.'];
        }

        $allConfig = $this->config->all($tenant->id);
        $sn = $allConfig['biometric.device_sn'] ?? '';

        if (!$sn) {
            return ['success' => false, 'message' => 'Device Serial Number (SN) not configured in Biometric Settings.'];
        }

        $pin = $this->extractPin($member->biometric_member_id);

        if (!$pin) {
            return ['success' => false, 'message' => 'Member has no biometric ID assigned.'];
        }

        $name = mb_substr($member->name, 0, 24);
        $card = ''; // SenseFace 2a RFID card if available

        // Command 1: User Data
        // Format: DATA USER PIN={PIN}\tName={Name}\tPri=0\tPasswd=\tCard={Card}\tGrp=1\tTZ=1
        $cmdString = "DATA USER PIN={$pin}\tName={$name}\tPri=0\tPasswd=\tCard={$card}\tGrp=1\tTZ=1";

        $cmd = BiometricDeviceCommand::create([
            'device_sn' => $sn,
            'command_type' => 'DATA USER',
            'command_string' => $cmdString,
            'status' => 'pending',
            'member_id' => $member->id,
            'biometric_member_id' => $member->biometric_member_id,
            'action' => $action,
        ]);

        // Command 2: If member has profile photo, queue DATA USERPIC for Visible Light Face engine
        if ($member->profile_photo_path) {
            $this->queueUserPhoto($member, $sn, $pin);
        }

        return [
            'success' => true,
            'command_id' => $cmd->id,
            'message' => "Member {$member->name} queued for sync to ZKTeco device (SN: {$sn}).",
        ];
    }

    /**
     * Queue user photo push for Visible Light facial recognition (SenseFace 2a / ZKFace VX4.0).
     */
    public function queueUserPhoto(Member $member, string $sn, ?string $pin = null): ?BiometricDeviceCommand
    {
        if (!$member->profile_photo_path) {
            return null;
        }

        $pin = $pin ?: $this->extractPin($member->biometric_member_id);

        if (!$pin) {
            return null;
        }

        try {
            $content = $this->media->getContent($member->profile_photo_path);

            if (!$content) {
                return null;
            }

            $size = strlen($content);
            $base64 = base64_encode($content);

            // ZKTeco SenseFace visible light face photo command
            $cmdString = "DATA USERPIC PIN={$pin}\tSize={$size}\tContent={$base64}";

            return BiometricDeviceCommand::create([
                'device_sn' => $sn,
                'command_type' => 'DATA USERPIC',
                'command_string' => $cmdString,
                'status' => 'pending',
                'member_id' => $member->id,
                'biometric_member_id' => $member->biometric_member_id,
                'action' => 'photo_sync',
            ]);
        } catch (\Throwable $e) {
            Log::warning('ZktecoAdmsService::queueUserPhoto failed', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Queue deleting a member from the device.
     */
    public function queueDeleteMember(string $biometricMemberId): array
    {
        $tenant = app('tenant');

        if (!$tenant) {
            return ['success' => false, 'message' => 'Tenant context missing.'];
        }

        $allConfig = $this->config->all($tenant->id);
        $sn = $allConfig['biometric.device_sn'] ?? '';

        if (!$sn) {
            return ['success' => false, 'message' => 'Device Serial Number (SN) not configured.'];
        }

        $pin = $this->extractPin($biometricMemberId);

        if (!$pin) {
            return ['success' => false, 'message' => 'Invalid biometric ID.'];
        }

        $cmdString = "DATA DELETE USER PIN={$pin}";

        $cmd = BiometricDeviceCommand::create([
            'device_sn' => $sn,
            'command_type' => 'DATA DELETE USER',
            'command_string' => $cmdString,
            'status' => 'pending',
            'member_id' => null,
            'biometric_member_id' => $biometricMemberId,
            'action' => 'delete',
        ]);

        return [
            'success' => true,
            'command_id' => $cmd->id,
            'message' => "Delete command queued for PIN {$pin}.",
        ];
    }

    /**
     * Queue door unlock command.
     */
    public function queueDoorUnlock(int $doorNo = 1): array
    {
        $tenant = app('tenant');

        if (!$tenant) {
            return ['success' => false, 'message' => 'Tenant context missing.'];
        }

        $allConfig = $this->config->all($tenant->id);
        $sn = $allConfig['biometric.device_sn'] ?? '';

        if (!$sn) {
            return ['success' => false, 'message' => 'Device Serial Number (SN) not configured.'];
        }

        $cmdString = 'AC_UNBLOCK';

        $cmd = BiometricDeviceCommand::create([
            'device_sn' => $sn,
            'command_type' => 'AC_UNBLOCK',
            'command_string' => $cmdString,
            'status' => 'pending',
            'member_id' => null,
            'biometric_member_id' => null,
            'action' => 'unlock',
        ]);

        return [
            'success' => true,
            'command_id' => $cmd->id,
            'message' => 'Door unlock command queued.',
        ];
    }

    /**
     * Fetch pending commands for device and return formatted text for /iclock/getrequest.
     */
    public function getPendingCommandsString(string $sn): string
    {
        $commands = BiometricDeviceCommand::query()
            ->where('device_sn', $sn)
            ->where('status', 'pending')
            ->orderBy('id', 'asc')
            ->limit(30)
            ->get();

        if ($commands->isEmpty()) {
            return "OK\r\n";
        }

        $lines = [];

        foreach ($commands as $cmd) {
            $lines[] = "C:{$cmd->id}:{$cmd->command_string}";
            $cmd->update(['status' => 'sent']);
        }

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Handle execution result reported by device via POST /iclock/devicecmd.
     * Request body: ID={id}&Return={ret}&CMD={cmd}
     */
    public function handleDeviceCommandResult(string $sn, int $commandId, int $returnCode, ?string $cmdString = null): bool
    {
        $cmd = BiometricDeviceCommand::query()
            ->where('id', $commandId)
            ->where('device_sn', $sn)
            ->first();

        if (!$cmd) {
            Log::warning('ZktecoAdmsService::handleDeviceCommandResult: command not found', [
                'sn' => $sn,
                'command_id' => $commandId,
                'return_code' => $returnCode,
            ]);

            return false;
        }

        $isSuccess = $returnCode >= 0;
        $status = $isSuccess ? 'executed' : 'failed';

        $cmd->update([
            'status' => $status,
            'return_code' => $returnCode,
            'executed_at' => now(),
        ]);

        // If command was for a member, update member's biometric_last_synced_at
        if ($isSuccess && $cmd->member_id && $cmd->action !== 'delete') {
            $member = Member::find($cmd->member_id);

            if ($member) {
                $member->timestamps = false;
                $member->update(['biometric_last_synced_at' => now()]);
                $member->timestamps = true;
            }
        }

        // Audit log
        try {
            BiometricSyncLog::create([
                'member_id' => $cmd->member_id,
                'biometric_member_id' => $cmd->biometric_member_id,
                'direction' => 'up',
                'action' => $cmd->action ?: 'manual_sync',
                'status' => $isSuccess ? 'success' : 'failed',
                'device_maker' => 'zkteco',
                'device_model' => 'SenseFace 2a',
                'payload' => ['command_id' => $cmd->id, 'command' => $cmd->command_string],
                'response' => ['return_code' => $returnCode, 'cmd_string' => $cmdString],
                'error_message' => $isSuccess ? null : "ZKTeco command failed with return code {$returnCode}",
                'synced_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('ZktecoAdmsService: sync-log write failed', ['error' => $e->getMessage()]);
        }

        return true;
    }

    /**
     * Parse tab-delimited attendance log lines received from POST /iclock/cdata?table=ATTLOG.
     *
     * Standard ZKTeco ATTLOG line format:
     * {PIN}\t{YYYY-MM-DD HH:MM:SS}\t{Status}\t{VerifyType}\t{WorkCode}\t{Reserved1}\t{Reserved2}
     *
     * Verify types:
     * 15 = Face (ZKFace VX4.0 Visible Light)
     * 1  = Fingerprint (ZKFinger VX13.0)
     * 3  = Card / RFID
     * 2  = Password / PIN
     * 0  = Password / Other
     */
    public function parseAttendanceLogs(string $rawText): array
    {
        $events = [];
        $lines = preg_split('/\r\n|\r|\n/', trim($rawText));

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            // Tab-delimited split
            $parts = explode("\t", $line);

            if (count($parts) < 2) {
                // Try whitespace split fallback
                $parts = preg_split('/\s+/', $line, 6);
            }

            if (count($parts) < 2) {
                continue;
            }

            $pin = trim($parts[0] ?? '');
            $time = trim($parts[1] ?? '');
            $state = isset($parts[2]) ? (int) $parts[2] : 0;
            $verifyType = isset($parts[3]) ? (int) $parts[3] : 0;

            if ($pin === '' || $time === '') {
                continue;
            }

            $method = match ($verifyType) {
                15 => 'face',
                1 => 'fingerprint',
                3, 4 => 'card',
                2 => 'password',
                default => 'face', // Default to face for SenseFace terminals if unspecified
            };

            // Standard minor code mapping for internal compatibility
            $minorCode = match ($method) {
                'face' => 75,
                'fingerprint' => 113,
                'card' => 38,
                default => 75,
            };

            $events[] = [
                'employeeNoString' => $pin,
                'time' => $time,
                'minor' => $minorCode,
                'auth_method' => $method,
                'attendanceStatus' => $state === 0 ? 'check_in' : 'check_out',
                'raw' => [
                    'line' => $line,
                    'pin' => $pin,
                    'time' => $time,
                    'state' => $state,
                    'verify_type' => $verifyType,
                ],
            ];
        }

        return $events;
    }

    /**
     * Extract clean PIN from a biometric member ID (e.g. MEM-2026-0042 -> 0042 or 42).
     */
    public function extractPin(string $biometricMemberId): string
    {
        if (preg_match('/-([0-9]+)$/', $biometricMemberId, $m)) {
            return str_pad((int) $m[1], 4, '0', STR_PAD_LEFT);
        }

        $cleaned = preg_replace('/[^0-9]/', '', $biometricMemberId);

        return $cleaned !== '' ? $cleaned : $biometricMemberId;
    }
}

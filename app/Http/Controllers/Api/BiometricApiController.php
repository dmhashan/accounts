<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ImportBiometricAccessEventsJob;
use App\Models\BiometricAccessEvent;
use App\Models\BiometricSyncLog;
use App\Models\Member;
use App\Models\Tenant;
use App\Services\BiometricSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BiometricApiController extends Controller
{
    public function __construct(
        private readonly BiometricSyncService $biometric,
    ) {}

    /**
     * POST /api/settings/biometric/test-connection
     */
    public function testConnection(): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $result = $this->biometric->testConnection($tenant);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] ? 'Connected successfully.' : ($result['message'] ?? 'Connection failed.'),
        ], $result['success'] ? 200 : 422);
    }

    /**
     * POST /api/settings/biometric/sync-all
     */
    public function syncAllMembers(): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $result = $this->biometric->syncAllMembers($tenant);

        return response()->json([
            'message' => $result['message'],
            'data' => ['synced' => $result['synced'], 'failed' => $result['failed']],
        ]);
    }

    /**
     * POST /api/settings/biometric/unlock
     */
    public function unlockDoor(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $doorNo = max(1, (int) $request->input('door_no', 1));
        $result = $this->biometric->unlockDoor($tenant, $doorNo);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'] ?? ($result['success'] ? 'Door unlocked.' : 'Unlock failed.'),
        ], $result['success'] ? 200 : 422);
    }

    /**
     * POST /api/settings/biometric/keep-unlock
     */
    public function keepDoorUnlocked(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $doorNo = max(1, (int) $request->input('door_no', 1));
        $result = $this->biometric->keepDoorUnlocked($tenant, $doorNo);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'] ?? ($result['success'] ? 'Door set to keep-unlocked mode.' : 'Keep-unlock failed.'),
        ], $result['success'] ? 200 : 422);
    }

    /**
     * POST /api/settings/biometric/close
     */
    public function closeDoor(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $doorNo = max(1, (int) $request->input('door_no', 1));
        $result = $this->runDoorAction('closeDoor', $tenant, $doorNo);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'] ?? ($result['success'] ? 'Door closed.' : 'Close failed.'),
        ], $result['success'] ? 200 : 422);
    }

    /**
     * POST /api/settings/biometric/keep-close
     */
    public function keepDoorClosed(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $doorNo = max(1, (int) $request->input('door_no', 1));
        $result = $this->runDoorAction('keepDoorClosed', $tenant, $doorNo);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'] ?? ($result['success'] ? 'Door set to keep-closed mode.' : 'Keep-close failed.'),
        ], $result['success'] ? 200 : 422);
    }

    /**
     * GET /api/settings/biometric/door-status
     */
    public function doorStatus(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $doorNo = max(1, (int) $request->query('door_no', 1));
        $result = $this->runDoorStatusAction($tenant, $doorNo);

        return response()->json([
            'success' => $result['success'] ?? true,
            'state' => $result['state'] ?? 'unknown',
            'source' => $result['source'] ?? null,
            'message' => $result['message'] ?? 'OK',
        ]);
    }

    /**
     * POST /api/members/{member}/biometric-assign-id
     * Auto-generate and assign a biometric_member_id to a member that doesn't have one.
     */
    public function assignMemberId(Request $request, Member $member): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        if ($member->tenant_id !== $tenant->id) {
            abort(404);
        }

        if ($member->biometric_member_id) {
            return response()->json([
                'message' => 'Member already has a biometric ID.',
                'biometric_member_id' => $member->biometric_member_id,
            ]);
        }

        $id = Member::generateBiometricMemberId($tenant->id);

        if (!$id) {
            return response()->json([
                'message' => 'Device capacity reached. Maximum 9999 biometric members per tenant.',
            ], 422);
        }

        $member->timestamps = false;
        $member->update(['biometric_member_id' => $id]);
        $member->timestamps = true;

        return response()->json([
            'message' => 'Biometric ID assigned.',
            'biometric_member_id' => $id,
        ]);
    }

    /**
     * POST /api/members/{member}/biometric-sync
     */
    public function syncMember(Request $request, Member $member): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        if ($member->tenant_id !== $tenant->id) {
            abort(404);
        }

        $this->biometric->syncMember($member, 'manual_sync');

        $member->refresh();

        return response()->json([
            'message' => 'Member synced to biometric device.',
            'biometric_last_synced_at' => optional($member->biometric_last_synced_at)->toISOString(),
        ]);
    }

    /**
     * GET /api/members/{member}/biometric-logs
     */
    public function memberLogs(Request $request, Member $member): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        if ($member->tenant_id !== $tenant->id) {
            abort(404);
        }

        $logs = BiometricSyncLog::where('tenant_id', $tenant->id)
            ->where('member_id', $member->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'direction' => $log->direction,
                'action' => $log->action,
                'status' => $log->status,
                'device_maker' => $log->device_maker,
                'device_model' => $log->device_model,
                'error_message' => $log->error_message,
                'synced_at' => optional($log->synced_at)->toISOString(),
            ]);

        return response()->json(['data' => $logs]);
    }

    /**
     * POST /api/members/{member}/biometric-setup-fingerprint
     *
     * Triggers fingerprint enrolment on the device for the given member.
     */
    public function setupFingerprint(Request $request, Member $member): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        if ($member->tenant_id !== $tenant->id) {
            abort(404);
        }

        $result = $this->biometric->setupMemberFingerprint($member);

        return response()->json($result);
    }

    /**
     * GET /api/members/{member}/biometric-device-info
     *
     * Queries the biometric device directly for the current record of this member.
     */
    public function memberDeviceInfo(Request $request, Member $member): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        if ($member->tenant_id !== $tenant->id) {
            abort(404);
        }

        $result = $this->biometric->getMemberDeviceInfo($member);

        return response()->json($result);
    }

    /**
     * POST /api/members/{member}/biometric-upload-face-photo
     *
     * Pulls the enrolled face from the device and sets it as the member's profile photo.
     * Only proceeds when the member has no existing photo.
     */
    public function uploadFaceAsPhoto(Request $request, Member $member): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        if ($member->tenant_id !== $tenant->id) {
            abort(404);
        }

        $result = $this->biometric->uploadFaceAsAvatar($member);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 422);
        }

        return response()->json([
            'profile_photo_url' => $result['profile_photo_url'],
        ]);
    }

    /**
     * GET /api/members/{member}/biometric-face-image
     *
     * Proxies the enrolled face image from the device with digest auth.
     */
    public function faceImage(Request $request, Member $member): Response
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        if ($member->tenant_id !== $tenant->id) {
            abort(404);
        }

        $result = $this->biometric->getMemberFaceImage($member);

        if (!$result['success'] || $result['body'] === '') {
            abort(404);
        }

        return response($result['body'], 200, [
            'Content-Type' => $result['content_type'],
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    /**
     * GET /api/settings/biometric/recent-logs
     */
    public function recentLogs(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');
        $perPage = max(1, min(100, (int) $request->query('per_page', 20)));

        $query = BiometricSyncLog::where('tenant_id', $tenant->id)
            ->with('member:id,name,biometric_member_id')
            ->orderByDesc('created_at');

        $failedCount = (clone $query)->where('status', 'failed')->count();

        $paginated = $query->paginate($perPage);

        $data = collect($paginated->items())->map(fn ($log) => [
            'id' => $log->id,
            'member' => $log->member ? ['id' => $log->member->id, 'name' => $log->member->name, 'biometric_member_id' => $log->member->biometric_member_id] : null,
            'direction' => $log->direction,
            'action' => $log->action,
            'status' => $log->status,
            'device_maker' => $log->device_maker,
            'device_model' => $log->device_model,
            'error_message' => $log->error_message,
            'synced_at' => optional($log->synced_at)->toISOString(),
        ]);

        return response()->json([
            'data' => $data,
            'failed_count' => $failedCount,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * GET /api/settings/biometric/access-events
     *
     * Real-time authentication events captured by the device: each successful or
     * failed (attempted) authentication, with the picture taken at that moment.
     */
    public function accessEvents(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');
        $perPage = max(1, min(100, (int) $request->query('per_page', 20)));
        $resultFilter = (string) $request->query('result', '');

        $query = BiometricAccessEvent::where('tenant_id', $tenant->id)
            ->with('member:id,name,biometric_member_id')
            ->orderByDesc('event_time')
            ->orderByDesc('id');

        if (in_array($resultFilter, ['success', 'failed'], true)) {
            $query->where('result', $resultFilter);
        }

        $attemptedCount = BiometricAccessEvent::where('tenant_id', $tenant->id)
            ->where('result', 'failed')
            ->count();

        $paginated = $query->paginate($perPage);

        $data = collect($paginated->items())->map(fn ($event) => [
            'id' => $event->id,
            'member' => $event->member
                ? ['id' => $event->member->id, 'name' => $event->member->name, 'biometric_member_id' => $event->member->biometric_member_id]
                : null,
            'person_name' => $event->person_name,
            'employee_no' => $event->employee_no,
            'auth_method' => $event->auth_method,
            'result' => $event->result,
            'minor_code' => $event->minor_code,
            'picture_url' => $this->biometric->accessEventPictureUrl($event->picture_path),
            'event_time' => optional($event->event_time)->toISOString(),
        ]);

        return response()->json([
            'data' => $data,
            'attempted_count' => $attemptedCount,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * POST /api/settings/biometric/access-events/sync
     *
     * Queue a job that imports all access events currently held on the device
     * into the Recent Authentication Events log.
     */
    public function syncAccessEvents(): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        if (!$this->biometric->isEnabled($tenant->id)) {
            return response()->json(['message' => 'Biometric integration is disabled.'], 422);
        }

        ImportBiometricAccessEventsJob::dispatch($tenant->id);

        return response()->json([
            'message' => 'Importing events from the device. They will appear here shortly.',
        ]);
    }

    // -------------------------------------------------------------------------
    // Real-time webhook management
    // -------------------------------------------------------------------------

    /**
     * POST /api/settings/biometric/webhook/generate-token
     *
     * Generate a fresh webhook token and save it to the tenant configuration.
     * Any previously configured webhook on the device will need to be re-applied.
     */
    public function generateWebhookToken(): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $token = \Illuminate\Support\Str::random(48);

        app(\App\Services\TenantConfigurationService::class)
            ->updateBatch($tenant->id, ['biometric.webhook_token' => $token]);

        return response()->json([
            'message' => 'New webhook token generated.',
            'token' => $token,
        ]);
    }

    /**
     * POST /api/settings/biometric/webhook/configure
     *
     * Push the HTTP notification configuration to the device so it sends
     * real-time events to our webhook URL.
     */
    public function configureWebhook(): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $result = $this->biometric->configureWebhook($tenant);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success']
                ? 'Device configured for real-time event push.'
                : ($result['message'] ?? 'Configuration failed.'),
        ], $result['success'] ? 200 : 422);
    }

    /**
     * GET /api/settings/biometric/webhook/status
     *
     * Read the current HTTP notification config from the device.
     */
    public function webhookStatus(): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $result = $this->biometric->getWebhookConfig($tenant);

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'Failed to read device config.'], 422);
        }

        $host = $result['data']['HttpHostNotification'] ?? [];

        return response()->json([
            'success' => true,
            'config' => [
                'ip' => $host['ipAddress'] ?? null,
                'port' => $host['portNo'] ?? null,
                'path' => $host['url'] ?? null,
                'protocol' => $host['protocolType'] ?? null,
            ],
        ]);
    }

    /**
     * Execute a biometric door-control action by method name.
     *
     * @return array{success: bool, message?: string}
     */
    private function runDoorAction(string $method, Tenant $tenant, int $doorNo): array
    {
        if (!method_exists($this->biometric, $method)) {
            return ['success' => false, 'message' => 'Door action is not supported.'];
        }

        /** @var array{success: bool, message?: string} $result */
        $result = $this->biometric->{$method}($tenant, $doorNo);

        return $result;
    }

    /**
     * Execute door-status action with safe method resolution.
     *
     * @return array{success?: bool, state?: string, source?: string|null, message?: string}
     */
    private function runDoorStatusAction(Tenant $tenant, int $doorNo): array
    {
        if (!method_exists($this->biometric, 'getDoorStatus')) {
            return ['success' => false, 'state' => 'unknown', 'message' => 'Door status is not supported.'];
        }

        /** @var array{success?: bool, state?: string, source?: string|null, message?: string} $result */
        $result = $this->biometric->{'getDoorStatus'}($tenant, $doorNo);

        return $result;
    }
}

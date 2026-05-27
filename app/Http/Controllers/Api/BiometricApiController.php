<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BiometricSyncLog;
use App\Models\Member;
use App\Models\Tenant;
use App\Services\BiometricSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     * POST /api/settings/biometric/sync-attendance
     */
    public function syncAttendance(): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $result = $this->biometric->pullAttendance($tenant);

        return response()->json([
            'message' => $result['message'],
            'data' => ['created' => $result['created'], 'errors' => $result['errors']],
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
            ->where('biometric_member_id', $member->id)
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
}

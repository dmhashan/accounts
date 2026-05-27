<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BulkNotification;
use App\Models\Member;
use App\Models\Tenant;
use App\Services\BulkNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    public function __construct(private readonly BulkNotificationService $service) {}

    public function index(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');
        $perPage = min((int) $request->integer('per_page', 15), 50);
        $search = trim((string) $request->query('search', ''));

        return response()->json($this->service->index($tenant->id, $perPage, $search));
    }

    public function show(BulkNotification $bulkNotification): JsonResponse
    {
        $this->authorizeNotification($bulkNotification);

        return response()->json($this->service->show($bulkNotification));
    }

    public function members(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');
        $search = trim((string) $request->query('search', ''));

        $members = Member::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_temp', false)
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('biometric_member_id', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get(['id', 'biometric_member_id', 'name', 'phone_number', 'is_active']);

        return response()->json([
            'data' => $members->map(fn (Member $m) => [
                'id' => $m->id,
                'member_id' => $m->biometric_member_id,
                'name' => $m->name,
                'phone_number' => $m->phone_number,
                'is_active' => (bool) $m->is_active,
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:621'],
            'member_ids' => ['required', 'array', 'min:1'],
            'member_ids.*' => ['integer', 'exists:members,id'],
        ]);

        $notification = $this->service->store($tenant, $request->user(), $validated);

        return response()->json($this->service->show($notification), 201);
    }

    public function update(Request $request, BulkNotification $bulkNotification): JsonResponse
    {
        $this->authorizeNotification($bulkNotification);

        if ($bulkNotification->isSent()) {
            return response()->json(['message' => 'Sent notifications cannot be modified.'], 422);
        }

        /** @var Tenant $tenant */
        $tenant = app('tenant');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:621'],
            'member_ids' => ['required', 'array', 'min:1'],
            'member_ids.*' => ['integer', 'exists:members,id'],
        ]);

        $notification = $this->service->update($bulkNotification, $tenant, $validated);

        return response()->json($this->service->show($notification));
    }

    public function destroy(BulkNotification $bulkNotification): JsonResponse
    {
        $this->authorizeNotification($bulkNotification);

        if ($bulkNotification->isSent()) {
            return response()->json(['message' => 'Sent notifications cannot be deleted.'], 422);
        }

        $this->service->destroy($bulkNotification);

        return response()->json(null, 204);
    }

    public function send(BulkNotification $bulkNotification): JsonResponse
    {
        $this->authorizeNotification($bulkNotification);

        if ($bulkNotification->isSent()) {
            return response()->json(['message' => 'This notification has already been sent.'], 422);
        }

        if ($bulkNotification->recipients()->count() === 0) {
            return response()->json(['message' => 'No recipients selected.'], 422);
        }

        $result = $this->service->send($bulkNotification);

        return response()->json([
            'message' => 'Notification queued for delivery.',
            'recipient_count' => $result['recipient_count'],
        ]);
    }

    private function authorizeNotification(BulkNotification $notification): void
    {
        /** @var Tenant $tenant */
        $tenant = app('tenant');

        abort_if($notification->tenant_id !== $tenant->id, 403);
    }
}

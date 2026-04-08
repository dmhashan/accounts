<?php

namespace App\Services;

use App\Models\BulkNotification;
use App\Models\BulkNotificationRecipient;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BulkNotificationService
{
    public function __construct(private readonly SmsService $smsService)
    {
    }

    public function index(int $tenantId, int $perPage, string $search): array
    {
        $notifications = BulkNotification::query()
            ->where('tenant_id', $tenantId)
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->withCount('recipients')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'data' => collect($notifications->items())->map(fn (BulkNotification $n) => $this->summarize($n)),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'per_page'     => $notifications->perPage(),
                'total'        => $notifications->total(),
            ],
        ];
    }

    public function show(BulkNotification $notification): array
    {
        $notification->load(['recipients.member', 'creator']);

        return [
            'id'               => $notification->id,
            'name'             => $notification->name,
            'message'          => $notification->message,
            'status'           => $notification->status,
            'sent_at'          => $notification->sent_at?->toDateTimeString(),
            'created_at'       => $notification->created_at->toDateTimeString(),
            'created_by_name'  => $notification->creator?->name,
            'recipients'       => $notification->recipients->map(fn (BulkNotificationRecipient $r) => [
                'id'           => $r->id,
                'member_id'    => $r->member_id,
                'member_name'  => $r->member?->name,
                'phone_number' => $r->phone_number,
            ])->values()->all(),
        ];
    }

    public function store(Tenant $tenant, User $user, array $data): BulkNotification
    {
        return DB::transaction(function () use ($tenant, $user, $data) {
            /** @var BulkNotification $notification */
            $notification = BulkNotification::create([
                'tenant_id'  => $tenant->id,
                'created_by' => $user->id,
                'name'       => $data['name'],
                'message'    => $data['message'],
                'status'     => 'draft',
            ]);

            $this->syncRecipients($notification, $tenant->id, $data['member_ids'] ?? []);

            return $notification;
        });
    }

    public function update(BulkNotification $notification, Tenant $tenant, array $data): BulkNotification
    {
        DB::transaction(function () use ($notification, $tenant, $data) {
            $notification->update([
                'name'    => $data['name'],
                'message' => $data['message'],
            ]);

            $this->syncRecipients($notification, $tenant->id, $data['member_ids'] ?? []);
        });

        return $notification;
    }

    public function destroy(BulkNotification $notification): void
    {
        $notification->delete();
    }

    /**
     * Finalize and send the notification. Returns ['success', 'failed', 'campaign_id'].
     */
    public function send(BulkNotification $notification): array
    {
        $contacts = $notification->recipients()->pluck('phone_number')->values()->all();

        $result = $this->smsService->sendBulk($contacts, $notification->message);

        if ($result['success']) {
            $notification->update([
                'status'  => 'sent',
                'sent_at' => now(),
            ]);
        }

        return [
            'success'          => $result['success'],
            'campaign_id'      => $result['campaign_id'],
            'recipient_count'  => count($contacts),
        ];
    }

    // -------------------------------------------------------------------------

    private function syncRecipients(BulkNotification $notification, int $tenantId, array $memberIds): void
    {
        $notification->recipients()->delete();

        if (empty($memberIds)) {
            return;
        }

        $members = Member::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $memberIds)
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '')
            ->get(['id', 'phone_number']);

        $rows = $members->map(fn (Member $m) => [
            'bulk_notification_id' => $notification->id,
            'member_id'            => $m->id,
            'phone_number'         => $m->phone_number,
            'created_at'           => now(),
            'updated_at'           => now(),
        ])->all();

        if ($rows !== []) {
            BulkNotificationRecipient::insert($rows);
        }
    }

    private function summarize(BulkNotification $notification): array
    {
        return [
            'id'               => $notification->id,
            'name'             => $notification->name,
            'message'          => $notification->message,
            'status'           => $notification->status,
            'sent_at'          => $notification->sent_at?->toDateTimeString(),
            'created_at'       => $notification->created_at->toDateTimeString(),
            'recipients_count' => $notification->recipients_count ?? 0,
        ];
    }
}

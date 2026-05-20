<?php

namespace App\Jobs;

use App\Mail\MemberNotificationMail;
use App\Models\BulkNotification;
use App\Models\MemberNotification;
use App\Services\SmsService;
use App\Services\TenantConfigurationService;
use App\Services\TenantMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBulkNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    /**
     * @param  array<string>  $channels  Any subset of: 'sms', 'email', 'in_app'
     */
    public function __construct(
        private readonly int $bulkNotificationId,
        private readonly array $channels = ['sms', 'email', 'in_app'],
    ) {}

    public function handle(SmsService $smsService, TenantMailService $tenantMail, TenantConfigurationService $tenantConfig): void
    {
        $notification = BulkNotification::with('recipients.member')
            ->find($this->bulkNotificationId);

        if (!$notification) {
            Log::warning('SendBulkNotificationJob: BulkNotification not found.', [
                'id' => $this->bulkNotificationId,
            ]);

            return;
        }

        $channels = $tenantConfig->enabledChannels($notification->tenant_id, $this->channels);

        // SMS — single bulk API call for all recipients
        if (in_array('sms', $channels, true)) {
            $contacts = $notification->recipients()->pluck('phone_number')->values()->all();

            if (!empty($contacts)) {
                $smsService->sendBulk($contacts, $notification->message, $notification->tenant_id);
            }
        }

        // Email and in-app per member
        $inAppInserts = [];
        $now = now();

        foreach ($notification->recipients as $recipient) {
            $member = $recipient->member;

            if (!$member) {
                continue;
            }

            if (in_array('email', $channels, true) && $member->email) {
                try {
                    $tenantMail->mailerForTenant($notification->tenant_id)
                        ->to($member->email)
                        ->send(new MemberNotificationMail($notification->name, $notification->message));
                } catch (\Throwable $e) {
                    Log::error('SendBulkNotificationJob: Email send failed.', [
                        'member_id' => $member->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (in_array('in_app', $channels, true)) {
                $inAppInserts[] = [
                    'tenant_id' => $notification->tenant_id,
                    'member_id' => $member->id,
                    'type' => 'bulk',
                    'title' => $notification->name,
                    'body' => $notification->message,
                    'is_read' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($inAppInserts)) {
            MemberNotification::insert($inAppInserts);
        }

        $notification->update([
            'status' => 'sent',
            'sent_at' => $now,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendBulkNotificationJob: Job failed permanently.', [
            'bulk_notification_id' => $this->bulkNotificationId,
            'error' => $e->getMessage(),
        ]);

        BulkNotification::where('id', $this->bulkNotificationId)
            ->update(['status' => 'failed']);
    }
}

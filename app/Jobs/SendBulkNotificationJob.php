<?php

namespace App\Jobs;

use App\Mail\MemberNotificationMail;
use App\Models\BulkNotification;
use App\Models\Member;
use App\Models\MemberNotification;
use App\Services\SmsService;
use App\Services\TenantConfigurationService;
use App\Services\TenantMailService;
use App\Support\TenantEmailBranding;
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
        $notification = BulkNotification::find($this->bulkNotificationId);

        if (!$notification) {
            Log::warning('SendBulkNotificationJob: BulkNotification not found.', [
                'id' => $this->bulkNotificationId,
            ]);

            return;
        }

        $tenantId = (int) app('tenant')->id;
        $channels = $tenantConfig->enabledChannels($tenantId, $this->channels);

        if (in_array('sms', $channels, true)) {
            $this->sendSmsInChunks($notification, $smsService, $tenantId);
        }

        $now = now();
        $tenantBranding = TenantEmailBranding::forTenantId($tenantId);
        $sendEmail = in_array('email', $channels, true);
        $sendInApp = in_array('in_app', $channels, true);

        if ($sendEmail || $sendInApp) {
            $notification->recipients()
                ->with('member:id,first_name,last_name,name,email,profile_photo_path')
                ->orderBy('id')
                ->chunkById(200, function ($recipients) use ($notification, $tenantId, $tenantMail, $tenantBranding, $sendEmail, $sendInApp, $now): void {
                    $inAppInserts = [];

                    foreach ($recipients as $recipient) {
                        $member = $recipient->member;

                        if (!$member) {
                            continue;
                        }

                        if ($sendEmail && $member->email) {
                            $this->sendEmail($member, $notification, $tenantMail, $tenantId, $tenantBranding);
                        }

                        if ($sendInApp) {
                            $inAppInserts[] = [
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

                    if ($inAppInserts !== []) {
                        MemberNotification::insert($inAppInserts);
                    }
                }, 'id');
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

    private function memberName(Member $member): string
    {
        return trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? ''))
            ?: ($member->name ?? 'Member');
    }

    private function sendSmsInChunks(BulkNotification $notification, SmsService $smsService, int $tenantId): void
    {
        $notification->recipients()
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '')
            ->orderBy('id')
            ->chunkById(500, function ($recipients) use ($notification, $smsService, $tenantId): void {
                $contacts = $recipients->pluck('phone_number')->filter()->values()->all();

                if ($contacts !== []) {
                    $smsService->sendBulk($contacts, $notification->message, $tenantId);
                }
            }, 'id');
    }

    /**
     * @param  array<string, string|null>  $tenantBranding
     */
    private function sendEmail(
        Member $member,
        BulkNotification $notification,
        TenantMailService $tenantMail,
        int $tenantId,
        array $tenantBranding,
    ): void {
        try {
            $memberName = $this->memberName($member);

            $tenantMail->mailerForTenant($tenantId)
                ->to($member->email)
                ->send(new MemberNotificationMail(
                    $notification->name,
                    $notification->message,
                    $tenantBranding,
                    $memberName,
                    TenantEmailBranding::memberAvatarUrl($member),
                    TenantEmailBranding::initials($memberName),
                ));
        } catch (\Throwable $e) {
            Log::error('SendBulkNotificationJob: Email send failed.', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

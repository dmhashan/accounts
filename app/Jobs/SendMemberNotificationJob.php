<?php

namespace App\Jobs;

use App\Mail\MemberNotificationMail;
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

class SendMemberNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param  array<string>  $channels  Any subset of: 'sms', 'email', 'in_app'
     */
    public function __construct(
        private readonly int $tenantId,
        private readonly int $memberId,
        private readonly string $type,
        private readonly string $title,
        private readonly string $body,
        private readonly array $channels = ['sms', 'email', 'in_app'],
    ) {}

    public function handle(SmsService $smsService, TenantMailService $tenantMail, TenantConfigurationService $tenantConfig): void
    {
        $member = Member::query()->find($this->memberId);

        if (!$member) {
            Log::warning('SendMemberNotificationJob: Member not found.', [
                'member_id' => $this->memberId,
            ]);

            return;
        }

        $channels = $tenantConfig->enabledChannels($this->tenantId, $this->channels);

        foreach ($channels as $channel) {
            match ($channel) {
                'sms' => $this->sendSms($member, $smsService, $tenantConfig),
                'email' => $this->sendEmail($member, $tenantMail),
                'in_app' => $this->sendInApp($member),
                default => null,
            };
        }
    }

    private function sendSms(Member $member, SmsService $smsService, TenantConfigurationService $tenantConfig): void
    {
        $phone = $member->phone_number;

        if (!$phone) {
            return;
        }

        $cfg = $tenantConfig->all($this->tenantId);
        $whatsappEnabled = ($cfg['notifications.whatsapp.enabled'] ?? '0') === '1';
        $smsEnabled = ($cfg['notifications.sms.enabled'] ?? '0') === '1';

        $allowWhatsapp = $whatsappEnabled && (bool) $member->allow_whatsapp;
        $allowSms = $smsEnabled && (bool) $member->allow_sms;
        $whatsappNumber = $member->whatsapp_number ?: $phone;

        Log::debug('SendMemberNotificationJob: Evaluating channels for recipient.', [
            'member_id' => $member->id,
            'whatsappEnabled' => $whatsappEnabled,
            'smsEnabled' => $smsEnabled,
            'allowWhatsapp' => (bool) $member->allow_whatsapp,
            'allowSms' => (bool) $member->allow_sms,
        ]);

        if ($allowWhatsapp) {
            Log::debug('SendMemberNotificationJob: Attempting WhatsApp message.', [
                'member_id' => $member->id,
                'whatsapp_number' => $whatsappNumber,
            ]);
            $success = $smsService->sendWhatsappOnly($whatsappNumber, $this->body, $this->tenantId);

            if ($success) {
                Log::info('SendMemberNotificationJob: WhatsApp message sent successfully.', [
                    'member_id' => $member->id,
                ]);

                return;
            }

            Log::warning('SendMemberNotificationJob: WhatsApp message failed; falling back to SMS if allowed.', [
                'member_id' => $member->id,
            ]);
        }

        if ($allowSms) {
            Log::debug('SendMemberNotificationJob: Attempting fallback/direct SMS message.', [
                'member_id' => $member->id,
                'phone_number' => $phone,
            ]);
            $smsService->sendSmsOnly($phone, $this->body, $this->tenantId);
        }
    }

    private function sendEmail(Member $member, TenantMailService $tenantMail): void
    {
        if (!$member->email) {
            return;
        }

        try {
            $tenantMail->mailerForTenant($this->tenantId)
                ->to($member->email)
                ->send(new MemberNotificationMail(
                    $this->title,
                    $this->body,
                    TenantEmailBranding::forTenantId($this->tenantId),
                    $this->memberName($member),
                    TenantEmailBranding::memberAvatarUrl($member),
                    TenantEmailBranding::initials($this->memberName($member)),
                ));
        } catch (\Throwable $e) {
            Log::error('SendMemberNotificationJob: Email send failed.', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendInApp(Member $member): void
    {
        MemberNotification::create([
            'member_id' => $member->id,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
        ]);
    }

    private function memberName(Member $member): string
    {
        return trim((string) ($member->name ?? '')) ?: 'Member';
    }
}

<?php

namespace App\Jobs;

use App\Mail\ManagerEventNotificationMail;
use App\Models\ManagerNotificationDelivery;
use App\Models\ManagerNotificationRecipient;
use App\Models\ManagerNotificationSetting;
use App\Models\Tenant;
use App\Services\ManagerNotificationCatalog;
use App\Services\SmsService;
use App\Services\Tenancy\TenantDatabaseManager;
use App\Services\TenantMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendManagerNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120];

    public function __construct(
        private readonly int $tenantId,
        private readonly string $eventUuid,
        public readonly string $eventKey,
        private readonly string $summary,
        private readonly array $details,
        private readonly ?string $actorName,
    ) {}

    public function handle(TenantDatabaseManager $tenancy, TenantMailService $tenantMail, SmsService $sms): void
    {
        $tenant = $tenancy->activateById($this->tenantId) ?? Tenant::query()->find($this->tenantId);

        if (!$tenant) {
            Log::warning('Manager notification tenant not found.', ['tenant_id' => $this->tenantId]);

            return;
        }

        app()->instance('tenant', $tenant);
        $settings = ManagerNotificationSetting::query()->where('tenant_id', $this->tenantId)->first();

        if (!$settings || !isset(ManagerNotificationCatalog::EVENTS[$this->eventKey])) {
            return;
        }

        $eventLabel = ManagerNotificationCatalog::EVENTS[$this->eventKey];
        $this->deliverChannel('email', (bool) $settings->email_enabled, $settings->email_events ?? [], $eventLabel, $tenant, $tenantMail, $sms);
        $this->deliverChannel('sms', (bool) $settings->sms_enabled, $settings->sms_events ?? [], $eventLabel, $tenant, $tenantMail, $sms);
    }

    private function deliverChannel(string $channel, bool $enabled, array $selectedEvents, string $eventLabel, Tenant $tenant, TenantMailService $tenantMail, SmsService $sms): void
    {
        if (!$enabled || !in_array($this->eventKey, $selectedEvents, true)) {
            return;
        }

        $recipients = ManagerNotificationRecipient::query()
            ->where('tenant_id', $this->tenantId)
            ->where('channel', $channel)
            ->orderBy('id')
            ->get();

        foreach ($recipients as $recipient) {
            $destinationHash = hash('sha256', $channel . "\0" . $recipient->normalized_destination);
            $delivery = ManagerNotificationDelivery::query()->firstOrCreate(
                [
                    'tenant_id' => $this->tenantId,
                    'event_uuid' => $this->eventUuid,
                    'channel' => $channel,
                    'destination_hash' => $destinationHash,
                ],
                [
                    'event_key' => $this->eventKey,
                    'destination' => $recipient->destination,
                    'summary' => $this->summary,
                    'status' => 'pending',
                ],
            );

            if ($delivery->status === 'sent') {
                continue;
            }

            $delivery->increment('attempts');
            $delivery->forceFill(['status' => 'pending', 'error_message' => null])->save();

            try {
                if ($channel === 'email') {
                    $tenantMail->mailerForTenant($this->tenantId)
                        ->to($recipient->destination, $recipient->label)
                        ->send(new ManagerEventNotificationMail(
                            $tenant->name ?: 'Your organisation',
                            $eventLabel,
                            $this->summary,
                            $this->safeDetails(),
                            $this->actorName,
                        ));
                } else {
                    $message = $this->smsMessage($tenant->name ?: 'Gym', $eventLabel);

                    if (!$sms->send($recipient->destination, $message, $this->tenantId)) {
                        throw new \RuntimeException('SMS provider did not confirm delivery.');
                    }
                }

                $delivery->forceFill(['status' => 'sent', 'sent_at' => now()])->save();
            } catch (\Throwable $exception) {
                $delivery->forceFill([
                    'status' => 'failed',
                    'error_message' => mb_substr($exception->getMessage(), 0, 1000),
                ])->save();

                Log::warning('Manager notification delivery failed.', [
                    'tenant_id' => $this->tenantId,
                    'event_key' => $this->eventKey,
                    'channel' => $channel,
                    'delivery_id' => $delivery->id,
                    'error' => $exception->getMessage(),
                ]);

                throw $exception;
            }
        }

        $this->pruneDeliveryHistory();
    }

    private function safeDetails(): array
    {
        $safe = [];

        foreach ($this->details as $label => $value) {
            if (!is_scalar($value) && $value !== null) {
                continue;
            }

            $safe[(string) $label] = mb_substr(trim(strip_tags((string) $value)), 0, 180);
        }

        return $safe;
    }

    private function smsMessage(string $tenantName, string $eventLabel): string
    {
        $message = trim(strip_tags($tenantName)) . ': ' . $eventLabel . '. ' . trim(strip_tags($this->summary));

        foreach ($this->safeDetails() as $label => $value) {
            $message .= ' ' . $label . ': ' . $value . '.';
        }

        return mb_substr($message, 0, 480);
    }

    private function pruneDeliveryHistory(): void
    {
        $keepIds = ManagerNotificationDelivery::query()
            ->where('tenant_id', $this->tenantId)
            ->orderByDesc('id')
            ->limit(500)
            ->pluck('id');

        if ($keepIds->isNotEmpty()) {
            ManagerNotificationDelivery::query()
                ->where('tenant_id', $this->tenantId)
                ->whereNotIn('id', $keepIds)
                ->delete();
        }
    }
}

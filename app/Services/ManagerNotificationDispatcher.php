<?php

namespace App\Services;

use App\Jobs\SendManagerNotificationJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ManagerNotificationDispatcher
{
    public function dispatch(int $tenantId, string $eventKey, string $summary, array $details = []): void
    {
        if (!isset(ManagerNotificationCatalog::EVENTS[$eventKey])) {
            return;
        }

        try {
            SendManagerNotificationJob::dispatch(
                $tenantId,
                (string) Str::uuid(),
                $eventKey,
                $summary,
                $details,
                Auth::user()?->name,
            )->afterCommit();
        } catch (\Throwable $exception) {
            Log::warning('Manager notification could not be queued.', [
                'tenant_id' => $tenantId,
                'event_key' => $eventKey,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

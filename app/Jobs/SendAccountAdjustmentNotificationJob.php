<?php

namespace App\Jobs;

use App\Mail\AccountAdjustmentNotificationMail;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantMailService;
use App\Support\SidebarPermissionCatalog;
use App\Support\TenantEmailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAccountAdjustmentNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $tenantId,
        public readonly string $action,
        public readonly array $adjustmentDetails,
    ) {}

    public function handle(TenantMailService $tenantMail): void
    {
        $tenant = Tenant::find($this->tenantId);

        if (!$tenant) {
            Log::warning('SendAccountAdjustmentNotificationJob: Tenant not found.', [
                'tenant_id' => $this->tenantId,
            ]);

            return;
        }

        $branding = TenantEmailBranding::forTenant($tenant);

        $admins = User::whereHas('role', function ($query) {
            $query->whereIn('slug', SidebarPermissionCatalog::adminRoleSlugs());
        })->get();

        if ($admins->isEmpty()) {
            Log::info('SendAccountAdjustmentNotificationJob: No admin recipients found.', [
                'tenant_id' => $this->tenantId,
            ]);

            return;
        }

        $mailer = $tenantMail->mailerForTenant($this->tenantId);

        foreach ($admins as $admin) {
            try {
                $mailer->to($admin->email, $admin->name)
                    ->send(new AccountAdjustmentNotificationMail(
                        $this->action,
                        $this->adjustmentDetails,
                        $branding,
                        $admin->name,
                    ));
            } catch (\Throwable $e) {
                Log::error('SendAccountAdjustmentNotificationJob: Email send failed.', [
                    'admin_email' => $admin->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

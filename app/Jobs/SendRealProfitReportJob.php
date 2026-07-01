<?php

namespace App\Jobs;

use App\Mail\RealProfitReportMail;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RealProfitReportService;
use App\Services\Tenancy\TenantDatabaseManager;
use App\Services\TenantMailService;
use App\Support\TenantEmailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendRealProfitReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly int $tenantId,
        private readonly ?string $month,
    ) {}

    public function handle(TenantMailService $tenantMail, RealProfitReportService $reports): void
    {
        $tenant = app(TenantDatabaseManager::class)->activateById($this->tenantId)
            ?? Tenant::find($this->tenantId);

        if (!$tenant) {
            Log::warning('SendRealProfitReportJob: tenant not found.', [
                'tenant_id' => $this->tenantId,
            ]);

            return;
        }

        app()->instance('tenant', $tenant);

        $recipients = User::query()
            ->whereHas('role', fn ($q) => $q->where('slug', 'admin'))
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get(['id', 'name', 'email']);

        if ($recipients->isEmpty()) {
            Log::info('SendRealProfitReportJob: no admin recipients found.', [
                'tenant_id' => $this->tenantId,
            ]);

            return;
        }

        try {
            $pdf = $reports->pdf($this->tenantId, $this->month, $tenant);
            $report = $pdf['report'];
            $tenantName = $tenant->name ?? 'Your Organisation';
            $tenantBranding = TenantEmailBranding::forTenant($tenant);

            $mailer = $tenantMail->mailerForTenant($this->tenantId);

            foreach ($recipients as $recipient) {
                $mailer->to($recipient->email, $recipient->name)->send(
                    new RealProfitReportMail(
                        $tenantName,
                        $report['month_label'],
                        $report['summary'],
                        $pdf['contents'],
                        $pdf['filename'],
                        $tenantBranding,
                    ),
                );
            }
        } catch (\Throwable $e) {
            Log::error('SendRealProfitReportJob: email send failed.', [
                'tenant_id' => $this->tenantId,
                'month' => $this->month,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

<?php

namespace App\Jobs;

use App\Mail\DailySummaryReportMail;
use App\Models\DailySummaryReport;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantMailService;
use App\Support\TenantEmailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SendDailySummaryReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly int $tenantId,
        private readonly int $reportId,
    ) {}

    public function handle(TenantMailService $tenantMail): void
    {
        $report = DailySummaryReport::query()
            ->find($this->reportId);

        if (!$report || !$report->pdf_path) {
            Log::warning('SendDailySummaryReportJob: report or PDF path missing.', [
                'report_id' => $this->reportId,
            ]);

            return;
        }

        $recipients = User::query()
            ->whereHas('role', fn ($q) => $q->where('slug', 'admin'))
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get(['id', 'name', 'email']);

        if ($recipients->isEmpty()) {
            Log::info('SendDailySummaryReportJob: no admin recipients found.', [
            ]);

            return;
        }

        try {
            $disk = config('filesystems.media_disk', 'public');
            $pdfContent = Storage::disk($disk)->get($report->pdf_path);

            if (!$pdfContent) {
                Log::warning('SendDailySummaryReportJob: PDF not found on disk.', [
                    'report_id' => $this->reportId,
                    'pdf_path' => $report->pdf_path,
                ]);

                return;
            }

            $tenant = Tenant::find($this->tenantId);
            $tenantName = $tenant?->name ?? 'Your Organisation';
            $tenantBranding = TenantEmailBranding::forTenant($tenant);
            $dateLabel = $report->report_date->format('d M Y');
            $changeCount = is_array($report->changes) ? count($report->changes) : 0;
            $pdfFilename = basename($report->pdf_path);

            $mailer = $tenantMail->mailerForTenant($this->tenantId);

            foreach ($recipients as $recipient) {
                $mailer->to($recipient->email, $recipient->name)->send(
                    new DailySummaryReportMail(
                        $tenantName,
                        $dateLabel,
                        $report->prepared_by_name,
                        $changeCount,
                        $pdfContent,
                        $pdfFilename,
                        $tenantBranding,
                    ),
                );
            }
        } catch (\Throwable $e) {
            Log::error('SendDailySummaryReportJob: email send failed.', [
                'report_id' => $this->reportId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

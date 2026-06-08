<?php

namespace App\Jobs;

use App\Mail\FormSubmissionMail;
use App\Models\FormSubmission;
use App\Support\TenantEmailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SendFormSubmissionEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly int $tenantId,
        private readonly int $submissionId,
    ) {}

    public function handle(\App\Services\TenantMailService $tenantMail): void
    {
        $submission = FormSubmission::where('tenant_id', $this->tenantId)
            ->with(['template:id,title', 'member:id,first_name,last_name,name,email,biometric_member_id,profile_photo_path'])
            ->find($this->submissionId);

        if (!$submission || !$submission->pdf_path) {
            Log::warning('SendFormSubmissionEmailJob: Submission or PDF path not found.', [
                'tenant_id' => $this->tenantId,
                'submission_id' => $this->submissionId,
            ]);

            return;
        }

        $member = $submission->member;

        if (!$member || !$member->email) {
            return;
        }

        try {
            $disk = config('filesystems.media_disk', 'public');
            $pdfContent = Storage::disk($disk)->get($submission->pdf_path);

            if (!$pdfContent) {
                Log::warning('SendFormSubmissionEmailJob: PDF file not found on disk.', [
                    'submission_id' => $this->submissionId,
                    'pdf_path' => $submission->pdf_path,
                ]);

                return;
            }

            $memberName = trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) ?: ($member->name ?? 'Member');
            $formTitle = $submission->template?->title ?? 'Form Submission';
            $pdfFilename = basename($submission->pdf_path);

            $tenantMail->mailerForTenant($this->tenantId)
                ->to($member->email, $memberName)
                ->send(new FormSubmissionMail(
                    $formTitle,
                    $memberName,
                    $pdfContent,
                    $pdfFilename,
                    TenantEmailBranding::forTenantId($this->tenantId),
                    TenantEmailBranding::memberAvatarUrl($member),
                    TenantEmailBranding::initials($memberName),
                ));
        } catch (\Throwable $e) {
            Log::error('SendFormSubmissionEmailJob: Email send failed.', [
                'submission_id' => $this->submissionId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

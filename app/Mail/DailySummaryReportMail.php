<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailySummaryReportMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly string $tenantName,
        public readonly string $dateLabel,
        public readonly string $preparedByName,
        public readonly int $changeCount,
        private readonly string $pdfContent,
        private readonly string $pdfFilename,
        public readonly array $tenantBranding = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Daily Summary Report — ' . $this->dateLabel,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.daily-summary-report');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, $this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}

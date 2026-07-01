<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RealProfitReportMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly string $tenantName,
        public readonly string $monthLabel,
        public readonly array $summary,
        private readonly string $pdfContent,
        private readonly string $pdfFilename,
        public readonly array $tenantBranding = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Real Profit Report - ' . $this->monthLabel,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.real-profit-report');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, $this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}

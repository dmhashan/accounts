<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FormSubmissionMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly string $formTitle,
        public readonly string $memberName,
        private readonly string $pdfContent,
        private readonly string $pdfFilename,
        public readonly array $tenantBranding = [],
        public readonly ?string $recipientAvatarUrl = null,
        public readonly ?string $recipientInitials = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->formTitle);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.form-submission');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, $this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}

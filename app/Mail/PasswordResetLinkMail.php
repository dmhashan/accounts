<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetLinkMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly string $tenantName,
        public readonly string $resetUrl,
        public readonly array $tenantBranding = [],
        public readonly ?string $recipientName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset your ' . $this->tenantName . ' password',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.password-reset-link');
    }
}

<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemberNotificationMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly string $notificationTitle,
        public readonly string $notificationBody,
        public readonly array $tenantBranding = [],
        public readonly ?string $recipientName = null,
        public readonly ?string $recipientAvatarUrl = null,
        public readonly ?string $recipientInitials = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->notificationTitle);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.member-notification');
    }
}

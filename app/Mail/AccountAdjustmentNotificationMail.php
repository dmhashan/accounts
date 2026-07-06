<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountAdjustmentNotificationMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly string $action,
        public readonly array $adjustmentDetails,
        public readonly array $tenantBranding = [],
        public readonly ?string $recipientName = null,
    ) {}

    public function envelope(): Envelope
    {
        $subject = sprintf(
            '[%s] Account Balance Adjustment %s',
            $this->tenantBranding['name'] ?? 'Organisation',
            ucfirst($this->action),
        );

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.account-adjustment-notification');
    }
}

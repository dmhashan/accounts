<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ManagerEventNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $eventLabel,
        public readonly string $summary,
        public readonly array $details,
        public readonly ?string $actorName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->eventLabel);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.manager-event-notification',
            with: [
                'eventLabel' => $this->eventLabel,
                'summary' => $this->summary,
                'details' => $this->details,
                'actorName' => $this->actorName,
            ],
        );
    }
}

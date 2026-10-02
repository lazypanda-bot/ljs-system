<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Symfony\Component\Mime\Address;

class ActivityMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(
        public string $subjectLine,
        public string $messageBody,
        public ?string $replyToEmail = null,
        public ?string $replyToName = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
            replyTo: $this->replyToEmail ? [new Address($this->replyToEmail, $this->replyToName ?? '')] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.activity');
    }

    public function attachments(): array
    {
        return [];
    }
}
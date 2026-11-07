<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class LoginVerificationMail extends Mailable
{
    public function __construct(public readonly string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'LJS Realty login verification code');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.login-verification',
            text: 'emails.login-verification-text',
        );
    }
}

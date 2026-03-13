<?php

namespace App\Mail;

// IMPORTANT: Configure MAIL_* settings in your .env file before using this:
// MAIL_MAILER=smtp
// MAIL_HOST=your-smtp-host
// MAIL_PORT=587
// MAIL_USERNAME=your-username
// MAIL_PASSWORD=your-password
// MAIL_ENCRYPTION=tls
// MAIL_FROM_ADDRESS=noreply@boerstaphorst.nl
// MAIL_FROM_NAME="Boer Staphorst Interieuradvies"

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class AdviceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Submission $submission, public string $pdfPath) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Jouw interieuradvies van Boer Staphorst');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.advice');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as('interieuradvies.pdf')
                ->withMime('application/pdf'),
        ];
    }
}

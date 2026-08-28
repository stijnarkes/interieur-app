<?php

namespace App\Mail;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuizResultMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Submission $submission, public string $pdfPath) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Jouw Woonstijl | Boer Staphorst');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.quiz-result');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as('jouw-woonstijl.pdf')
                ->withMime('application/pdf'),
        ];
    }
}

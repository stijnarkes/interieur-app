<?php

namespace App\Mail;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class QuizResultMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param  string  $pdfPath  storage-key op config('filesystems.quiz_pdfs_disk'), zie QuizResultPdfService */
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
            // fromData i.p.v. fromPath: het PDF-bestand staat op de geconfigureerde disk (mogelijk
            // S3), niet per se als lokaal bestand op deze server — zie QuizResultPdfService.
            Attachment::fromData(
                fn () => Storage::disk(config('filesystems.quiz_pdfs_disk'))->get($this->pdfPath),
                'jouw-woonstijl.pdf',
            )->withMime('application/pdf'),
        ];
    }
}

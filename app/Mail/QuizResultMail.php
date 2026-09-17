<?php

namespace App\Mail;

use App\Models\SiteContent;
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

    /**
     * @param  string  $pdfPath  storage-key op config('filesystems.quiz_pdfs_disk'), zie QuizResultPdfService
     * @param  ?array{inviteUrl: string}  $partnerInvite  gezet zodra GenerateAndSendQuizResultPdfJob
     *   automatisch een partneruitnodiging aanmaakte (zie PartnerLinkService) — toont dan een extra
     *   sectie in de mail. Blijft `null` zolang de partnerfunctie uitstaat, dit de partnertest zelf
     *   is, of het aanmaken onverwacht mislukte (nooit de hoofd-e-mail daarop laten wachten/falen).
     */
    public function __construct(public Submission $submission, public string $pdfPath, public ?array $partnerInvite = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: SiteContent::current()->email_subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.quiz-result', with: [
            'siteContent' => SiteContent::current(),
            'partnerInviteUrl' => $this->partnerInvite['inviteUrl'] ?? null,
        ]);
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

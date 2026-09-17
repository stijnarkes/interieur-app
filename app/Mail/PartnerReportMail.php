<?php

namespace App\Mail;

use App\Models\PartnerLink;
use App\Models\SiteContent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/** Zelfde opbouw als App\Mail\QuizResultMail, maar voor de gezamenlijke PDF van de partnerfunctie. */
class PartnerReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $pdfPath  storage-key op config('filesystems.quiz_pdfs_disk'), zie PartnerReportPdfService
     * @param  ?string  $recipientName  naam van déze ontvanger (initiator of partner) — de mail gaat
     *   per deelnemer apart uit (zie PartnerReportMailer), dus de aanhef spreekt bewust alleen
     *   diegene aan, niet allebei de namen tegelijk.
     */
    public function __construct(public PartnerLink $link, public string $pdfPath, public ?string $recipientName = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Jullie gezamenlijke woonstijl van Boer Staphorst');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.partner-result', with: [
            'siteContent' => SiteContent::current(),
            'link' => $this->link,
            'recipientName' => $this->recipientName,
        ]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => Storage::disk(config('filesystems.quiz_pdfs_disk'))->get($this->pdfPath),
                'jullie-gezamenlijke-woonstijl.pdf',
            )->withMime('application/pdf'),
        ];
    }
}

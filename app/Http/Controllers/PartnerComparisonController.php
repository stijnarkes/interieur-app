<?php

namespace App\Http\Controllers;

use App\Models\PartnerComparison;
use App\Models\PartnerLink;
use App\Models\PartnerParticipant;
use App\Models\StyleProfile;
use App\Services\PartnerReportMailer;
use App\Services\PartnerReportPdfService;
use App\Support\PartnerAccessGuard;
use App\Support\PartnerFactPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Toont het gezamenlijke resultaat aan een geldige deelnemer — zie het implementatieplan,
 * sectie "Tokens en toegang": geeft nooit resultaatinhoud van de ándere rol terug vóór de
 * koppeling voltooid is, en het toegangstoken van de aanvrager bepaalt uitsluitend readonly-
 * toegang tot precies déze koppeling.
 */
class PartnerComparisonController extends Controller
{
    public function show(string $accessToken): JsonResponse
    {
        $participant = PartnerAccessGuard::resolve($accessToken);

        if (! $participant) {
            return response()->json(['message' => 'Niet gevonden.'], 404);
        }

        $link = $participant->partnerLink;

        if ($link->status !== PartnerLink::STATUS_COMPLETED) {
            return response()->json([
                'status' => $link->status,
                'role' => $participant->role,
            ]);
        }

        $comparison = PartnerComparison::where('partner_link_id', $link->id)->first();

        if (! $comparison || $comparison->status !== PartnerComparison::STATUS_READY) {
            return response()->json([
                'status' => 'processing',
                'role' => $participant->role,
            ]);
        }

        $styleLabel = fn (?string $key) => $key ? (StyleProfile::forStyle($key)?->label ?? $key) : null;

        return response()->json([
            'status' => 'ready',
            'role' => $participant->role,
            'initiatorName' => $link->initiator_name,
            'partnerName' => $link->partner_name,
            'initiatorStyle' => $styleLabel($link->initiator_snapshot['primary_style'] ?? null),
            'partnerStyle' => $styleLabel($link->partner_snapshot['primary_style'] ?? null),
            'initiatorPalette' => $link->initiator_snapshot['chosen_base_palette'] ?? null,
            'partnerPalette' => $link->partner_snapshot['chosen_base_palette'] ?? null,
            'initiatorAccentColors' => $link->initiator_snapshot['chosen_accent_colors'] ?? [],
            'partnerAccentColors' => $link->partner_snapshot['chosen_accent_colors'] ?? [],
            'facts' => PartnerFactPresenter::resolveStyleKeys(
                $comparison->facts ?? [],
                fn (?string $key) => $key ? (StyleProfile::forStyle($key)?->label ?? $key) : $key,
            ),
            'suggestions' => $comparison->suggestions,
        ]);
    }

    /**
     * Genereert de gezamenlijke PDF (of hergebruikt een al eerder gegenereerd bestand — de inhoud
     * ligt vast zodra de vergelijking 'ready' is, dus opnieuw genereren voegt niets toe) en
     * streamt 'm direct terug. Zelfde token-toegangsregel als show(): pas beschikbaar zodra de
     * koppeling voltooid én de vergelijking klaar is.
     */
    public function report(string $accessToken)
    {
        [$participant, $link, $comparison] = $this->resolveReadyComparison($accessToken);

        if (! $comparison) {
            abort(404);
        }

        if (! $comparison->pdf_path || ! Storage::disk(config('filesystems.quiz_pdfs_disk'))->exists($comparison->pdf_path)) {
            $pdfPath = app(PartnerReportPdfService::class)->generate($link, $comparison);
            $comparison->update(['pdf_path' => $pdfPath]);
        }

        return Storage::disk(config('filesystems.quiz_pdfs_disk'))->response(
            $comparison->pdf_path,
            'gezamenlijke-woonstijl.pdf',
            ['Content-Disposition' => 'inline; filename="gezamenlijke-woonstijl.pdf"'],
        );
    }

    /**
     * Stuurt de gezamenlijke PDF naar het eigen e-mailadres van déze deelnemer — nooit naar het
     * adres van de ander, en nooit gekoppeld aan diens toegangstoken. Zelfde idempotentiepatroon
     * als QuizLeadController::isInFlight(): een al verzonden of nog verse aanvraag start nooit een
     * tweede verzending, een eerder mislukte of vastgelopen poging mag wél opnieuw.
     */
    public function mail(Request $request, string $accessToken, PartnerReportMailer $mailer): JsonResponse
    {
        $data = $request->validate(['email' => 'required|email|max:255']);

        [$participant, $link, $comparison] = $this->resolveReadyComparison($accessToken);

        if (! $comparison) {
            return response()->json(['message' => 'Nog niet beschikbaar.'], 409);
        }

        if ($mailer->isInFlight($participant)) {
            return $this->mailResponseFor($participant);
        }

        $mailer->send($participant, $link, $comparison, $data['email']);

        return $this->mailResponseFor($participant->fresh());
    }

    private function mailResponseFor(PartnerParticipant $participant): JsonResponse
    {
        return match ($participant->mail_status) {
            'sent' => response()->json(['status' => 'sent', 'message' => 'Verstuurd naar je e-mailadres.']),
            'failed' => response()->json(['status' => 'failed', 'message' => 'Het versturen is niet gelukt.'], 200),
            default => response()->json(['status' => 'queued', 'message' => 'Wordt verstuurd...']),
        };
    }

    /** @return array{0: PartnerParticipant, 1: PartnerLink, 2: ?PartnerComparison} */
    private function resolveReadyComparison(string $accessToken): array
    {
        $participant = PartnerAccessGuard::resolve($accessToken);

        if (! $participant) {
            abort(404);
        }

        $link = $participant->partnerLink;

        if ($link->status !== PartnerLink::STATUS_COMPLETED) {
            return [$participant, $link, null];
        }

        $comparison = PartnerComparison::where('partner_link_id', $link->id)
            ->where('status', PartnerComparison::STATUS_READY)
            ->first();

        return [$participant, $link, $comparison];
    }
}

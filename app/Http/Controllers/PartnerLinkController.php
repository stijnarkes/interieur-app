<?php

namespace App\Http\Controllers;

use App\Models\PartnerEvent;
use App\Models\PartnerLink;
use App\Models\PartnerParticipant;
use App\Models\QuizResult;
use App\Models\Submission;
use App\Services\PartnerLinkService;
use App\Support\PartnerAccessGuard;
use App\Support\PartnerToken;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Aanmaken, opvragen, claimen en intrekken van een partnerkoppeling — zie het implementatieplan
 * "Partnerfunctie: Ontdek jullie gezamenlijke woonstijl", secties "Datamodel"/"Tokens en toegang".
 * Alle acties zijn publiek (anonieme bezoekers, geen accounts) en beveiligd via tokens i.p.v.
 * sessies, exact zoals de rest van de klant-quiz al werkt.
 */
class PartnerLinkController extends Controller
{
    /**
     * Aanmaken, of idempotent de al bestaande, nog geldige uitnodiging teruggeven — zie
     * App\Services\PartnerLinkService, die dit ook automatisch aanroept zodra iemand zijn/haar
     * eigen aanvraagformulier verstuurt (zie QuizLeadController/GenerateAndSendQuizResultPdfJob).
     * Deze route zelf wordt door de klant-quiz niet meer aangeroepen (het uitnodigingsblok is
     * verhuisd naar de bevestigingsmail), maar blijft bestaan voor eventueel toekomstig/handmatig
     * gebruik.
     */
    public function create(Request $request, PartnerLinkService $partnerLinkService): JsonResponse
    {
        $data = $request->validate([
            'resultUuid' => 'required|uuid|exists:quiz_results,uuid',
            'name' => 'nullable|string|max:255',
        ]);

        $quizResult = QuizResult::where('uuid', $data['resultUuid'])->firstOrFail();

        // Nooit een los naam-/e-mailveld van de client verwachten: hergebruikt bewust wat de
        // bezoeker al invulde bij het aanvraagformulier voor het eigen individuele rapport (zie
        // QuizLeadController/Submission) — dat scheelt dubbele invoer.
        $submission = Submission::where('quiz_result_id', $quizResult->id)->first();

        $invite = $partnerLinkService->createOrGetInvite(
            $quizResult,
            $data['name'] ?? $submission?->name,
            $submission?->email,
        );

        return response()->json([
            'inviteUrl' => $invite['inviteUrl'],
            'inviteExpiresAt' => $invite['link']->invite_expires_at->toIso8601String(),
            'status' => $invite['link']->status,
            'accessToken' => $invite['accessToken'],
            'resultUrl' => $invite['resultUrl'],
        ]);
    }

    /**
     * Alleen-lezen, claimt nooit — veilig om te bevragen vanuit een WhatsApp-linkvoorvertoning of
     * een simpele "is deze link nog geldig"-check vóórdat de bezoeker daadwerkelijk klikt.
     */
    public function preview(string $inviteToken): JsonResponse
    {
        $link = PartnerLink::where('invite_token_hash', PartnerToken::hash($inviteToken))->first();

        if (! $link) {
            return response()->json(['valid' => false, 'expired' => false, 'alreadyClaimed' => false]);
        }

        return response()->json([
            'valid' => $link->isClaimable(),
            'expired' => $link->isExpired(),
            'alreadyClaimed' => $link->status !== PartnerLink::STATUS_WAITING && ! $link->isExpired() && ! $link->isRevoked(),
            'initiatorName' => $link->initiator_name,
        ]);
    }

    /**
     * Atomair: de db-constraint unique(['partner_link_id','role']) garandeert dat een tweede,
     * gelijktijdige claimpoging nooit een tweede partner-rij aanmaakt — zie de class-docblock van
     * PartnerParticipant. Geeft het toegangstoken maar één keer terug (in dit antwoord); de
     * frontend bewaart het zelf in localStorage voor "hervatten op hetzelfde apparaat", exact
     * zoals resources/js/quiz/state.js al met de quizvoortgang doet.
     */
    public function claim(Request $request, string $inviteToken): JsonResponse
    {
        $data = $request->validate([
            'partnerName' => 'nullable|string|max:255',
        ]);

        $link = PartnerLink::where('invite_token_hash', PartnerToken::hash($inviteToken))->first();

        if (! $link) {
            return response()->json(['message' => 'Deze uitnodiging bestaat niet (meer).'], 404);
        }

        // Al geclaimd op dít apparaat: geef gewoon het bestaande toegangsbewijs-resultaat terug
        // i.p.v. een foutmelding — de invite-pagina checkt dit zelf al via localStorage vóór het
        // claimen, maar een dubbele aanvraag (dubbelklik, teruggekeerd tabblad) moet nooit crashen.
        $existingAccessToken = $request->header('X-Partner-Access-Token');
        if ($existingAccessToken) {
            $participant = PartnerAccessGuard::resolve($existingAccessToken);
            if ($participant && $participant->partner_link_id === $link->id && $participant->role === PartnerParticipant::ROLE_PARTNER) {
                return response()->json(['alreadyClaimed' => true, 'accessToken' => $existingAccessToken]);
            }
        }

        if (! $link->isClaimable()) {
            return response()->json([
                'message' => $link->isExpired()
                    ? 'Deze uitnodiging is verlopen.'
                    : 'Deze uitnodiging is al gebruikt of ingetrokken.',
            ], 409);
        }

        $accessToken = PartnerToken::generate();

        try {
            DB::transaction(function () use ($link, $data, $accessToken) {
                $link->participants()->create([
                    'role' => PartnerParticipant::ROLE_PARTNER,
                    'access_token_hash' => PartnerToken::hash($accessToken),
                ]);

                $link->update([
                    'status' => PartnerLink::STATUS_PARTNER_STARTED,
                    'partner_name' => $data['partnerName'] ?? null,
                ]);
            });
        } catch (QueryException) {
            // De unique(['partner_link_id','role'])-constraint sloeg toe: een gelijktijdige
            // claimpoging won de race. Geen tweede rij, geen half toegepaste wijziging.
            return response()->json(['message' => 'Deze uitnodiging is al gebruikt of ingetrokken.'], 409);
        }

        PartnerEvent::record('partner_started', $link->id);

        return response()->json([
            'accessToken' => $accessToken,
            'inviteExpiresAt' => $link->invite_expires_at->toIso8601String(),
        ]);
    }

    /** Poll-vriendelijke statuscheck voor de wachtende initiator — nooit resultaatinhoud. */
    public function status(Request $request, string $inviteToken): JsonResponse
    {
        $data = $request->validate(['accessToken' => 'required|string']);

        $link = PartnerLink::where('invite_token_hash', PartnerToken::hash($inviteToken))->first();
        $participant = PartnerAccessGuard::resolve($data['accessToken']);

        if (! $link || ! $participant || $participant->partner_link_id !== $link->id || $participant->role !== PartnerParticipant::ROLE_INITIATOR) {
            return response()->json(['message' => 'Niet gevonden.'], 404);
        }

        return response()->json(['status' => $link->status]);
    }

    /** Alleen de initiator mag intrekken — daarna is de uitnodigingslink onbruikbaar. */
    public function revoke(Request $request, string $inviteToken): JsonResponse
    {
        $data = $request->validate(['accessToken' => 'required|string']);

        $link = PartnerLink::where('invite_token_hash', PartnerToken::hash($inviteToken))->first();
        $participant = PartnerAccessGuard::resolve($data['accessToken']);

        if (! $link || ! $participant || $participant->partner_link_id !== $link->id || $participant->role !== PartnerParticipant::ROLE_INITIATOR) {
            return response()->json(['message' => 'Niet gevonden.'], 404);
        }

        $link->update(['status' => PartnerLink::STATUS_REVOKED, 'revoked_at' => now()]);

        return response()->json(['status' => $link->status]);
    }
}

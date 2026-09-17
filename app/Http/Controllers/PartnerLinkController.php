<?php

namespace App\Http\Controllers;

use App\Models\PartnerEvent;
use App\Models\PartnerLink;
use App\Models\PartnerParticipant;
use App\Models\QuizResult;
use App\Models\Submission;
use App\Support\PartnerAccessGuard;
use App\Support\PartnerSnapshotBuilder;
use App\Support\PartnerToken;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Aanmaken, opvragen, claimen en intrekken van een partnerkoppeling — zie het implementatieplan
 * "Partnerfunctie: Ontdek jullie gezamenlijke woonstijl", secties "Datamodel"/"Tokens en toegang".
 * Alle acties zijn publiek (anonieme bezoekers, geen accounts) en beveiligd via tokens i.p.v.
 * sessies, exact zoals de rest van de klant-quiz al werkt.
 */
class PartnerLinkController extends Controller
{
    private const INVITE_LIFETIME_DAYS = 30;

    /** Aanmaken, of idempotent de al bestaande, nog geldige uitnodiging teruggeven. */
    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'resultUuid' => 'required|uuid|exists:quiz_results,uuid',
            'name' => 'nullable|string|max:255',
            'notifyByEmail' => 'nullable|boolean',
            'shareConfirmationTextVersion' => 'required|string|max:100',
        ]);

        $quizResult = QuizResult::where('uuid', $data['resultUuid'])->firstOrFail();

        // Nooit een los e-mailadres van de client aannemen: hergebruikt bewust het adres dat de
        // bezoeker al invulde bij het aanvraagformulier voor het eigen individuele rapport (zie
        // QuizLeadController/Submission) — dat scheelt een dubbel veld, en het uitnodigingsblok
        // toont dit vinkje sowieso pas nadat dat formulier al verstuurd is (zie quiz.js).
        $notifyEmail = $request->boolean('notifyByEmail')
            ? Submission::where('quiz_result_id', $quizResult->id)->value('email')
            : null;

        $existing = PartnerLink::where('initiator_quiz_result_id', $quizResult->id)
            ->whereNotIn('status', [PartnerLink::STATUS_REVOKED, PartnerLink::STATUS_EXPIRED])
            ->where('invite_expires_at', '>', now())
            ->first();

        if ($existing) {
            return $this->inviteResponse($existing, Crypt::decryptString($existing->invite_token_encrypted));
        }

        $inviteToken = PartnerToken::generate();
        $accessToken = PartnerToken::generate();

        $link = DB::transaction(function () use ($quizResult, $data, $inviteToken, $accessToken, $notifyEmail) {
            $link = PartnerLink::create([
                'initiator_quiz_result_id' => $quizResult->id,
                'initiator_snapshot' => PartnerSnapshotBuilder::build($quizResult),
                'status' => PartnerLink::STATUS_WAITING,
                'invite_token_hash' => PartnerToken::hash($inviteToken),
                'invite_token_encrypted' => Crypt::encryptString($inviteToken),
                'invite_expires_at' => now()->addDays(self::INVITE_LIFETIME_DAYS),
                'initiator_name' => $data['name'] ?? null,
                'share_confirmed_at' => now(),
                'share_confirmation_text_version' => $data['shareConfirmationTextVersion'],
            ]);

            $link->participants()->create([
                'role' => PartnerParticipant::ROLE_INITIATOR,
                'quiz_result_id' => $quizResult->id,
                'access_token_hash' => PartnerToken::hash($accessToken),
                // Optioneel: zodra de partner klaar is, mailt QuizResultController::
                // linkPartnerParticipant() de gezamenlijke PDF hier automatisch naartoe (via
                // PartnerReportMailer) — anders is de link hieronder de enige toegang, en die kan
                // (bewust, zie PartnerToken) nooit achteraf opnieuw opgevraagd worden.
                'email' => $notifyEmail,
            ]);

            return $link;
        });

        PartnerEvent::record('invite_created', $link->id);

        return $this->inviteResponse($link, $inviteToken, $accessToken);
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

    /**
     * `accessToken`/`resultUrl` zijn alleen gezet bij de daadwerkelijke aanmaak (nooit bij de
     * idempotente herhaling hieronder) — alleen op dát moment is de plaintext bekend, zie
     * App\Support\PartnerToken. Dit IS dus de enige keer dat de initiator zijn/haar eigen link
     * naar het gezamenlijke resultaat te zien krijgt; vandaar het optionele e-mailadres bij create()
     * als extra, latere bezorgroute.
     */
    private function inviteResponse(PartnerLink $link, string $inviteToken, ?string $accessToken = null): JsonResponse
    {
        return response()->json([
            'inviteUrl' => url("/gezamenlijk/uitnodiging/{$inviteToken}"),
            'inviteExpiresAt' => $link->invite_expires_at->toIso8601String(),
            'status' => $link->status,
            'accessToken' => $accessToken,
            'resultUrl' => $accessToken ? url("/gezamenlijk/{$accessToken}") : null,
        ]);
    }
}

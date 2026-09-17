<?php

namespace App\Services;

use App\Models\PartnerEvent;
use App\Models\PartnerLink;
use App\Models\PartnerParticipant;
use App\Models\QuizResult;
use App\Support\PartnerSnapshotBuilder;
use App\Support\PartnerToken;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Aanmaken (of idempotent hergebruiken) van een partneruitnodiging — gedeeld tussen
 * PartnerLinkController::create() (de JSON-route) en GenerateAndSendQuizResultPdfJob (die de
 * uitnodiging automatisch aanmaakt zodra iemand zijn/haar eigen aanvraagformulier verstuurt, zie
 * QuizLeadController). De uitnodiging staat zo altijd al klaar tegen de tijd dat de bevestigingsmail
 * met de individuele PDF verstuurd wordt — geen aparte knop op de resultatenpagina meer nodig.
 */
class PartnerLinkService
{
    private const INVITE_LIFETIME_DAYS = 30;

    /**
     * @param  ?string  $initiatorName  hergebruikt bewust de naam uit het aanvraagformulier
     *   (Submission::name) — nooit een los veld opnieuw uitvragen.
     * @param  ?string  $notifyEmail  idem voor het e-mailadres: is dit gezet, dan mailt
     *   QuizResultController::linkPartnerParticipant() het gezamenlijke rapport hier straks
     *   automatisch naartoe zodra de partner klaar is (via PartnerReportMailer) — zonder dit
     *   adres is de link in de bevestigingsmail de enige toegang, en die kan (bewust, zie
     *   App\Support\PartnerToken) nooit achteraf opnieuw opgevraagd worden.
     * @return array{link: PartnerLink, inviteUrl: string, resultUrl: ?string, accessToken: ?string}
     */
    public function createOrGetInvite(QuizResult $quizResult, ?string $initiatorName, ?string $notifyEmail): array
    {
        $existing = PartnerLink::where('initiator_quiz_result_id', $quizResult->id)
            ->whereNotIn('status', [PartnerLink::STATUS_REVOKED, PartnerLink::STATUS_EXPIRED])
            ->where('invite_expires_at', '>', now())
            ->first();

        if ($existing) {
            return $this->inviteData($existing, Crypt::decryptString($existing->invite_token_encrypted));
        }

        $inviteToken = PartnerToken::generate();
        $accessToken = PartnerToken::generate();

        $link = DB::transaction(function () use ($quizResult, $inviteToken, $accessToken, $initiatorName, $notifyEmail) {
            $link = PartnerLink::create([
                'initiator_quiz_result_id' => $quizResult->id,
                'initiator_snapshot' => PartnerSnapshotBuilder::build($quizResult),
                'status' => PartnerLink::STATUS_WAITING,
                'invite_token_hash' => PartnerToken::hash($inviteToken),
                'invite_token_encrypted' => Crypt::encryptString($inviteToken),
                'invite_expires_at' => now()->addDays(self::INVITE_LIFETIME_DAYS),
                'initiator_name' => $initiatorName,
                'share_confirmed_at' => now(),
                'share_confirmation_text_version' => 'v1',
            ]);

            $link->participants()->create([
                'role' => PartnerParticipant::ROLE_INITIATOR,
                'quiz_result_id' => $quizResult->id,
                'access_token_hash' => PartnerToken::hash($accessToken),
                'email' => $notifyEmail,
            ]);

            return $link;
        });

        PartnerEvent::record('invite_created', $link->id);

        return $this->inviteData($link, $inviteToken, $accessToken);
    }

    /** @return array{link: PartnerLink, inviteUrl: string, resultUrl: ?string, accessToken: ?string} */
    private function inviteData(PartnerLink $link, string $inviteToken, ?string $accessToken = null): array
    {
        return [
            'link' => $link,
            'inviteUrl' => url("/gezamenlijk/uitnodiging/{$inviteToken}"),
            // Alleen gezet bij de daadwerkelijke aanmaak, nooit bij idempotent hergebruik — zie
            // App\Support\PartnerToken: de plaintext van het toegangstoken is daarna niet meer te
            // achterhalen.
            'accessToken' => $accessToken,
            'resultUrl' => $accessToken ? url("/gezamenlijk/{$accessToken}") : null,
        ];
    }
}

<?php

namespace Tests\Feature;

use App\Mail\QuizResultMail;
use App\Models\PartnerLink;
use App\Models\PartnerParticipant;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\QuizResult;
use App\Models\QuizSetting;
use App\Models\StyleProfile;
use App\Support\PartnerToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt de verhuizing van het uitnodigingsblok naar de bevestigingsmail: de partneruitnodiging
 * wordt nu automatisch aangemaakt zodra iemand zijn/haar eigen aanvraagformulier verstuurt (zie
 * GenerateAndSendQuizResultPdfJob), zonder aparte knop op de resultatenpagina — behalve voor de
 * partnertest zelf, die nooit een eigen uitnodiging voor zichzelf mag krijgen.
 */
class PartnerInviteEmailTest extends TestCase
{
    use RefreshDatabase;

    private function makeQuizResult(): QuizResult
    {
        StyleProfile::query()->firstOrCreate(
            ['style_key' => 'japandi'],
            ['label' => 'Japandi', 'slug' => 'japandi', 'long_description' => 'Rust en warmte passen bij jou.'],
        );

        QuizQuestion::query()->firstOrCreate(
            ['question_key' => 'vloer'],
            ['section' => 'materials-colors', 'title' => 'Welke vloer?', 'folder' => null,
                'sort_order' => 10, 'max_selections' => 1, 'weight' => 1, 'image_display_mode' => 'contain'],
        );

        $option = QuizOption::query()->firstOrCreate(
            ['question_id' => 'vloer', 'option_slug' => 'eiken'],
            ['style_key' => 'japandi', 'primary_style' => 'japandi', 'title' => 'Eiken vloer', 'is_active' => true, 'has_image' => false],
        );

        return QuizResult::create([
            'uuid' => (string) Str::uuid(),
            'answers' => ['vloer' => [$option->option_slug]],
            'style_scores' => ['japandi' => 1],
            'primary_style' => 'japandi',
        ]);
    }

    private function postLead(string $resultUuid, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/quiz-lead', array_merge([
            'resultUuid' => $resultUuid,
            'name' => 'Anna',
            'email' => 'anna@example.com',
        ], $overrides));
    }

    #[Test]
    public function zolang_de_partnerfunctie_uit_staat_wordt_er_geen_uitnodiging_aangemaakt(): void
    {
        Mail::fake();
        $result = $this->makeQuizResult();

        $this->postLead($result->uuid)->assertOk();

        $this->assertSame(0, PartnerLink::count());
        Mail::assertSent(QuizResultMail::class, fn (QuizResultMail $mail) => $mail->partnerInvite === null);
    }

    #[Test]
    public function het_versturen_van_het_eigen_aanvraagformulier_maakt_automatisch_een_uitnodiging_aan(): void
    {
        Mail::fake();
        QuizSetting::current()->update(['partner_feature_enabled' => true]);
        $result = $this->makeQuizResult();

        $this->postLead($result->uuid)->assertOk();

        $this->assertSame(1, PartnerLink::count());
        $link = PartnerLink::first();
        $this->assertSame($result->id, $link->initiator_quiz_result_id);
        $this->assertSame('Anna', $link->initiator_name);

        // De initiator kreeg zijn/haar eigen adres automatisch gekoppeld — dus zodra de partner
        // klaar is, mailt PartnerReportMailer het gezamenlijke rapport hier vanzelf naartoe.
        $initiator = $link->participants()->where('role', 'initiator')->first();
        $this->assertSame('anna@example.com', $initiator->email);

        Mail::assertSent(QuizResultMail::class, function (QuizResultMail $mail) {
            return $mail->partnerInvite !== null
                && str_contains($mail->partnerInvite['inviteUrl'], '/gezamenlijk/uitnodiging/');
        });
    }

    #[Test]
    public function een_tweede_inzending_voor_hetzelfde_resultaat_maakt_geen_tweede_uitnodiging(): void
    {
        Mail::fake();
        QuizSetting::current()->update(['partner_feature_enabled' => true]);
        $result = $this->makeQuizResult();

        $this->postLead($result->uuid)->assertOk();

        // Simuleert een "opnieuw versturen": email_status terugzetten zodat isInFlight() een
        // nieuwe poging toestaat (zie QuizLeadController), zonder de hele flow opnieuw te doorlopen.
        \App\Models\Submission::where('quiz_result_id', $result->id)->update(['email_status' => 'failed']);
        $this->postLead($result->uuid)->assertOk();

        $this->assertSame(1, PartnerLink::count());
    }

    /**
     * De partnertest zelf mag nooit een eigen uitnodiging krijgen — anders zou een partner op zijn
     * beurt weer iemand anders kunnen uitnodigen, wat tegen de "max. 2 deelnemers"-regel ingaat.
     */
    #[Test]
    public function de_partnertest_zelf_krijgt_nooit_een_eigen_uitnodiging(): void
    {
        Mail::fake();
        QuizSetting::current()->update(['partner_feature_enabled' => true]);

        $initiatorResult = $this->makeQuizResult();
        $link = PartnerLink::create([
            'initiator_quiz_result_id' => $initiatorResult->id,
            'initiator_snapshot' => ['primary_style' => 'japandi'],
            'invite_token_hash' => PartnerToken::hash('invite-token'),
            'invite_token_encrypted' => encrypt('invite-token'),
            'invite_expires_at' => now()->addDays(30),
        ]);
        $partnerAccessToken = PartnerToken::generate();
        $link->participants()->create([
            'role' => PartnerParticipant::ROLE_PARTNER,
            'access_token_hash' => PartnerToken::hash($partnerAccessToken),
        ]);

        $partnerResult = $this->makeQuizResult();

        $this->postLead($partnerResult->uuid, ['partnerClaimToken' => $partnerAccessToken])->assertOk();

        // Nog steeds precies 1 PartnerLink (die van de initiator) — geen tweede voor de partner.
        $this->assertSame(1, PartnerLink::count());
        Mail::assertSent(QuizResultMail::class, fn (QuizResultMail $mail) => $mail->partnerInvite === null);
    }
}

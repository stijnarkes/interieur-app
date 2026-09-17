<?php

namespace Tests\Feature;

use App\Mail\PartnerReportMail;
use App\Models\AccentColor;
use App\Models\PartnerComparison;
use App\Models\PartnerLink;
use App\Models\PartnerParticipant;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\QuizResult;
use App\Models\QuizSetting;
use App\Models\StyleProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Dekt de gezamenlijke PDF (download) en de eigen-adres-mailaanvraag (zie PartnerComparisonController). */
class PartnerReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AccentColor::query()->delete();
        QuizSetting::current()->update(['partner_feature_enabled' => true]);
        Storage::fake(config('filesystems.quiz_pdfs_disk'));

        StyleProfile::create(['style_key' => 'japandi', 'label' => 'Japandi', 'slug' => 'japandi']);
        StyleProfile::create(['style_key' => 'modern', 'label' => 'Modern', 'slug' => 'modern']);

        QuizQuestion::create([
            'question_key' => 'vloer', 'section' => 'materials-colors', 'title' => 'Welke vloer?',
            'folder' => null, 'sort_order' => 10, 'max_selections' => 1, 'weight' => 1, 'image_display_mode' => 'contain',
        ]);
        QuizOption::create([
            'question_id' => 'vloer', 'style_key' => 'japandi', 'option_slug' => 'eiken',
            'primary_style' => 'japandi', 'title' => 'Eiken vloer', 'is_active' => true, 'has_image' => false,
        ]);
        QuizOption::create([
            'question_id' => 'vloer', 'style_key' => 'modern', 'option_slug' => 'beton',
            'primary_style' => 'modern', 'title' => 'Betonlook vloer', 'is_active' => true, 'has_image' => false,
        ]);
    }

    /** @return array{0: string, 1: string} [initiatorAccessToken, partnerAccessToken] van een voltooide koppeling */
    private function completedLink(): array
    {
        $result = QuizResult::create([
            'uuid' => (string) Str::uuid(), 'answers' => ['vloer' => ['eiken']],
            'style_scores' => ['japandi' => 1], 'primary_style' => 'japandi',
        ]);

        $create = $this->postJson('/api/partner-links', [
            'resultUuid' => $result->uuid, 'shareConfirmationTextVersion' => 'v1',
        ])->assertOk();
        $inviteToken = Str::afterLast($create->json('inviteUrl'), '/');
        $initiatorAccessToken = $create->json('accessToken');

        $claim = $this->postJson("/api/partner-links/{$inviteToken}/claim")->assertOk();
        $partnerAccessToken = $claim->json('accessToken');

        $partnerResult = $this->postJson('/api/quiz-result', [
            'answers' => ['vloer' => ['beton']],
        ])->assertOk();

        $this->patchJson("/api/quiz-result/{$partnerResult->json('resultUuid')}/complete-partner", [
            'partnerClaimToken' => $partnerAccessToken,
        ])->assertOk();

        return [$initiatorAccessToken, $partnerAccessToken];
    }

    #[Test]
    public function het_rapport_is_pas_beschikbaar_zodra_de_koppeling_voltooid_is(): void
    {
        $result = QuizResult::create([
            'uuid' => (string) Str::uuid(), 'answers' => [], 'style_scores' => [], 'primary_style' => 'japandi',
        ]);
        $create = $this->postJson('/api/partner-links', [
            'resultUuid' => $result->uuid, 'shareConfirmationTextVersion' => 'v1',
        ])->assertOk();

        $this->get("/api/partner-comparisons/{$create->json('accessToken')}/report")->assertNotFound();
    }

    #[Test]
    public function het_rapport_kan_gedownload_worden_zodra_de_vergelijking_klaar_is(): void
    {
        [$initiatorAccessToken] = $this->completedLink();

        $response = $this->get("/api/partner-comparisons/{$initiatorAccessToken}/report")->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));

        $comparison = PartnerComparison::first();
        $this->assertNotNull($comparison->pdf_path);
        Storage::disk(config('filesystems.quiz_pdfs_disk'))->assertExists($comparison->pdf_path);
    }

    #[Test]
    public function een_deelnemer_kan_het_rapport_naar_het_eigen_adres_laten_mailen(): void
    {
        Mail::fake();
        [$initiatorAccessToken] = $this->completedLink();

        $this->postJson("/api/partner-comparisons/{$initiatorAccessToken}/mail", ['email' => 'anna@example.com'])
            ->assertOk()
            ->assertJson(['status' => 'sent']);

        Mail::assertSent(PartnerReportMail::class, 1);

        $participant = PartnerParticipant::where('access_token_hash', hash('sha256', $initiatorAccessToken))->first();
        $this->assertSame('anna@example.com', $participant->email);
        $this->assertSame('sent', $participant->mail_status);
    }

    /**
     * Regressie: de mail ging per deelnemer apart uit, maar sprak altijd beide namen tegelijk aan
     * ("Beste Anna & Bram,") — ook in Bram's eigen mail. De aanhef moet alleen de daadwerkelijke
     * ontvanger noemen.
     */
    #[Test]
    public function de_mail_spreekt_alleen_de_daadwerkelijke_ontvanger_aan(): void
    {
        Mail::fake();
        [$initiatorAccessToken, $partnerAccessToken] = $this->completedLink();
        PartnerLink::first()->update(['initiator_name' => 'Anna', 'partner_name' => 'Bram']);

        $this->postJson("/api/partner-comparisons/{$initiatorAccessToken}/mail", ['email' => 'anna@example.com'])->assertOk();
        $this->postJson("/api/partner-comparisons/{$partnerAccessToken}/mail", ['email' => 'bram@example.com'])->assertOk();

        Mail::assertSent(PartnerReportMail::class, function (PartnerReportMail $mail) {
            if (! $mail->hasTo('anna@example.com')) {
                return true;
            }
            $html = $mail->render();

            return str_contains($html, 'Beste Anna,') && ! str_contains($html, 'Bram');
        });

        Mail::assertSent(PartnerReportMail::class, function (PartnerReportMail $mail) {
            if (! $mail->hasTo('bram@example.com')) {
                return true;
            }
            $html = $mail->render();

            return str_contains($html, 'Beste Bram,') && ! str_contains($html, 'Anna');
        });
    }

    #[Test]
    public function een_tweede_mailaanvraag_van_dezelfde_deelnemer_verstuurt_nooit_een_tweede_mail(): void
    {
        Mail::fake();
        [$initiatorAccessToken] = $this->completedLink();

        $this->postJson("/api/partner-comparisons/{$initiatorAccessToken}/mail", ['email' => 'anna@example.com'])->assertOk();
        $this->postJson("/api/partner-comparisons/{$initiatorAccessToken}/mail", ['email' => 'anna@example.com'])->assertOk();

        Mail::assertSent(PartnerReportMail::class, 1);
    }

    /**
     * De initiator kan bij het aanmaken van de uitnodiging het "seintje"-vinkje aanzetten, dat het
     * adres van het eigen aanvraagformulier hergebruikt (zie Submission) — dat is zijn/haar enige
     * garantie op het rapport, want het toegangstoken/de link kan daarna nooit opnieuw opgevraagd
     * worden (zie App\Support\PartnerToken). Zodra de partner de test afrondt, moet die mail
     * automatisch verstuurd worden, zonder dat de initiator er zelf om hoeft te vragen.
     */
    #[Test]
    public function een_initiator_die_het_seintje_vinkje_aanzette_krijgt_het_rapport_automatisch_zodra_de_partner_klaar_is(): void
    {
        Mail::fake();

        $result = QuizResult::create([
            'uuid' => (string) Str::uuid(), 'answers' => ['vloer' => ['eiken']],
            'style_scores' => ['japandi' => 1], 'primary_style' => 'japandi',
        ]);
        \App\Models\Submission::create([
            'quiz_result_id' => $result->id, 'style' => 'Japandi', 'email' => 'anna@example.com',
        ]);

        $create = $this->postJson('/api/partner-links', [
            'resultUuid' => $result->uuid,
            'notifyByEmail' => true,
            'shareConfirmationTextVersion' => 'v1',
        ])->assertOk();
        $inviteToken = Str::afterLast($create->json('inviteUrl'), '/');

        Mail::assertNothingSent();

        $claim = $this->postJson("/api/partner-links/{$inviteToken}/claim")->assertOk();
        $partnerAccessToken = $claim->json('accessToken');

        $partnerResult = $this->postJson('/api/quiz-result', ['answers' => ['vloer' => ['beton']]])->assertOk();
        $this->patchJson("/api/quiz-result/{$partnerResult->json('resultUuid')}/complete-partner", [
            'partnerClaimToken' => $partnerAccessToken,
        ])->assertOk();

        Mail::assertSent(PartnerReportMail::class, function (PartnerReportMail $mail) {
            return $mail->hasTo('anna@example.com');
        });
        Mail::assertSent(PartnerReportMail::class, 1);

        $initiator = PartnerLink::first()->participants()->where('role', 'initiator')->first();
        $this->assertSame('sent', $initiator->mail_status);
    }

    #[Test]
    public function beide_deelnemers_kunnen_onafhankelijk_hun_eigen_adres_opgeven(): void
    {
        Mail::fake();
        [$initiatorAccessToken, $partnerAccessToken] = $this->completedLink();

        $this->postJson("/api/partner-comparisons/{$initiatorAccessToken}/mail", ['email' => 'anna@example.com'])->assertOk();
        $this->postJson("/api/partner-comparisons/{$partnerAccessToken}/mail", ['email' => 'bram@example.com'])->assertOk();

        Mail::assertSent(PartnerReportMail::class, 2);
    }

    /**
     * Symmetrisch met de initiator: voor de partner is het gezamenlijke resultaat juist altíjd
     * meteen klaar (ze zijn per definitie de laatste van de twee) — die krijgt het dus automatisch
     * gemaild op het moment dat de eigen aanvraag (Submission) al bekend is, zonder dat er ooit
     * expliciet om gevraagd is.
     */
    #[Test]
    public function de_partner_krijgt_het_gezamenlijke_rapport_automatisch_op_het_adres_van_het_eigen_aanvraagformulier(): void
    {
        Mail::fake();

        $result = QuizResult::create([
            'uuid' => (string) Str::uuid(), 'answers' => ['vloer' => ['eiken']],
            'style_scores' => ['japandi' => 1], 'primary_style' => 'japandi',
        ]);
        $create = $this->postJson('/api/partner-links', ['resultUuid' => $result->uuid])->assertOk();
        $inviteToken = Str::afterLast($create->json('inviteUrl'), '/');

        $claim = $this->postJson("/api/partner-links/{$inviteToken}/claim")->assertOk();
        $partnerAccessToken = $claim->json('accessToken');

        $partnerResult = $this->postJson('/api/quiz-result', ['answers' => ['vloer' => ['beton']]])->assertOk();
        \App\Models\Submission::create([
            'quiz_result_id' => \App\Models\QuizResult::where('uuid', $partnerResult->json('resultUuid'))->value('id'),
            'style' => 'Modern', 'name' => 'Bram', 'email' => 'bram@example.com',
        ]);

        $this->patchJson("/api/quiz-result/{$partnerResult->json('resultUuid')}/complete-partner", [
            'partnerClaimToken' => $partnerAccessToken,
        ])->assertOk();

        Mail::assertSent(PartnerReportMail::class, fn (PartnerReportMail $mail) => $mail->hasTo('bram@example.com'));

        $partnerParticipant = PartnerParticipant::where('access_token_hash', hash('sha256', $partnerAccessToken))->first();
        $this->assertSame('sent', $partnerParticipant->mail_status);
    }
}

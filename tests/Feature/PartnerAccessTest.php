<?php

namespace Tests\Feature;

use App\Models\AccentColor;
use App\Models\BasePalette;
use App\Models\PartnerLink;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\QuizResult;
use App\Models\QuizSetting;
use App\Models\StyleProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt de toegangsregels rond een partnerkoppeling (zie App\Support\PartnerAccessGuard): een
 * uitnodigingstoken geeft nooit leesrechten op een resultaat, en geen van beide deelnemers kan de
 * data van de ander opvragen vóórdat de koppeling daadwerkelijk voltooid is.
 */
class PartnerAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AccentColor::query()->delete();
        QuizSetting::current()->update(['partner_feature_enabled' => true]);

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

    private function makeInvite(): array
    {
        $result = QuizResult::create([
            'uuid' => (string) Str::uuid(),
            'answers' => ['vloer' => ['eiken']],
            'style_scores' => ['japandi' => 1],
            'primary_style' => 'japandi',
        ]);

        $create = $this->postJson('/api/partner-links', [
            'resultUuid' => $result->uuid,
            'shareConfirmationTextVersion' => 'v1',
        ])->assertOk();

        $inviteToken = Str::afterLast($create->json('inviteUrl'), '/');

        return [$inviteToken, $create->json('accessToken')];
    }

    #[Test]
    public function een_uitnodigingstoken_geeft_geen_toegang_tot_de_vergelijking(): void
    {
        [$inviteToken] = $this->makeInvite();

        $this->getJson("/api/partner-comparisons/{$inviteToken}")->assertStatus(404);
    }

    #[Test]
    public function voor_de_koppeling_voltooid_is_geeft_de_vergelijking_alleen_de_status_terug(): void
    {
        [$inviteToken, $initiatorAccessToken] = $this->makeInvite();

        $waitingResponse = $this->getJson("/api/partner-comparisons/{$initiatorAccessToken}")
            ->assertOk()
            ->assertJson(['status' => 'waiting', 'role' => 'initiator']);
        $this->assertNull($waitingResponse->json('facts'));

        $claim = $this->postJson("/api/partner-links/{$inviteToken}/claim")->assertOk();
        $partnerAccessToken = $claim->json('accessToken');

        $startedResponse = $this->getJson("/api/partner-comparisons/{$partnerAccessToken}")
            ->assertOk()
            ->assertJson(['status' => 'partner_started', 'role' => 'partner']);
        $this->assertNull($startedResponse->json('facts'));
    }

    #[Test]
    public function na_afronden_van_de_partnertest_zien_beide_deelnemers_dezelfde_vergelijking(): void
    {
        [$inviteToken, $initiatorAccessToken] = $this->makeInvite();

        $claim = $this->postJson("/api/partner-links/{$inviteToken}/claim")->assertOk();
        $partnerAccessToken = $claim->json('accessToken');

        $partnerResult = $this->postJson('/api/quiz-result', [
            'answers' => ['vloer' => ['beton']],
        ])->assertOk();

        $this->patchJson("/api/quiz-result/{$partnerResult->json('resultUuid')}/complete-partner", [
            'partnerClaimToken' => $partnerAccessToken,
        ])->assertOk();

        $link = PartnerLink::first();
        $this->assertSame('completed', $link->fresh()->status);

        foreach ([$initiatorAccessToken, $partnerAccessToken] as $token) {
            $response = $this->getJson("/api/partner-comparisons/{$token}")->assertOk();
            $this->assertSame('ready', $response->json('status'));
            $this->assertSame('Japandi', $response->json('initiatorStyle'));
            $this->assertSame('Modern', $response->json('partnerStyle'));
            $this->assertIsArray($response->json('facts.similarities'));
            $this->assertIsArray($response->json('facts.differences'));
        }
    }

    /**
     * Regressie: de partner_snapshot werd eerder al bevroren zodra /api/quiz-result binnenkwam —
     * ruim vóórdat de bezoeker de kans kreeg om een basispalet/accentkleur te kiezen, dus die
     * keuzes stonden nooit in de gezamenlijke uitslag (alleen de primaire stijl). Dit toetst dat de
     * daadwerkelijk gekozen kleuren er nu wél in staan, doordat completePartnerResult() pas
     * aangeroepen wordt nádat die keuzes zijn opgeslagen — zie quiz.js's showReportAndLead().
     */
    #[Test]
    public function de_gekozen_basiskleuren_en_accentkleuren_van_de_partner_komen_terug_in_de_gezamenlijke_uitslag(): void
    {
        BasePalette::create([
            'style_key' => 'modern', 'name' => 'Licht en fris',
            'description' => 'Testomschrijving', 'colors' => [['name' => 'Wit', 'hex' => '#FFFFFF']],
            'sort_order' => 10, 'is_active' => true,
        ]);
        AccentColor::create([
            'name' => 'Antraciet', 'hex' => '#2B2B2B', 'style_keys' => ['modern'],
            'sort_order' => 10, 'is_active' => true,
        ]);

        [$inviteToken] = $this->makeInvite();

        $claim = $this->postJson("/api/partner-links/{$inviteToken}/claim")->assertOk();
        $partnerAccessToken = $claim->json('accessToken');

        $partnerResult = $this->postJson('/api/quiz-result', [
            'answers' => ['vloer' => ['beton']],
        ])->assertOk();
        $resultUuid = $partnerResult->json('resultUuid');
        $paletteId = $partnerResult->json('basePaletteOptions.0.id');
        $accentColorId = $partnerResult->json('accentColorOptions.0.id');

        $this->patchJson("/api/quiz-result/{$resultUuid}/base-palette", ['basePaletteId' => $paletteId])->assertOk();
        $this->patchJson("/api/quiz-result/{$resultUuid}/accent-colors", ['accentColorIds' => [$accentColorId]])->assertOk();

        $this->patchJson("/api/quiz-result/{$resultUuid}/complete-partner", [
            'partnerClaimToken' => $partnerAccessToken,
        ])->assertOk();

        $link = PartnerLink::first()->fresh();
        $this->assertSame('Licht en fris', $link->partner_snapshot['chosen_base_palette']['name']);
        $this->assertSame('Antraciet', $link->partner_snapshot['chosen_accent_colors'][0]['name']);
    }

    #[Test]
    public function een_onbekend_toegangstoken_krijgt_altijd_een_generieke_404(): void
    {
        $this->getJson('/api/partner-comparisons/'.str_repeat('a', 64))->assertStatus(404);
    }
}

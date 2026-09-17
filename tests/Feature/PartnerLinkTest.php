<?php

namespace Tests\Feature;

use App\Models\PartnerLink;
use App\Models\QuizResult;
use App\Models\QuizSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt het aanmaken/idempotent ophalen van een partneruitnodiging (zie PartnerLinkController) —
 * los van het claimen zelf (zie PartnerClaimTest) en van de toegangsregels (zie PartnerAccessTest).
 */
class PartnerLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        QuizSetting::current()->update(['partner_feature_enabled' => true]);
    }

    private function makeQuizResult(): QuizResult
    {
        return QuizResult::create([
            'uuid' => (string) Str::uuid(),
            'answers' => ['vloer' => ['eiken']],
            'style_scores' => ['japandi' => 1],
            'primary_style' => 'japandi',
        ]);
    }

    #[Test]
    public function de_route_bestaat_niet_zolang_de_partnerfunctie_uit_staat(): void
    {
        QuizSetting::current()->update(['partner_feature_enabled' => false]);
        $result = $this->makeQuizResult();

        $this->postJson('/api/partner-links', [
            'resultUuid' => $result->uuid,
            'shareConfirmationTextVersion' => 'v1',
        ])->assertNotFound();
    }

    #[Test]
    public function een_uitnodiging_aanmaken_geeft_een_werkende_link_en_toegangstoken(): void
    {
        $result = $this->makeQuizResult();

        $response = $this->postJson('/api/partner-links', [
            'resultUuid' => $result->uuid,
            'name' => 'Anna',
            'shareConfirmationTextVersion' => 'v1',
        ])->assertOk();

        $response->assertJsonStructure(['inviteUrl', 'inviteExpiresAt', 'status', 'accessToken', 'resultUrl']);
        $this->assertNotEmpty($response->json('accessToken'));
        $this->assertStringContainsString($response->json('accessToken'), $response->json('resultUrl'));
        $this->assertSame('waiting', $response->json('status'));

        $link = PartnerLink::first();
        $this->assertNotNull($link);
        $this->assertSame($result->id, $link->initiator_quiz_result_id);
        $this->assertSame('japandi', $link->initiator_snapshot['primary_style']);
        $this->assertCount(1, $link->participants);
        $this->assertSame('initiator', $link->participants->first()->role);
    }

    /**
     * De initiator krijgt zijn/haar eigen link naar het gezamenlijke resultaat ALLEEN op het
     * moment van aanmaken — dat toegangstoken kan daarna (bewust) nooit opnieuw opgevraagd worden,
     * zie App\Support\PartnerToken. Zie PartnerReportTest voor de bijbehorende automatische mail.
     */
    #[Test]
    public function de_resultaatlink_wordt_alleen_bij_de_eerste_aanmaak_teruggegeven_nooit_bij_een_idempotente_herhaling(): void
    {
        $result = $this->makeQuizResult();

        $first = $this->postJson('/api/partner-links', [
            'resultUuid' => $result->uuid, 'shareConfirmationTextVersion' => 'v1',
        ])->assertOk();
        $this->assertNotEmpty($first->json('resultUrl'));

        $second = $this->postJson('/api/partner-links', [
            'resultUuid' => $result->uuid, 'shareConfirmationTextVersion' => 'v1',
        ])->assertOk();
        $this->assertNull($second->json('resultUrl'));
    }

    /**
     * Nooit een los e-mailadres van de client aannemen: het "seintje"-vinkje hergebruikt het adres
     * dat de bezoeker al invulde bij het eigen aanvraagformulier (zie Submission/QuizLeadController)
     * — vandaar dat dit alleen werkt als daar al een Submission voor bestaat.
     */
    #[Test]
    public function het_seintje_vinkje_hergebruikt_het_adres_uit_het_eigen_aanvraagformulier(): void
    {
        $result = $this->makeQuizResult();
        \App\Models\Submission::create([
            'quiz_result_id' => $result->id, 'style' => 'Japandi', 'email' => 'anna@example.com',
        ]);

        $this->postJson('/api/partner-links', [
            'resultUuid' => $result->uuid,
            'notifyByEmail' => true,
            'shareConfirmationTextVersion' => 'v1',
        ])->assertOk();

        $initiator = PartnerLink::first()->participants()->where('role', 'initiator')->first();
        $this->assertSame('anna@example.com', $initiator->email);
    }

    #[Test]
    public function zonder_aanvraagformulier_blijft_het_seintje_vinkje_zonder_effect(): void
    {
        $result = $this->makeQuizResult();

        $this->postJson('/api/partner-links', [
            'resultUuid' => $result->uuid,
            'notifyByEmail' => true,
            'shareConfirmationTextVersion' => 'v1',
        ])->assertOk();

        $initiator = PartnerLink::first()->participants()->where('role', 'initiator')->first();
        $this->assertNull($initiator->email);
    }

    #[Test]
    public function een_tweede_aanvraag_voor_hetzelfde_resultaat_maakt_geen_nieuwe_koppeling(): void
    {
        $result = $this->makeQuizResult();

        $first = $this->postJson('/api/partner-links', [
            'resultUuid' => $result->uuid,
            'shareConfirmationTextVersion' => 'v1',
        ])->assertOk();

        $second = $this->postJson('/api/partner-links', [
            'resultUuid' => $result->uuid,
            'shareConfirmationTextVersion' => 'v1',
        ])->assertOk();

        $this->assertSame(1, PartnerLink::count());
        $this->assertSame($first->json('inviteUrl'), $second->json('inviteUrl'));
        // De idempotente herhaling geeft bewust geen nieuw toegangstoken terug (dat zou een
        // eerder, al bewaard token in localStorage impliciet ongeldig laten lijken) — het
        // decrypten van de bewaarde link volstaat om dezelfde uitnodiging opnieuw te delen.
        $this->assertNull($second->json('accessToken'));
    }

    #[Test]
    public function een_verlopen_uitnodiging_is_niet_meer_claimbaar_en_krijgt_bij_een_nieuwe_aanvraag_een_nieuwe_link(): void
    {
        $result = $this->makeQuizResult();

        $this->postJson('/api/partner-links', [
            'resultUuid' => $result->uuid,
            'shareConfirmationTextVersion' => 'v1',
        ])->assertOk();

        $link = PartnerLink::first();
        $link->update(['invite_expires_at' => now()->subDay()]);

        $this->assertFalse($link->fresh()->isClaimable());

        $second = $this->postJson('/api/partner-links', [
            'resultUuid' => $result->uuid,
            'shareConfirmationTextVersion' => 'v1',
        ])->assertOk();

        $this->assertSame(2, PartnerLink::count());
        $this->assertNotSame($link->invite_token_hash, PartnerLink::latest('id')->first()->invite_token_hash);
    }

    #[Test]
    public function het_uitnodigingstoken_staat_nooit_als_platte_tekst_in_de_database(): void
    {
        $result = $this->makeQuizResult();

        $response = $this->postJson('/api/partner-links', [
            'resultUuid' => $result->uuid,
            'shareConfirmationTextVersion' => 'v1',
        ])->assertOk();

        $inviteUrl = $response->json('inviteUrl');
        $inviteToken = Str::afterLast($inviteUrl, '/');

        $link = PartnerLink::first();
        $this->assertNotSame($inviteToken, $link->invite_token_hash);
        $this->assertNotSame($inviteToken, $link->invite_token_encrypted);
        $this->assertSame($inviteToken, Crypt::decryptString($link->invite_token_encrypted));
    }

    #[Test]
    public function preview_claimt_nooit_en_toont_alleen_geldigheid(): void
    {
        $result = $this->makeQuizResult();

        $response = $this->postJson('/api/partner-links', [
            'resultUuid' => $result->uuid,
            'shareConfirmationTextVersion' => 'v1',
        ])->assertOk();

        $inviteToken = Str::afterLast($response->json('inviteUrl'), '/');

        $this->getJson("/api/partner-links/{$inviteToken}/preview")
            ->assertOk()
            ->assertJson(['valid' => true, 'expired' => false, 'alreadyClaimed' => false]);

        $this->assertSame(1, PartnerLink::first()->participants()->count());

        $this->getJson('/api/partner-links/onbestaand-token/preview')
            ->assertOk()
            ->assertJson(['valid' => false]);
    }

    #[Test]
    public function alleen_de_initiator_kan_de_uitnodiging_intrekken(): void
    {
        $result = $this->makeQuizResult();

        $create = $this->postJson('/api/partner-links', [
            'resultUuid' => $result->uuid, 'shareConfirmationTextVersion' => 'v1',
        ])->assertOk();
        $inviteToken = Str::afterLast($create->json('inviteUrl'), '/');
        $initiatorAccessToken = $create->json('accessToken');

        $this->patchJson("/api/partner-links/{$inviteToken}/revoke", ['accessToken' => 'onzin-token'])
            ->assertNotFound();
        $this->assertSame('waiting', PartnerLink::first()->status);

        $this->patchJson("/api/partner-links/{$inviteToken}/revoke", ['accessToken' => $initiatorAccessToken])
            ->assertOk()
            ->assertJson(['status' => 'revoked']);
        $this->assertSame('revoked', PartnerLink::first()->fresh()->status);

        $this->postJson("/api/partner-links/{$inviteToken}/claim")->assertStatus(409);
    }
}

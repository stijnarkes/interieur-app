<?php

namespace Tests\Feature;

use App\Models\PartnerLink;
use App\Models\PartnerParticipant;
use App\Models\QuizResult;
use App\Models\QuizSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt het atomaire claimmechanisme (zie PartnerLinkController::claim() en de
 * unique(['partner_link_id','role'])-constraint op partner_participants) — de daadwerkelijke
 * race-conditie-garantie tegen twee gelijktijdige claims op dezelfde uitnodiging.
 */
class PartnerClaimTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        QuizSetting::current()->update(['partner_feature_enabled' => true]);
    }

    private function makeInviteToken(): string
    {
        $result = QuizResult::create([
            'uuid' => (string) Str::uuid(),
            'answers' => ['vloer' => ['eiken']],
            'style_scores' => ['japandi' => 1],
            'primary_style' => 'japandi',
        ]);

        $response = $this->postJson('/api/partner-links', [
            'resultUuid' => $result->uuid,
            'shareConfirmationTextVersion' => 'v1',
        ])->assertOk();

        return Str::afterLast($response->json('inviteUrl'), '/');
    }

    #[Test]
    public function claimen_geeft_een_nieuw_toegangstoken_en_zet_de_link_op_partner_started(): void
    {
        $inviteToken = $this->makeInviteToken();

        $response = $this->postJson("/api/partner-links/{$inviteToken}/claim", [
            'partnerName' => 'Bram',
        ])->assertOk();

        $this->assertNotEmpty($response->json('accessToken'));

        $link = PartnerLink::first();
        $this->assertSame('partner_started', $link->status);
        $this->assertSame('Bram', $link->partner_name);
        $this->assertSame(2, $link->participants()->count());
    }

    #[Test]
    public function een_tweede_claimpoging_op_dezelfde_uitnodiging_wordt_geweigerd(): void
    {
        $inviteToken = $this->makeInviteToken();

        $this->postJson("/api/partner-links/{$inviteToken}/claim")->assertOk();

        $this->postJson("/api/partner-links/{$inviteToken}/claim")
            ->assertStatus(409);

        $this->assertSame(2, PartnerParticipant::count());
    }

    #[Test]
    public function gelijktijdige_claims_resulteren_nooit_in_twee_partnerrijen(): void
    {
        $inviteToken = $this->makeInviteToken();
        $link = PartnerLink::first();

        // Simuleert de daadwerkelijke race: twee "gelijktijdige" pogingen creëren allebei een
        // partner_participants-rij voor dezelfde rol — de db-constraint (niet een applicatie-
        // niveau-check) mag er hooguit één laten slagen.
        $succeeded = 0;
        $failed = 0;

        foreach ([1, 2] as $_) {
            try {
                $link->participants()->create([
                    'role' => PartnerParticipant::ROLE_PARTNER,
                    'access_token_hash' => \App\Support\PartnerToken::hash(\App\Support\PartnerToken::generate()),
                ]);
                $succeeded++;
            } catch (\Illuminate\Database\QueryException) {
                $failed++;
            }
        }

        $this->assertSame(1, $succeeded);
        $this->assertSame(1, $failed);
        $this->assertSame(1, PartnerParticipant::where('role', 'partner')->count());
    }

    #[Test]
    public function een_verlopen_of_ingetrokken_uitnodiging_kan_niet_geclaimd_worden(): void
    {
        $inviteToken = $this->makeInviteToken();
        $link = PartnerLink::first();
        $link->update(['status' => PartnerLink::STATUS_REVOKED, 'revoked_at' => now()]);

        $this->postJson("/api/partner-links/{$inviteToken}/claim")
            ->assertStatus(409);
    }

    #[Test]
    public function opnieuw_claimen_met_hetzelfde_bewaarde_toegangstoken_geeft_hetzelfde_token_terug(): void
    {
        $inviteToken = $this->makeInviteToken();

        $first = $this->postJson("/api/partner-links/{$inviteToken}/claim")->assertOk();
        $accessToken = $first->json('accessToken');

        $second = $this->withHeader('X-Partner-Access-Token', $accessToken)
            ->postJson("/api/partner-links/{$inviteToken}/claim")
            ->assertOk();

        $this->assertTrue($second->json('alreadyClaimed'));
        $this->assertSame($accessToken, $second->json('accessToken'));
        $this->assertSame(2, PartnerParticipant::count());
    }
}

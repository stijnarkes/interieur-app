<?php

namespace Tests\Feature;

use App\Filament\Resources\PartnerLinkResource;
use App\Models\PartnerLink;
use App\Models\PartnerParticipant;
use App\Models\QuizResult;
use App\Models\StyleProfile;
use App\Models\User;
use App\Support\PartnerToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt het admin-overzicht van partnerkoppelingen (zie PartnerLinkResource) — alleen-lezen inzicht
 * in wie de gezamenlijke test heeft gedaan, voor het team.
 */
class PartnerLinkResourceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function makePartnerLink(): PartnerLink
    {
        StyleProfile::create(['style_key' => 'japandi', 'label' => 'Japandi', 'slug' => 'japandi']);

        $result = QuizResult::create([
            'uuid' => (string) Str::uuid(), 'answers' => [], 'style_scores' => [], 'primary_style' => 'japandi',
        ]);

        $link = PartnerLink::create([
            'initiator_quiz_result_id' => $result->id,
            'initiator_snapshot' => ['primary_style' => 'japandi'],
            'initiator_name' => 'Anna',
            'invite_token_hash' => PartnerToken::hash('token'),
            'invite_token_encrypted' => encrypt('token'),
            'invite_expires_at' => now()->addDays(30),
        ]);

        $link->participants()->create([
            'role' => PartnerParticipant::ROLE_INITIATOR,
            'quiz_result_id' => $result->id,
            'access_token_hash' => PartnerToken::hash('access'),
        ]);

        return $link;
    }

    #[Test]
    public function het_overzicht_vereist_een_ingelogde_beheerder(): void
    {
        $this->get(PartnerLinkResource::getUrl('index'))->assertRedirect();
    }

    #[Test]
    public function het_overzicht_toont_de_initiator(): void
    {
        $link = $this->makePartnerLink();

        Livewire::actingAs($this->admin())
            ->test(PartnerLinkResource\Pages\ListPartnerLinks::class)
            ->assertCanSeeTableRecords([$link])
            ->assertSee('Anna');
    }

    #[Test]
    public function de_detailweergave_toont_de_koppeling(): void
    {
        $link = $this->makePartnerLink();

        Livewire::actingAs($this->admin())
            ->test(PartnerLinkResource\Pages\ViewPartnerLink::class, ['record' => $link->getKey()])
            ->assertSee('Anna');
    }
}

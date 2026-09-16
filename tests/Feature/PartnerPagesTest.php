<?php

namespace Tests\Feature;

use App\Models\QuizSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt de twee publieke, bookmarkbare partnerpagina's (zie PartnerPageController) — puur dat ze
 * bestaan/404'en volgens de feature-vlag en de juiste data-attributen meegeven; het daadwerkelijke
 * gedrag zit client-side (resources/js/partner.js) en in de JSON-routes (zie PartnerLinkTest e.a.).
 */
class PartnerPagesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function beide_paginas_bestaan_niet_zolang_de_partnerfunctie_uit_staat(): void
    {
        QuizSetting::current()->update(['partner_feature_enabled' => false]);

        $this->get('/gezamenlijk/uitnodiging/abc')->assertNotFound();
        $this->get('/gezamenlijk/abc')->assertNotFound();
    }

    #[Test]
    public function de_uitnodigingspagina_toont_het_uitnodigingstoken_als_data_attribuut(): void
    {
        QuizSetting::current()->update(['partner_feature_enabled' => true]);

        $this->get('/gezamenlijk/uitnodiging/mijn-token')
            ->assertOk()
            ->assertSee('data-invite-token="mijn-token"', false);
    }

    #[Test]
    public function de_resultaatpagina_toont_het_toegangstoken_als_data_attribuut(): void
    {
        QuizSetting::current()->update(['partner_feature_enabled' => true]);

        $this->get('/gezamenlijk/mijn-toegangstoken')
            ->assertOk()
            ->assertSee('data-access-token="mijn-toegangstoken"', false);
    }
}

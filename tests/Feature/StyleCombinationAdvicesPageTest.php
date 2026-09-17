<?php

namespace Tests\Feature;

use App\Filament\Pages\StyleCombinationAdvicesPage;
use App\Models\StyleCombinationAdvice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StyleCombinationAdvicesPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    /**
     * updateOrCreate i.p.v. create: de migratie 2026_09_17_090000_seed_style_combination_advices
     * zet dit paar (japandi/modern) al met echte redactionele tekst neer, dus deze test overschrijft
     * die rij bewust met een eigen, voorspelbare fixture i.p.v. te botsen op de unique-constraint.
     */
    private function makeAdvice(): StyleCombinationAdvice
    {
        return StyleCombinationAdvice::query()->updateOrCreate(
            ['style_key_a' => 'japandi', 'style_key_b' => 'modern'],
            [
                'title' => 'Japandi & Modern', 'intro' => 'Redactionele tekst volgt nog.',
                'basis_tip' => 'Redactionele tekst volgt nog.', 'materials_tip' => 'Redactionele tekst volgt nog.',
                'accent_tip' => 'Redactionele tekst volgt nog.', 'status' => 'concept', 'version' => 1,
            ],
        );
    }

    #[Test]
    public function een_combinatie_kan_bewerkt_worden_en_de_versie_gaat_omhoog(): void
    {
        $advice = $this->makeAdvice();

        Livewire::actingAs($this->admin())
            ->test(StyleCombinationAdvicesPage::class)
            ->callAction('editAdvice', data: [
                'title' => 'Rustig en strak',
                'intro' => 'Een mooie combinatie.',
                'basis_tip' => 'Basis-tip',
                'materials_tip' => 'Materialen-tip',
                'accent_tip' => 'Accent-tip',
                'base_palette_style_key' => 'japandi',
            ], arguments: ['adviceId' => $advice->id])
            ->assertHasNoActionErrors();

        $advice->refresh();
        $this->assertSame('Rustig en strak', $advice->title);
        $this->assertSame(2, $advice->version);
    }

    #[Test]
    public function publiceren_en_terugzetten_naar_concept_wisselt_de_status(): void
    {
        $advice = $this->makeAdvice();

        Livewire::actingAs($this->admin())
            ->test(StyleCombinationAdvicesPage::class)
            ->call('togglePublished', $advice->id);

        $this->assertSame('published', $advice->fresh()->status);

        Livewire::actingAs($this->admin())
            ->test(StyleCombinationAdvicesPage::class)
            ->call('togglePublished', $advice->id);

        $this->assertSame('concept', $advice->fresh()->status);
    }

    #[Test]
    public function de_pagina_vereist_een_ingelogde_beheerder(): void
    {
        $this->get(StyleCombinationAdvicesPage::getUrl())->assertRedirect();
    }
}

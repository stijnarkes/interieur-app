<?php

namespace Tests\Unit;

use App\Support\PartnerFactPresenter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt vooral describeSharedOptions() (via describe()): de "Wat jullie delen"-zin moet de
 * daadwerkelijk gedeelde keuzes bij naam noemen zodra die bekend zijn (optionTitles), en anders
 * netjes terugvallen op de oude, generieke zin i.p.v. een lege of halve bewering.
 */
class PartnerFactPresenterTest extends TestCase
{
    #[Test]
    public function een_gedeelde_optie_wordt_bij_naam_genoemd(): void
    {
        $text = PartnerFactPresenter::describe([
            'type' => 'shared_option_selection',
            'optionSlugs' => ['eiken'],
            'optionTitles' => ['Eiken vloer'],
        ]);

        $this->assertSame('Jullie kozen allebei voor Eiken vloer.', $text);
    }

    #[Test]
    public function twee_gedeelde_opties_worden_met_en_verbonden(): void
    {
        $text = PartnerFactPresenter::describe([
            'type' => 'shared_option_selection',
            'optionSlugs' => ['eiken', 'wit-stucwerk'],
            'optionTitles' => ['Eiken vloer', 'Wit stucwerk'],
        ]);

        $this->assertSame('Jullie kozen allebei voor Eiken vloer en Wit stucwerk.', $text);
    }

    #[Test]
    public function drie_of_meer_gedeelde_opties_krijgen_een_komma_lijst_met_en_voor_de_laatste(): void
    {
        $text = PartnerFactPresenter::describe([
            'type' => 'shared_option_selection',
            'optionSlugs' => ['eiken', 'wit-stucwerk', 'linnen-bank'],
            'optionTitles' => ['Eiken vloer', 'Wit stucwerk', 'Linnen bank'],
        ]);

        $this->assertSame('Jullie kozen allebei voor Eiken vloer, Wit stucwerk en Linnen bank.', $text);
    }

    #[Test]
    public function zonder_optionTitles_valt_de_zin_terug_op_de_generieke_tekst(): void
    {
        $text = PartnerFactPresenter::describe([
            'type' => 'shared_option_selection',
            'optionSlugs' => ['eiken'],
        ]);

        $this->assertSame('Bij minstens één vraag kozen jullie precies hetzelfde.', $text);
    }

    #[Test]
    public function resolveOptionTitles_vult_optionTitles_in_en_valt_terug_op_de_ruwe_slug(): void
    {
        $resolved = PartnerFactPresenter::resolveOptionTitles(
            [
                'similarities' => [
                    ['type' => 'shared_option_selection', 'optionSlugs' => ['eiken', 'onbekende-slug']],
                ],
                'differences' => [],
            ],
            fn (string $slug): ?string => $slug === 'eiken' ? 'Eiken vloer' : null,
        );

        $this->assertSame(
            ['Eiken vloer', 'onbekende-slug'],
            $resolved['similarities'][0]['optionTitles'],
        );
    }

    #[Test]
    public function resolveOptionTitles_laat_feiten_zonder_optionSlugs_ongemoeid(): void
    {
        $resolved = PartnerFactPresenter::resolveOptionTitles(
            [
                'similarities' => [['type' => 'accent_color_match', 'hexes' => ['#b5603c']]],
                'differences' => [],
            ],
            fn (string $slug): ?string => $slug,
        );

        $this->assertArrayNotHasKey('optionTitles', $resolved['similarities'][0]);
    }
}

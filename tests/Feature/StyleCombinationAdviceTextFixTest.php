<?php

namespace Tests\Feature;

use App\Models\StyleCombinationAdvice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regressie: vijf van de 21 combinatieteksten bevatten de bezittelijke-vorm-typefout "moderns"
 * i.p.v. "modern's" (zie 2026_09_17_110000_fix_moderns_possessive_typo_in_style_combination_advices).
 */
class StyleCombinationAdviceTextFixTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function geen_van_de_combinatieteksten_bevat_nog_de_typefout(): void
    {
        $withTypo = StyleCombinationAdvice::query()
            ->where('basis_tip', 'like', '%moderns %')
            ->orWhere('materials_tip', 'like', '%moderns %')
            ->orWhere('accent_tip', 'like', '%moderns %')
            ->count();

        $this->assertSame(0, $withTypo);
    }

    #[Test]
    public function de_bezittelijke_vorm_staat_nu_correct(): void
    {
        $advice = StyleCombinationAdvice::forPair('hotelLuxe', 'modern');

        $this->assertStringContainsString("modern's lichte", $advice->basis_tip);
    }
}

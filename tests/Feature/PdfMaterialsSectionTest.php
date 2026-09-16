<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt het conditioneel tonen van één of twee materialenborden in de PDF: alleen het bord van de
 * primaire stijl, en alléén als de bestaande resultaatlogica een tweede stijl als "invloed" heeft
 * aangemerkt (zie QuizLeadController::buildPdfContent(), 'secondaryStyle') ook dat van de
 * secundaire stijl. Rendert de blade-view rechtstreeks met een handgebouwde $result-array, zodat
 * dit los staat van de daadwerkelijke scoring/PDF-verzendflow (al apart getest).
 */
class PdfMaterialsSectionTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function baseResult(array $overrides = []): array
    {
        return array_replace_recursive([
            'resultName' => 'Jouw woonstijl: Hotel luxe',
            'description' => 'Test',
            'primaryStyle' => [
                'label' => 'Hotel luxe',
                'longDescription' => 'Test',
                'materials' => ['Fluweel', 'Marmer'],
                'materialsImage' => null,
                'materialsTip' => 'Combineer zachte en gladde materialen.',
                'recipe' => [],
                'avoid' => '',
                'furnitureAdvice' => [],
            ],
            'secondaryStyle' => null,
            'secondaryStyleLabel' => null,
            'personalPalette' => [],
            'accentColors' => [],
            'colorExplanation' => '',
            'moodboard' => [],
        ], $overrides);
    }

    #[Test]
    public function met_alleen_een_primaire_stijl_toont_de_pdf_één_materialenbord(): void
    {
        $html = view('pdf.quiz-result', ['result' => $this->baseResult()])->render();

        $this->assertStringContainsString('Materialen die passen bij jouw stijl<', $html);
        $this->assertStringNotContainsString('Materialen die passen bij jouw stijlmix', $html);
        $this->assertStringNotContainsString('class="section-title materials-style-title"', $html);
    }

    #[Test]
    public function hotel_luxe_met_landelijke_invloeden_toont_beide_materialenborden(): void
    {
        $result = $this->baseResult([
            'resultName' => 'Jouw woonstijl: Hotel luxe met Landelijk-invloeden',
            'secondaryStyle' => [
                'label' => 'Landelijk',
                'materials' => ['Grenen hout', 'Linnen'],
                'materialsImage' => null,
                'materialsTip' => 'Combineer warme, natuurlijke materialen.',
            ],
            'secondaryStyleLabel' => 'Landelijk',
        ]);

        $html = view('pdf.quiz-result', ['result' => $result])->render();

        $this->assertStringContainsString('Materialen die passen bij jouw stijlmix', $html);
        $this->assertStringContainsString('Jouw woonstijl combineert elementen van Hotel luxe met invloeden van Landelijk.', $html);
        $this->assertStringContainsString('>Hotel luxe<', $html);
        $this->assertStringContainsString('>Landelijk<', $html);
        $this->assertStringContainsString('Grenen hout', $html);
        $this->assertStringContainsString('Linnen', $html);
    }

    #[Test]
    public function japandi_met_scandinavische_invloeden_toont_beide_materialenborden(): void
    {
        $result = $this->baseResult([
            'primaryStyle' => ['label' => 'Japandi', 'materials' => ['Bamboe'], 'materialsImage' => null, 'materialsTip' => null],
            'secondaryStyle' => [
                'label' => 'Scandinavisch',
                'materials' => ['Licht hout', 'Wol'],
                'materialsImage' => null,
                'materialsTip' => null,
            ],
            'secondaryStyleLabel' => 'Scandinavisch',
        ]);

        $html = view('pdf.quiz-result', ['result' => $result])->render();

        $this->assertStringContainsString('Materialen die passen bij jouw stijlmix', $html);
        $this->assertStringContainsString('>Japandi<', $html);
        $this->assertStringContainsString('>Scandinavisch<', $html);
        $this->assertStringContainsString('Bamboe', $html);
        $this->assertStringContainsString('Licht hout', $html);
    }

    #[Test]
    public function een_secundaire_stijl_zonder_eigen_materialenbord_crasht_niet_en_valt_terug_op_één_bord(): void
    {
        $result = $this->baseResult([
            // Een secundaire stijl is aangemerkt als invloed, maar heeft nog geen materialen
            // ingevuld in Stijlprofielen — moet nooit de PDF-generatie laten crashen.
            'secondaryStyle' => [
                'label' => 'Landelijk',
                'materials' => [],
                'materialsImage' => null,
                'materialsTip' => null,
            ],
            'secondaryStyleLabel' => 'Landelijk',
        ]);

        $html = view('pdf.quiz-result', ['result' => $result])->render();

        // Het omslagblok mag de invloedsstijl nog gewoon noemen ("Past ook goed bij jou:
        // Landelijk") — dat is ongewijzigd, bestaand gedrag. Alleen het materialenblok zelf mag
        // hier nooit een tweede bord/sub-titel tonen, omdat er geen materialendata voor is.
        $this->assertStringContainsString('Materialen die passen bij jouw stijl<', $html);
        $this->assertStringNotContainsString('Materialen die passen bij jouw stijlmix', $html);
        $this->assertStringNotContainsString('class="section-title materials-style-title"', $html);
    }
}

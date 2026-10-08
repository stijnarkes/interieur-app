<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt het conditioneel tonen van één of twee materiaaladvies-blokken (naam-pillen + toelichting)
 * in de PDF: alleen dat van de primaire stijl, en alléén als de bestaande resultaatlogica een
 * tweede stijl als "invloed" heeft aangemerkt (zie QuizLeadController::buildPdfContent(),
 * 'secondaryStyle') ook dat van de secundaire stijl. Rendert de blade-view rechtstreeks met een
 * handgebouwde $result-array, zodat dit los staat van de daadwerkelijke scoring/PDF-verzendflow
 * (al apart getest).
 *
 * Toont bewust nooit meer een gefotografeerd materialenbord (zie klantfeedback: een vast bord
 * sluit niet aan op wat de bezoeker zelf koos) — zie de laatste test hieronder, die dat expliciet
 * met een echte (niet-lege) materialsImage-waarde controleert; de overige tests gebruiken toevallig
 * allemaal `materialsImage => null`, dus die bewijzen op zichzelf niet dat een gevulde waarde ook
 * onderdrukt wordt.
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
    public function met_alleen_een_primaire_stijl_toont_de_pdf_één_materiaaladviesblok(): void
    {
        $html = view('pdf.quiz-result', ['result' => $this->baseResult()])->render();

        $this->assertStringContainsString('Materiaalinspiratie bij jouw stijl<', $html);
        $this->assertStringNotContainsString('Materiaalinspiratie bij jouw stijlmix', $html);
        $this->assertStringNotContainsString('class="section-title materials-style-title"', $html);
    }

    #[Test]
    public function hotel_luxe_met_landelijke_invloeden_toont_beide_materiaaladviesblokken(): void
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

        $this->assertStringContainsString('Materiaalinspiratie bij jouw stijlmix', $html);
        $this->assertStringContainsString('Bij jou komen Hotel luxe en Landelijk mooi samen!', $html);
        $this->assertStringContainsString('>Hotel luxe<', $html);
        $this->assertStringContainsString('>Landelijk<', $html);
        $this->assertStringContainsString('Grenen hout', $html);
        $this->assertStringContainsString('Linnen', $html);
    }

    #[Test]
    public function japandi_met_scandinavische_invloeden_toont_beide_materiaaladviesblokken(): void
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

        $this->assertStringContainsString('Materiaalinspiratie bij jouw stijlmix', $html);
        $this->assertStringContainsString('>Japandi<', $html);
        $this->assertStringContainsString('>Scandinavisch<', $html);
        $this->assertStringContainsString('Bamboe', $html);
        $this->assertStringContainsString('Licht hout', $html);
    }

    #[Test]
    public function een_secundaire_stijl_zonder_eigen_materiaaladvies_crasht_niet_en_valt_terug_op_één_blok(): void
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

        // Het materialenblok zelf mag hier nooit een tweede blok/sub-titel tonen, omdat er geen
        // materialendata voor de secundaire stijl is.
        $this->assertStringContainsString('Materiaalinspiratie bij jouw stijl<', $html);
        $this->assertStringNotContainsString('Materiaalinspiratie bij jouw stijlmix', $html);
        $this->assertStringNotContainsString('class="section-title materials-style-title"', $html);
    }

    #[Test]
    public function een_gevulde_materialsimage_wordt_nooit_meer_als_afbeelding_getoond(): void
    {
        $result = $this->baseResult([
            'primaryStyle' => [
                'label' => 'Hotel luxe',
                'materials' => ['Fluweel'],
                'materialsImage' => 'materials/hotel-luxe.webp',
                'materialsTip' => 'Combineer zachte en gladde materialen.',
            ],
            'secondaryStyle' => [
                'label' => 'Landelijk',
                'materials' => ['Grenen hout'],
                'materialsImage' => 'materials/landelijk.webp',
                'materialsTip' => 'Combineer warme, natuurlijke materialen.',
            ],
            'secondaryStyleLabel' => 'Landelijk',
        ]);

        $html = view('pdf.quiz-result', ['result' => $result])->render();

        // De pillen/toelichting (algemeen materiaaladvies) blijven gewoon staan...
        $this->assertStringContainsString('Fluweel', $html);
        $this->assertStringContainsString('Grenen hout', $html);
        // ...maar er verschijnt nooit meer een <img> voor een materialenbord, ook niet als
        // materialsImage een echt pad bevat (de PDF-cover toont wel altijd het merklogo als
        // <img>, dus specifiek op de materialenbord-class/paden controleren i.p.v. op <img in het
        // algemeen).
        $this->assertStringNotContainsString('materials-board-image', $html);
        $this->assertStringNotContainsString('materials/hotel-luxe.webp', $html);
        $this->assertStringNotContainsString('materials/landelijk.webp', $html);
        // En de oude, expliciete "we laten je de materialen zien"-formulering is weg.
        $this->assertStringNotContainsString('laten we je de materialen', $html);
    }
}

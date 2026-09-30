<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt dat de PDF-cover voortaan de door QuizResultTextComposer samengestelde tekst toont
 * (inclusief de invloed-zin bij een secundaire stijl) i.p.v. de kale $primaryStyle['longDescription']
 * + een losse "Past ook goed bij jou"-regel — die twee vertelden de invloedstijl eerder dubbel.
 * Rendert de blade-view rechtstreeks (zelfde aanpak als PdfMaterialsSectionTest), los van de
 * daadwerkelijke scoring-/PDF-verzendflow.
 */
class QuizResultPdfDescriptionTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function baseResult(array $overrides = []): array
    {
        return array_replace_recursive([
            'resultName' => 'Jouw woonstijl: Hotel luxe',
            'description' => 'Basistekst van de hoofdstijl.',
            'primaryStyle' => [
                'label' => 'Hotel luxe',
                'longDescription' => 'Basistekst van de hoofdstijl.',
                'materials' => [],
                'materialsImage' => null,
                'materialsTip' => '',
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
    public function zonder_secundaire_stijl_toont_de_pdf_gewoon_de_kale_omschrijving(): void
    {
        $html = view('pdf.quiz-result', ['result' => $this->baseResult()])->render();

        $this->assertStringContainsString('Basistekst van de hoofdstijl.', $html);
        $this->assertStringNotContainsString('Past ook goed bij jou', $html);
    }

    #[Test]
    public function met_secundaire_stijl_toont_de_pdf_de_samengestelde_tekst_en_niet_meer_de_losse_regel(): void
    {
        $composed = 'Basistekst van de hoofdstijl. Ook Japandi komt in je keuzes naar voren. Natuurlijke materialen en eenvoudige vormen kunnen voor meer rust en warmte zorgen.';

        $html = view('pdf.quiz-result', ['result' => $this->baseResult([
            'description' => $composed,
            'secondaryStyleLabel' => 'Japandi',
        ])])->render();

        // De samengestelde tekst (inclusief invloed-zin) staat er precies één keer.
        $this->assertSame(1, substr_count($html, 'Ook Japandi komt in je keuzes naar voren'));
        // De oude, losse "Past ook goed bij jou"-regel is weg — anders zou Japandi twee keer
        // genoemd worden voor hetzelfde feit.
        $this->assertStringNotContainsString('Past ook goed bij jou', $html);
    }
}

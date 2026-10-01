<?php

namespace Tests\Feature;

use App\Models\Submission;
use App\Services\QuizResultPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt dat het huisstijllettertype Clarendon LT Std daadwerkelijk in de gegenereerde PDF belandt
 * (zie QuizResultPdfService::registerClarendonFont()). Rechtstreeks registreren bij dompdf i.p.v.
 * via een CSS @font-face met een url() naar een lokaal pad — dat laatste bleek dompdf's eigen
 * URL-parsing te raken en werd zonder foutmelding stilletjes genegeerd (viel terug op Arial/
 * Georgia). Deze test rendert een echte PDF en controleert of het lettertype er echt in zit, zodat
 * een toekomstige regressie (bv. een verplaatst fontbestand) hier meteen zichtbaar wordt i.p.v. pas
 * bij het oog opvallend op een geprinte PDF.
 */
class QuizResultPdfFontTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function de_gegenereerde_pdf_bevat_het_ingesloten_clarendon_lettertype(): void
    {
        Storage::fake('public');

        $submission = Submission::create([
            'style' => 'Japandi',
            'name' => 'Test',
            'email' => 'test@example.com',
            'quiz_result' => ['resultName' => 'Jouw woonstijl: Japandi', 'primaryStyle' => ['label' => 'Japandi']],
        ]);

        $path = app(QuizResultPdfService::class)->generate($submission);

        $pdfContents = Storage::disk('public')->get($path);

        $this->assertStringContainsString('ClarendonLTStd', $pdfContents, 'De PDF moet het ingesloten Clarendon-lettertype bevatten, niet (stilletjes) terugvallen op Arial/Georgia.');
    }
}

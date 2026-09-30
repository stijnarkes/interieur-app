<?php

namespace Tests\Feature;

use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt het herstelcommando quiz:regenerate-missing-pdfs — nodig geworden nadat bleek dat
 * QUIZ_PDFS_DISK standaard op de lokale, niet-persistente "public"-disk stond: elke deploy op
 * Laravel Cloud wiste daardoor alle al gegenereerde PDF's, terwijl Submission::pdf_path in de
 * database bleef staan alsof het bestand er nog was (zie SubmissionPdfController, dat dan een 404
 * teruggeeft). Dit commando genereert de PDF gewoon opnieuw uit de nog aanwezige quiz_result-data.
 */
class RegenerateMissingSubmissionPdfsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function fakeQuizResult(): array
    {
        return [
            'resultName' => 'Jouw woonstijl: Japandi',
            'description' => 'Test',
            'primaryStyle' => [
                'label' => 'Japandi',
                'longDescription' => 'Test',
                'materials' => ['Hout', 'Linnen'],
                'materialsImage' => null,
                'materialsTip' => 'Combineer natuurlijke materialen.',
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
        ];
    }

    private function makeSubmission(?string $pdfPath): Submission
    {
        return Submission::create([
            'style' => 'Japandi',
            'quiz_answers' => [],
            'quiz_result' => $this->fakeQuizResult(),
            'name' => 'Test Klant',
            'email' => 'test@example.com',
            'pdf_path' => $pdfPath,
            'result_generated' => true,
        ]);
    }

    #[Test]
    public function genereert_de_pdf_opnieuw_als_het_bestand_ontbreekt(): void
    {
        Storage::fake('public');

        $submission = $this->makeSubmission('submissions/999/quiz-result.pdf');

        $this->artisan('quiz:regenerate-missing-pdfs')->assertSuccessful();

        $submission->refresh();
        $this->assertNotNull($submission->pdf_path);
        Storage::disk('public')->assertExists($submission->pdf_path);
    }

    #[Test]
    public function genereert_ook_een_pdf_als_pdf_path_helemaal_leeg_is(): void
    {
        Storage::fake('public');

        $submission = $this->makeSubmission(null);

        $this->artisan('quiz:regenerate-missing-pdfs')->assertSuccessful();

        $submission->refresh();
        $this->assertNotNull($submission->pdf_path);
        Storage::disk('public')->assertExists($submission->pdf_path);
    }

    #[Test]
    public function laat_een_inzending_met_een_bestaande_pdf_ongemoeid(): void
    {
        Storage::fake('public');

        $submission = $this->makeSubmission('submissions/keep/quiz-result.pdf');
        Storage::disk('public')->put($submission->pdf_path, 'bestaande-inhoud');

        $this->artisan('quiz:regenerate-missing-pdfs')->assertSuccessful();

        $submission->refresh();
        $this->assertSame('submissions/keep/quiz-result.pdf', $submission->pdf_path);
        $this->assertSame('bestaande-inhoud', Storage::disk('public')->get($submission->pdf_path));
    }

    #[Test]
    public function raakt_geen_oude_inzending_zonder_quiz_result(): void
    {
        Storage::fake('public');

        $submission = Submission::create([
            'style' => 'Onbekend',
            'quiz_answers' => null,
            'quiz_result' => null,
            'pdf_path' => null,
        ]);

        $this->artisan('quiz:regenerate-missing-pdfs')->assertSuccessful();

        $this->assertNull($submission->refresh()->pdf_path);
    }

    #[Test]
    public function limit_behandelt_maar_een_deel_zodat_een_hernieuwde_run_de_rest_afwerkt(): void
    {
        Storage::fake('public');

        $eerste = $this->makeSubmission(null);
        $tweede = $this->makeSubmission(null);

        $this->artisan('quiz:regenerate-missing-pdfs', ['--limit' => 1])->assertSuccessful();

        $verwerkt = collect([$eerste->refresh(), $tweede->refresh()])->filter(fn (Submission $s) => $s->pdf_path !== null);
        $this->assertCount(1, $verwerkt, 'Met --limit=1 mag maar één van de twee inzendingen deze run een PDF krijgen.');

        // Een tweede run zonder limiet werkt de rest gewoon af — dit is precies hoe we het op
        // productie ook draaien als één run wordt afgebroken door een platformlimiet.
        $this->artisan('quiz:regenerate-missing-pdfs')->assertSuccessful();
        $this->assertNotNull($eerste->refresh()->pdf_path);
        $this->assertNotNull($tweede->refresh()->pdf_path);
    }

    #[Test]
    public function dry_run_genereert_niets(): void
    {
        Storage::fake('public');

        $submission = $this->makeSubmission(null);

        $this->artisan('quiz:regenerate-missing-pdfs', ['--dry-run' => true])->assertSuccessful();

        $this->assertNull($submission->refresh()->pdf_path);
    }
}

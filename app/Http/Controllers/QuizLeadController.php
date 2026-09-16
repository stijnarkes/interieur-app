<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateAndSendQuizResultPdfJob;
use App\Models\QuizOption;
use App\Models\QuizResult;
use App\Models\SiteContent;
use App\Models\StyleProfile;
use App\Models\Submission;
use App\Services\QuizResultPdfService;
use App\Services\QuizResultTextComposer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Verwerkt het leadformulier. Bouwt de PDF-/e-mailinhoud zelf op uit het al server-side
 * berekende QuizResult (zie QuizResultController/QuizScoringService) + StyleProfile, i.p.v. een
 * kant-en-klare resultaat-JSON van de client te vertrouwen.
 *
 * PDF-generatie + mailverzending lopen synchroon binnen deze aanvraag (via
 * GenerateAndSendQuizResultPdfJob::handle(), rechtstreeks aangeroepen i.p.v. op een wachtrij gezet
 * — er draait geen queue-worker). Dat gaf eerder een vals "verzenden mislukt" bij een trage
 * aanvraag (veel foto's ophalen/verwerken), waardoor de verbinding tussen browser en server soms
 * verbrak vóórdat het antwoord terugkwam terwijl de mail server-side alsnog aankwam. De eigenlijke
 * fix zit in PdfImageResolver: foto's worden nu server-side gecachet (zie daar), zodat vrijwel
 * elke aanvraag na de eerste keer per foto razendsnel verwerkt wordt i.p.v. steeds opnieuw alles
 * bij S3 op te halen. `email_status` 'queued' bestaat nog wel als kortstondige tussentoestand
 * (voor het geval de aanvraag halverwege afbreekt) — zie isInFlight() hieronder — maar wordt in de
 * normale flow binnen dezelfde aanvraag alweer overschreven met 'sent'/'failed' vóórdat het
 * antwoord teruggaat.
 *
 * Idempotent per quiz_result_id, maar alleen zolang een eerdere poging daadwerkelijk slaagde of nog
 * loopt: een herhaalde inzending voor hetzelfde resultaat (dubbelklik, of een bevestigde "opnieuw
 * proberen"-klik na een echte mislukking — zie resources/js/quiz/components/lead.js) start nooit
 * een tweede poging zolang `email_status` `'sent'` of (nog vers) `'queued'` is. Een poging die al
 * langer dan 2 minuten op `'queued'` staat (bv. omdat de vorige aanvraag halverwege is afgebroken)
 * wordt als vastgelopen beschouwd en mag opnieuw geprobeerd worden.
 */
class QuizLeadController extends Controller
{
    private const STALE_QUEUE_AFTER_MINUTES = 2;

    public function handle(Request $request, QuizResultTextComposer $textComposer, QuizResultPdfService $pdfService): JsonResponse
    {
        $data = $request->validate([
            'resultUuid' => 'required|uuid|exists:quiz_results,uuid',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'marketingOptIn' => 'nullable|boolean',
        ]);

        $quizResult = QuizResult::where('uuid', $data['resultUuid'])->firstOrFail();

        $existing = Submission::where('quiz_result_id', $quizResult->id)->first();
        if ($existing && $this->isInFlight($existing)) {
            return $this->responseFor($existing);
        }

        $submission = Submission::updateOrCreate(
            ['quiz_result_id' => $quizResult->id],
            [
                'style' => StyleProfile::forStyle($quizResult->primary_style)?->label ?? $quizResult->primary_style,
                'quiz_answers' => $quizResult->answers,
                'quiz_result' => $this->buildPdfContent($quizResult, $textComposer),
                'name' => $data['name'] ?? null,
                'email' => $data['email'],
                'email_opt_in' => $request->boolean('marketingOptIn', false),
                'result_id' => $quizResult->uuid,
                'result_generated' => true,
                'email_status' => 'queued',
                'email_error' => null,
            ]
        );

        $job = new GenerateAndSendQuizResultPdfJob($submission->id);

        try {
            $job->handle($pdfService);
        } catch (\Throwable $e) {
            $job->failed($e);
        }

        return $this->responseFor($submission->fresh());
    }

    /** @see class-docblock voor de "vastgelopen"-uitzondering. */
    private function isInFlight(Submission $submission): bool
    {
        if ($submission->email_status === 'sent') {
            return true;
        }

        if ($submission->email_status === 'queued') {
            return $submission->updated_at?->gt(now()->subMinutes(self::STALE_QUEUE_AFTER_MINUTES)) ?? false;
        }

        return false;
    }

    /**
     * `status` (naast de mensleesbare `message`) laat de frontend betrouwbaar vertakken op de
     * werkelijke uitkomst i.p.v. op de HTTP-statuscode. `'queued'` bevestigt bewust alleen dat de
     * aanvraag ontvangen is, nooit dat de e-mail al verstuurd is — dat weten we op dit moment
     * simpelweg nog niet.
     */
    private function responseFor(Submission $submission): JsonResponse
    {
        return match ($submission->email_status) {
            'sent' => response()->json([
                'status' => 'sent',
                'message' => 'Je advies is verstuurd naar je e-mailadres.',
            ]),
            'failed' => response()->json([
                'status' => 'failed',
                'message' => 'Je gegevens zijn opgeslagen, maar het versturen van de e-mail is niet gelukt.',
            ], 200),
            default => response()->json([
                'status' => 'queued',
                'message' => SiteContent::current()->lead_queued_body,
            ]),
        };
    }

    /**
     * Bouwt exact de vorm die resources/views/pdf/quiz-result.blade.php verwacht, volledig uit
     * server-side data (QuizResult/StyleProfile/QuizOption) — geen AI meer.
     *
     * @return array<string, mixed>
     */
    private function buildPdfContent(QuizResult $quizResult, QuizResultTextComposer $textComposer): array
    {
        $primary = $quizResult->primary_style ? StyleProfile::forStyle($quizResult->primary_style) : null;
        $secondary = $quizResult->secondary_style ? StyleProfile::forStyle($quizResult->secondary_style) : null;

        $advice = $textComposer->build($quizResult);

        return [
            'resultName' => $advice['comboName'],
            'description' => $advice['intro'],
            'primaryStyle' => $primary ? [
                'label' => $primary->label,
                'subtitle' => $primary->subtitle,
                'longDescription' => $primary->long_description,
                'traitsIntro' => $primary->traits_intro,
                'traits' => $primary->core_traits,
                'heroImage' => $primary->hero_image,
                'colorTip' => $primary->color_tip,
                'materials' => $primary->materials,
                'materialsImage' => $primary->materials_image,
                'materialsTip' => $primary->materials_tip,
                'furnitureAdvice' => $primary->furniture_shapes,
                'recipe' => $primary->recipe,
                'avoid' => implode(' ', $primary->wat_past_minder_goed ?? []),
            ] : null,
            'secondaryStyleLabel' => $secondary?->label,
            'personalPalette' => $primary?->base_colors ?? [],
            'accentColors' => $primary?->accent_colors ?? [],
            'colorExplanation' => $primary
                ? "Dit zijn de kleuren die passen bij de {$primary->label}-stijl."
                : '',
            'moodboard' => $this->moodboardFor($quizResult),
        ];
    }

    /**
     * Toont bewust alleen daadwerkelijk gekozen producten (nooit verzonnen/generieke beelden) —
     * geordend op relevantie: foto's die aan de basisstijl bijdragen eerst, dan de invloed-stijl,
     * dan de rest.
     *
     * @return array<int, array{title: string, image: string}>
     */
    private function moodboardFor(QuizResult $quizResult): array
    {
        $optionSlugs = collect($quizResult->answers)->flatten()->unique()->values()->all();
        $priority = array_values(array_filter([$quizResult->primary_style, $quizResult->secondary_style]));

        return QuizOption::query()
            ->whereIn('option_slug', $optionSlugs)
            ->get()
            ->sortBy(function (QuizOption $option) use ($priority): int {
                foreach ($priority as $rank => $styleKey) {
                    if (in_array($styleKey, $option->linkedStyleKeys(), true)) {
                        return $rank;
                    }
                }

                return count($priority);
            })
            ->map(fn (QuizOption $option): array => [
                'title' => $option->title,
                'image' => $option->publicImageUrl(),
            ])
            ->values()
            ->all();
    }
}

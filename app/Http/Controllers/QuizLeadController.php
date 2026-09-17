<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateAndSendQuizResultPdfJob;
use App\Models\QuizOption;
use App\Models\QuizResult;
use App\Models\SiteContent;
use App\Models\StyleProfile;
use App\Models\Submission;
use App\Services\PartnerLinkService;
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
 * — er draait geen queue-worker) en mogen best een paar seconden duren (PdfImageResolver cachet
 * de foto's server-side, maar dompdf's eigen verwerking van meerdere ingebedde foto's kost sowieso
 * tijd). Dat op zichzelf is geen probleem — het echte probleem was dat de verbinding tussen
 * browser en server bij zo'n iets langere aanvraag soms verbrak vóórdat het antwoord terugkwam,
 * terwijl de server intussen gewoon doorwerkte en de mail alsnog verstuurde: de bezoeker zag dan
 * ten onrechte "verzenden mislukt". Zie status() hieronder + resources/js/quiz/components/lead.js:
 * bij zo'n afgebroken verbinding vraagt de browser nu gewoon na wat er écht gebeurd is, in plaats
 * van meteen een mislukking te concluderen. `email_status` 'queued' bestaat als kortstondige
 * tussentoestand (voor het geval de aanvraag halverwege afbreekt) — zie isInFlight() hieronder —
 * en wordt in de normale flow binnen dezelfde aanvraag alweer overschreven met 'sent'/'failed'
 * vóórdat het antwoord teruggaat.
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

    public function handle(
        Request $request,
        QuizResultTextComposer $textComposer,
        QuizResultPdfService $pdfService,
        PartnerLinkService $partnerLinkService,
    ): JsonResponse {
        $data = $request->validate([
            'resultUuid' => 'required|uuid|exists:quiz_results,uuid',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'marketingOptIn' => 'nullable|boolean',
            // Alleen gezet tijdens de geïsoleerde partnertest (zie resources/js/quiz/quiz.js) —
            // laat GenerateAndSendQuizResultPdfJob weten dat dít geen "gewone" individuele
            // inzending is, zodat er nooit een (voor de partner zinloze, want al deelnemer)
            // nieuwe partneruitnodiging voor wordt aangemaakt.
            'partnerClaimToken' => 'nullable|string',
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

        $job = new GenerateAndSendQuizResultPdfJob($submission->id, $data['partnerClaimToken'] ?? null);

        try {
            $job->handle($pdfService, $partnerLinkService);
        } catch (\Throwable $e) {
            $job->failed($e);
        }

        return $this->responseFor($submission->fresh());
    }

    /**
     * Alleen-lezen statuscheck, gebruikt door lead.js wanneer de aanvraag zelf (POST hierboven)
     * geen antwoord kreeg — een trage verbinding kan verbreken vóórdat het antwoord terugkomt,
     * terwijl de server intussen gewoon doorwerkt en de mail alsnog verstuurt. In plaats van dan
     * meteen "mislukt" te concluderen, vraagt de browser hier gewoon na wat er echt gebeurd is.
     * Geen destructieve/bijwerkende actie — alleen de al bepaalde uitkomst opnieuw teruggeven.
     */
    public function status(string $resultUuid): JsonResponse
    {
        $quizResult = QuizResult::where('uuid', $resultUuid)->first();
        $submission = $quizResult ? Submission::where('quiz_result_id', $quizResult->id)->first() : null;

        if (! $submission) {
            return response()->json(['status' => 'unknown'], 404);
        }

        return $this->responseFor($submission);
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
                'colorTip' => $primary->color_tip,
                'materials' => $primary->materials,
                'materialsImage' => $primary->materials_image,
                'materialsTip' => $primary->materials_tip,
                'furnitureAdvice' => $primary->furniture_shapes,
                'recipe' => $this->personalizedRecipe($primary, $quizResult),
                'avoid' => implode(' ', $primary->wat_past_minder_goed ?? []),
            ] : null,
            'secondaryStyleLabel' => $secondary?->label,
            // Alleen de materialen-gerelateerde velden — de rest van het secundaire stijlprofiel
            // (kenmerken, meubeladvies, recept, etc.) blijft bewust ongebruikt: alleen de
            // primaire stijl bepaalt die onderdelen van de PDF. Zie quiz-result.blade.php's
            // materialenblok: toont dit board alleen als $quizResult->secondary_style ook echt
            // gezet is — exact dezelfde "invloed"-bepaling als QuizResultTextComposer gebruikt
            // voor "... met ...-invloeden" in de titel, geen aparte/nieuwe drempel.
            'secondaryStyle' => $secondary ? [
                'label' => $secondary->label,
                'materials' => $secondary->materials,
                'materialsImage' => $secondary->materials_image,
                'materialsTip' => $secondary->materials_tip,
            ] : null,
            // Toont het door de bezoeker gekozen basispalet (zie
            // QuizResultController::chooseBasePalette()) — nooit een eigen suggestie. Oudere
            // resultaten van vóór deze feature (of resultaten waarvan de stijl geen paletten had)
            // vallen terug op de vaste StyleProfile::base_colors, exact het oude gedrag; dan
            // blijft 'basePaletteName' leeg en toont de PDF de generieke titel/tekst.
            'personalPalette' => $quizResult->chosen_base_palette['colors'] ?? $primary?->base_colors ?? [],
            'basePaletteName' => $quizResult->chosen_base_palette['name'] ?? null,
            // Toont exact wat de bezoeker zelf koos bij de accentkleurstap (zie
            // QuizResultController::chooseAccentColors()) — nooit een eigen suggestie. Oudere
            // resultaten van vóór deze feature (of een bewuste "geen accentkleur"-keuze) hebben
            // geen kleuren; dan blijft dit leeg en verbergt de PDF het accentkleurenblok vanzelf
            // (zie quiz-result.blade.php).
            'accentColors' => $quizResult->chosen_accent_colors ?? [],
            'colorExplanation' => $quizResult->chosen_base_palette['description']
                ?? ($primary ? "Dit zijn de kleuren die passen bij de {$primary->label}-stijl." : ''),
            'moodboard' => $this->moodboardFor($quizResult),
        ];
    }

    /**
     * Vervangt de vaste "Basis"/"Accentkleur"-regels in het interieurrecept (zie
     * StyleProfile::recipe, altijd generieke stijladvies-tekst) door de kleuren die de bezoeker
     * zelf koos bij de basispalet-/accentkleurstap — nooit een eigen suggestie tonen naast wat al
     * écht gekozen is. Valt terug op de originele, generieke tekst voor resultaten van vóór deze
     * feature (of een stijl zonder geconfigureerde basispaletten) zodat oude PDF's niet
     * onverwacht een lege regel tonen. De overige regels (Grote meubels, Materialen,
     * Accessoires) blijven ongewijzigd — dat is en blijft generiek stijladvies.
     *
     * @return array<int, array{label: string, value: string}>
     */
    private function personalizedRecipe(StyleProfile $primary, QuizResult $quizResult): array
    {
        $chosenPaletteColors = $quizResult->chosen_base_palette['colors'] ?? null;
        $chosenAccentColors = $quizResult->chosen_accent_colors;

        return collect($primary->recipe ?? [])
            ->map(function (array $item) use ($chosenPaletteColors, $chosenAccentColors): array {
                if (($item['label'] ?? null) === 'Basis' && ! empty($chosenPaletteColors)) {
                    $item['value'] = collect($chosenPaletteColors)->pluck('name')->filter()->implode(', ');
                }

                if (($item['label'] ?? null) === 'Accentkleur' && $chosenAccentColors !== null) {
                    $item['value'] = $chosenAccentColors !== []
                        ? collect($chosenAccentColors)->pluck('name')->filter()->implode(', ')
                        : 'Geen accentkleur — bewust gekozen voor rustige basiskleuren';
                }

                return $item;
            })
            ->all();
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

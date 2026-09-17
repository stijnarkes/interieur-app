<?php

namespace App\Http\Controllers;

use App\Models\AccentColor;
use App\Models\BasePalette;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\QuizResult;
use App\Models\QuizTransitionSection;
use App\Models\SiteContent;
use App\Models\StyleProfile;
use App\Models\Submission;
use App\Services\AccentColorSelector;
use App\Services\QuizResultTextComposer;
use App\Support\QuizImageManifest;
use App\Support\QuizStructure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Alleen-lezen voorbeeldweergave voor de admin: laat precies zien hoe een vraag, overgangsscherm,
 * de resultatenpagina of de bevestigingsmail er voor een bezoeker uitziet, zonder de hele
 * klant-quiz te moeten doorlopen. Slaat nooit iets op — result()/email() bouwen een niet-
 * gepersisteerd QuizResult/Submission-object en raken QuizScoringService/QuizResultRepository
 * nooit aan (zie de nieuwe QuizPreviewControllerTest voor de garantie dat er geen rijen
 * bijkomen). Achter de bestaande `auth`-middleware, zelfde patroon als SubmissionPdfController.
 */
class QuizPreviewController extends Controller
{
    public function question(QuizQuestion $question): View
    {
        $meta = QuizStructure::question($question->question_key);

        $options = QuizOption::query()
            ->where('question_id', $question->question_key)
            ->where('is_active', true)
            ->get()
            ->filter(fn (QuizOption $option): bool => $option->linkedStyleKeys() !== [])
            ->map(fn (QuizOption $option): array => [
                'id' => $option->option_slug,
                'title' => $option->title,
                'image' => $option->publicImageUrl(),
            ])
            ->values()
            ->all();

        return view('quiz.preview.question', [
            'question' => [
                'title' => $meta['title'] ?? $question->title,
                'maxSelections' => $meta['maxSelections'] ?? $question->max_selections,
                'imageDisplayMode' => $meta['imageDisplayMode'] ?? $question->image_display_mode,
                'options' => $options,
            ],
        ]);
    }

    public function transition(string $sectionId): View
    {
        abort_unless(array_key_exists($sectionId, QuizStructure::SECTIONS), 404);

        $sectionIds = array_keys(QuizStructure::SECTIONS);
        $stored = QuizTransitionSection::forSection($sectionId);
        $fallback = QuizStructure::SECTIONS[$sectionId];

        return view('quiz.preview.transition', [
            'section' => [
                'title' => $stored?->title ?? $fallback['title'],
                'tagline' => $stored?->tagline ?? '',
                'wrapUp' => $stored?->wrap_up,
                'cta' => $stored?->cta ?? 'Beginnen',
                'image' => QuizImageManifest::publicUrlForPath("images/interior/transitions/{$sectionId}.webp"),
            ],
            'sectionIndex' => array_search($sectionId, $sectionIds, true),
            'totalSections' => count($sectionIds),
        ]);
    }

    public function result(
        Request $request,
        QuizResultTextComposer $textComposer,
        AccentColorSelector $accentColorSelector,
    ): View {
        $styles = StyleProfile::query()->orderBy('id')->get();

        [$primary, $secondary] = $this->resolveChosenStyles($request, $styles);

        $advice = $this->buildAdvice($textComposer, $primary, $secondary);

        $accentColorOptions = $accentColorSelector
            ->forResult($primary?->style_key, $secondary?->style_key)
            ->map(fn (AccentColor $color): array => $color->toOptionArray())
            ->all();

        // Nooit gemengd met de secundaire stijl — zelfde regel als QuizResultController::store().
        $basePaletteOptions = $primary
            ? BasePalette::query()->active()->forStyle($primary->style_key)
                ->get()
                ->map(fn (BasePalette $palette): array => $palette->toOptionArray())
                ->all()
            : [];

        return view('quiz.preview.result', [
            'styles' => $styles,
            'selectedPrimaryKey' => $primary?->style_key,
            'selectedSecondaryKey' => $secondary?->style_key,
            'result' => [
                'comboName' => $advice['comboName'],
                'intro' => $advice['intro'],
                'primaryStyle' => $primary ? $this->styleForFrontend($primary) : null,
                'secondaryStyle' => $secondary ? $this->styleForFrontend($secondary) : null,
                'basePaletteOptions' => $basePaletteOptions,
                'accentColorOptions' => $accentColorOptions,
            ],
        ]);
    }

    public function email(Request $request, QuizResultTextComposer $textComposer): View
    {
        $styles = StyleProfile::query()->orderBy('id')->get();

        [$primary, $secondary] = $this->resolveChosenStyles($request, $styles);

        $advice = $this->buildAdvice($textComposer, $primary, $secondary);

        $submission = new Submission([
            'name' => 'Voorbeeldnaam',
            'quiz_result' => [
                'resultName' => $advice['comboName'],
                'description' => $advice['intro'],
            ],
        ]);

        $emailHtml = view('emails.quiz-result', [
            'siteContent' => SiteContent::current(),
            'submission' => $submission,
            // Laat het admin-voorbeeld ook de partnerblok-opmaak zien zodra de functie aanstaat —
            // een neppe, nooit-echt-werkende link, puur voor de weergave (zie GenerateAndSendQuizResultPdfJob
            // voor de echte, automatische aanmaak).
            'partnerInviteUrl' => \App\Models\QuizSetting::current()->partner_feature_enabled
                ? url('/gezamenlijk/uitnodiging/voorbeeld')
                : null,
        ])->render();

        return view('quiz.preview.email', [
            'styles' => $styles,
            'selectedPrimaryKey' => $primary?->style_key,
            'selectedSecondaryKey' => $secondary?->style_key,
            'emailHtml' => $emailHtml,
        ]);
    }

    /** @return array{comboName: string, intro: string} */
    private function buildAdvice(QuizResultTextComposer $textComposer, ?StyleProfile $primary, ?StyleProfile $secondary): array
    {
        // Nooit opgeslagen (geen ->save()) — puur een drager van de twee velden die
        // QuizResultTextComposer nodig heeft, exact zoals QuizResultController::store() ze ook
        // gebruikt. Raakt QuizScoringService/QuizResultRepository niet aan.
        $result = new QuizResult([
            'primary_style' => $primary?->style_key,
            'secondary_style' => $secondary?->style_key,
        ]);

        return $textComposer->build($result);
    }

    /** @return array{key: string, label: string, slug: ?string, colors: array} */
    private function styleForFrontend(StyleProfile $profile): array
    {
        return [
            'key' => $profile->style_key,
            'label' => $profile->label,
            'slug' => $profile->slug,
            'colors' => $profile->base_colors ?? [],
        ];
    }

    /**
     * Leest `?style=`/`?secondary=` (style_key), valt terug op de eerste stijl als er niets (of
     * iets ongeldigs) gekozen is — zodat een voorbeeldpagina altijd een resultaat toont, ook bij
     * het allereerste bezoek zonder querystring.
     *
     * @param  \Illuminate\Support\Collection<int, StyleProfile>  $styles
     * @return array{0: ?StyleProfile, 1: ?StyleProfile}
     */
    private function resolveChosenStyles(Request $request, \Illuminate\Support\Collection $styles): array
    {
        $primary = $styles->firstWhere('style_key', $request->query('style')) ?? $styles->first();

        $secondaryKey = $request->query('secondary');
        $secondary = $secondaryKey ? $styles->firstWhere('style_key', $secondaryKey) : null;

        return [$primary, $secondary];
    }
}

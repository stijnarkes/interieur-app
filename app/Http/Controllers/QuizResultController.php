<?php

namespace App\Http\Controllers;

use App\Models\AccentColor;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\QuizResult;
use App\Models\StyleProfile;
use App\Repositories\QuizResultRepository;
use App\Services\AccentColorSelector;
use App\Services\QuizResultTextComposer;
use App\Services\QuizScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Berekent en bewaart het quizresultaat server-side, direct na het afronden van de test — dus
 * vóór het leadformulier. Dit sluit het gat waarbij de client eerder alles zelf berekende en de
 * server dat blind vertrouwde (zie QuizLeadController). Geeft bewust nooit ruwe punten/scores
 * terug; alleen de labels/tekst die de resultatenpagina nodig heeft.
 */
class QuizResultController extends Controller
{
    public function store(
        Request $request,
        QuizScoringService $scoring,
        QuizResultRepository $repository,
        QuizResultTextComposer $textComposer,
        AccentColorSelector $accentColorSelector,
    ): JsonResponse {
        $data = $request->validate([
            'answers' => 'required|array',
            'answers.*' => 'array',
            'answers.*.*' => 'string',
        ]);

        $answers = $this->onlyValidAnswers($data['answers']);

        $computed = $scoring->compute($answers);
        $result = $repository->store($answers, $computed);

        $advice = $textComposer->build($result);

        $styleLabel = function (?string $key) {
            if (! $key) {
                return null;
            }

            $profile = StyleProfile::forStyle($key);

            return [
                'key' => $key,
                'label' => $profile?->label ?? $key,
                'slug' => $profile?->slug,
                'heroImage' => $profile?->hero_image,
                'colors' => $profile?->base_colors ?? [],
            ];
        };

        $accentColorOptions = $accentColorSelector
            ->forResult($result->primary_style, $result->secondary_style)
            ->map(fn (AccentColor $color): array => $color->toOptionArray())
            ->all();

        return response()->json([
            'resultUuid' => $result->uuid,
            'comboName' => $advice['comboName'],
            'intro' => $advice['intro'],
            'primaryStyle' => $styleLabel($result->primary_style),
            'secondaryStyle' => $styleLabel($result->secondary_style),
            'accentColorOptions' => $accentColorOptions,
        ]);
    }

    /**
     * Slaat de door de bezoeker gekozen accentkleuren op bij het al berekende resultaat — een
     * losse stap ná store(), zodat de stijlberekening zelf nooit opnieuw hoeft te draaien. Alleen
     * ID's die daadwerkelijk in de (server-side herberekende) toegestane set voor dít resultaat
     * zitten worden geaccepteerd — zelfde anti-manipulatiepatroon als onlyValidAnswers().
     */
    public function chooseAccentColors(
        Request $request,
        string $uuid,
        QuizResultRepository $repository,
        AccentColorSelector $accentColorSelector,
    ): JsonResponse {
        $data = $request->validate([
            'accentColorIds' => 'required|array|min:1|max:2',
            'accentColorIds.*' => ['integer', Rule::exists('accent_colors', 'id')],
        ]);

        $result = QuizResult::where('uuid', $uuid)->firstOrFail();

        $allowed = $accentColorSelector->forResult($result->primary_style, $result->secondary_style);

        $chosen = $allowed
            ->filter(fn (AccentColor $color) => in_array($color->id, $data['accentColorIds'], true))
            ->map(fn (AccentColor $color): array => $color->toOptionArray())
            ->values();

        if ($chosen->isEmpty()) {
            return response()->json([
                'message' => 'Geen van de gekozen kleuren is (meer) geldig voor dit resultaat.',
            ], 422);
        }

        $repository->saveAccentColors($result, $chosen->all());

        return response()->json([
            'accentColors' => $chosen->all(),
        ]);
    }

    /**
     * Laat alleen option_slugs door die daadwerkelijk bij de opgegeven vraag horen — voorkomt dat
     * een gemanipuleerd verzoek punten aan een willekeurige stijl toekent via een option_slug die
     * bij een andere vraag hoort.
     *
     * @param  array<string, array<int, string>>  $answers
     * @return array<string, array<int, string>>
     */
    private function onlyValidAnswers(array $answers): array
    {
        $questionKeys = QuizQuestion::query()->pluck('question_key')->all();

        $validated = [];

        foreach ($answers as $questionId => $optionIds) {
            if (! in_array($questionId, $questionKeys, true)) {
                continue;
            }

            $validOptionSlugs = QuizOption::query()
                ->where('question_id', $questionId)
                ->whereIn('option_slug', (array) $optionIds)
                ->pluck('option_slug')
                ->all();

            if ($validOptionSlugs !== []) {
                $validated[$questionId] = $validOptionSlugs;
            }
        }

        return $validated;
    }
}

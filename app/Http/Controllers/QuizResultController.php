<?php

namespace App\Http\Controllers;

use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\StyleProfile;
use App\Repositories\QuizResultRepository;
use App\Services\AI\QuizAdviceFallback;
use App\Services\AI\QuizAdviceGenerator;
use App\Services\QuizScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Berekent en bewaart het quizresultaat server-side, direct na het afronden van de test — dus
 * vóór het leadformulier. Dit sluit het gat waarbij de client eerder alles zelf berekende en de
 * server dat blind vertrouwde (zie QuizLeadController). Geeft bewust nooit ruwe percentages of
 * puntentotalen terug; alleen de labels/tekst die de resultatenpagina nodig heeft.
 */
class QuizResultController extends Controller
{
    public function store(
        Request $request,
        QuizScoringService $scoring,
        QuizResultRepository $repository,
        QuizAdviceFallback $fallback,
        QuizAdviceGenerator $generator,
    ): JsonResponse {
        $data = $request->validate([
            'answers' => 'required|array',
            'answers.*' => 'array',
            'answers.*.*' => 'string',
        ]);

        $answers = $this->onlyValidAnswers($data['answers']);

        $computed = $scoring->compute($answers);
        $result = $repository->store($answers, $computed);

        $advice = $generator->generate($result, 'short_result');

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

        return response()->json([
            'resultUuid' => $result->uuid,
            'comboName' => $advice['comboName'],
            'intro' => $advice['intro'],
            'primaryStyle' => $styleLabel($result->primary_style),
            'secondaryStyle' => $styleLabel($result->secondary_style),
            'tertiaryStyle' => $styleLabel($result->tertiary_style),
            'keywordChips' => $fallback->keywordChips($result),
            'case' => $result->case,
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

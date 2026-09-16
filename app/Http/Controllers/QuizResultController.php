<?php

namespace App\Http\Controllers;

use App\Models\AccentColor;
use App\Models\BasePalette;
use App\Models\PartnerEvent;
use App\Models\PartnerLink;
use App\Models\PartnerParticipant;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\QuizResult;
use App\Models\StyleProfile;
use App\Repositories\QuizResultRepository;
use App\Services\AccentColorSelector;
use App\Services\PartnerComparisonService;
use App\Services\QuizResultTextComposer;
use App\Services\QuizScoringService;
use App\Support\PartnerAccessGuard;
use App\Support\PartnerSnapshotBuilder;
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
        PartnerComparisonService $partnerComparisonService,
    ): JsonResponse {
        $data = $request->validate([
            'answers' => 'required|array',
            'answers.*' => 'array',
            'answers.*.*' => 'string',
            'partnerClaimToken' => 'nullable|string',
        ]);

        $answers = $this->onlyValidAnswers($data['answers']);

        $computed = $scoring->compute($answers);
        $result = $repository->store($answers, $computed);

        if (! empty($data['partnerClaimToken'])) {
            $this->linkPartnerParticipant($data['partnerClaimToken'], $result, $partnerComparisonService);
        }

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
                'colors' => $profile?->base_colors ?? [],
            ];
        };

        $accentColorOptions = $accentColorSelector
            ->forResult($result->primary_style, $result->secondary_style)
            ->map(fn (AccentColor $color): array => $color->toOptionArray())
            ->all();

        // Nooit gemengd met de secundaire stijl (in tegenstelling tot accentkleuren): elke stijl
        // heeft zijn eigen, bewust samengestelde basispaletten — zie het implementatieplan
        // "Basispaletten + vernieuwde accentkleuren".
        $basePaletteOptions = $result->primary_style
            ? BasePalette::query()->active()->forStyle($result->primary_style)
                ->get()
                ->map(fn (BasePalette $palette): array => $palette->toOptionArray())
                ->all()
            : [];

        return response()->json([
            'resultUuid' => $result->uuid,
            'comboName' => $advice['comboName'],
            'intro' => $advice['intro'],
            'primaryStyle' => $styleLabel($result->primary_style),
            'secondaryStyle' => $styleLabel($result->secondary_style),
            'basePaletteOptions' => $basePaletteOptions,
            'accentColorOptions' => $accentColorOptions,
        ]);
    }

    /**
     * Slaat het door de bezoeker gekozen basispalet op — een losse stap ná store(), analoog aan
     * chooseAccentColors() hieronder. Alleen een palet dat daadwerkelijk (nog) actief bij de
     * primaire stijl van dít resultaat hoort wordt geaccepteerd — nooit de client vertrouwen.
     */
    public function chooseBasePalette(Request $request, string $uuid, QuizResultRepository $repository): JsonResponse
    {
        $data = $request->validate([
            'basePaletteId' => ['required', 'integer', Rule::exists('base_palettes', 'id')],
        ]);

        $result = QuizResult::where('uuid', $uuid)->firstOrFail();

        $palette = $result->primary_style
            ? BasePalette::query()->active()->forStyle($result->primary_style)->find($data['basePaletteId'])
            : null;

        if (! $palette) {
            return response()->json([
                'message' => 'Dit basispalet is niet (meer) geldig voor dit resultaat.',
            ], 422);
        }

        $repository->saveBasePalette($result, $palette->toOptionArray());

        return response()->json([
            'basePalette' => $palette->toOptionArray(),
        ]);
    }

    /**
     * Slaat de door de bezoeker gekozen accentkleuren op bij het al berekende resultaat — een
     * losse stap ná store(), zodat de stijlberekening zelf nooit opnieuw hoeft te draaien. Alleen
     * ID's die daadwerkelijk in de (server-side herberekende) toegestane set voor dít resultaat
     * zitten worden geaccepteerd — zelfde anti-manipulatiepatroon als onlyValidAnswers(). Een
     * lege lijst is een geldige, bewuste keuze ("Ik houd het liever bij rustige basiskleuren") —
     * geen minimum meer, in tegenstelling tot vóór de basispaletten-feature.
     */
    public function chooseAccentColors(
        Request $request,
        string $uuid,
        QuizResultRepository $repository,
        AccentColorSelector $accentColorSelector,
    ): JsonResponse {
        $data = $request->validate([
            'accentColorIds' => ['present', 'array', 'max:2'],
            'accentColorIds.*' => ['integer', Rule::exists('accent_colors', 'id')],
        ]);

        $result = QuizResult::where('uuid', $uuid)->firstOrFail();

        if ($data['accentColorIds'] === []) {
            $repository->saveAccentColors($result, []);

            return response()->json(['accentColors' => []]);
        }

        // Een kleur die exact de hex van een kleur uit het al gekozen basispalet deelt telt niet
        // meer als geldige, aparte accentkeuze ("Zit al in je basis") — zelfde controle als de
        // klant-quiz zelf al doet (zie accentColorStep.js), hier herhaald omdat de client nooit
        // vertrouwd wordt.
        $baseHexes = collect($result->chosen_base_palette['colors'] ?? [])
            ->pluck('hex')
            ->filter()
            ->map(fn (string $hex): string => strtolower($hex))
            ->all();

        $allowed = $accentColorSelector->forResult($result->primary_style, $result->secondary_style);

        $chosen = $allowed
            ->filter(fn (AccentColor $color) => in_array($color->id, $data['accentColorIds'], true))
            ->reject(fn (AccentColor $color) => in_array(strtolower($color->hex), $baseHexes, true))
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
     * Koppelt een net afgeronde, geïsoleerde partnertest aan de bijbehorende
     * partner_participants-rij (aangemaakt bij PartnerLinkController::claim()) en start meteen,
     * synchroon, de vergelijking — zelfde synchrone patroon als PDF/mail in QuizLeadController,
     * geen queue nodig. Een ongeldig/onbekend/reeds-gebruikt token wordt bewust stilzwijgend
     * genegeerd: de individuele test van déze bezoeker is en blijft dan gewoon geldig, alleen
     * zonder partnerkoppeling — nooit de hele quizinzending laten mislukken op een kapot token.
     */
    private function linkPartnerParticipant(
        string $partnerClaimToken,
        QuizResult $result,
        PartnerComparisonService $partnerComparisonService,
    ): void {
        $participant = PartnerAccessGuard::resolve($partnerClaimToken);

        if (! $participant || $participant->role !== PartnerParticipant::ROLE_PARTNER || $participant->quiz_result_id !== null) {
            return;
        }

        $link = $participant->partnerLink;

        if (! $link || $link->status === PartnerLink::STATUS_REVOKED) {
            return;
        }

        $participant->update(['quiz_result_id' => $result->id]);

        $link->update([
            'partner_quiz_result_id' => $result->id,
            'partner_snapshot' => PartnerSnapshotBuilder::build($result),
            'status' => PartnerLink::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        $partnerComparisonService->compareAndStore($link->fresh());

        PartnerEvent::record('partner_completed', $link->id);
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

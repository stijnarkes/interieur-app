<?php

namespace App\Http\Controllers;

use App\Mail\QuizResultMail;
use App\Models\QuizOption;
use App\Models\QuizResult;
use App\Models\StyleProfile;
use App\Models\Submission;
use App\Services\QuizResultTextComposer;
use App\Services\QuizResultPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Verwerkt het leadformulier. Bouwt de PDF-/e-mailinhoud zelf op uit het al server-side
 * berekende QuizResult (zie QuizResultController/QuizScoringService) + StyleProfile, i.p.v. een
 * kant-en-klare resultaat-JSON van de client te vertrouwen.
 *
 * Idempotent per quiz_result_id: een herhaalde inzending voor hetzelfde resultaat (dubbelklik, of
 * een retry na een onterechte "verzenden mislukt"-melding — zie resources/js/quiz/components/
 * lead.js) genereert nooit een tweede PDF of e-mail, maar geeft gewoon de eerder bepaalde
 * uitkomst opnieuw terug.
 */
class QuizLeadController extends Controller
{
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
        if ($existing) {
            return $this->responseFor($existing);
        }

        $submission = Submission::create([
            'quiz_result_id' => $quizResult->id,
            'style' => StyleProfile::forStyle($quizResult->primary_style)?->label ?? $quizResult->primary_style,
            'quiz_answers' => $quizResult->answers,
            'quiz_result' => $this->buildPdfContent($quizResult, $textComposer),
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'email_opt_in' => $request->boolean('marketingOptIn', false),
            'result_id' => $quizResult->uuid,
            'result_generated' => true,
        ]);

        try {
            $pdfPath = $pdfService->generate($submission);
            $submission->update(['pdf_path' => $pdfPath]);

            Mail::to($submission->email)->send(new QuizResultMail($submission, $pdfPath));

            $submission->update(['email_status' => 'sent', 'email_sent_at' => now()]);
        } catch (\Throwable $e) {
            $submission->update(['email_status' => 'failed', 'email_error' => $e->getMessage()]);
        }

        return $this->responseFor($submission->fresh());
    }

    private function responseFor(Submission $submission): JsonResponse
    {
        if ($submission->email_status === 'failed') {
            return response()->json([
                'message' => 'Je gegevens zijn opgeslagen, maar het versturen van de e-mail is niet gelukt.',
            ], 200);
        }

        return response()->json(['message' => 'Je advies is verstuurd naar je e-mailadres.']);
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
                'materialsTip' => $primary->materials_tip,
                'furnitureAdvice' => $primary->furniture_shapes,
                'recipe' => $primary->recipe,
                'avoid' => implode(' ', $primary->wat_past_minder_goed ?? []),
            ] : null,
            'secondaryStyleLabel' => $secondary?->label,
            'personalPalette' => $primary?->base_colors ?? [],
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

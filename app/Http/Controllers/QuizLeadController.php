<?php

namespace App\Http\Controllers;

use App\Mail\QuizResultMail;
use App\Models\QuizOption;
use App\Models\QuizResult;
use App\Models\StyleProfile;
use App\Models\Submission;
use App\Services\AI\QuizAdviceGenerator;
use App\Services\QuizResultPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Verwerkt het leadformulier. Bouwt de PDF-/e-mailinhoud voortaan zelf op uit het al server-side
 * berekende QuizResult (zie QuizResultController/QuizScoringService) + StyleProfile, i.p.v. een
 * kant-en-klare resultaat-JSON van de client te vertrouwen — dat laatste liet een bezoeker in
 * theorie zelf bepalen welke stijl/inhoud in z'n eigen PDF terechtkwam.
 */
class QuizLeadController extends Controller
{
    public function handle(Request $request, QuizAdviceGenerator $generator): JsonResponse
    {
        $data = $request->validate([
            'resultUuid' => 'required|uuid|exists:quiz_results,uuid',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'marketingOptIn' => 'nullable|boolean',
        ]);

        $quizResult = QuizResult::where('uuid', $data['resultUuid'])->firstOrFail();

        $submission = Submission::create([
            'quiz_result_id' => $quizResult->id,
            'style' => StyleProfile::forStyle($quizResult->primary_style)?->label ?? $quizResult->primary_style,
            'quiz_answers' => $quizResult->answers,
            'quiz_result' => $this->buildPdfContent($quizResult, $generator),
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'email_opt_in' => $request->boolean('marketingOptIn', false),
            'result_id' => $quizResult->uuid,
            'result_generated' => true,
        ]);

        try {
            $pdfPath = (new QuizResultPdfService)->generate($submission);
            $submission->update(['pdf_path' => $pdfPath]);

            Mail::to($submission->email)->send(new QuizResultMail($submission, $pdfPath));

            $submission->update(['email_status' => 'sent', 'email_sent_at' => now()]);
        } catch (\Throwable $e) {
            $submission->update(['email_status' => 'failed', 'email_error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Je gegevens zijn opgeslagen, maar het versturen van de e-mail is niet gelukt.',
            ], 200);
        }

        return response()->json(['message' => 'Je advies is verstuurd naar je e-mailadres.']);
    }

    /**
     * Bouwt exact de vorm die resources/views/pdf/quiz-result.blade.php verwacht, maar dan
     * volledig uit server-side data (QuizResult/StyleProfile/QuizOption) i.p.v. client-JSON.
     *
     * @return array<string, mixed>
     */
    private function buildPdfContent(QuizResult $quizResult, QuizAdviceGenerator $generator): array
    {
        $primary = $quizResult->primary_style ? StyleProfile::forStyle($quizResult->primary_style) : null;
        $secondary = $quizResult->secondary_style ? StyleProfile::forStyle($quizResult->secondary_style) : null;

        $advice = $generator->generate($quizResult, 'pdf_full');

        return [
            'resultName' => $advice['comboName'],
            'description' => $advice['intro'],
            // roomAdvice wordt hier al meegegeven zodat een toekomstige PDF-vernieuwing (het
            // "Zo komt jouw stijl terug in huis"-blok) ook voor nu al verstuurde inzendingen
            // beschikbaar is, zonder dat oude Submissions opnieuw gegenereerd hoeven te worden.
            'roomAdvice' => $advice['roomAdvice'] ?? null,
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
                ? "Dit kleurenpalet is opgebouwd rond de tinten die passen bij jouw {$primary->label}-stijl."
                : '',
            'moodboard' => $this->moodboardFor($quizResult),
        ];
    }

    /**
     * Toont bewust alleen daadwerkelijk gekozen producten (nooit verzonnen/generieke beelden) —
     * geordend op relevantie: foto's die aan de primaire stijl bijdragen eerst, dan secundair, dan
     * tertiair, dan de rest. Zo krijgt de basis-stijl de meeste visuele nadruk in het moodboard
     * zonder dat er favoritisme in de puntentelling zelf zit.
     *
     * @return array<int, array{title: string, image: string}>
     */
    private function moodboardFor(QuizResult $quizResult): array
    {
        $optionSlugs = collect($quizResult->answers)->flatten()->unique()->values()->all();
        $priority = array_values(array_filter([$quizResult->primary_style, $quizResult->secondary_style, $quizResult->tertiary_style]));

        return QuizOption::query()
            ->whereIn('option_slug', $optionSlugs)
            ->with('styleLinks')
            ->get()
            ->sortBy(function (QuizOption $option) use ($priority): int {
                $optionStyles = $option->styleKeys();

                foreach ($priority as $rank => $styleKey) {
                    if (in_array($styleKey, $optionStyles, true)) {
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

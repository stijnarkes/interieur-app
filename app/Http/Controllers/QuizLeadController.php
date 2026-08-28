<?php

namespace App\Http\Controllers;

use App\Mail\QuizResultMail;
use App\Models\Submission;
use App\Services\QuizResultPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class QuizLeadController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        // Afbeeldingspaden mogen alleen naar de eigen, publieke interieur-fotomap wijzen — dit
        // voorkomt dat iemand hier een willekeurig bestandspad (bv. met "../") in stopt, dat later
        // door de PDF-generator van de schijf gelezen zou worden (padtraversal).
        $imagePathRule = 'regex:/^\/images\/interior\/[a-zA-Z0-9\/_-]+\.(webp|jpe?g|png)$/';

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'marketingOptIn' => 'nullable|boolean',
            'resultName' => 'required|string|max:255',
            'description' => 'nullable|string',
            'topStyles' => 'nullable|array',
            'traits' => 'nullable|array',
            // Elk subveld van primaryStyle heeft hier een eigen regel nodig — Laravel's
            // validate() laat anders alléén de expliciet genoemde sleutels door en gooit de
            // rest van de array stilzwijgend weg (dit was de oorzaak van een bug waarbij de
            // PDF/e-mail alleen "materials" ontving en al het andere kwijtraakte).
            'primaryStyle' => 'nullable|array',
            'primaryStyle.label' => 'nullable|string|max:255',
            'primaryStyle.subtitle' => 'nullable|string|max:255',
            'primaryStyle.longDescription' => 'nullable|string',
            'primaryStyle.traitsIntro' => 'nullable|string',
            'primaryStyle.traits' => 'nullable|array',
            'primaryStyle.traits.*' => 'nullable|string|max:255',
            'primaryStyle.heroImage' => ['nullable', 'string', $imagePathRule],
            'primaryStyle.colorTip' => 'nullable|string',
            'primaryStyle.materials' => 'nullable|array',
            'primaryStyle.materials.*.name' => 'nullable|string|max:255',
            'primaryStyle.materials.*.image' => ['nullable', 'string', $imagePathRule],
            'primaryStyle.materialsTip' => 'nullable|string',
            'primaryStyle.furnitureAdvice' => 'nullable|array',
            'primaryStyle.furnitureAdvice.intro' => 'nullable|string',
            'primaryStyle.furnitureAdvice.items' => 'nullable|array',
            'primaryStyle.furnitureAdvice.items.*' => 'nullable|string|max:255',
            'primaryStyle.recipe' => 'nullable|array',
            'primaryStyle.recipe.*.label' => 'nullable|string|max:255',
            'primaryStyle.recipe.*.value' => 'nullable|string|max:500',
            'primaryStyle.avoid' => 'nullable|string',
            'secondaryStyleLabel' => 'nullable|string|max:255',
            'colorExplanation' => 'nullable|string|max:1000',
            'personalPalette' => 'nullable|array',
            'personalPalette.*.name' => 'nullable|string|max:255',
            'personalPalette.*.hex' => 'nullable|string|regex:/^#[0-9a-fA-F]{3,8}$/',
            'personalPalette.*.role' => 'nullable|string|max:255',
            'moodboard' => 'nullable|array',
            'moodboard.*.title' => 'nullable|string|max:255',
            'moodboard.*.image' => ['nullable', 'string', $imagePathRule],
            'answers' => 'nullable|array',
        ]);

        $topStyleLabel = $data['topStyles'][0]['label'] ?? $data['resultName'];

        $submission = Submission::create([
            'style' => $topStyleLabel,
            'quiz_answers' => $data['answers'] ?? [],
            'quiz_result' => [
                'resultName' => $data['resultName'],
                'description' => $data['description'] ?? '',
                'topStyles' => $data['topStyles'] ?? [],
                'traits' => $data['traits'] ?? [],
                // Rijke inhoud voor de PDF/e-mail, zodat die exact aansluit bij de
                // resultatenpagina — zie QuizResultPdfService/pdf/quiz-result.blade.php.
                'primaryStyle' => $data['primaryStyle'] ?? null,
                'secondaryStyleLabel' => $data['secondaryStyleLabel'] ?? null,
                'colorExplanation' => $data['colorExplanation'] ?? null,
                'personalPalette' => $data['personalPalette'] ?? [],
                'moodboard' => $data['moodboard'] ?? [],
            ],
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'email_opt_in' => $request->boolean('marketingOptIn', false),
            'result_id' => (string) Str::uuid(),
            'result_generated' => true,
        ]);

        try {
            $pdfPath = (new QuizResultPdfService)->generate($submission);
            $submission->update(['pdf_path' => "submissions/{$submission->id}/quiz-result.pdf"]);

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
}

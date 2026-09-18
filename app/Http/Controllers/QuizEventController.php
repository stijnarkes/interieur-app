<?php

namespace App\Http\Controllers;

use App\Models\QuizEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Neemt alleen de twee trechterstappen aan die géén natuurlijk serverraakpunt hebben — "gestart"
 * en "vraag bereikt" gebeuren puur client-side (de quiz beantwoordt vragen zonder server-round-trip
 * per vraag, zie resources/js/quiz/state.js). "quiz_completed"/"lead_submitted" worden bewust NOOIT
 * hier aangenomen: die registreert de server zelf, rechtstreeks vanuit QuizResultController::
 * store()/QuizLeadController::handle(), op het moment dat ze al daadwerkelijk gebeuren — nooit een
 * client vertrouwen voor een gebeurtenis die de server net zo makkelijk zelf, betrouwbaar, kan
 * vaststellen.
 */
class QuizEventController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', Rule::in([QuizEvent::STARTED, QuizEvent::QUESTION_REACHED])],
            'questionKey' => ['nullable', 'string', Rule::exists('quiz_questions', 'question_key')],
        ]);

        QuizEvent::record($data['name'], $data['questionKey'] ?? null);

        return response()->json(['ok' => true]);
    }
}

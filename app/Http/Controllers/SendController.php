<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SendController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $request->validate([
            'resultId'       => 'required|string',
            'name'           => 'nullable|string',
            'email'          => 'required|email',
            'marketingOptIn' => 'boolean',
        ]);

        $submission = Submission::where('result_id', $request->input('resultId'))->first();

        if ($submission) {
            $submission->update([
                'name'         => $request->input('name'),
                'email'        => $request->input('email'),
                'email_opt_in' => $request->boolean('marketingOptIn', false),
            ]);
        }

        // TODO: verstuur het interieuradvies per e-mail
        // Gebruik bijv. Laravel Mail: Mail::to($request->email)->send(new AdviceMail(...))

        return response()->json(['message' => 'Gegevens opgeslagen. E-mail wordt zo verstuurd.']);
    }
}

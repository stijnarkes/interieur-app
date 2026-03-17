<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateInteriorAdviceJob;
use App\Models\Generation;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GenerateController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $request->validate([
            'style'          => 'required|string',
            'moodWords'      => 'nullable|string',
            'colors'         => 'nullable|string',
            'name'           => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'marketingOptIn' => 'nullable|boolean',
            'imageBase64'    => 'nullable|string',
        ]);

        $style       = $request->input('style');
        $moodWords   = $request->input('moodWords') ?? '';
        $colors      = $request->input('colors') ?? '';
        $imageBase64 = $request->input('imageBase64') ?? '';

        // Submission aanmaken voor admin/leads
        $submission = Submission::create([
            'style'        => $style,
            'mood_words'   => $moodWords ?: null,
            'colors'       => $colors ?: null,
            'name'         => $request->input('name') ?: null,
            'email'        => $request->input('email') ?: null,
            'email_opt_in' => $request->boolean('marketingOptIn', false),
        ]);

        // Uploadfoto direct opslaan; base64 wordt niet meegegeven aan de job
        $roomPhotoPath = null;
        if ($imageBase64) {
            $roomPhotoPath = "submissions/{$submission->id}/room_photo.jpg";
            Storage::disk('public')->put($roomPhotoPath, base64_decode($imageBase64));
            $submission->update(['has_room_photo' => true, 'room_photo_path' => $roomPhotoPath]);
        }

        // Generation record aanmaken met alle input
        $generation = Generation::create([
            'status'        => 'queued',
            'submission_id' => $submission->id,
            'input'         => [
                'style'         => $style,
                'moodWords'     => $moodWords,
                'colors'        => $colors,
                'roomPhotoPath' => $roomPhotoPath, // job leest afbeelding van schijf
            ],
        ]);

        // Job dispatchen — keert direct terug, blokkeert de server niet
        GenerateInteriorAdviceJob::dispatch($generation->id);

        return response()->json([
            'generationId' => $generation->id,
            'status'       => 'queued',
        ], 202);
    }
}

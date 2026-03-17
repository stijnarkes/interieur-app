<?php

namespace App\Http\Controllers;

use App\Models\Generation;
use Illuminate\Http\JsonResponse;

class GenerationStatusController extends Controller
{
    public function show(Generation $generation): JsonResponse
    {
        if ($generation->isFailed()) {
            return response()->json([
                'id'     => $generation->id,
                'status' => 'failed',
                'error'  => $generation->error ?? 'Er is een onbekende fout opgetreden.',
            ]);
        }

        if (! $generation->isCompleted()) {
            return response()->json([
                'id'     => $generation->id,
                'status' => $generation->status, // queued | processing
            ]);
        }

        // Voltooid: geef het volledige resultaat terug
        return response()->json(array_merge($generation->result ?? [], [
            'id'     => $generation->id,
            'status' => 'completed',
        ]));
    }
}

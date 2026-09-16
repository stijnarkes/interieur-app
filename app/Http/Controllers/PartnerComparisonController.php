<?php

namespace App\Http\Controllers;

use App\Models\PartnerComparison;
use App\Models\PartnerLink;
use App\Models\StyleProfile;
use App\Support\PartnerAccessGuard;
use Illuminate\Http\JsonResponse;

/**
 * Toont het gezamenlijke resultaat aan een geldige deelnemer — zie het implementatieplan,
 * sectie "Tokens en toegang": geeft nooit resultaatinhoud van de ándere rol terug vóór de
 * koppeling voltooid is, en het toegangstoken van de aanvrager bepaalt uitsluitend readonly-
 * toegang tot precies déze koppeling.
 */
class PartnerComparisonController extends Controller
{
    public function show(string $accessToken): JsonResponse
    {
        $participant = PartnerAccessGuard::resolve($accessToken);

        if (! $participant) {
            return response()->json(['message' => 'Niet gevonden.'], 404);
        }

        $link = $participant->partnerLink;

        if ($link->status !== PartnerLink::STATUS_COMPLETED) {
            return response()->json([
                'status' => $link->status,
                'role' => $participant->role,
            ]);
        }

        $comparison = PartnerComparison::where('partner_link_id', $link->id)->first();

        if (! $comparison || $comparison->status !== PartnerComparison::STATUS_READY) {
            return response()->json([
                'status' => 'processing',
                'role' => $participant->role,
            ]);
        }

        $styleLabel = fn (?string $key) => $key ? (StyleProfile::forStyle($key)?->label ?? $key) : null;

        return response()->json([
            'status' => 'ready',
            'role' => $participant->role,
            'initiatorName' => $link->initiator_name,
            'partnerName' => $link->partner_name,
            'initiatorStyle' => $styleLabel($link->initiator_snapshot['primary_style'] ?? null),
            'partnerStyle' => $styleLabel($link->partner_snapshot['primary_style'] ?? null),
            'initiatorPalette' => $link->initiator_snapshot['chosen_base_palette'] ?? null,
            'partnerPalette' => $link->partner_snapshot['chosen_base_palette'] ?? null,
            'initiatorAccentColors' => $link->initiator_snapshot['chosen_accent_colors'] ?? [],
            'partnerAccentColors' => $link->partner_snapshot['chosen_accent_colors'] ?? [],
            'facts' => $comparison->facts,
            'suggestions' => $comparison->suggestions,
        ]);
    }
}

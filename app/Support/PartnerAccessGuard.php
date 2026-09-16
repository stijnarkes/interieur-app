<?php

namespace App\Support;

use App\Models\PartnerParticipant;

/**
 * Enige plek die een binnenkomend toegangstoken vertaalt naar een PartnerParticipant-rij — nooit
 * de database-primaire-sleutel als toegangsbewijs gebruiken (zie het implementatieplan, sectie
 * "Tokens en toegang"). Geeft bewust nooit resultaatinhoud van de ándere rol terug vóór de
 * koppeling voltooid is — zie requireRole()/requireCompletedLink() op de aanroepende controllers.
 */
class PartnerAccessGuard
{
    public static function resolve(string $accessToken): ?PartnerParticipant
    {
        return PartnerParticipant::with('partnerLink')
            ->where('access_token_hash', PartnerToken::hash($accessToken))
            ->first();
    }
}

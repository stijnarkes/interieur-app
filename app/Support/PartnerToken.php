<?php

namespace App\Support;

/**
 * Tokenlogica voor de partnerfunctie (zie het implementatieplan, sectie "Tokens en toegang").
 * Elk token is 256 bits (ruim boven de gevraagde 128), en de database bewaart nooit de plaintext
 * van een toegangstoken — alleen de hash. Het uitnodigingstoken is de enige uitzondering: díe
 * wordt óók versleuteld bewaard (niet gehasht), omdat de initiator dezelfde link idempotent
 * opnieuw moet kunnen opvragen (zie PartnerLinkController).
 */
class PartnerToken
{
    public static function generate(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function hash(string $plaintext): string
    {
        return hash('sha256', $plaintext);
    }
}

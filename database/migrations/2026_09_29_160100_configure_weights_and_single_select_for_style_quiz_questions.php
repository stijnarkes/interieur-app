<?php

use App\Models\QuizQuestion;
use Illuminate\Database\Migrations\Migration;

/**
 * Zie de opdracht "scoring woonstijltest Boer Staphorst": V5 (keuken) en V9 (badkamer) wegen 1,5
 * mee in de uitslag, de andere zeven vragen 1,0 — QuizQuestion::weight bestond al (voorheen alleen
 * gebruikt om punten over meerdere gekozen opties te verdelen, zie de oude QuizScoringService),
 * dus geen nieuwe kolom nodig.
 *
 * Selectieaantallen: tegel en behang staan op max_selections = 1, meubelstof en verlichting op 2
 * (twee kiezen is toegestaan, niet verplicht — zie QuizScoringService voor hoe twee gekozen opties
 * per stijl worden gemiddeld vóór het vraaggewicht wordt toegepast), de overige vijf op 1. Eerst
 * kortstondig alle negen op 1 gezet (zie de eerdere versie van deze migratie, vóór de correctie in
 * het uitvoeringsverslag) — dat bleek voor meubelstof/verlichting niet de bedoeling.
 *
 * Zoekt op question_key (stabiel, uniek) i.p.v. op titel/volgorde — bestand tegen een latere
 * titelwijziging in de admin.
 */
return new class extends Migration
{
    private const WEIGHTED_QUESTION_KEYS = [
        'welke-keuken-spreekt-jou-het-meeste-aan-hecR6',
        'welke-badkamer-spreekt-jou-het-meest-aan-E17aH',
    ];

    private const TWO_SELECTIONS_ALLOWED_QUESTION_KEYS = [
        'sofaMaterial',
        'lighting',
    ];

    private const SINGLE_SELECTION_QUESTION_KEYS = [
        'welke-tegel-spreekt-jou-het-meeste-aan-GhJat',
        'wallColor',
        'sofaModel',
        'welke-keuken-spreekt-jou-het-meeste-aan-hecR6',
        'welk-servies-spreekt-jou-het-meest-aan-W3569',
        'welke-eethoek-zou-jij-kiezen-y50IU',
        'welke-badkamer-spreekt-jou-het-meest-aan-E17aH',
    ];

    private const STYLE_QUIZ_QUESTION_KEYS = [
        ...self::SINGLE_SELECTION_QUESTION_KEYS,
        ...self::TWO_SELECTIONS_ALLOWED_QUESTION_KEYS,
    ];

    public function up(): void
    {
        QuizQuestion::query()
            ->whereIn('question_key', self::SINGLE_SELECTION_QUESTION_KEYS)
            ->update(['max_selections' => 1]);

        QuizQuestion::query()
            ->whereIn('question_key', self::TWO_SELECTIONS_ALLOWED_QUESTION_KEYS)
            ->update(['max_selections' => 2]);

        QuizQuestion::query()
            ->whereIn('question_key', self::WEIGHTED_QUESTION_KEYS)
            ->update(['weight' => 1.5]);

        QuizQuestion::query()
            ->whereIn('question_key', self::STYLE_QUIZ_QUESTION_KEYS)
            ->whereNotIn('question_key', self::WEIGHTED_QUESTION_KEYS)
            ->update(['weight' => 1.0]);
    }

    public function down(): void
    {
        // Bewust geen terugdraai-logica: dit stelt de negen bestaande vragen in op de waarden die
        // de opdracht vereist, geen tijdelijke/experimentele wijziging.
    }
};

<?php

use App\Models\QuizQuestion;
use Illuminate\Database\Migrations\Migration;

/**
 * Zie de opdracht "scoring woonstijltest Boer Staphorst": V5 (keuken) en V9 (badkamer) wegen 1,5
 * mee in de uitslag, de andere zeven vragen 1,0 — QuizQuestion::weight bestond al (voorheen alleen
 * gebruikt om punten over meerdere gekozen opties te verdelen, zie de oude QuizScoringService),
 * dus geen nieuwe kolom nodig.
 *
 * Geconstateerde afwijking t.o.v. de opdracht (gemeld in het uitvoeringsverslag): vier van de negen
 * vragen (tegel, behang, meubelstof, verlichting) stonden in de admin op max_selections = 2, terwijl
 * de hele uitslagberekening (per vraag precies één gekozen score, vergeleken met het gemiddelde/de
 * spreiding van de andere opties in diezelfde vraag) uitgaat van exact één gekozen optie per vraag —
 * zie de opdrachttekst "Per beantwoorde vraag wordt exact één optie gekozen." Zet daarom alle negen
 * vragen expliciet op max_selections = 1.
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

    private const STYLE_QUIZ_QUESTION_KEYS = [
        'welke-tegel-spreekt-jou-het-meeste-aan-GhJat',
        'wallColor',
        'sofaMaterial',
        'sofaModel',
        'welke-keuken-spreekt-jou-het-meeste-aan-hecR6',
        'welk-servies-spreekt-jou-het-meest-aan-W3569',
        'welke-eethoek-zou-jij-kiezen-y50IU',
        'lighting',
        'welke-badkamer-spreekt-jou-het-meest-aan-E17aH',
    ];

    public function up(): void
    {
        QuizQuestion::query()
            ->whereIn('question_key', self::STYLE_QUIZ_QUESTION_KEYS)
            ->update(['max_selections' => 1]);

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

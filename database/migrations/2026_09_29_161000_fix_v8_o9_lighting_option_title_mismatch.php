<?php

use App\Models\QuizOption;
use Illuminate\Database\Migrations\Migration;

/**
 * Titel/afbeelding-mismatch gevonden tijdens de handmatige verificatie voor de opdracht "scoring
 * woonstijltest Boer Staphorst": deze verlichtingsoptie (V8 O9) heette "Hanglamp Tivoli, zand",
 * maar de daadwerkelijke afbeelding toont een zwarte lineaire railspot (zie de brontabel: "Zwarte
 * lineaire railspot", Modern 1,00). Corrigeert alleen het zichtbare label — afbeelding, slug en
 * scorekoppeling (QuizAnswerScoreMatrix, gesleuteld op option_slug) blijven ongewijzigd.
 *
 * Conditionele where-update op de exacte, nog foute titel (i.p.v. altijd overschrijven) zodat een
 * admin die dit label intussen zelf al gecorrigeerd heeft nooit overschreven wordt — zelfde
 * voorzichtige patroon als eerdere content-correcties in dit traject.
 */
return new class extends Migration
{
    private const OPTION_SLUG = 'lighting-hanglamp-tivoli-zand-ycz8O';

    private const OLD_TITLE = 'Hanglamp Tivoli, zand';

    private const NEW_TITLE = 'Zwarte railspot';

    public function up(): void
    {
        QuizOption::query()
            ->where('option_slug', self::OPTION_SLUG)
            ->where('title', self::OLD_TITLE)
            ->update(['title' => self::NEW_TITLE]);
    }

    public function down(): void
    {
        QuizOption::query()
            ->where('option_slug', self::OPTION_SLUG)
            ->where('title', self::NEW_TITLE)
            ->update(['title' => self::OLD_TITLE]);
    }
};

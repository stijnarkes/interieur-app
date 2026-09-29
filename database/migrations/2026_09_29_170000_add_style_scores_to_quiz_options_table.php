<?php

use App\Models\QuizOption;
use App\Support\QuizAnswerScoreMatrix;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Verplaatst de 0-1-matchscores per stijl van code (QuizAnswerScoreMatrix, zie de opdracht "scoring
 * woonstijltest Boer Staphorst") naar de database, zodat een beheerder ze zelf kan bekijken en
 * aanpassen in de admin (zie QuizOptionsPage) i.p.v. dat alleen een ontwikkelaar de code kan
 * wijzigen. QuizAnswerScoreMatrix blijft bestaan als de geverifieerde brondata om de 60 echte
 * opties hieronder mee te backfillen — QuizScoringService leest vanaf nu style_scores uit de
 * database, niet meer de matrixklasse.
 *
 * Nullable: een optie zonder style_scores (nieuw aangemaakt, nog niet ingevuld) draagt bewust 0 bij
 * aan elke stijl (zie QuizScoringService), net als een onbekende option_slug dat voorheen in de
 * matrix deed — geen crash, geen verzonnen score.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_options', function (Blueprint $table) {
            $table->json('style_scores')->nullable()->after('style_keys');
        });

        foreach (QuizAnswerScoreMatrix::all() as $optionSlug => $scores) {
            QuizOption::query()->where('option_slug', $optionSlug)->update(['style_scores' => $scores]);
        }
    }

    public function down(): void
    {
        Schema::table('quiz_options', function (Blueprint $table) {
            $table->dropColumn('style_scores');
        });
    }
};

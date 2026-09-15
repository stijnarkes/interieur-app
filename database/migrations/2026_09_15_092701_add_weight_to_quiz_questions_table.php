<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "vraag.gewicht" — hoeveel punten een vraag in totaal te verdelen heeft over de gekozen
 * optie(s), zie QuizScoringService::compute(). Default 1 zodat bestaand gedrag (alle vragen
 * wegen impliciet gelijk) ongewijzigd blijft totdat een admin dit bewust aanpast.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->unsignedTinyInteger('weight')->default(1)->after('max_selections');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropColumn('weight');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nieuwe uitslagberekening (zie QuizAnswerScoreMatrix/QuizScoringService, opdracht "scoring
 * woonstijltest") kiest de hoofdstijl op basis van een genormaliseerde uitslagScore i.p.v. de ruwe
 * som — de oude secondary_influence_ratio (percentage van de ruwe basisscore, non-destructief
 * hier nog aanwezig maar niet meer gebruikt) past niet meer op die schaal. In plaats daarvan: de
 * op-één-na-hoogste stijl wordt als "invloed" getoond als het (absolute) verschil in uitslagScore
 * met de hoofdstijl hooguit deze instelbare grens is, ván boven de 0 uitslagScore uitkomt, én op
 * minstens 2 afzonderlijke vragen boven haar eigen vraaggemiddelde scoorde.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_settings', function (Blueprint $table) {
            $table->decimal('secondary_influence_max_gap', 5, 2)->default(0.5);
        });
    }

    public function down(): void
    {
        Schema::table('quiz_settings', function (Blueprint $table) {
            $table->dropColumn('secondary_influence_max_gap');
        });
    }
};

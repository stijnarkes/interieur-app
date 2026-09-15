<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vervangt de 3 oude "clear_winner/close_pair/close_triple"-marges (die met de vereenvoudigde
 * scoring niet meer gebruikt worden, maar non-destructief blijven staan) door één instelbare
 * drempel: hoeveel procent van de basisscore de op-één-na-hoogste stijl minstens moet halen om
 * als "invloed" getoond te worden — zie QuizScoringService::determineResult().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('secondary_influence_ratio')->default(70);
        });
    }

    public function down(): void
    {
        Schema::table('quiz_settings', function (Blueprint $table) {
            $table->dropColumn('secondary_influence_ratio');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * De vereenvoudigde scoring vergelijkt ruwe punten rechtstreeks (zie QuizScoringService) en heeft
 * geen percentage-van-totaal-berekening meer nodig — style_percentages wordt niet meer gevuld.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_results', function (Blueprint $table) {
            $table->json('style_percentages')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('quiz_results', function (Blueprint $table) {
            $table->json('style_percentages')->nullable(false)->change();
        });
    }
};

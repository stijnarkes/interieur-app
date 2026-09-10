<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hoeveel antwoordopties een bezoeker bij deze vraag tegelijk mag kiezen — default 1 (huidig,
 * single-select gedrag) zodat bestaande vragen ongewijzigd blijven werken. Zie QuizOptionsPage
 * voor het admin-veld en resources/js/quiz/state.js's toggleAnswer() voor het front-end gedrag.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_selections')->default(1)->after('folder');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropColumn('max_selections');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Eén-rij-tabel voor quiz-brede instellingen die niet bij een specifieke vraag/optie horen. Nu
 * alleen `color_preference_max_selections`: de kleurvoorkeur-vraag staat (bewust) niet in
 * quiz_questions — die wordt hardcoded als eerste vraag toegevoegd (zie data.js/remoteConfig.js)
 * omdat 'ie zich fundamenteel anders gedraagt (sfeerpaletten i.p.v. losse antwoordopties) — dus
 * kan zijn maximum-aantal-keuzes niet gewoon op een `quiz_questions`-rij meeliften. Zie
 * QuizSetting::current() en QuizPalettesPage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('color_preference_max_selections')->default(1);
            $table->timestamps();
        });

        DB::table('quiz_settings')->insert([
            'color_preference_max_selections' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_settings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * quiz_settings werd volledig verwijderd door 2026_09_10_104026_drop_quiz_color_preference_tables
 * (samen met de toen ook afgeschafte sfeerpaletten-instelling) — de tabel bestaat dus op dit punt
 * in de migratiegeschiedenis niet meer, en moet hier opnieuw worden aangemaakt (niet gewijzigd).
 * Nieuwe inhoud: de 3 procentpunt-marges die QuizScoringService::determineRanking() gebruikt om
 * een duidelijke winnaar / twee bijna-gelijke stijlen / drie bijna-gelijke stijlen / een
 * tegenstrijdige verdeling te onderscheiden — admin-instelbaar, zie het implementatieplan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('primary_dominant_margin')->default(15);
            $table->unsignedTinyInteger('close_pair_margin')->default(8);
            $table->unsignedTinyInteger('close_triple_margin')->default(5);
            $table->timestamps();
        });

        DB::table('quiz_settings')->insert([
            'primary_dominant_margin' => 15,
            'close_pair_margin' => 8,
            'close_triple_margin' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_settings');
    }
};

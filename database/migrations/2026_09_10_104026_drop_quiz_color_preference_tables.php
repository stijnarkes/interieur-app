<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * De kleurvoorkeur-vraag (sfeerpaletten) is uit de quiz verwijderd — bezoekers vonden het lastig
 * om zelf een kleurensfeer te kiezen, en de vraag telde toch al niet mee in de woonstijlscore.
 * Het resultaat toont voortaan gewoon het vaste kleurenpalet van de winnende woonstijl
 * (styleProfiles.js) in plaats van een zelf samengesteld persoonlijk palet. Daarmee vervallen ook
 * de tabellen die uitsluitend voor deze vraag bestonden. Zie QuizAnswerFormatter voor hoe oudere
 * inzendingen (die nog een colorPreference-antwoord bevatten) leesbaar blijven zonder deze
 * tabellen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('quiz_palette_colors');
        Schema::dropIfExists('quiz_palettes');
        Schema::dropIfExists('quiz_settings');
    }

    public function down(): void
    {
        // Bewust geen down(): de kleurvoorkeur-vraag en haar admin-scherm zijn definitief
        // verwijderd (zie QuizPalettesPage/colorQuestionStep.js/paletteEngine.js, ook
        // verwijderd) — hier teruggaan zou lege tabellen zonder bijbehorende UI achterlaten.
    }
};

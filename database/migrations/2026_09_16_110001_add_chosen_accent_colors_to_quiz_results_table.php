<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bewaart de 1-2 accentkleuren die de bezoeker zelf koos ná de stijlberekening — gedenormaliseerd
 * ({id,name,hex}) zodat een latere naams-/hexwijziging of verwijdering in de accent_colors-catalogus
 * een al opgeslagen resultaat/PDF nooit met terugwerkende kracht verandert. Nullable: bestaande
 * resultaten van vóór deze feature hebben geen keuze en blijven gewoon werken (PDF toont dan alleen
 * de basiskleuren, zie QuizLeadController::buildPdfContent()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_results', function (Blueprint $table) {
            $table->json('chosen_accent_colors')->nullable()->after('secondary_style');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_results', function (Blueprint $table) {
            $table->dropColumn('chosen_accent_colors');
        });
    }
};

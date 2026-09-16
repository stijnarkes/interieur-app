<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bewaart het door de bezoeker gekozen basispalet gedenormaliseerd (id/naam/omschrijving/kleuren)
 * op het resultaat zelf — zelfde redenering als chosen_accent_colors: de basispaletten-catalogus
 * mag later wijzigen zonder een al gegenereerde PDF/e-mail met terugwerkende kracht te veranderen.
 * Nullable: resultaten van vóór deze feature (en resultaten waarvan de stijl geen paletten heeft)
 * hebben er nooit één.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_results', function (Blueprint $table) {
            $table->json('chosen_base_palette')->nullable()->after('chosen_accent_colors');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_results', function (Blueprint $table) {
            $table->dropColumn('chosen_base_palette');
        });
    }
};

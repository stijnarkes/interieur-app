<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vervangt de net ingevoerde hoofd-/tweede-stijl-beperking (primary_style + secondary_style) weer
 * door een vrije lijst: een optie mag bij zoveel stijlen passen als de styliste aanvinkt, zonder
 * limiet van 2. `primary_style`/`secondary_style` blijven non-destructief bestaan als fallback
 * (QuizOption::linkedStyleKeys() leest deze kolom alleen als style_keys nog leeg is) — bestaande
 * opties die nog nooit via het nieuwe aanvink-formulier bewerkt zijn, blijven zo gewoon werken.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_options', function (Blueprint $table) {
            $table->json('style_keys')->nullable()->after('secondary_style');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_options', function (Blueprint $table) {
            $table->dropColumn('style_keys');
        });
    }
};

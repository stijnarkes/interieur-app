<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per vraag instelbaar of antwoordoptiefoto's als "contain" (volledig zichtbaar, met rand —
 * huidig gedrag, zie .option-image img in app.css) of "cover" (tegel vullend, bijgesneden)
 * getoond worden. Default 'contain' zodat bestaande vragen ongewijzigd blijven ogen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->string('image_display_mode')->default('contain')->after('max_selections');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropColumn('image_display_mode');
        });
    }
};

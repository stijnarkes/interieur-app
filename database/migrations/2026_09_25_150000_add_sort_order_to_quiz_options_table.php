<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Antwoordopties werden tot nu toe altijd op aanmaakvolgorde (id) getoond — nu admin-beheerbaar
 * via slepen op de Antwoordopties-pagina (zie QuizOptionsPage::reorderOptions()), net als de
 * vragen zelf al konden. Backfill zet de huidige, al zichtbare id-volgorde vast als startpunt, dus
 * de volgorde verandert voor niemand zichtbaar totdat er voor het eerst gesleept wordt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_options', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('question_id');
        });

        $optionsByQuestion = DB::table('quiz_options')->orderBy('id')->get()->groupBy('question_id');

        foreach ($optionsByQuestion as $options) {
            foreach ($options->values() as $index => $option) {
                DB::table('quiz_options')->where('id', $option->id)->update(['sort_order' => ($index + 1) * 10]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('quiz_options', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};

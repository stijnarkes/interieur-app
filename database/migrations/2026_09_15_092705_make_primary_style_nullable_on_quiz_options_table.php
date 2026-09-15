<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `primary_style` stond sinds het ontstaan van deze tabel op NOT NULL, maar de opdracht
 * "vereenvoudiging woonstijltest" vereist expliciet dat een optie zonder stijlkoppeling kan
 * bestaan ("verzin niet automatisch een stijl voor ontbrekende opties"). Normaliseert ook een
 * eventuele lege string (het oude "geen stijl"-equivalent onder de NOT NULL-constraint) naar
 * echte NULL, zodat `whereNotNull('primary_style')` (zie QuizConfigController) betrouwbaar is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_options', function (Blueprint $table) {
            $table->string('primary_style')->nullable()->change();
        });

        DB::table('quiz_options')->where('primary_style', '')->update(['primary_style' => null]);
    }

    public function down(): void
    {
        DB::table('quiz_options')->whereNull('primary_style')->update(['primary_style' => '']);

        Schema::table('quiz_options', function (Blueprint $table) {
            $table->string('primary_style')->nullable(false)->change();
        });
    }
};

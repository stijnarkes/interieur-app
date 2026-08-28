<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_options', function (Blueprint $table) {
            // Alleen gezet voor door de admin zelf toegevoegde extra keuzes (zie
            // QuizOptionResource's "Nieuwe optie"-actie) — bestaande, geseede opties laten dit
            // leeg en blijven hun afbeelding via de vaste map/stijl-slug-conventie ophalen
            // (zie QuizOption::resolvedImagePath()), zodat al geüploade foto's blijven werken.
            $table->string('image_path')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_options', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};

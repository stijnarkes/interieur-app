<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QuizQuestion::weight was tot nu toe altijd een geheel getal (unsignedTinyInteger, 1-10) — de
 * opdracht "scoring woonstijltest Boer Staphorst" vraagt om een gewicht van 1,5 voor de keuken-/
 * badkamervraag (zie de volgende migratie), dus deze kolom moet decimalen kunnen bevatten. Verruimt
 * naar decimal(3,1) unsigned (bv. 1.0, 1.5, 10.0) i.p.v. een nieuwe, aparte kolom — één kolom voor
 * vraaggewicht blijft de enige bron van waarheid (zie QuizScoringService), geen tweede,
 * verwarrende plek om een gewicht in te stellen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->decimal('weight', 3, 1)->unsigned()->default(1.0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->unsignedTinyInteger('weight')->default(1)->change();
        });
    }
};

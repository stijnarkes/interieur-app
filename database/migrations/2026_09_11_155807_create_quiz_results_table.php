<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Servergeberekend quizresultaat, los van Submission: een resultaat moet al bestaan en getoond
 * worden vóórdat een bezoeker het leadformulier invult, terwijl Submission juist die leadcapture
 * (naam/e-mail/PDF-verzending) vertegenwoordigt. Submission verwijst hierna naar een quiz_results-rij
 * i.p.v. dat de client een kant-en-klare resultaat-JSON aanlevert die de server blind opslaat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_results', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->json('answers');
            $table->json('style_scores');
            $table->json('style_percentages');
            $table->string('primary_style')->nullable();
            $table->string('secondary_style')->nullable();
            $table->string('tertiary_style')->nullable();
            $table->string('primary_strength')->nullable();
            $table->string('secondary_strength')->nullable();
            $table->string('tertiary_strength')->nullable();
            $table->string('case')->nullable();
            $table->json('dominant_traits')->nullable();
            $table->json('room_profiles')->nullable();
            $table->string('color_preference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_results');
    }
};

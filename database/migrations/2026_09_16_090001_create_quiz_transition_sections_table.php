<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Precies 2 vaste rijen (materials-colors/objects, zie QuizStructure::SECTIONS) met de teksten
 * van de overgangsschermen tussen de twee onderdelen van de stijltest — tot nu toe hardcoded in
 * resources/js/quiz/data.js. Zelfde niet-aanmaakbaar/niet-verwijderbaar patroon als style_profiles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_transition_sections', function (Blueprint $table) {
            $table->id();
            $table->string('section_id')->unique();
            $table->string('title')->nullable();
            $table->text('tagline')->nullable();
            $table->text('wrap_up')->nullable();
            $table->string('cta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_transition_sections');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Minimale, PII-vrije trechtertelling voor de hoofdquiz — zelfde ontwerp als `partner_events`
 * (zie het implementatieplan "Partnerfunctie"): geen bezoekersidentiteit, puur een telling van
 * event-namen, dus geen tracking-script en geen consent nodig. `question_key` is bewust een los
 * tekstveld zonder foreign key naar quiz_questions: een later verwijderde/hernoemde vraag mag de
 * historische trechterdata nooit ongeldig maken (zelfde denormalisatieredenering als elders in
 * deze app, bv. QuizResult::chosen_accent_colors).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_events', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // quiz_started | question_reached | quiz_completed | lead_submitted
            $table->string('question_key')->nullable(); // alleen gezet bij question_reached
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_events');
    }
};

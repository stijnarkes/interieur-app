<?php

use App\Models\QuizMaterial;
use App\Models\QuizOption;
use App\Support\QuizImageManifest;
use Illuminate\Database\Migrations\Migration;

/**
 * Antwoordoptie- en materiaalfoto's stonden tot nu toe op dezelfde 1600px-brede master als de
 * grote overgangs-/sfeerfoto's, terwijl ze zelf altijd als klein kaartje getoond worden (quiz,
 * admin, PDF-moodboard) — dat kostte onnodig laadtijd, vooral merkbaar bij een vraag met 6
 * kaartjes tegelijk. Nieuwe uploads slaan voortaan al kleiner op (zie
 * QuizOption/QuizMaterial::storeImage()); dit verkleint eenmalig ook de al bestaande bestanden.
 */
return new class extends Migration
{
    public function up(): void
    {
        QuizOption::query()->where('has_image', true)->each(function (QuizOption $option): void {
            if ($path = $option->resolvedImagePath()) {
                QuizImageManifest::resizeInPlace($path, 800);
            }
        });

        QuizMaterial::query()->where('has_image', true)->each(function (QuizMaterial $material): void {
            QuizImageManifest::resizeInPlace($material->relativePath(), 800);
        });
    }

    public function down(): void
    {
        // Onomkeerbaar: de oorspronkelijke, grotere bestanden zijn overschreven.
    }
};

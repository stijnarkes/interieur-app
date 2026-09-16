<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogus van basispaletten waaruit de bezoeker er ná de stijlberekening één kiest (zie
 * App\Models\BasePalette) — vervangt de vaste, nooit door de bezoeker gekozen
 * StyleProfile::base_colors. Anders dan AccentColor::style_keys hoort een basispalet altijd bij
 * precies één stijl (elke stijl heeft zijn eigen, bewust samengestelde paletten), dus hier een
 * los `style_key` in plaats van een gedeelde array.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('base_palettes', function (Blueprint $table) {
            $table->id();
            $table->string('style_key');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('colors');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('base_palettes');
    }
};

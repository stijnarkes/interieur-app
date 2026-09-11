<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vervangt resources/js/quiz/styleProfiles.js als bron van waarheid: één rij per vaste woonstijl
 * (zie QuizStructure::STYLES), admin-beheerbaar via StyleProfilesPage i.p.v. hardcoded in de
 * JS-bundel. De AI-tekstlaag mag alleen uit deze inhoud putten, nooit zelf stijlkenmerken verzinnen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('style_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('style_key')->unique();
            $table->string('label');
            $table->string('slug');
            $table->string('subtitle')->nullable();
            $table->text('long_description')->nullable();
            $table->text('traits_intro')->nullable();
            $table->json('core_traits')->nullable();
            $table->string('hero_image')->nullable();
            $table->json('base_colors')->nullable();
            $table->json('accent_colors')->nullable();
            $table->text('color_tip')->nullable();
            $table->json('materials')->nullable();
            $table->text('materials_tip')->nullable();
            $table->json('furniture_shapes')->nullable();
            $table->text('lighting')->nullable();
            $table->json('accessories')->nullable();
            $table->text('advice_primary')->nullable();
            $table->text('advice_secondary')->nullable();
            $table->text('advice_tertiary')->nullable();
            $table->json('wat_past_goed')->nullable();
            $table->json('wat_past_minder_goed')->nullable();
            $table->json('recipe')->nullable();
            $table->json('product_tags')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('style_profiles');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Redactioneel beheerde combinatietips voor de partnerfunctie (zie het implementatieplan
 * "Partnerfunctie: Ontdek jullie gezamenlijke woonstijl", sectie 7 — 21 vaste records: 6 stijlen
 * geven 15 unieke paren + 6 zelfde-stijl-combinaties). `style_key_a`/`style_key_b` liggen altijd
 * alfabetisch gesorteerd opgeslagen (zie StyleCombinationAdvice::forPair()) zodat een paar maar op
 * één manier kan bestaan, ongeacht de volgorde waarin initiator/partner de stijlen hebben.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('style_combination_advices', function (Blueprint $table) {
            $table->id();
            $table->string('style_key_a');
            $table->string('style_key_b');
            $table->string('title');
            $table->text('intro');
            $table->text('basis_tip');
            $table->text('materials_tip');
            $table->text('accent_tip');
            $table->string('base_palette_style_key')->nullable();
            $table->string('status')->default('concept');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->unique(['style_key_a', 'style_key_b']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('style_combination_advices');
    }
};

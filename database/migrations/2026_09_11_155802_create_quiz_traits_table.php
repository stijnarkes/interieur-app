<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vrije, admin-uitbreidbare eigenschappen-taxonomie (kleur/materiaal/vorm/sfeer) die aan
 * antwoordopties gekoppeld wordt via quiz_option_traits — zie het implementatieplan
 * "persoonlijke digitale interieuradviseur": deze traits vormen de rode draad die de AI-tekstlaag
 * gebruikt om voorkeuren te herkennen die verder gaan dan alleen de winnende woonstijl.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_traits', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_traits');
    }
};

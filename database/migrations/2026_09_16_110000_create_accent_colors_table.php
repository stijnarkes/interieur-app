<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Centrale kleurencatalogus voor de accentkleurstap ná de stijlberekening (zie AccentColorSelector).
 * `style_keys` (json) is bewust hetzelfde patroon als quiz_options.style_keys: een kleur mag bij
 * meerdere woonstijlen horen zonder duplicatie, en de koppeling is los van de per-stijl content op
 * style_profiles (die blijft ongemoeid, puur intern/voor de styliste).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accent_colors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('hex');
            $table->json('style_keys');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accent_colors');
    }
};

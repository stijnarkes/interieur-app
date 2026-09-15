<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eén samengestelde materialenfoto per stijl (bv. een moodboard-achtige collage), i.p.v. losse
 * fototegels per materiaal — die laatste stonden toch nog nooit ingevuld met echte foto's en
 * toonden daardoor alleen lege placeholder-vakjes. `materials` blijft bestaan als de tekstuele
 * lijst met materiaalnamen die bij deze ene foto horen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('style_profiles', function (Blueprint $table) {
            $table->string('materials_image')->nullable()->after('materials');
        });
    }

    public function down(): void
    {
        Schema::table('style_profiles', function (Blueprint $table) {
            $table->dropColumn('materials_image');
        });
    }
};

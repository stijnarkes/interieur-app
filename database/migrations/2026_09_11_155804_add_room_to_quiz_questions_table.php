<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Welke kamer een vraag vertegenwoordigt (woonkamer/eethoek/keuken) — null betekent "algemeen",
 * voor vragen die niet aan één ruimte gebonden zijn (bv. tegel, wandmateriaal, verlichting). Wordt
 * gebruikt om naast de algemene woonstijl ook een los stijlprofiel per kamer te berekenen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->string('room')->nullable()->after('section');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropColumn('room');
        });
    }
};

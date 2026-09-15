<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vereenvoudigde stijlkoppeling: een optie mag bij één of maximaal twee woonstijlen passen, en
 * beide krijgen de volledige puntenwaarde (geen gewogen verdeling) — zie het implementatieplan
 * "vereenvoudiging woonstijltest". `primary_style` bestond al en wordt hiermee weer de
 * daadwerkelijke bron van waarheid voor de hoofdstijl; deze migratie voegt alleen de
 * ontbrekende tweede kolom toe. `internal_note` is de interne notitie voor de styliste.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_options', function (Blueprint $table) {
            $table->string('secondary_style')->nullable()->after('primary_style');
            $table->text('internal_note')->nullable()->after('secondary_style');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_options', function (Blueprint $table) {
            $table->dropColumn(['secondary_style', 'internal_note']);
        });
    }
};

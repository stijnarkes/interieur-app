<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Maakt de stijlkoppeling gewogen i.p.v. elke koppeling exact +1 te laten tellen — een optie kan
 * hierdoor bv. Japandi +3 en Natuurlijk +1 opleveren i.p.v. beide even zwaar. Default 1 zodat
 * bestaande koppelingen precies hetzelfde blijven scoren als vandaag.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_option_styles', function (Blueprint $table) {
            $table->tinyInteger('points')->default(1)->after('style_key');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_option_styles', function (Blueprint $table) {
            $table->dropColumn('points');
        });
    }
};

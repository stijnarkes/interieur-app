<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enige aan/uit-schakelaar voor de partnerfunctie (zie QuizSetting::current()) — staat uit totdat
 * expliciet aangezet, zodat de bestaande individuele quiz onveranderd blijft tot activering.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_settings', function (Blueprint $table) {
            $table->boolean('partner_feature_enabled')->default(false)->after('secondary_influence_ratio');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_settings', function (Blueprint $table) {
            $table->dropColumn('partner_feature_enabled');
        });
    }
};

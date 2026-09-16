<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optioneel, handmatig te beheren materiaal-/kenmerktags per optie — voedt straks de
 * materialenvergelijking van de partnerfunctie (zie PartnerComparisonService). Leeg toegestaan:
 * zonder tags toont de vergelijking simpelweg geen materiaalclaim, nooit een verzonnen claim.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_options', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('style_keys');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_options', function (Blueprint $table) {
            $table->dropColumn('tags');
        });
    }
};

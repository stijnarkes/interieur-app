<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
            $table->boolean('can_manage_quiz')->default(false);
            $table->boolean('can_view_results')->default(false);
        });

        // Bestaande gebruikers (van vóór dit rechtensysteem) behouden volledige toegang, zodat
        // niemand zichzelf per ongeluk buitensluit zodra deze migratie draait. Nieuwe gebruikers
        // die hierna worden aangemaakt starten wél zonder rechten (zie UserResource).
        DB::table('users')->update([
            'is_admin' => true,
            'can_manage_quiz' => true,
            'can_view_results' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_admin', 'can_manage_quiz', 'can_view_results']);
        });
    }
};

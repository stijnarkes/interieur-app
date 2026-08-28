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
            $table->timestamp('leads_viewed_at')->nullable()->after('can_view_results');
        });

        // Bestaande gebruikers hebben alle huidige leads al kunnen zien — alleen leads
        // die hierna binnenkomen moeten als "nieuw" tellen.
        DB::table('users')->update(['leads_viewed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('leads_viewed_at');
        });
    }
};

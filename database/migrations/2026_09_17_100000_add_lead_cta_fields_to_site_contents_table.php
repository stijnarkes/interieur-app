<?php

use App\Models\SiteContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Voegt een interieuradvies-CTA toe aan het bevestigingsscherm na het versturen van het
 * woonstijlrapport (zowel de "verstuurd"- als de "in behandeling"-variant, zie lead.js) — tot nu
 * toe stond die knop alleen in de bevestigingsmail (email_cta_label/email_cta_url). Eigen velden
 * i.p.v. de e-mailvelden hergebruiken, zodat de tekst/link voor beide plekken apart bijgesteld kan
 * worden via TekstenPage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_contents', function (Blueprint $table) {
            $table->string('lead_cta_label')->nullable()->after('lead_resend_label');
            $table->string('lead_cta_url')->nullable()->after('lead_cta_label');
        });

        // Backfill i.p.v. op een reseed vertrouwen: SiteContentSeeder slaat een bestaande rij over,
        // dus een omgeving die al geseed was zou deze twee nieuwe velden anders leeg houden.
        SiteContent::query()->whereNull('lead_cta_label')->update([
            'lead_cta_label' => 'Plan een interieuradvies',
            'lead_cta_url' => 'https://www.boer-staphorst.nl/wonen/interieuradvies',
        ]);
    }

    public function down(): void
    {
        Schema::table('site_contents', function (Blueprint $table) {
            $table->dropColumn(['lead_cta_label', 'lead_cta_url']);
        });
    }
};

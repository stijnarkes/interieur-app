<?php

use App\Models\SiteContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PDF-generatie + mailverzending lopen voortaan via een wachtrij-taak (zie
 * GenerateAndSendQuizResultPdfJob) i.p.v. synchroon binnen de aanvraag — dat voorkomt een "mislukt"
 * melding op het scherm terwijl de e-mail eigenlijk gewoon (iets later) wél aankomt, omdat de
 * verbinding tussen browser en server bij een trage aanvraag kon verbreken vóórdat het antwoord
 * terugkwam. De bezoeker krijgt nu meteen een bevestiging dat de aanvraag ontvangen is, met deze
 * twee nieuwe, admin-bewerkbare teksten (zie TekstenPage).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_contents', function (Blueprint $table) {
            $table->string('lead_queued_title')->nullable()->after('lead_success_body');
            $table->text('lead_queued_body')->nullable()->after('lead_queued_title');
        });

        // Backfill i.p.v. op een reseed vertrouwen: SiteContentSeeder slaat een bestaande rij
        // over, dus een omgeving die al geseed was zou deze twee nieuwe velden anders leeg houden.
        SiteContent::query()->whereNull('lead_queued_title')->update([
            'lead_queued_title' => 'Je aanvraag is ontvangen',
            'lead_queued_body' => 'Je ontvangt je rapport binnenkort per e-mail.',
        ]);
    }

    public function down(): void
    {
        Schema::table('site_contents', function (Blueprint $table) {
            $table->dropColumn(['lead_queued_title', 'lead_queued_body']);
        });
    }
};

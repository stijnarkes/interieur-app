<?php

use App\Models\SiteContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Twee toevoegingen aan de admin-bewerkbare teksten (zie TekstenPage): de nieuwe basispaletstap
 * (vóór de accentkleurenstap, zie BasePalette/basePaletteStep.js) en een expliciete
 * "geen accentkleur"-keuze bij de bestaande accentkleurenstap (zie accentColorStep.js's
 * skipLabel/skippedSummary) — eerder gevraagd, nu concreet ingevuld nu het basispalet zelf al de
 * rustige/neutrale keuze vertegenwoordigt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_contents', function (Blueprint $table) {
            $table->string('base_palette_step_title')->nullable()->after('accent_step_error_message');
            $table->text('base_palette_step_intro')->nullable()->after('base_palette_step_title');
            $table->string('base_palette_step_hint')->nullable()->after('base_palette_step_intro');
            $table->string('base_palette_step_continue_label')->nullable()->after('base_palette_step_hint');
            $table->string('base_palette_step_chosen_title')->nullable()->after('base_palette_step_continue_label');
            $table->string('base_palette_step_change_label')->nullable()->after('base_palette_step_chosen_title');

            $table->string('accent_step_skip_label')->nullable()->after('base_palette_step_change_label');
            $table->text('accent_step_skipped_summary')->nullable()->after('accent_step_skip_label');
        });

        // Backfill i.p.v. op een reseed vertrouwen: SiteContentSeeder slaat een bestaande rij
        // over, dus een omgeving die al geseed was zou deze nieuwe velden anders leeg houden.
        SiteContent::query()->whereNull('base_palette_step_title')->update([
            'base_palette_step_title' => 'Welk basispalet past het beste bij jou?',
            'base_palette_step_intro' => 'Elk basispalet vertaalt jouw woonstijl naar een eigen sfeer van kleuren. Kies het palet dat het beste bij jou past.',
            'base_palette_step_hint' => 'Kies het palet dat het beste bij jou past.',
            'base_palette_step_continue_label' => 'Doorgaan',
            'base_palette_step_chosen_title' => 'Jouw gekozen basispalet',
            'base_palette_step_change_label' => 'Wijzig keuze',
            'accent_step_skip_label' => 'Ik houd het liever bij rustige basiskleuren',
            'accent_step_skipped_summary' => 'Je hebt gekozen voor rustige basiskleuren, zonder extra accentkleur.',
        ]);
    }

    public function down(): void
    {
        Schema::table('site_contents', function (Blueprint $table) {
            $table->dropColumn([
                'base_palette_step_title',
                'base_palette_step_intro',
                'base_palette_step_hint',
                'base_palette_step_continue_label',
                'base_palette_step_chosen_title',
                'base_palette_step_change_label',
                'accent_step_skip_label',
                'accent_step_skipped_summary',
            ]);
        });
    }
};

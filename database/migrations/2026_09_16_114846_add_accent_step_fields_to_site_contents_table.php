<?php

use App\Models\SiteContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * De accentkleurenstap (ná de stijlberekening, zie AccentColorSelector/accentColorStep.js) is
 * gebouwd nadat TekstenPage/SiteContent al bestonden, en gebruikte tot nu toe alleen hardcoded
 * teksten (zie resources/js/quiz/copy.js's ACCENT_COLOR_STEP_COPY) — die stap miste dus per
 * ongeluk in de admin. Voegt 'm alsnog toe, zelfde patroon als de andere SiteContent-velden.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_contents', function (Blueprint $table) {
            $table->string('accent_step_title')->nullable()->after('email_cta_url');
            $table->text('accent_step_intro')->nullable()->after('accent_step_title');
            $table->string('accent_step_hint')->nullable()->after('accent_step_intro');
            $table->string('accent_step_continue_label')->nullable()->after('accent_step_hint');
            $table->string('accent_step_chosen_title')->nullable()->after('accent_step_continue_label');
            $table->string('accent_step_change_label')->nullable()->after('accent_step_chosen_title');
            $table->string('accent_step_error_message')->nullable()->after('accent_step_change_label');
        });

        // Backfill i.p.v. op een reseed vertrouwen: SiteContentSeeder slaat een bestaande rij
        // over, dus een omgeving die al geseed was zou deze nieuwe velden anders leeg houden.
        SiteContent::query()->whereNull('accent_step_title')->update([
            'accent_step_title' => 'Welke accentkleuren spreken jou het meeste aan?',
            'accent_step_intro' => 'Je woonstijl hebben we inmiddels goed in beeld. Kies nu maximaal twee kleuren waarmee jij jouw interieur persoonlijk zou maken.',
            'accent_step_hint' => 'Kies minimaal 1, maximaal 2 kleuren.',
            'accent_step_continue_label' => 'Doorgaan',
            'accent_step_chosen_title' => 'Jouw gekozen accentkleuren',
            'accent_step_change_label' => 'Wijzig keuze',
            'accent_step_error_message' => 'Je keuze kon niet worden opgeslagen. Probeer het opnieuw.',
        ]);
    }

    public function down(): void
    {
        Schema::table('site_contents', function (Blueprint $table) {
            $table->dropColumn([
                'accent_step_title',
                'accent_step_intro',
                'accent_step_hint',
                'accent_step_continue_label',
                'accent_step_chosen_title',
                'accent_step_change_label',
                'accent_step_error_message',
            ]);
        });
    }
};

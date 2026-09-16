<?php

use App\Models\SiteContent;
use Illuminate\Database\Migrations\Migration;

/**
 * Twee tekstcorrecties na live feedback:
 * 1. "Kies minimaal 1, maximaal 2 kleuren." bij de accentkleurenstap was tegenstrijdig met de net
 *    toegevoegde "Ik houd het liever bij rustige basiskleuren"-knop (0 kleuren is nu ook geldig).
 * 2. De basispaletstap maakte niet duidelijk dat alle getoonde paletten al bij de berekende stijl
 *    passen — de keuze is een verfijning binnen die stijl, geen risico om "verkeerd" te kiezen.
 *    Nieuwe tekst gebruikt een {style}-plekhouder (ingevuld door basePaletteStep.js), zelfde
 *    patroon als lead_success_body's {name}/{email}.
 *
 * Update alleen als het veld nog exact de oude standaardtekst bevat — een eigen aanpassing van de
 * admin via Teksten blijft zo onaangeroerd.
 */
return new class extends Migration
{
    public function up(): void
    {
        SiteContent::query()
            ->where('accent_step_hint', 'Kies minimaal 1, maximaal 2 kleuren.')
            ->update(['accent_step_hint' => 'Kies maximaal 2 kleuren.']);

        SiteContent::query()
            ->where('base_palette_step_intro', 'Elk basispalet vertaalt jouw woonstijl naar een eigen sfeer van kleuren. Kies het palet dat het beste bij jou past.')
            ->update(['base_palette_step_intro' => 'Op basis van je antwoorden past de {style}-stijl het beste bij jou. Deze basispaletten sluiten daar allemaal op aan — kies de sfeer die jij het mooist vindt om je basis verder te verfijnen.']);
    }

    public function down(): void
    {
        // Bewust geen terugdraai-logica: puur een tekstcorrectie, niet iets om ooit terug te willen.
    }
};

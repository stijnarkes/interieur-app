<?php

use App\Models\AccentColor;
use Illuminate\Database\Migrations\Migration;

/**
 * Vervangt de 6 accentkleuren van de (nu "Modern Scandinavisch" geheten) stijl — de zachte
 * pastels pasten bij het oude, lichte/knusse "Scandinavisch", niet bij de nieuwe, ingetogener
 * richting (zie 2026_09_29_150000_rewrite_modern_scandinavisch_style_profile). Geen van deze 6
 * kleuren wordt door een andere stijl gedeeld (elk staat hier met style_keys => ['scandinavisch'],
 * zie AccentColorSeeder), dus verwijderen raakt geen enkele andere stijl. Zelfde
 * updateOrCreate-op-naam + opruim-patroon als 2026_09_16_141703_replace_accent_color_catalog.
 * Veilig voor bestaande resultaten: een gekozen accentkleur staat al gedenormaliseerd in het
 * quizresultaat (chosen_accent_colors).
 */
return new class extends Migration
{
    public function up(): void
    {
        $oldNames = ['Poederblauw', 'Zacht mintgroen', 'Botergeel', 'Poederroze', 'Licht pistachegroen', 'Zacht lila'];

        // Zelfde sort_order-plekken als de kleuren die ze vervangen (280-330), zodat de positie in
        // de lijst niet verspringt.
        foreach ($this->colors() as $index => $color) {
            AccentColor::query()->updateOrCreate(
                ['name' => $color['name']],
                [...$color, 'sort_order' => 280 + ($index * 10)],
            );
        }

        AccentColor::query()->whereIn('name', $oldNames)->delete();
    }

    public function down(): void
    {
        // Bewust geen terugdraai-logica: inhoudelijke stijlrepositionering, geen technische fix.
    }

    /** @return array<int, array{name: string, hex: string, style_keys: array<int, string>}> */
    private function colors(): array
    {
        return [
            ['name' => 'Antraciet', 'hex' => '#33363A', 'style_keys' => ['scandinavisch']],
            ['name' => 'Dennengroen', 'hex' => '#435446', 'style_keys' => ['scandinavisch']],
            ['name' => 'Roestbruin', 'hex' => '#A9603F', 'style_keys' => ['scandinavisch']],
            ['name' => 'Warm taupe', 'hex' => '#B8A488', 'style_keys' => ['scandinavisch']],
            ['name' => 'Leisteengrijs', 'hex' => '#6B7371', 'style_keys' => ['scandinavisch']],
            ['name' => 'Amberbruin', 'hex' => '#B8863F', 'style_keys' => ['scandinavisch']],
        ];
    }
};

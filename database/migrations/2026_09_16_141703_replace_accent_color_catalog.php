<?php

use App\Models\AccentColor;
use Illuminate\Database\Migrations\Migration;

/**
 * Vervangt de eerste, voorlopige accentkleurencatalogus (20 kleuren, zie AccentColorSeeder) door
 * de definitieve set van 33 kleuren (6 per stijl, met bewuste overlap tussen stijlen waar de
 * kleur letterlijk hetzelfde is — zie het implementatieplan "Basispaletten + vernieuwde
 * accentkleuren", Bijlage A). Draait als migratie (niet alleen als seeder-wijziging) omdat dit
 * ook op de al gevulde productiedatabase moet landen. Veilig: gekozen accentkleuren per
 * quizresultaat staan al gedenormaliseerd opgeslagen (chosen_accent_colors), dus geen enkel
 * al gegenereerd PDF/e-mail verandert met terugwerkende kracht door deze cataloguswijziging.
 */
return new class extends Migration
{
    public function up(): void
    {
        $colors = $this->colors();

        foreach ($colors as $index => $color) {
            AccentColor::updateOrCreate(
                ['name' => $color['name']],
                [...$color, 'sort_order' => ($index + 1) * 10],
            );
        }

        // Ruimt kleuren op die niet meer in de nieuwe, definitieve lijst voorkomen — nooit een
        // live koppeling naar quizresultaten (zie hierboven), dus veilig te verwijderen.
        AccentColor::query()
            ->whereNotIn('name', collect($colors)->pluck('name')->all())
            ->delete();
    }

    public function down(): void
    {
        // Bewust geen terugdraai-logica: de oude catalogus is een startwaarde die niet
        // teruggehaald hoeft te worden, en een admin kan intussen al wijzigingen hebben gemaakt.
    }

    /** @return array<int, array{name: string, hex: string, style_keys: array<int, string>}> */
    private function colors(): array
    {
        return [
            ['name' => 'Bordeaux', 'hex' => '#702C3C', 'style_keys' => ['hotelLuxe', 'modern']],
            ['name' => 'Smaragdgroen', 'hex' => '#1F5948', 'style_keys' => ['hotelLuxe']],
            ['name' => 'Nachtblauw', 'hex' => '#202E46', 'style_keys' => ['hotelLuxe']],
            ['name' => 'Aubergine', 'hex' => '#52354D', 'style_keys' => ['hotelLuxe']],
            ['name' => 'Oudroze', 'hex' => '#BE9193', 'style_keys' => ['hotelLuxe', 'landelijk']],
            ['name' => 'Cognac', 'hex' => '#A96D40', 'style_keys' => ['hotelLuxe']],
            ['name' => 'Saliegroen', 'hex' => '#9AA58B', 'style_keys' => ['landelijk']],
            ['name' => 'Duifblauw', 'hex' => '#8D9FAA', 'style_keys' => ['landelijk']],
            ['name' => 'Zachte terracotta', 'hex' => '#BE8066', 'style_keys' => ['landelijk']],
            ['name' => 'Gedempt oker', 'hex' => '#BE9A4D', 'style_keys' => ['landelijk']],
            ['name' => 'Kastanjebruin', 'hex' => '#76503C', 'style_keys' => ['landelijk']],
            ['name' => 'Gedempt olijfgroen', 'hex' => '#7C8060', 'style_keys' => ['japandi']],
            ['name' => 'Kleiroze', 'hex' => '#BA9587', 'style_keys' => ['japandi']],
            ['name' => 'Gedempte terracotta', 'hex' => '#AD735B', 'style_keys' => ['japandi']],
            ['name' => 'Kaneelbruin', 'hex' => '#956849', 'style_keys' => ['japandi']],
            ['name' => 'Walnootbruin', 'hex' => '#69503E', 'style_keys' => ['japandi']],
            ['name' => 'Houtskool', 'hex' => '#3D3D38', 'style_keys' => ['japandi']],
            ['name' => 'Kobaltblauw', 'hex' => '#2850C8', 'style_keys' => ['kleurExplosie', 'modern']],
            ['name' => 'Fuchsia', 'hex' => '#CF3480', 'style_keys' => ['kleurExplosie']],
            ['name' => 'Mandarijnoranje', 'hex' => '#EC782F', 'style_keys' => ['kleurExplosie']],
            ['name' => 'Citroengeel', 'hex' => '#EED638', 'style_keys' => ['kleurExplosie']],
            ['name' => 'Grasgroen', 'hex' => '#489447', 'style_keys' => ['kleurExplosie']],
            ['name' => 'Violet', 'hex' => '#8050B0', 'style_keys' => ['kleurExplosie']],
            ['name' => 'Diep groen', 'hex' => '#315A48', 'style_keys' => ['modern']],
            ['name' => 'Roestoranje', 'hex' => '#B86843', 'style_keys' => ['modern']],
            ['name' => 'Chocoladebruin', 'hex' => '#604536', 'style_keys' => ['modern']],
            ['name' => 'Zwart', 'hex' => '#202020', 'style_keys' => ['modern']],
            ['name' => 'Poederblauw', 'hex' => '#A8C6DB', 'style_keys' => ['scandinavisch']],
            ['name' => 'Zacht mintgroen', 'hex' => '#B7D4C3', 'style_keys' => ['scandinavisch']],
            ['name' => 'Botergeel', 'hex' => '#EDDA94', 'style_keys' => ['scandinavisch']],
            ['name' => 'Poederroze', 'hex' => '#E4BDC5', 'style_keys' => ['scandinavisch']],
            ['name' => 'Licht pistachegroen', 'hex' => '#CDD7AF', 'style_keys' => ['scandinavisch']],
            ['name' => 'Zacht lila', 'hex' => '#C8BDD9', 'style_keys' => ['scandinavisch']],
        ];
    }
};

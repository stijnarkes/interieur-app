<?php

namespace Database\Seeders;

use App\Models\AccentColor;
use Illuminate\Database\Seeder;

/**
 * Definitieve kleurencatalogus voor de accentkleurstap (zie App\Services\AccentColorSelector) —
 * 6 kleuren per stijl, admin-beheerbaar via AccentColorsPage. Een kleur mag bewust bij meerdere
 * stijlen horen als de kleur letterlijk hetzelfde is (bv. Bordeaux past bij zowel Hotel luxe als
 * Modern) i.p.v. per stijl gedupliceerd te worden. Zie ook de migratie
 * `2026_09_16_141703_replace_accent_color_catalog`, die deze definitieve lijst ook op een al
 * gevulde (productie)database toepast.
 */
class AccentColorSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->colors() as $index => $color) {
            AccentColor::updateOrCreate(
                ['name' => $color['name']],
                [...$color, 'sort_order' => ($index + 1) * 10],
            );
        }
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
            ['name' => 'Antraciet', 'hex' => '#33363A', 'style_keys' => ['scandinavisch']],
            ['name' => 'Dennengroen', 'hex' => '#435446', 'style_keys' => ['scandinavisch']],
            ['name' => 'Roestbruin', 'hex' => '#A9603F', 'style_keys' => ['scandinavisch']],
            ['name' => 'Warm taupe', 'hex' => '#B8A488', 'style_keys' => ['scandinavisch']],
            ['name' => 'Leisteengrijs', 'hex' => '#6B7371', 'style_keys' => ['scandinavisch']],
            ['name' => 'Amberbruin', 'hex' => '#B8863F', 'style_keys' => ['scandinavisch']],
        ];
    }
}

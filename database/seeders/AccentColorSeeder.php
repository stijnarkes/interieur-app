<?php

namespace Database\Seeders;

use App\Models\AccentColor;
use Illuminate\Database\Seeder;

/**
 * Eerste kleurencatalogus voor de accentkleurstap (zie App\Services\AccentColorSelector). Deels
 * geïnspireerd op de al bestaande, interne accent_colors per stijl in StyleProfileSeeder, maar nu
 * losgetrokken tot één centrale, admin-beheerbare catalogus (AccentColorsPage) waarin een kleur
 * bewust bij meerdere verwante stijlen mag horen (bv. Antraciet past bij Japandi, Modern én
 * Scandinavisch) i.p.v. per stijl gedupliceerd te worden.
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
            ['name' => 'Donkerbruin', 'hex' => '#3e2a20', 'style_keys' => ['hotelLuxe', 'japandi', 'landelijk']],
            ['name' => 'Champagne', 'hex' => '#c9a86a', 'style_keys' => ['hotelLuxe']],
            ['name' => 'Bordeaux', 'hex' => '#5c1f2e', 'style_keys' => ['hotelLuxe', 'kleurExplosie']],
            ['name' => 'Diep smaragdgroen', 'hex' => '#2e4034', 'style_keys' => ['hotelLuxe', 'modern']],
            ['name' => 'Zwart', 'hex' => '#17181a', 'style_keys' => ['hotelLuxe', 'modern']],
            ['name' => 'Taupe', 'hex' => '#a8967d', 'style_keys' => ['japandi', 'scandinavisch']],
            ['name' => 'Mosgroen', 'hex' => '#6b7a4f', 'style_keys' => ['japandi', 'landelijk']],
            ['name' => 'Terracotta', 'hex' => '#c1694f', 'style_keys' => ['japandi', 'landelijk']],
            ['name' => 'Antraciet', 'hex' => '#33363a', 'style_keys' => ['japandi', 'modern', 'scandinavisch']],
            ['name' => 'Zachtblauw', 'hex' => '#a9c2d0', 'style_keys' => ['japandi', 'scandinavisch']],
            ['name' => 'Saliegroen', 'hex' => '#9caf88', 'style_keys' => ['japandi', 'scandinavisch']],
            ['name' => 'Kobaltblauw', 'hex' => '#1d4e89', 'style_keys' => ['kleurExplosie', 'modern']],
            ['name' => 'Okergeel', 'hex' => '#e0a730', 'style_keys' => ['kleurExplosie', 'landelijk']],
            ['name' => 'Warm rood', 'hex' => '#e8583a', 'style_keys' => ['kleurExplosie']],
            ['name' => 'Roze', 'hex' => '#d6467e', 'style_keys' => ['kleurExplosie']],
            ['name' => 'Warm bruin', 'hex' => '#7a5230', 'style_keys' => ['landelijk']],
            ['name' => 'Vergrijsd groen', 'hex' => '#8a9483', 'style_keys' => ['landelijk', 'scandinavisch']],
            ['name' => 'Mosterdgeel', 'hex' => '#c9971f', 'style_keys' => ['landelijk', 'scandinavisch']],
            ['name' => 'Petrolblauw', 'hex' => '#1f4e5f', 'style_keys' => ['modern', 'scandinavisch']],
            ['name' => 'Donkerblauw', 'hex' => '#16324f', 'style_keys' => ['modern', 'scandinavisch']],
        ];
    }
}

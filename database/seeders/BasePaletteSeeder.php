<?php

namespace Database\Seeders;

use App\Models\BasePalette;
use Illuminate\Database\Seeder;

/**
 * Eerste basispalettencatalogus (zie App\Models\BasePalette) — 3 paletten per stijl, elk met een
 * sfeernaam, korte omschrijving en 3 kleuren. Anders dan de accentkleurencatalogus (AccentColor)
 * hoort een basispalet altijd bij precies één stijl. Zie het implementatieplan "Basispaletten +
 * vernieuwde accentkleuren", Bijlage B, voor de herkomst van deze data.
 */
class BasePaletteSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->palettes() as $styleKey => $palettes) {
            foreach ($palettes as $index => $palette) {
                BasePalette::updateOrCreate(
                    ['style_key' => $styleKey, 'name' => $palette['name']],
                    [...$palette, 'style_key' => $styleKey, 'sort_order' => ($index + 1) * 10],
                );
            }
        }
    }

    /** @return array<string, array<int, array{name: string, description: string, colors: array<int, array{name: string, hex: string}>}>> */
    private function palettes(): array
    {
        return [
            'hotelLuxe' => [
                [
                    'name' => 'Licht en elegant',
                    'description' => 'Een lichte basis voor een elegante ruimte.',
                    'colors' => [
                        ['name' => 'Ivoor', 'hex' => '#F3EEE3'],
                        ['name' => 'Crème', 'hex' => '#E9DFC9'],
                        ['name' => 'Champagnebeige', 'hex' => '#D6C3A5'],
                    ],
                ],
                [
                    'name' => 'Warm en geborgen',
                    'description' => 'Warme beige- en bruintinten geven de ruimte diepte.',
                    'colors' => [
                        ['name' => 'Crème', 'hex' => '#E9DFC9'],
                        ['name' => 'Taupe', 'hex' => '#A39485'],
                        ['name' => 'Mokka', 'hex' => '#806554'],
                    ],
                ],
                [
                    'name' => 'Diep en sfeervol',
                    'description' => 'Donkerbruin maakt de sfeer intiem; gebruik de lichte tint om het geheel open te houden.',
                    'colors' => [
                        ['name' => 'Champagnebeige', 'hex' => '#D6C3A5'],
                        ['name' => 'Mokka', 'hex' => '#806554'],
                        ['name' => 'Espressobruin', 'hex' => '#3E2E28'],
                    ],
                ],
            ],
            'landelijk' => [
                [
                    'name' => 'Licht en luchtig',
                    'description' => 'Lichte natuurtinten geven een open en zachte basis.',
                    'colors' => [
                        ['name' => 'Roomwit', 'hex' => '#F5F0E6'],
                        ['name' => 'Linnenbeige', 'hex' => '#DED2BF'],
                        ['name' => 'Havermout', 'hex' => '#CFC1A9'],
                    ],
                ],
                [
                    'name' => 'Warm en huiselijk',
                    'description' => 'Zand en leem zorgen voor een warme, huiselijke sfeer.',
                    'colors' => [
                        ['name' => 'Roomwit', 'hex' => '#F5F0E6'],
                        ['name' => 'Zandkleur', 'hex' => '#D5BE9B'],
                        ['name' => 'Leem', 'hex' => '#AC9175'],
                    ],
                ],
                [
                    'name' => 'Rustiek en geborgen',
                    'description' => 'Bruintinten geven diepte; roomwit houdt de ruimte licht.',
                    'colors' => [
                        ['name' => 'Linnenbeige', 'hex' => '#DED2BF'],
                        ['name' => 'Zachte taupe', 'hex' => '#B1A18F'],
                        ['name' => 'Kastanjebruin', 'hex' => '#76503C'],
                    ],
                ],
            ],
            'japandi' => [
                [
                    'name' => 'Licht en verstild',
                    'description' => 'Lichte, natuurlijke tinten voor een rustige ruimte.',
                    'colors' => [
                        ['name' => 'Warm krijtwit', 'hex' => '#F2EFE7'],
                        ['name' => 'Ecru', 'hex' => '#E4DAC6'],
                        ['name' => 'Zandkleur', 'hex' => '#D1BD9E'],
                    ],
                ],
                [
                    'name' => 'Warm en aards',
                    'description' => 'Zachte aardetinten geven iets meer warmte.',
                    'colors' => [
                        ['name' => 'Ecru', 'hex' => '#E4DAC6'],
                        ['name' => 'Licht leem', 'hex' => '#C2AD93'],
                        ['name' => 'Kleibruin', 'hex' => '#A0866E'],
                    ],
                ],
                [
                    'name' => 'Rustig met diepte',
                    'description' => 'Bruin geeft diepte, terwijl zandkleur het geheel rustig houdt.',
                    'colors' => [
                        ['name' => 'Zandkleur', 'hex' => '#D1BD9E'],
                        ['name' => 'Paddenstoeltaupe', 'hex' => '#A39789'],
                        ['name' => 'Walnootbruin', 'hex' => '#69503E'],
                    ],
                ],
            ],
            'kleurExplosie' => [
                [
                    'name' => 'Rustige basis',
                    'description' => 'Een rustige achtergrond waarop kleur opvalt.',
                    'colors' => [
                        ['name' => 'Warm wit', 'hex' => '#F6F2E9'],
                        ['name' => 'Crème', 'hex' => '#E9DFC9'],
                        ['name' => 'Licht zandbeige', 'hex' => '#DDCFB9'],
                    ],
                ],
                [
                    'name' => 'Zacht en zonnig',
                    'description' => 'Zachte roze- en perziktinten als vriendelijke kleurbasis.',
                    'colors' => [
                        ['name' => 'Crème', 'hex' => '#E9DFC9'],
                        ['name' => 'Lichtroze', 'hex' => '#EBC9D1'],
                        ['name' => 'Perzik', 'hex' => '#EDB99C'],
                    ],
                ],
                [
                    'name' => 'Fris en speels',
                    'description' => 'Lichtblauw en zachtgroen geven een frisse basis voor fellere accenten.',
                    'colors' => [
                        ['name' => 'Warm wit', 'hex' => '#F6F2E9'],
                        ['name' => 'Lichtblauw', 'hex' => '#BDD5E5'],
                        ['name' => 'Licht pistachegroen', 'hex' => '#CDD7AF'],
                    ],
                ],
            ],
            'modern' => [
                [
                    'name' => 'Helder en minimalistisch',
                    'description' => 'Wit en lichtgrijs geven een helder, rustig geheel.',
                    'colors' => [
                        ['name' => 'Helder wit', 'hex' => '#FFFFFF'],
                        ['name' => 'Zacht wit', 'hex' => '#F3F2EE'],
                        ['name' => 'Lichtgrijs', 'hex' => '#D5D7D8'],
                    ],
                ],
                [
                    'name' => 'Warm en rustig',
                    'description' => 'Greige en zandbeige houden de basis strak maar warm.',
                    'colors' => [
                        ['name' => 'Zacht wit', 'hex' => '#F3F2EE'],
                        ['name' => 'Licht greige', 'hex' => '#CEC7BC'],
                        ['name' => 'Zandbeige', 'hex' => '#CDB99E'],
                    ],
                ],
                [
                    'name' => 'Strak met contrast',
                    'description' => 'Grijs en antraciet geven een duidelijk contrast.',
                    'colors' => [
                        ['name' => 'Zacht wit', 'hex' => '#F3F2EE'],
                        ['name' => 'Betongrijs', 'hex' => '#A7A8A5'],
                        ['name' => 'Antraciet', 'hex' => '#383B3E'],
                    ],
                ],
            ],
            'scandinavisch' => [
                [
                    'name' => 'Licht en mat',
                    'description' => 'Gebroken wit en licht hout geven een frisse, warme basis.',
                    'colors' => [
                        ['name' => 'Gebroken wit', 'hex' => '#F5F3EE'],
                        ['name' => 'Mat steengrijs', 'hex' => '#D3CDC1'],
                        ['name' => 'Licht eikenhout', 'hex' => '#C7B79C'],
                    ],
                ],
                [
                    'name' => 'Rustig en geaard',
                    'description' => 'Taupe en greige geven iets meer warmte; combineer ze met licht hout.',
                    'colors' => [
                        ['name' => 'Zacht taupe', 'hex' => '#D8CBB8'],
                        ['name' => 'Warme greige', 'hex' => '#C4B6A0'],
                        ['name' => 'Dieptaupe', 'hex' => '#9C8A72'],
                    ],
                ],
                [
                    'name' => 'Fris met grijs',
                    'description' => 'Melkwit en grijs houden het licht. Voeg hout en stof toe voor een zachte uitstraling.',
                    'colors' => [
                        ['name' => 'Melkwit', 'hex' => '#F7F5EF'],
                        ['name' => 'Parelgrijs', 'hex' => '#D6D5D0'],
                        ['name' => 'Middengrijs', 'hex' => '#A6A6A2'],
                    ],
                ],
            ],
        ];
    }
}

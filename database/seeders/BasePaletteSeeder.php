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
                    'description' => 'Een lichte, zachte basis met een verfijnde uitstraling.',
                    'colors' => [
                        ['name' => 'Ivoor', 'hex' => '#F3EEE3'],
                        ['name' => 'Crème', 'hex' => '#E9DFC9'],
                        ['name' => 'Champagnebeige', 'hex' => '#D6C3A5'],
                    ],
                ],
                [
                    'name' => 'Warm en geborgen',
                    'description' => 'Warme tinten voor een comfortabele, luxe sfeer.',
                    'colors' => [
                        ['name' => 'Crème', 'hex' => '#E9DFC9'],
                        ['name' => 'Taupe', 'hex' => '#A39485'],
                        ['name' => 'Mokka', 'hex' => '#806554'],
                    ],
                ],
                [
                    'name' => 'Diep en sfeervol',
                    'description' => 'Rijke bruintinten voor een intieme, uitgesproken sfeer.',
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
                    'description' => 'Zachte natuurtinten voor een ontspannen, lichte woning.',
                    'colors' => [
                        ['name' => 'Roomwit', 'hex' => '#F5F0E6'],
                        ['name' => 'Linnenbeige', 'hex' => '#DED2BF'],
                        ['name' => 'Havermout', 'hex' => '#CFC1A9'],
                    ],
                ],
                [
                    'name' => 'Warm en huiselijk',
                    'description' => 'Warme zand- en leemtinten voor een vertrouwd thuisgevoel.',
                    'colors' => [
                        ['name' => 'Roomwit', 'hex' => '#F5F0E6'],
                        ['name' => 'Zandkleur', 'hex' => '#D5BE9B'],
                        ['name' => 'Leem', 'hex' => '#AC9175'],
                    ],
                ],
                [
                    'name' => 'Rustiek en geborgen',
                    'description' => 'Natuurlijke bruintinten voor een knusse sfeer met karakter.',
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
                    'description' => 'Lichte, natuurlijke tinten voor eenvoud en rust.',
                    'colors' => [
                        ['name' => 'Warm krijtwit', 'hex' => '#F2EFE7'],
                        ['name' => 'Ecru', 'hex' => '#E4DAC6'],
                        ['name' => 'Zandkleur', 'hex' => '#D1BD9E'],
                    ],
                ],
                [
                    'name' => 'Warm en aards',
                    'description' => 'Zachte aardetinten voor een natuurlijke, warme uitstraling.',
                    'colors' => [
                        ['name' => 'Ecru', 'hex' => '#E4DAC6'],
                        ['name' => 'Licht leem', 'hex' => '#C2AD93'],
                        ['name' => 'Kleibruin', 'hex' => '#A0866E'],
                    ],
                ],
                [
                    'name' => 'Rustig met diepte',
                    'description' => 'Gedempte tinten met donkerbruin voor een geborgen geheel.',
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
                    'description' => 'Een lichte achtergrond waarop jouw kleuraccenten opvallen.',
                    'colors' => [
                        ['name' => 'Warm wit', 'hex' => '#F6F2E9'],
                        ['name' => 'Crème', 'hex' => '#E9DFC9'],
                        ['name' => 'Licht zandbeige', 'hex' => '#DDCFB9'],
                    ],
                ],
                [
                    'name' => 'Zacht en zonnig',
                    'description' => 'Vriendelijke roze- en perziktinten voor een vrolijke basis.',
                    'colors' => [
                        ['name' => 'Crème', 'hex' => '#E9DFC9'],
                        ['name' => 'Lichtroze', 'hex' => '#EBC9D1'],
                        ['name' => 'Perzik', 'hex' => '#EDB99C'],
                    ],
                ],
                [
                    'name' => 'Fris en speels',
                    'description' => 'Lichte blauwe en groene tinten voor een levendig geheel.',
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
                    'description' => 'Wit en lichtgrijs voor een frisse, rustige uitstraling.',
                    'colors' => [
                        ['name' => 'Helder wit', 'hex' => '#FFFFFF'],
                        ['name' => 'Zacht wit', 'hex' => '#F3F2EE'],
                        ['name' => 'Lichtgrijs', 'hex' => '#D5D7D8'],
                    ],
                ],
                [
                    'name' => 'Warm en rustig',
                    'description' => 'Zachte neutrale tinten voor een modern interieur met warmte.',
                    'colors' => [
                        ['name' => 'Zacht wit', 'hex' => '#F3F2EE'],
                        ['name' => 'Licht greige', 'hex' => '#CEC7BC'],
                        ['name' => 'Zandbeige', 'hex' => '#CDB99E'],
                    ],
                ],
                [
                    'name' => 'Strak met contrast',
                    'description' => 'Een lichte basis met grijs en antraciet voor duidelijke contrasten.',
                    'colors' => [
                        ['name' => 'Zacht wit', 'hex' => '#F3F2EE'],
                        ['name' => 'Betongrijs', 'hex' => '#A7A8A5'],
                        ['name' => 'Antraciet', 'hex' => '#383B3E'],
                    ],
                ],
            ],
            'scandinavisch' => [
                [
                    'name' => 'Licht en natuurlijk',
                    'description' => 'Melkwit en lichte houttinten voor een luchtig, natuurlijk thuis.',
                    'colors' => [
                        ['name' => 'Melkwit', 'hex' => '#F7F5EF'],
                        ['name' => 'Licht beige', 'hex' => '#E5DCCB'],
                        ['name' => 'Bleek houtbeige', 'hex' => '#D8C5A5'],
                    ],
                ],
                [
                    'name' => 'Zacht en warm',
                    'description' => 'Lichte crèmetinten voor een vriendelijke, comfortabele sfeer.',
                    'colors' => [
                        ['name' => 'Gebroken wit', 'hex' => '#F3EFE5'],
                        ['name' => 'Licht crème', 'hex' => '#EEE4CF'],
                        ['name' => 'Lichte zandkleur', 'hex' => '#DCCCB2'],
                    ],
                ],
                [
                    'name' => 'Fris en rustig',
                    'description' => 'Zachte grijs- en blauwtinten voor een heldere, ontspannen basis.',
                    'colors' => [
                        ['name' => 'Melkwit', 'hex' => '#F7F5EF'],
                        ['name' => 'Parelgrijs', 'hex' => '#DADDD9'],
                        ['name' => 'Zacht blauwgrijs', 'hex' => '#B5C5CE'],
                    ],
                ],
            ],
        ];
    }
}

<?php

use App\Models\BasePalette;
use Illuminate\Database\Migrations\Migration;

/**
 * Vervangt de 3 basispaletten van de (nu "Modern Scandinavisch" geheten) stijl door een
 * ingetogener, meer geaarde set — zie 2026_09_29_150000_rewrite_modern_scandinavisch_style_profile
 * voor de bredere herpositionering. updateOrCreate op ['style_key','name'] (zelfde patroon als
 * BasePaletteSeeder): de 3 nieuwe namen zijn nieuwe rijen, dus de oude 3 worden hierna expliciet
 * verwijderd — geen risico op een live koppeling die breekt, een gekozen basispalet staat al
 * gedenormaliseerd in het quizresultaat (chosen_base_palette).
 */
return new class extends Migration
{
    public function up(): void
    {
        $oldNames = ['Licht en natuurlijk', 'Zacht en warm', 'Fris en rustig'];

        foreach ($this->palettes() as $index => $palette) {
            BasePalette::query()->updateOrCreate(
                ['style_key' => 'scandinavisch', 'name' => $palette['name']],
                [...$palette, 'style_key' => 'scandinavisch', 'sort_order' => ($index + 1) * 10],
            );
        }

        BasePalette::query()
            ->where('style_key', 'scandinavisch')
            ->whereIn('name', $oldNames)
            ->delete();
    }

    public function down(): void
    {
        // Bewust geen terugdraai-logica: inhoudelijke stijlrepositionering, geen technische fix.
    }

    /** @return array<int, array{name: string, description: string, colors: array<int, array{name: string, hex: string}>}> */
    private function palettes(): array
    {
        return [
            [
                'name' => 'Licht en mat',
                'description' => 'Gebroken wit en een mat, warm grijs voor een rustige, verfijnde basis.',
                'colors' => [
                    ['name' => 'Gebroken wit', 'hex' => '#F5F3EE'],
                    ['name' => 'Mat steengrijs', 'hex' => '#D3CDC1'],
                    ['name' => 'Licht eikenhout', 'hex' => '#C7B79C'],
                ],
            ],
            [
                'name' => 'Rustig en geaard',
                'description' => 'Zachte, aardse taupetinten voor een kalme, ingetogen sfeer.',
                'colors' => [
                    ['name' => 'Zacht taupe', 'hex' => '#D8CBB8'],
                    ['name' => 'Warme greige', 'hex' => '#C4B6A0'],
                    ['name' => 'Dieptaupe', 'hex' => '#9C8A72'],
                ],
            ],
            [
                'name' => 'Koel en helder',
                'description' => 'Heldere grijstinten met een vleugje kilte voor een strakke, lichte basis.',
                'colors' => [
                    ['name' => 'Melkwit', 'hex' => '#F7F5EF'],
                    ['name' => 'Parelgrijs', 'hex' => '#D6D5D0'],
                    ['name' => 'Zacht antracietgrijs', 'hex' => '#A6A6A2'],
                ],
            ],
        ];
    }
};

<?php

namespace Database\Seeders;

use App\Models\QuizTransitionSection;
use Illuminate\Database\Seeder;

/**
 * Zet de huidige, letterlijke overgangsscherm-teksten (voorheen hardcoded in
 * resources/js/quiz/data.js) als beginwaarde neer.
 */
class QuizTransitionSectionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->sections() as $section) {
            QuizTransitionSection::updateOrCreate(['section_id' => $section['section_id']], $section);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function sections(): array
    {
        return [
            [
                'section_id' => 'materials-colors',
                'title' => 'Kleur & materiaal',
                'tagline' => 'Ontdek welke kleuren, materialen en afwerkingen het beste bij jouw smaak passen.',
                'wrap_up' => null,
                'cta' => 'Beginnen',
            ],
            [
                'section_id' => 'objects',
                'title' => 'Wonen & inrichting',
                'tagline' => 'Ontdek welke meubels, keuken en woonaccessoires het beste bij jouw ideale interieur passen.',
                'wrap_up' => 'Mooi! We weten nu welke materialen en kleuren je aanspreken.',
                'cta' => 'Verder naar wonen & inrichting',
            ],
        ];
    }
}

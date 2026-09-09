<?php

namespace Database\Seeders;

use App\Models\QuizOption;
use App\Support\QuizImageManifest;
use Illuminate\Database\Seeder;

/**
 * Seedt quiz_options vanuit een JSON-fixture (database/seeders/fixtures/quiz-options.json).
 * Gebruikt firstOrCreate: opnieuw draaien overschrijft geen admin-wijzigingen die na de eerste
 * seed zijn gemaakt. De fixture staat momenteel leeg — sinds de omzetting naar de nieuwe set
 * woonstijlen (zie QuizStructure::STYLES) bouwt de admin de antwoordopties zelf op via de
 * "Antwoordopties"-pagina in plaats van vanuit een meegeleverde startset.
 */
class QuizOptionSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/fixtures/quiz-options.json');
        $options = json_decode(file_get_contents($path), true);

        foreach ($options as $option) {
            $record = QuizOption::firstOrCreate(
                ['option_slug' => $option['option_slug']],
                $option
            );

            $path = $record->resolvedImagePath();
            $hasImage = $path && QuizImageManifest::existsAtPath($path);

            if ($record->has_image !== $hasImage) {
                $record->forceFill(['has_image' => $hasImage])->save();
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\QuizOption;
use App\Support\QuizImageManifest;
use Illuminate\Database\Seeder;

/**
 * Seedt quiz_options vanuit een JSON-fixture die 1-op-1 is gedumpt uit de live
 * resources/js/quiz/data.js (zie database/seeders/fixtures/quiz-options.json), zodat er niets
 * met de hand is overgetypt. Gebruikt firstOrCreate: opnieuw draaien overschrijft geen
 * admin-wijzigingen die na de eerste seed zijn gemaakt.
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

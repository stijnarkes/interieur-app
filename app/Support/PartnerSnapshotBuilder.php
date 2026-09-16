<?php

namespace App\Support;

use App\Models\QuizResult;

/**
 * Bevriest exact de velden van een QuizResult die de partnerfunctie later nodig heeft om te
 * vergelijken (zie App\Services\PartnerComparisonService) — dezelfde denormalisatieredenering als
 * QuizResult::chosen_accent_colors/chosen_base_palette: een later gewijzigd/verwijderd resultaat
 * mag een al bevroren snapshot nooit met terugwerkende kracht veranderen.
 */
class PartnerSnapshotBuilder
{
    /** @return array<string, mixed> */
    public static function build(QuizResult $result): array
    {
        return [
            'quiz_result_uuid' => $result->uuid,
            'primary_style' => $result->primary_style,
            'secondary_style' => $result->secondary_style,
            'answers' => $result->answers,
            'chosen_base_palette' => $result->chosen_base_palette,
            'chosen_accent_colors' => $result->chosen_accent_colors,
        ];
    }
}

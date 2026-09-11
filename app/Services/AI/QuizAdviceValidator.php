<?php

namespace App\Services\AI;

/**
 * Laatste vangnet vóór AI-output gebruikt wordt: verplichte velden, redelijke lengtes, en een
 * regex-check tegen per ongeluk gelekte percentages/cijfers (de AI mag daar nooit over schrijven,
 * zie resources/prompts/quiz-advice.v1.md). Faalt de validatie, dan behandelt QuizAdviceGenerator
 * dit identiek aan een mislukte AI-aanroep — nooit halve/onbetrouwbare content gebruiken.
 */
class QuizAdviceValidator
{
    public function validate(mixed $output, string $variant): bool
    {
        if (! is_array($output)) {
            return false;
        }

        if (! $this->validText($output['comboName'] ?? null, 3, 120)) {
            return false;
        }

        if (! $this->validText($output['intro'] ?? null, 20, 1200)) {
            return false;
        }

        if ($variant === 'pdf_full') {
            $roomAdvice = $output['roomAdvice'] ?? null;
            if (! is_array($roomAdvice)) {
                return false;
            }

            foreach ($roomAdvice as $text) {
                if ($text !== null && ! $this->validText($text, 10, 800)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function validText(mixed $value, int $minLength, int $maxLength): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $length = mb_strlen(trim($value));

        if ($length < $minLength || $length > $maxLength) {
            return false;
        }

        return ! $this->containsLeakedNumbers($value);
    }

    private function containsLeakedNumbers(string $text): bool
    {
        return (bool) preg_match('/\d{1,3}\s?%|\b\d{1,3}\s*(procent|punten)\b/i', $text);
    }
}

<?php

namespace App\Services;

use App\Models\AccentColor;
use Illuminate\Support\Collection;

/**
 * Stelt de ~6 accentkleuren samen die de bezoeker mag kiezen, op basis van het al berekende
 * stijlprofiel (QuizScoringService) — raakt de scoreberekening zelf niet aan. Gebruikt bewust
 * alleen primary_style/secondary_style: een secundaire stijl wordt door QuizScoringService al
 * alleen gezet als die significant genoeg is (secondary_influence_ratio + spreiding), dus een
 * losse percentageberekening is hier niet nodig.
 */
class AccentColorSelector
{
    private const TARGET_COUNT = 6;

    private const MAX_FROM_PRIMARY_WHEN_SECONDARY_PRESENT = 4;

    /** @return Collection<int, AccentColor> */
    public function forResult(?string $primaryStyle, ?string $secondaryStyle): Collection
    {
        $primaryColors = $primaryStyle ? $this->colorsFor($primaryStyle) : collect();
        $secondaryColors = $secondaryStyle ? $this->colorsFor($secondaryStyle) : collect();

        $primaryTake = $secondaryColors->isNotEmpty()
            ? min($primaryColors->count(), self::MAX_FROM_PRIMARY_WHEN_SECONDARY_PRESENT)
            : self::TARGET_COUNT;

        $selected = $primaryColors->take($primaryTake);

        if ($selected->count() < self::TARGET_COUNT && $secondaryColors->isNotEmpty()) {
            $selected = $selected->concat(
                $secondaryColors
                    ->reject(fn (AccentColor $color) => $selected->contains('id', $color->id))
                    ->take(self::TARGET_COUNT - $selected->count())
            );
        }

        // Vult aan met meer primaire kleuren als de gecombineerde catalogus voor deze combinatie
        // van stijlen kleiner is dan het streefaantal (bv. een kleine secundaire-stijlset).
        if ($selected->count() < self::TARGET_COUNT) {
            $selected = $selected->concat(
                $primaryColors
                    ->reject(fn (AccentColor $color) => $selected->contains('id', $color->id))
                    ->take(self::TARGET_COUNT - $selected->count())
            );
        }

        return $selected->values();
    }

    /** @return Collection<int, AccentColor> */
    private function colorsFor(string $styleKey): Collection
    {
        return AccentColor::query()
            ->active()
            ->get()
            ->filter(fn (AccentColor $color) => in_array($styleKey, $color->linkedStyleKeys(), true))
            ->values();
    }
}

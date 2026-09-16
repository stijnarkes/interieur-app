<?php

namespace App\Support;

/**
 * Vertaalt een ruw feit uit PartnerComparisonService (zie PartnerComparison::facts) naar een
 * leesbare Nederlandse zin — gebruikt door de gezamenlijke PDF (resources/views/pdf/
 * partner-result.blade.php). De webpagina doet dezelfde vertaling client-side (zie
 * resources/js/partner/resultPage.js's FACT_LABELS) omdat die daar al de losse velden nodig heeft
 * voor styling; hier gaat het puur om platte tekst voor in het rapport.
 */
class PartnerFactPresenter
{
    /**
     * Vervangt de ruwe style_key-velden in elk feit door hun leesbare label — de webpagina/PDF
     * tonen zelf nooit een interne key. Gedeeld tussen PartnerComparisonController::show() en
     * PartnerReportPdfService, de enige twee plekken die PartnerComparisonService-output ooit
     * naar buiten geven.
     *
     * @param  array{similarities?: array, differences?: array}  $facts
     * @param  callable(?string): ?string  $styleLabel
     * @return array{similarities: array, differences: array}
     */
    public static function resolveStyleKeys(array $facts, callable $styleLabel): array
    {
        $mapFact = function (array $fact) use ($styleLabel): array {
            foreach (['styleKey', 'initiatorStyleKey', 'partnerStyleKey'] as $field) {
                if (isset($fact[$field])) {
                    $fact[$field] = $styleLabel($fact[$field]);
                }
            }

            return $fact;
        };

        return [
            'similarities' => array_map($mapFact, $facts['similarities'] ?? []),
            'differences' => array_map($mapFact, $facts['differences'] ?? []),
        ];
    }

    public static function describe(array $fact): ?string
    {
        return match ($fact['type'] ?? null) {
            'primary_style_match' => "Jullie hebben allebei {$fact['styleKey']} als hoofdstijl.",
            'secondary_style_match' => "Jullie delen ook {$fact['styleKey']} als invloed.",
            'primary_style_difference' => "Verschillende hoofdstijl: {$fact['initiatorStyleKey']} bij de één, {$fact['partnerStyleKey']} bij de ander.",
            'base_palette_color_match' => 'Jullie kozen (deels) dezelfde basiskleur.',
            'accent_color_match' => 'Jullie kozen dezelfde accentkleur.',
            'shared_option_selection' => 'Bij minstens één vraag kozen jullie precies hetzelfde.',
            'shared_material_tags' => 'Gedeelde materiaalvoorkeur: '.implode(', ', $fact['tags'] ?? []).'.',
            default => null,
        };
    }

    /**
     * @param  array{styleKey?: string}[]  $facts  `styleKey`/`initiatorStyleKey`/`partnerStyleKey`
     *   moeten hier al herleesbare labels zijn (zie PartnerComparisonController::resolveFactLabels()).
     * @return array<int, string>
     */
    public static function describeAll(array $facts): array
    {
        return array_values(array_filter(array_map(self::describe(...), $facts)));
    }
}

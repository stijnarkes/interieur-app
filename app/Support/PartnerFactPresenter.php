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

    /**
     * Vervangt de `optionSlugs` van een shared_option_selection-feit door hun titel
     * (`optionTitles`) — zelfde aanpak als resolveStyleKeys() hierboven, alleen voor
     * QuizOption::option_slug i.p.v. style_key. Een slug zonder match (bv. een inmiddels
     * verwijderde/hernoemde optie) valt terug op de ruwe slug i.p.v. uit de zin te verdwijnen —
     * dat zou de telling in describeSharedOptions() anders laten kloppen met wat er staat.
     *
     * @param  array{similarities?: array, differences?: array}  $facts
     * @param  callable(string): ?string  $optionTitle
     * @return array{similarities: array, differences: array}
     */
    public static function resolveOptionTitles(array $facts, callable $optionTitle): array
    {
        $mapFact = function (array $fact) use ($optionTitle): array {
            if (isset($fact['optionSlugs'])) {
                $fact['optionTitles'] = array_map(
                    fn (string $slug): string => $optionTitle($slug) ?? $slug,
                    $fact['optionSlugs'],
                );
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
            'shared_option_selection' => self::describeSharedOptions($fact['optionTitles'] ?? []),
            'shared_material_tags' => 'Gedeelde materiaalvoorkeur: '.implode(', ', $fact['tags'] ?? []).'.',
            default => null,
        };
    }

    /**
     * Noemt de daadwerkelijk gedeelde keuzes bij naam i.p.v. alleen "bij minstens één vraag" te
     * zeggen — die telling/namen zaten al in de feit-data (PartnerComparisonService::
     * sharedOptionSlugs()), maar werden hiervoor genegeerd. Geen titels bekend (bv. geen
     * $optionTitle-resolutie toegepast) -> terugvallen op de oude, generieke zin i.p.v. een lege
     * bewering.
     *
     * @param  string[]  $titles
     */
    private static function describeSharedOptions(array $titles): string
    {
        if ($titles === []) {
            return 'Bij minstens één vraag kozen jullie precies hetzelfde.';
        }

        if (count($titles) === 1) {
            return "Jullie kozen allebei voor {$titles[0]}.";
        }

        $last = array_pop($titles);

        return 'Jullie kozen allebei voor '.implode(', ', $titles)." en {$last}.";
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

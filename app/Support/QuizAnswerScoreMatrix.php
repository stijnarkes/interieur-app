<?php

namespace App\Support;

/**
 * Eén centraal, onderhoudbaar scoreobject voor de negen beeldvragen (V1-V9) van de woonstijltest —
 * zie de opdracht "scoring woonstijltest Boer Staphorst". Elke optie krijgt hier, onafhankelijk van
 * de andere vijf stijlen, een vaste 0-1-matchscore per stijl (alleen 0,00/0,30/0,50/0,70/0,85/1,00
 * komen voor; de zes waarden van één optie hoeven niet tot 1 op te tellen). Dit vervangt voor de
 * uitslagberekening (zie QuizScoringService) de oude, aan de optie gekoppelde stijl-tags
 * (QuizOption::style_keys) — die blijven verder gewoon bestaan voor andere doeleinden (admin-
 * overzicht, badges), maar tellen hier niet meer mee.
 *
 * Sleutel is `option_slug` (stabiel, uniek, blijft hetzelfde bij een admin-reorder — zie
 * QuizOptionsPage) i.p.v. vraagvolgorde of database-id, zodat een interne array-volgorde-wijziging
 * nooit stilzwijgend een verkeerde koppeling veroorzaakt. Elke optie hieronder is één-op-één
 * geverifieerd tegen de zichtbare afbeelding (niet alleen de titel) vóór koppeling — zie het
 * begeleidende verslag voor de volledige V1-V9/O1-O12-naar-option_slug-mapping. De ene gevonden
 * titel/afbeelding-mismatch (V8 O9 heette "Hanglamp Tivoli, zand" maar toont een zwarte railspot —
 * altijd al inhoudelijk juist gekoppeld) is inmiddels gecorrigeerd, zie
 * 2026_09_29_161000_fix_v8_o9_lighting_option_title_mismatch.
 *
 * De kolom "Bedoelde hoofdstijl" uit de brontabel is bewust NERGENS in deze klasse terug te vinden
 * — dat was in de opdracht uitdrukkelijk documentatie, nooit een vaste uitslag of extra bonus.
 */
class QuizAnswerScoreMatrix
{
    public const HOTEL_LUXE = 'hotelLuxe';

    public const LANDELIJK = 'landelijk';

    public const JAPANDI = 'japandi';

    public const KLEUR_EXPLOSIE = 'kleurExplosie';

    public const MODERN = 'modern';

    public const MODERN_SCANDINAVISCH = 'scandinavisch';

    /**
     * @return array<string, float> style_key => score (0-1), of een lege array voor een
     *   onbekende/nog niet gekoppelde option_slug — draagt dan bewust 0 bij aan elke stijl in
     *   plaats van een fout te gooien (bv. een gloednieuwe, nog niet gescoorde antwoordoptie).
     */
    public static function scoresFor(string $optionSlug): array
    {
        return self::matrix()[$optionSlug] ?? [];
    }

    /** @return array<int, string> */
    public static function styleKeys(): array
    {
        return [
            self::HOTEL_LUXE,
            self::LANDELIJK,
            self::JAPANDI,
            self::KLEUR_EXPLOSIE,
            self::MODERN,
            self::MODERN_SCANDINAVISCH,
        ];
    }

    /**
     * @return array<string, array<string, float>> option_slug => (style_key => score)
     */
    private static function matrix(): array
    {
        // Kolomvolgorde per rij: Hotel luxe, Landelijk, Japandi, Kleur explosie, Modern,
        // Modern Scandinavisch — exact zoals de brontabel.
        $score = fn (float $hotelLuxe, float $landelijk, float $japandi, float $kleurExplosie, float $modern, float $modernScandinavisch): array => [
            self::HOTEL_LUXE => $hotelLuxe,
            self::LANDELIJK => $landelijk,
            self::JAPANDI => $japandi,
            self::KLEUR_EXPLOSIE => $kleurExplosie,
            self::MODERN => $modern,
            self::MODERN_SCANDINAVISCH => $modernScandinavisch,
        ];

        return [
            // V1 — Welke tegel spreekt jou het meeste aan?
            'welke-tegel-spreekt-jou-het-meeste-aan-ghjat-apandi-iOhXq' => $score(0.50, 0.30, 0.85, 0.00, 0.70, 0.70), // O1 Beige smalle steenstrips
            'welke-tegel-spreekt-jou-het-meeste-aan-ghjat-kleurexplosie-4JsPV' => $score(0.00, 0.30, 0.50, 0.30, 0.70, 0.85), // O2 Saliegroene zeshoekige tegels
            'welke-tegel-spreekt-jou-het-meeste-aan-ghjat-tegel-pM8Bk' => $score(1.00, 0.00, 0.30, 0.30, 0.70, 0.30), // O3 Bronskleurige geribbelde tegels
            'welke-tegel-spreekt-jou-het-meeste-aan-ghjat-landelijk-0N7b9' => $score(0.00, 0.85, 0.50, 0.50, 0.30, 0.30), // O4 Terracotta vierkante tegels
            'welke-tegel-spreekt-jou-het-meeste-aan-ghjat-modern-jCh5x' => $score(0.85, 0.00, 0.30, 0.00, 0.70, 0.30), // O5 Witte marmerlook met aders
            'welke-tegel-spreekt-jou-het-meeste-aan-ghjat-kleur-explosie-xDjAW' => $score(0.30, 0.30, 0.00, 1.00, 0.50, 0.30), // O6 Veelkleurige tegelstalen

            // V2 — Welk behang spreekt jou het meest aan?
            'wallcolor-behang-hotel-luxe-0wBtI' => $score(1.00, 0.00, 0.00, 0.30, 0.50, 0.30), // O1 Donker goud geometrisch patroon
            'wallcolor-behang-landelijk-3AUJj' => $score(0.30, 0.85, 0.50, 0.00, 0.30, 0.50), // O2 Crèmekleurig botanisch lijnpatroon
            'wallcolor-behang-modern-OVEAR' => $score(0.30, 0.30, 0.70, 0.00, 0.70, 0.85), // O3 Licht effen structuurbehang
            'wallcolor-behang-scandinavisch-nErLL' => $score(0.30, 0.00, 0.30, 0.00, 0.85, 0.70), // O4 Grijs geometrisch lijnpatroon
            'wallcolor-behang-japandi-VrAkn' => $score(0.30, 0.50, 0.85, 0.00, 0.30, 0.70), // O5 Beige geweven textuurbehang
            'wallcolor-behang-kleurexplosie-A3UFt' => $score(0.30, 0.30, 0.00, 0.85, 0.70, 0.30), // O6 Roze en terracotta strepen

            // V3 — Welke meubelstof spreekt jou het meest aan?
            'sofamaterial-japandi-J0z7z' => $score(0.30, 0.50, 0.85, 0.00, 0.30, 0.70), // O1 Licht greige weefsel
            'sofamaterial-fusion-127-brandy-5QVZ5' => $score(0.50, 0.70, 0.70, 0.30, 0.30, 0.30), // O2 Warme bruine grof geweven stof
            'sofamaterial-racer-109-desert-MpWcZ' => $score(0.30, 0.50, 0.70, 0.00, 0.50, 0.70), // O3 Ivoorkleurige stof met fijne strepen
            'sofamaterial-kleurexplosie-aHy4z' => $score(0.70, 0.30, 0.30, 0.85, 0.50, 0.30), // O4 Diepgroene gladde stof
            'sofamaterial-miami-taupe-12-gIDw5' => $score(0.30, 0.00, 0.30, 0.00, 0.70, 0.70), // O5 Koel grijs fijn weefsel
            'sofamaterial-scandinavisch-m8Leo' => $score(0.30, 0.30, 0.70, 0.00, 0.50, 0.70), // O6 Ivoor met subtiel geometrisch patroon

            // V4 — In welke woonkamer voel jij je het meest thuis?
            'sofamodel-bank-helios-wXWIy' => $score(0.70, 0.50, 0.00, 0.50, 0.50, 0.30), // O1 Cognackleurige gecapitonneerde bank
            'sofa-model-japandi' => $score(0.00, 0.00, 0.00, 0.00, 0.85, 0.70), // O2 Grijze hoekbank op donkere poten
            'sofamodel-bank-moa-7uyUN' => $score(0.30, 0.00, 0.00, 0.85, 0.85, 0.50), // O3 Roze modulaire hoekbank
            'sofamodel-bank-niella-iaOm3' => $score(0.30, 0.30, 0.70, 0.00, 0.50, 0.70), // O4 Lichte lage modulaire bank
            'sofa-model-hotel-chique' => $score(0.50, 0.00, 0.70, 0.00, 0.85, 0.50), // O5 Ivoorkleurige afgeronde ligbank
            'sofamodel-bank-vint-rg8T5' => $score(0.85, 0.30, 0.00, 0.70, 0.50, 0.30), // O6 Olijfgroene loungeopstelling

            // V5 — Welke keuken spreekt jou het meest aan? (gewicht 1,5)
            'welke-keuken-spreekt-jou-het-meeste-aan-hecr6-test-MCgYK' => $score(0.00, 0.30, 0.85, 0.00, 0.70, 0.85), // O1 Lichte keuken met houten bovenkasten
            'welke-keuken-spreekt-jou-het-meeste-aan-hecr6-hotel-luxe-en-modern-luxe-lgBQl' => $score(1.00, 0.00, 0.30, 0.30, 0.70, 0.30), // O2 Bruine eilandkeuken met gouden accenten
            'welke-keuken-spreekt-jou-het-meeste-aan-hecr6-keuken-modern-dv9ns' => $score(0.50, 0.00, 0.30, 0.00, 1.00, 0.30), // O3 Taupe keuken met zwart eiland
            'welke-keuken-spreekt-jou-het-meeste-aan-hecr6-scandinavisch-hH5e3' => $score(0.00, 0.30, 0.30, 0.50, 0.70, 0.85), // O4 Lichte keuken met blauw tegelkookeiland
            'welke-keuken-spreekt-jou-het-meeste-aan-hecr6-landelijk-cRrQW' => $score(0.85, 0.85, 0.00, 0.00, 0.30, 0.30), // O5 Witte paneelkeuken met marmer
            'welke-keuken-spreekt-jou-het-meeste-aan-hecr6-kleur-explosie-keuken-uspKq' => $score(0.30, 0.00, 0.00, 1.00, 0.85, 0.50), // O6 Oranje kookeiland in lichte ruimte

            // V6 — Welk servies spreekt jou het meest aan?
            'welk-servies-spreekt-jou-het-meest-aan-w3569-alle-stijlen-ahyDp' => $score(0.30, 0.50, 0.70, 0.70, 0.00, 0.30), // O1 Aardewerken bekers met turquoise glazuur
            'welk-servies-spreekt-jou-het-meest-aan-w3569-kleur-explosie-xnNvY' => $score(0.00, 0.30, 0.30, 1.00, 0.30, 0.30), // O2 Veelkleurige geglazuurde bekers
            'welk-servies-spreekt-jou-het-meest-aan-w3569-modern-luxe-JVtxZ' => $score(0.00, 1.00, 0.00, 0.50, 0.00, 0.30), // O3 Blauw wit bloemservies
            'welk-servies-spreekt-jou-het-meest-aan-w3569-modern-luxe-CoJDy' => $score(1.00, 0.00, 0.00, 0.50, 0.50, 0.30), // O4 Kopjes met goudkleurige oren
            'welk-servies-spreekt-jou-het-meest-aan-w3569-modern-wS57t' => $score(0.30, 0.00, 0.00, 0.30, 1.00, 0.30), // O5 Wit hoekig kopje met zwarte lijnen
            'welk-servies-spreekt-jou-het-meest-aan-w3569-servies-scandinavisch-NRuU3' => $score(0.00, 0.30, 0.50, 0.00, 0.70, 0.85), // O6 Eenvoudig wit servies

            // V7 — Welke stoel zou jij kiezen?
            'welke-eethoek-zou-jij-kiezen-y50iu-landelijk-brJYi' => $score(0.00, 0.30, 0.50, 0.00, 0.50, 0.85), // O1 Lichte kuipstoel met houten draaipoot
            'welke-eethoek-zou-jij-kiezen-y50iu-kleurexplosie-3IAL1' => $score(0.30, 0.00, 0.00, 1.00, 0.85, 0.30), // O2 Rode stoel met lila metalen poten
            'welke-eethoek-zou-jij-kiezen-y50iu-japandi-eethoek-6pKTQ' => $score(0.30, 0.00, 0.30, 0.00, 0.85, 0.70), // O3 Lichte gestoffeerde stoel met metaalpoot
            'welke-eethoek-zou-jij-kiezen-y50iu-scandinavisch-evVln' => $score(0.00, 0.00, 0.00, 0.00, 0.70, 0.85), // O4 Grijze hoekbank met dunne poten (bewust: zie klassedocblok)
            'welke-eethoek-zou-jij-kiezen-y50iu-hotel-luxe-eetkamer-aZYxc' => $score(0.70, 0.50, 0.30, 0.30, 0.70, 0.30), // O5 Cognackleurige ronde fauteuil
            'welke-eethoek-zou-jij-kiezen-y50iu-moderne-eetkamer-IbSmr' => $score(0.00, 0.30, 0.30, 0.00, 0.85, 0.70), // O6 Beige stoel met zwarte armleuningen

            // V8 — Welke verlichting spreekt jou het meest aan? (12 opties)
            'lighting-japandi' => $score(0.00, 0.50, 1.00, 0.00, 0.30, 0.50), // O1 Gevlochten organische hanglamp
            'lighting-modern-country' => $score(0.00, 0.50, 0.85, 0.00, 0.70, 0.70), // O2 Houten tafellamp met cilindervorm
            'lighting-kleurexplosie-wXWI3' => $score(0.85, 0.30, 0.00, 0.70, 0.70, 0.30), // O3 Drie amberkleurige glazen pendels
            'lighting-scandinavisch-SsY5u' => $score(0.30, 0.00, 0.50, 0.00, 0.85, 0.85), // O4 Witte bolvormige pendel
            'lighting-modern-luxe-BV5RH' => $score(1.00, 0.00, 0.00, 0.50, 0.70, 0.30), // O5 Goudkleurige gelaagde hanglamp
            'lighting-modern-luxe-gZTNi' => $score(0.85, 0.30, 0.50, 0.30, 0.70, 0.30), // O6 Open bronzen druppelpendel
            'lighting-scandinavisch-landelijk-fjKTS' => $score(0.00, 0.50, 0.85, 0.00, 0.30, 0.70), // O7 Lichte stoffen ballonhanglamp
            'lighting-bold-monkey-dont-be-afraid-of-colour-tafellamp-pink-wsVlH' => $score(0.30, 0.00, 0.00, 1.00, 0.50, 0.30), // O8 Roze tafellamp met bont onderstel
            'lighting-hanglamp-tivoli-zand-ycz8O' => $score(0.00, 0.00, 0.00, 0.00, 1.00, 0.30), // O9 Zwarte lineaire railspot
            'lighting-freelight-hanglamp-livello-creme-led-32-watt-o-40cm-tYCeU' => $score(0.30, 0.00, 0.30, 0.00, 0.85, 0.70), // O10 Beige ringvormige hanglamp
            'lighting-lamp-scandinavisch-I31NW' => $score(0.00, 0.30, 0.50, 0.00, 0.70, 0.85), // O11 Zandkleurige conische pendel
            'lighting-lamp-landelijk-t8Bok' => $score(0.30, 0.30, 0.50, 0.00, 0.85, 0.50), // O12 Witte keramische sculptuurlamp

            // V9 — Welke badkamer spreekt jou het meest aan? (gewicht 1,5)
            'welke-badkamer-spreekt-jou-het-meest-aan-e17ah-badkamer-kleurexplosie-uSTv4' => $score(0.00, 0.00, 0.00, 1.00, 0.50, 0.30), // O1 Badkamer met turquoise en oranje tegels
            'welke-badkamer-spreekt-jou-het-meest-aan-e17ah-badkamer-japandi-cVaOF' => $score(0.30, 0.30, 0.85, 0.00, 0.50, 0.70), // O2 Bad met grijze steen en natuurlijke accenten
            'welke-badkamer-spreekt-jou-het-meest-aan-e17ah-badkamer-landelijk-a9PwQ' => $score(0.00, 0.85, 0.85, 0.00, 0.30, 0.30), // O3 Houten wastafelmeubel met stenen kommen
            'welke-badkamer-spreekt-jou-het-meest-aan-e17ah-badkamer-modern-hZ4vR' => $score(0.30, 0.00, 0.30, 0.00, 1.00, 0.70), // O4 Grijze badkamer met strakke witte meubels
            'welke-badkamer-spreekt-jou-het-meest-aan-e17ah-badkamer-scandinavisch-sCVuS' => $score(0.30, 0.00, 0.00, 0.70, 0.85, 0.50), // O5 Badkamer met marineblauwe tegelwand
            'welke-badkamer-spreekt-jou-het-meest-aan-e17ah-badkamer-hotel-luxe-XRRql' => $score(1.00, 0.00, 0.30, 0.00, 0.70, 0.30), // O6 Marmer en donker hout met goudkleurige kranen
        ];
    }
}

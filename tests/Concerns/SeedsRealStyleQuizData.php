<?php

namespace Tests\Concerns;

use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Support\QuizAnswerScoreMatrix;

/**
 * Bouwt de negen echte vragen en 60 echte antwoordopties van woonstijl.boer-staphorst.nl na —
 * zelfde question_key/option_slug als productie (zie het uitvoeringsverslag bij de opdracht
 * "scoring woonstijltest Boer Staphorst" voor de volledige V1-V9/O1-O12-mapping) — zodat
 * QuizAnswerScoreMatrix::scoresFor() ze herkent. Sinds de uitslagberekening volledig op die
 * matrix leunt (in plaats van op QuizOption::style_keys), heeft elke test die een realistisch,
 * berekenbaar quizresultaat nodig heeft deze échte data nodig — een verzonnen vraag/optie scoort
 * overal 0 en levert dus nooit een zinvolle hoofdstijl op.
 */
trait SeedsRealStyleQuizData
{
    private const HOTEL_LUXE = QuizAnswerScoreMatrix::HOTEL_LUXE;

    private const LANDELIJK = QuizAnswerScoreMatrix::LANDELIJK;

    private const JAPANDI = QuizAnswerScoreMatrix::JAPANDI;

    private const KLEUR_EXPLOSIE = QuizAnswerScoreMatrix::KLEUR_EXPLOSIE;

    private const MODERN = QuizAnswerScoreMatrix::MODERN;

    private const MODERN_SCANDINAVISCH = QuizAnswerScoreMatrix::MODERN_SCANDINAVISCH;

    /**
     * question_key => ['weight' => float, 'options' => [option_slug => bedoelde_hoofdstijl]]
     * In precies deze volgorde (O1..On) — de "bedoelde hoofdstijl" is hier puur om per stijl een
     * duidelijke testroute te kunnen kiezen, exact zoals de opdracht ("Bedoelde hoofdstijl" is
     * documentatie) 'm ook alleen als zodanig gebruikt, nooit als extra scorebonus.
     *
     * @return array<string, array{weight: float, options: array<string, string>}>
     */
    private function realQuestions(): array
    {
        return [
            'welke-tegel-spreekt-jou-het-meeste-aan-GhJat' => ['weight' => 1.0, 'options' => [
                'welke-tegel-spreekt-jou-het-meeste-aan-ghjat-apandi-iOhXq' => self::JAPANDI,
                'welke-tegel-spreekt-jou-het-meeste-aan-ghjat-kleurexplosie-4JsPV' => self::MODERN_SCANDINAVISCH,
                'welke-tegel-spreekt-jou-het-meeste-aan-ghjat-tegel-pM8Bk' => self::HOTEL_LUXE,
                'welke-tegel-spreekt-jou-het-meeste-aan-ghjat-landelijk-0N7b9' => self::LANDELIJK,
                'welke-tegel-spreekt-jou-het-meeste-aan-ghjat-modern-jCh5x' => self::MODERN,
                'welke-tegel-spreekt-jou-het-meeste-aan-ghjat-kleur-explosie-xDjAW' => self::KLEUR_EXPLOSIE,
            ]],
            'wallColor' => ['weight' => 1.0, 'options' => [
                'wallcolor-behang-hotel-luxe-0wBtI' => self::HOTEL_LUXE,
                'wallcolor-behang-landelijk-3AUJj' => self::LANDELIJK,
                'wallcolor-behang-modern-OVEAR' => self::MODERN,
                'wallcolor-behang-scandinavisch-nErLL' => self::MODERN_SCANDINAVISCH,
                'wallcolor-behang-japandi-VrAkn' => self::JAPANDI,
                'wallcolor-behang-kleurexplosie-A3UFt' => self::KLEUR_EXPLOSIE,
            ]],
            'sofaMaterial' => ['weight' => 1.0, 'options' => [
                'sofamaterial-japandi-J0z7z' => self::JAPANDI,
                'sofamaterial-fusion-127-brandy-5QVZ5' => self::HOTEL_LUXE,
                'sofamaterial-racer-109-desert-MpWcZ' => self::LANDELIJK,
                'sofamaterial-kleurexplosie-aHy4z' => self::KLEUR_EXPLOSIE,
                'sofamaterial-miami-taupe-12-gIDw5' => self::MODERN,
                'sofamaterial-scandinavisch-m8Leo' => self::MODERN_SCANDINAVISCH,
            ]],
            'sofaModel' => ['weight' => 1.0, 'options' => [
                'sofamodel-bank-helios-wXWIy' => self::HOTEL_LUXE,
                'sofa-model-japandi' => self::MODERN,
                'sofamodel-bank-moa-7uyUN' => self::MODERN_SCANDINAVISCH,
                'sofamodel-bank-niella-iaOm3' => self::LANDELIJK,
                'sofa-model-hotel-chique' => self::JAPANDI,
                'sofamodel-bank-vint-rg8T5' => self::KLEUR_EXPLOSIE,
            ]],
            'welke-keuken-spreekt-jou-het-meeste-aan-hecR6' => ['weight' => 1.5, 'options' => [
                'welke-keuken-spreekt-jou-het-meeste-aan-hecr6-test-MCgYK' => self::JAPANDI,
                'welke-keuken-spreekt-jou-het-meeste-aan-hecr6-hotel-luxe-en-modern-luxe-lgBQl' => self::HOTEL_LUXE,
                'welke-keuken-spreekt-jou-het-meeste-aan-hecr6-keuken-modern-dv9ns' => self::MODERN,
                'welke-keuken-spreekt-jou-het-meeste-aan-hecr6-scandinavisch-hH5e3' => self::MODERN_SCANDINAVISCH,
                'welke-keuken-spreekt-jou-het-meeste-aan-hecr6-landelijk-cRrQW' => self::LANDELIJK,
                'welke-keuken-spreekt-jou-het-meeste-aan-hecr6-kleur-explosie-keuken-uspKq' => self::KLEUR_EXPLOSIE,
            ]],
            'welk-servies-spreekt-jou-het-meest-aan-W3569' => ['weight' => 1.0, 'options' => [
                'welk-servies-spreekt-jou-het-meest-aan-w3569-alle-stijlen-ahyDp' => self::JAPANDI,
                'welk-servies-spreekt-jou-het-meest-aan-w3569-kleur-explosie-xnNvY' => self::KLEUR_EXPLOSIE,
                'welk-servies-spreekt-jou-het-meest-aan-w3569-modern-luxe-JVtxZ' => self::LANDELIJK,
                'welk-servies-spreekt-jou-het-meest-aan-w3569-modern-luxe-CoJDy' => self::HOTEL_LUXE,
                'welk-servies-spreekt-jou-het-meest-aan-w3569-modern-wS57t' => self::MODERN,
                'welk-servies-spreekt-jou-het-meest-aan-w3569-servies-scandinavisch-NRuU3' => self::MODERN_SCANDINAVISCH,
            ]],
            'welke-eethoek-zou-jij-kiezen-y50IU' => ['weight' => 1.0, 'options' => [
                'welke-eethoek-zou-jij-kiezen-y50iu-landelijk-brJYi' => self::LANDELIJK,
                'welke-eethoek-zou-jij-kiezen-y50iu-kleurexplosie-3IAL1' => self::KLEUR_EXPLOSIE,
                'welke-eethoek-zou-jij-kiezen-y50iu-japandi-eethoek-6pKTQ' => self::JAPANDI,
                'welke-eethoek-zou-jij-kiezen-y50iu-scandinavisch-evVln' => self::MODERN_SCANDINAVISCH,
                'welke-eethoek-zou-jij-kiezen-y50iu-hotel-luxe-eetkamer-aZYxc' => self::HOTEL_LUXE,
                'welke-eethoek-zou-jij-kiezen-y50iu-moderne-eetkamer-IbSmr' => self::MODERN,
            ]],
            'lighting' => ['weight' => 1.0, 'options' => [
                'lighting-japandi' => self::JAPANDI,
                'lighting-modern-country' => self::JAPANDI,
                'lighting-kleurexplosie-wXWI3' => self::KLEUR_EXPLOSIE,
                'lighting-scandinavisch-SsY5u' => self::MODERN_SCANDINAVISCH,
                'lighting-modern-luxe-BV5RH' => self::HOTEL_LUXE,
                'lighting-modern-luxe-gZTNi' => self::HOTEL_LUXE,
                'lighting-scandinavisch-landelijk-fjKTS' => self::LANDELIJK,
                'lighting-bold-monkey-dont-be-afraid-of-colour-tafellamp-pink-wsVlH' => self::KLEUR_EXPLOSIE,
                'lighting-hanglamp-tivoli-zand-ycz8O' => self::MODERN,
                'lighting-freelight-hanglamp-livello-creme-led-32-watt-o-40cm-tYCeU' => self::MODERN,
                'lighting-lamp-scandinavisch-I31NW' => self::MODERN_SCANDINAVISCH,
                'lighting-lamp-landelijk-t8Bok' => self::LANDELIJK,
            ]],
            'welke-badkamer-spreekt-jou-het-meest-aan-E17aH' => ['weight' => 1.5, 'options' => [
                'welke-badkamer-spreekt-jou-het-meest-aan-e17ah-badkamer-kleurexplosie-uSTv4' => self::KLEUR_EXPLOSIE,
                'welke-badkamer-spreekt-jou-het-meest-aan-e17ah-badkamer-japandi-cVaOF' => self::JAPANDI,
                'welke-badkamer-spreekt-jou-het-meest-aan-e17ah-badkamer-landelijk-a9PwQ' => self::LANDELIJK,
                'welke-badkamer-spreekt-jou-het-meest-aan-e17ah-badkamer-modern-hZ4vR' => self::MODERN,
                'welke-badkamer-spreekt-jou-het-meest-aan-e17ah-badkamer-scandinavisch-sCVuS' => self::MODERN_SCANDINAVISCH,
                'welke-badkamer-spreekt-jou-het-meest-aan-e17ah-badkamer-hotel-luxe-XRRql' => self::HOTEL_LUXE,
            ]],
        ];
    }

    /** @return array<string, array<int, string>> alle 9 question_keys => hun option_slugs, in volgorde */
    private function seedRealQuizQuestionsAndOptions(): array
    {
        $slugsByQuestion = [];

        foreach ($this->realQuestions() as $questionKey => $config) {
            // updateOrCreate i.p.v. create(): 4 van de 9 echte question_keys (wallColor,
            // sofaMaterial, sofaModel, lighting) zijn hergebruikte rijen uit de oorspronkelijke,
            // vaste vragenlijst (zie 2026_08_27_094537_create_quiz_questions_table, die deze al met
            // een insert() seedt) — de admin heeft ze later hernoemd i.p.v. verwijderd/opnieuw
            // aangemaakt. Een kale create() zou daarom op precies deze vier botsen op de unieke
            // question_key-constraint.
            // Meubelstof en verlichting staan op max_selections = 2 (twee kiezen toegestaan, niet
            // verplicht — zie QuizScoringService voor de gemiddelde-score-regel bij twee keuzes),
            // de overige zeven op 1.
            $maxSelections = in_array($questionKey, ['sofaMaterial', 'lighting'], true) ? 2 : 1;

            QuizQuestion::updateOrCreate(
                ['question_key' => $questionKey],
                [
                    'section' => 'materials-colors',
                    'title' => $questionKey,
                    'folder' => null,
                    'sort_order' => 10,
                    'max_selections' => $maxSelections,
                    'weight' => $config['weight'],
                    'image_display_mode' => 'contain',
                ],
            );

            $slugs = [];
            $sortOrder = 10;
            foreach ($config['options'] as $optionSlug => $bedoeldeHoofdstijl) {
                QuizOption::create([
                    'question_id' => $questionKey,
                    'sort_order' => $sortOrder,
                    'style_key' => $bedoeldeHoofdstijl,
                    'option_slug' => $optionSlug,
                    'style_keys' => [$bedoeldeHoofdstijl],
                    'title' => $optionSlug,
                    'is_active' => true,
                ]);
                $slugs[] = $optionSlug;
                $sortOrder += 10;
            }
            $slugsByQuestion[$questionKey] = $slugs;
        }

        return $slugsByQuestion;
    }

    /**
     * Bouwt een antwoordenset die voor elke vraag de optie kiest waarvan de "bedoelde hoofdstijl"
     * de gegeven stijl is — betrouwbaar genoeg om die stijl als hoofdstijl te laten winnen (zie
     * QuizScoringWoonstijlTest::alle_negen_bedoelde_opties_voor_een_stijl_geven_die_stijl_als_hoofdstijl()).
     *
     * @return array<string, array<int, string>>
     */
    private function answersFavoring(string $styleKey): array
    {
        $answers = [];
        foreach ($this->realQuestions() as $questionKey => $config) {
            foreach ($config['options'] as $optionSlug => $bedoeldeHoofdstijl) {
                if ($bedoeldeHoofdstijl === $styleKey) {
                    $answers[$questionKey] = [$optionSlug];
                    break;
                }
            }
        }

        return $answers;
    }
}

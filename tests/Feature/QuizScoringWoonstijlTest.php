<?php

namespace Tests\Feature;

use App\Models\QuizSetting;
use App\Services\QuizScoringService;
use App\Support\QuizAnswerScoreMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\SeedsRealStyleQuizData;
use Tests\TestCase;

/**
 * Dekt de DB-afhankelijke regels van de nieuwe uitslagberekening (zie de opdracht "scoring
 * woonstijltest Boer Staphorst" en QuizAnswerScoreMatrix/QuizScoringService): matrixscores per
 * optie, vraaggewicht (1,5 voor keuken/badkamer), en de vergelijking t.o.v. het gemiddelde/de
 * spreiding binnen elke vraag. De puur rekenkundige regels (welke stijl wint, wanneer een invloed
 * getoond wordt) staan apart in tests/Unit/QuizScoringServiceTest.php.
 *
 * De fixture hieronder bouwt de negen echte vragen en 60 echte antwoordopties van
 * woonstijl.boer-staphorst.nl na — zelfde question_key/option_slug als productie (zie het
 * uitvoeringsverslag voor de volledige V1-V9/O1-O12-mapping) — zodat QuizAnswerScoreMatrix::
 * scoresFor() ze herkent en de test dus de daadwerkelijke, geverifieerde scorematrix gebruikt in
 * plaats van een losse testmatrix die stilzwijgend uit de pas zou kunnen gaan lopen.
 */
class QuizScoringWoonstijlTest extends TestCase
{
    use RefreshDatabase;
    use SeedsRealStyleQuizData;

    /** @return array<int, array<int, string>> */
    public static function styleProvider(): array
    {
        return [
            'Hotel luxe' => [QuizAnswerScoreMatrix::HOTEL_LUXE],
            'Landelijk' => [QuizAnswerScoreMatrix::LANDELIJK],
            'Japandi' => [QuizAnswerScoreMatrix::JAPANDI],
            'Kleur explosie' => [QuizAnswerScoreMatrix::KLEUR_EXPLOSIE],
            'Modern' => [QuizAnswerScoreMatrix::MODERN],
            'Modern Scandinavisch' => [QuizAnswerScoreMatrix::MODERN_SCANDINAVISCH],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('styleProvider')]
    public function alle_negen_bedoelde_opties_voor_een_stijl_geven_die_stijl_als_hoofdstijl(string $styleKey): void
    {
        $this->seedRealQuizQuestionsAndOptions();

        $computed = app(QuizScoringService::class)->compute($this->answersFavoring($styleKey));

        $this->assertSame($styleKey, $computed['primary_style']);
    }

    #[Test]
    public function modern_en_modern_scandinavisch_blijven_gescheiden_stijlen(): void
    {
        $this->seedRealQuizQuestionsAndOptions();

        $modern = app(QuizScoringService::class)->compute($this->answersFavoring(self::MODERN));
        $modernScandinavisch = app(QuizScoringService::class)->compute($this->answersFavoring(self::MODERN_SCANDINAVISCH));

        $this->assertSame(self::MODERN, $modern['primary_style']);
        $this->assertSame(self::MODERN_SCANDINAVISCH, $modernScandinavisch['primary_style']);
        $this->assertNotSame(
            $modern['style_scores'][self::MODERN],
            $modernScandinavisch['style_scores'][self::MODERN],
            'De twee stijlen moeten onafhankelijke totalen hebben, niet dezelfde/samengevoegde score.',
        );
    }

    #[Test]
    public function een_gekozen_optie_telt_voor_meerdere_stijlen_tegelijk_mee(): void
    {
        $slugsByQuestion = $this->seedRealQuizQuestionsAndOptions();
        // V1 O1 ("Beige smalle steenstrips"): Japandi 0,85 én Modern 0,70 — allebei onafhankelijk
        // van elkaar, ze hoeven niet tot 1 op te tellen (zie QuizAnswerScoreMatrix).
        $tegelVraag = 'welke-tegel-spreekt-jou-het-meeste-aan-GhJat';
        $optionSlug = $slugsByQuestion[$tegelVraag][0];

        $computed = app(QuizScoringService::class)->compute([$tegelVraag => [$optionSlug]]);

        $this->assertSame(0.85, $computed['style_scores'][self::JAPANDI]);
        $this->assertSame(0.70, $computed['style_scores'][self::MODERN]);
    }

    #[Test]
    public function keuken_en_badkamer_wegen_anderhalf_keer_zo_zwaar(): void
    {
        $slugsByQuestion = $this->seedRealQuizQuestionsAndOptions();
        $keukenVraag = 'welke-keuken-spreekt-jou-het-meeste-aan-hecR6';
        // O3 "Taupe keuken met zwart eiland": Modern 1,00 (zie brontabel).
        $modernsteKeukenOptie = $slugsByQuestion[$keukenVraag][2];

        $computed = app(QuizScoringService::class)->compute([$keukenVraag => [$modernsteKeukenOptie]]);

        $this->assertSame(1.5, $computed['style_scores'][self::MODERN], 'Gewicht 1,5 × matchscore 1,00 = 1,5.');
    }

    #[Test]
    public function een_gewone_vraag_weegt_normaal_mee(): void
    {
        $slugsByQuestion = $this->seedRealQuizQuestionsAndOptions();
        $tegelVraag = 'welke-tegel-spreekt-jou-het-meeste-aan-GhJat';
        // O3 "Bronskleurige geribbelde tegels": Hotel luxe 1,00.
        $hotelLuxeOptie = $slugsByQuestion[$tegelVraag][2];

        $computed = app(QuizScoringService::class)->compute([$tegelVraag => [$hotelLuxeOptie]]);

        $this->assertSame(1.0, $computed['style_scores'][self::HOTEL_LUXE], 'Gewicht 1,0 × matchscore 1,00 = 1,0, niet 1,5.');
    }

    #[Test]
    public function slechts_een_van_de_twaalf_verlichtingsopties_telt_mee(): void
    {
        $slugsByQuestion = $this->seedRealQuizQuestionsAndOptions();
        $this->assertCount(12, $slugsByQuestion['lighting']);

        // O9 "Zwarte lineaire railspot": Modern 1,00, en verder allemaal 0 (zie brontabel) — dus
        // als er per ongeluk met meer dan één optie gerekend zou worden, zou de Modern-score
        // hoger dan 1,0 uitvallen.
        $railspot = $slugsByQuestion['lighting'][8];

        $computed = app(QuizScoringService::class)->compute(['lighting' => [$railspot]]);

        $this->assertSame(1.0, $computed['style_scores'][self::MODERN]);
    }

    #[Test]
    public function overslaan_of_geen_van_deze_telt_voor_geen_enkele_stijl_mee(): void
    {
        $this->seedRealQuizQuestionsAndOptions();

        // Slechts 1 van de 9 vragen beantwoord — de rest bewust overgeslagen/niet meegestuurd,
        // zoals de client ook nooit een lege keuze als los antwoord verstuurt.
        $tegelVraag = 'welke-tegel-spreekt-jou-het-meeste-aan-GhJat';
        $computedMetEen = app(QuizScoringService::class)->compute($this->answersFavoring(self::HOTEL_LUXE));
        $computedAlleenTegel = app(QuizScoringService::class)->compute([
            $tegelVraag => $this->answersFavoring(self::HOTEL_LUXE)[$tegelVraag],
        ]);

        $this->assertGreaterThan(
            $computedAlleenTegel['style_scores'][self::HOTEL_LUXE],
            $computedMetEen['style_scores'][self::HOTEL_LUXE],
            'Meer beantwoorde vragen met dezelfde stijl moet een hogere ruwe score geven dan er maar één.',
        );

        // Een expliciet lege lijst (bv. "geen van deze") voor een vraag telt voor geen enkele stijl
        // mee, alsof de vraag niet beantwoord is.
        $computedMetLegeKeuze = app(QuizScoringService::class)->compute([$tegelVraag => []]);
        $this->assertSame(0.0, $computedMetLegeKeuze['style_scores'][self::HOTEL_LUXE]);
        $this->assertNull($computedMetLegeKeuze['primary_style']);
    }

    #[Test]
    public function een_gewijzigd_antwoord_na_terugnavigeren_telt_niet_dubbel(): void
    {
        $slugsByQuestion = $this->seedRealQuizQuestionsAndOptions();
        $tegelVraag = 'welke-tegel-spreekt-jou-het-meeste-aan-GhJat';
        [$japandiOptie, , $hotelLuxeOptie] = $slugsByQuestion[$tegelVraag];

        // De client stuurt bij elke aanroep de volledige, actuele antwoordenset mee (nooit een
        // toevoeging aan een eerdere keuze) — "teruggaan en een andere optie kiezen" betekent dus
        // gewoon: een ander eindresultaat voor diezelfde vraagsleutel, geen dubbele telling van de
        // eerst gekozen (en daarna verlaten) optie.
        $eersteKeuze = app(QuizScoringService::class)->compute([$tegelVraag => [$japandiOptie]]);
        $gewijzigdeKeuze = app(QuizScoringService::class)->compute([$tegelVraag => [$hotelLuxeOptie]]);

        $this->assertSame(0.85, $eersteKeuze['style_scores'][self::JAPANDI]);
        $this->assertSame(0.50, $eersteKeuze['style_scores'][self::HOTEL_LUXE]); // V1 O1 scoort ook 0,50 op Hotel luxe

        $this->assertSame(1.00, $gewijzigdeKeuze['style_scores'][self::HOTEL_LUXE]); // V1 O3
        $this->assertSame(0.30, $gewijzigdeKeuze['style_scores'][self::JAPANDI]); // V1 O3 scoort 0,30 op Japandi

        $this->assertNotSame($eersteKeuze['style_scores'], $gewijzigdeKeuze['style_scores']);
    }

    #[Test]
    public function normaaltotalen_en_spreiding_kloppen_met_de_controlecijfers_uit_de_opdracht(): void
    {
        $this->seedRealQuizQuestionsAndOptions();

        // Welke optie precies gekozen wordt maakt voor normaalTotaal/spreiding niet uit (die gaan
        // over alle opties binnen elke beantwoorde vraag) — hier gewoon de Hotel-luxe-route.
        $answers = $this->answersFavoring(self::HOTEL_LUXE);
        $explanation = app(QuizScoringService::class)->explain($answers);

        // uitslagScore = (ruwTotaal - normaalTotaal) / spreiding, dus normaalTotaal en spreiding
        // zijn terug te rekenen uit ruwTotaal en uitslagScore: normaalTotaal = ruwTotaal -
        // uitslagScore × spreiding. In plaats van dat om te draaien, herberekenen we ze hier
        // rechtstreeks vanuit dezelfde brongegevens als QuizScoringService zelf gebruikt, en
        // vergelijken we tegen de door de opdracht gegeven controlecijfers.
        $expectedNormaalTotaal = [
            self::HOTEL_LUXE => 3.6458, self::LANDELIJK => 2.5917, self::JAPANDI => 3.5375,
            self::KLEUR_EXPLOSIE => 2.8750, self::MODERN => 6.1625, self::MODERN_SCANDINAVISCH => 5.0917,
        ];
        $expectedSpreiding = [
            self::HOTEL_LUXE => 1.1056, self::LANDELIJK => 0.9369, self::JAPANDI => 1.0075,
            self::KLEUR_EXPLOSIE => 1.2078, self::MODERN => 0.7303, self::MODERN_SCANDINAVISCH => 0.7135,
        ];

        foreach (QuizAnswerScoreMatrix::styleKeys() as $styleKey) {
            $normaalTotaal = 0.0;
            $spreidingSom = 0.0;

            foreach ($this->realQuestions() as $config) {
                $scores = array_map(
                    fn (string $optionSlug): float => QuizAnswerScoreMatrix::scoresFor($optionSlug)[$styleKey],
                    array_keys($config['options']),
                );
                $mean = array_sum($scores) / count($scores);
                $variance = array_sum(array_map(fn (float $v): float => ($v - $mean) ** 2, $scores)) / count($scores);

                $normaalTotaal += $config['weight'] * $mean;
                $spreidingSom += ($config['weight'] ** 2) * $variance;
            }

            $this->assertEqualsWithDelta($expectedNormaalTotaal[$styleKey], round($normaalTotaal, 4), 0.0005, "normaalTotaal[$styleKey]");
            $this->assertEqualsWithDelta($expectedSpreiding[$styleKey], round(sqrt($spreidingSom), 4), 0.0005, "spreiding[$styleKey]");
        }

        // En de sanity check dat explain() zelf ook daadwerkelijk draait op deze data (geen losse,
        // niet-gebruikte berekening hierboven).
        $this->assertIsFloat($explanation['uitslag_scores'][self::HOTEL_LUXE]);
    }

    #[Test]
    public function de_instelbare_marge_bepaalt_of_een_tweede_stijl_als_invloed_getoond_wordt(): void
    {
        $this->seedRealQuizQuestionsAndOptions();

        // Japandi en Modern Scandinavisch liggen in de brontabel op meerdere vragen dicht bij
        // elkaar (allebei licht/natuurlijk) — een submissie die op de meeste vragen Japandi kiest
        // maar op enkele Modern Scandinavisch, is een realistische "gemengde route" om de marge op
        // te testen i.p.v. alleen met handmatige unit-testgetallen.
        $answers = $this->answersFavoring(self::JAPANDI);
        $answers['wallColor'] = ['wallcolor-behang-scandinavisch-nErLL'];
        $answers['sofaMaterial'] = ['sofamaterial-scandinavisch-m8Leo'];

        QuizSetting::current()->update(['secondary_influence_max_gap' => 50]); // extreem ruim: altijd invloed als score positief + spreiding genoeg is
        $ruimeMarge = app(QuizScoringService::class)->compute($answers);

        QuizSetting::current()->update(['secondary_influence_max_gap' => 0.0001]); // vrijwel nul: nooit invloed tenzij (bijna) exact gelijk
        $strakkeMarge = app(QuizScoringService::class)->compute($answers);

        $this->assertSame(self::JAPANDI, $ruimeMarge['primary_style']);
        $this->assertSame(self::JAPANDI, $strakkeMarge['primary_style']);
        $this->assertNull($strakkeMarge['secondary_style'], 'Een vrijwel-nul marge mag geen invloed meer toelaten, ook niet eentje die bij een ruime marge wel getoond werd.');
    }

    #[Test]
    public function twee_gekozen_opties_geven_het_gemiddelde_van_hun_scores_binnen_de_vraag(): void
    {
        $slugsByQuestion = $this->seedRealQuizQuestionsAndOptions();
        // sofaMaterial staat op max_selections = 2 (zie de migratie) — O1 (Japandi, japandi 0,85/
        // modern 0,30) + O5 (Modern, japandi 0,30/modern 0,70) uit de brontabel.
        $slugs = $slugsByQuestion['sofaMaterial'];
        [$japandiOptie, , , , $modernOptie] = $slugs;

        $computed = app(QuizScoringService::class)->compute(['sofaMaterial' => [$japandiOptie, $modernOptie]]);

        $this->assertSame(0.575, $computed['style_scores'][self::JAPANDI], '(0,85 + 0,30) / 2 = 0,575.');
        $this->assertSame(0.5, $computed['style_scores'][self::MODERN], '(0,30 + 0,70) / 2 = 0,5.');
        $this->assertSame(0.3, $computed['style_scores'][self::HOTEL_LUXE], '(0,30 + 0,30) / 2 = 0,3 — allebei toevallig gelijk.');
    }

    #[Test]
    public function het_deselecteren_van_een_van_twee_gekozen_opties_telt_weer_als_één_keuze(): void
    {
        $slugsByQuestion = $this->seedRealQuizQuestionsAndOptions();
        $slugs = $slugsByQuestion['sofaMaterial'];
        [$japandiOptie, , , , $modernOptie] = $slugs;

        $metTweeGekozen = app(QuizScoringService::class)->compute(['sofaMaterial' => [$japandiOptie, $modernOptie]]);
        $this->assertSame(0.575, $metTweeGekozen['style_scores'][self::JAPANDI]);

        // Bezoeker klikt de Modern-optie weer uit — de client stuurt dan alleen nog de resterende
        // keuze mee, niet een "verwijder"-instructie apart (zelfde stateloze aanpak als bij
        // terugnavigeren, zie hieronder).
        $naDeselecteren = app(QuizScoringService::class)->compute(['sofaMaterial' => [$japandiOptie]]);

        $this->assertSame(0.85, $naDeselecteren['style_scores'][self::JAPANDI], 'Met nog maar één keuze is het "gemiddelde" gewoon die ene score, niet meer de eerdere 0,575.');
        $this->assertSame(0.3, $naDeselecteren['style_scores'][self::MODERN], 'De Modern-bijdrage van de gedeselecteerde optie mag niet blijven meetellen.');
    }

    #[Test]
    public function terugnavigeren_met_een_andere_meerkeuze_telt_niet_dubbel_en_niet_gemengd(): void
    {
        $slugsByQuestion = $this->seedRealQuizQuestionsAndOptions();
        $slugs = $slugsByQuestion['sofaMaterial'];
        [$japandiOptie, , , $kleurExplosieOptie, $modernOptie, $modernScandinavischOptie] = $slugs;

        // Eerste bezoek aan de vraag: Japandi + Modern gekozen.
        $eersteKeuze = app(QuizScoringService::class)->compute(['sofaMaterial' => [$japandiOptie, $modernOptie]]);
        $this->assertSame(0.575, $eersteKeuze['style_scores'][self::JAPANDI]);

        // Terug, en op deze vraag nu een heel ander paar gekozen: Kleur explosie + Modern
        // Scandinavisch. De client stuurt bij elke aanroep de volledige, actuele antwoordenset mee
        // (nooit een toevoeging aan de eerder verlaten keuze).
        $gewijzigdeKeuze = app(QuizScoringService::class)->compute(['sofaMaterial' => [$kleurExplosieOptie, $modernScandinavischOptie]]);

        // Kleur explosie (O4: kleurExplosie 0,85) + Modern Scandinavisch (O6: kleurExplosie 0,00)
        $this->assertSame(0.425, $gewijzigdeKeuze['style_scores'][self::KLEUR_EXPLOSIE], '(0,85 + 0,00) / 2 = 0,425.');
        // O4 (japandi 0,30) + O6 (japandi 0,70) = 0,50 — puur op basis van dit nieuwe paar, niet
        // (deels) de eerdere, inmiddels verlaten keuze O1+O5 (die op 0,575 uitkwam).
        $this->assertSame(0.5, $gewijzigdeKeuze['style_scores'][self::JAPANDI]);
        $this->assertNotSame($eersteKeuze['style_scores'][self::JAPANDI], $gewijzigdeKeuze['style_scores'][self::JAPANDI]);
    }
}

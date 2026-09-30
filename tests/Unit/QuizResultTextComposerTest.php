<?php

namespace Tests\Unit;

use App\Models\QuizResult;
use App\Models\StyleProfile;
use App\Services\QuizResultTextComposer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt de titel/introtekst-samenstelling, met nadruk op de invloed-zin: die komt sinds
 * 2026_09_30_090000_fill_advice_secondary_for_active_styles uit StyleProfile::advice_secondary
 * i.p.v. altijd dezelfde generieke zin (zie QuizResultTextComposer::secondaryInfluenceSentence()).
 */
class QuizResultTextComposerTest extends TestCase
{
    use RefreshDatabase;

    private QuizResultTextComposer $composer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->composer = new QuizResultTextComposer;
    }

    private function makeStyle(string $key, string $label, ?string $adviceSecondary = null): StyleProfile
    {
        return StyleProfile::create([
            'style_key' => $key,
            'label' => $label,
            'slug' => Str::slug($label),
            'long_description' => "Basistekst van {$label}.",
            'advice_secondary' => $adviceSecondary,
        ]);
    }

    private function resultFor(?string $primaryKey, ?string $secondaryKey = null): QuizResult
    {
        return new QuizResult([
            'uuid' => (string) Str::uuid(),
            'primary_style' => $primaryKey,
            'secondary_style' => $secondaryKey,
        ]);
    }

    #[Test]
    public function zonder_secundaire_stijl_is_de_intro_gewoon_de_kale_omschrijving(): void
    {
        $this->makeStyle('hotelLuxe', 'Hotel luxe');

        $advice = $this->composer->build($this->resultFor('hotelLuxe'));

        $this->assertSame('Jouw woonstijl: Hotel luxe', $advice['comboName']);
        $this->assertSame('Basistekst van Hotel luxe.', $advice['intro']);
    }

    #[Test]
    public function met_secundaire_stijl_en_ingevuld_veld_gebruikt_de_intro_de_eigen_invloedtekst(): void
    {
        $this->makeStyle('hotelLuxe', 'Hotel luxe');
        $this->makeStyle('japandi', 'Japandi', 'Ook Japandi komt in je keuzes naar voren. Natuurlijke materialen en eenvoudige vormen kunnen voor meer rust en warmte zorgen.');

        $advice = $this->composer->build($this->resultFor('hotelLuxe', 'japandi'));

        $this->assertSame('Jouw woonstijl: Hotel luxe met Japandi-invloeden', $advice['comboName']);
        $this->assertSame(
            'Basistekst van Hotel luxe. Ook Japandi komt in je keuzes naar voren. Natuurlijke materialen en eenvoudige vormen kunnen voor meer rust en warmte zorgen.',
            $advice['intro'],
        );
    }

    #[Test]
    public function zonder_ingevuld_veld_valt_de_intro_terug_op_de_generieke_zin(): void
    {
        $this->makeStyle('hotelLuxe', 'Hotel luxe');
        $this->makeStyle('natuurlijk', 'Natuurlijk'); // advice_secondary blijft null

        $advice = $this->composer->build($this->resultFor('hotelLuxe', 'natuurlijk'));

        $this->assertSame(
            'Basistekst van Hotel luxe. Daarnaast zien we bij jou ook duidelijk iets van Natuurlijk terug.',
            $advice['intro'],
        );
    }

    /** @return array<string, array{0: string, 1: string, 2: string}> style_key => [label, advice_secondary, verwachte deelzin] */
    public static function actieveStijlenProvider(): array
    {
        return [
            'Hotel luxe' => ['hotelLuxe', 'Hotel luxe', 'Ook Hotel luxe komt in je keuzes naar voren. Met een rijke stof, sfeervolle verlichting of een verfijnd detail kun je de ruimte extra luxe geven.'],
            'Landelijk' => ['landelijk', 'Landelijk', 'Ook Landelijk komt in je keuzes naar voren. Natuurlijk hout en comfortabele stoffen kunnen de ruimte warm en huiselijk maken.'],
            'Japandi' => ['japandi', 'Japandi', 'Ook Japandi komt in je keuzes naar voren. Natuurlijke materialen en eenvoudige vormen kunnen voor meer rust en warmte zorgen.'],
            'Kleur explosie' => ['kleurExplosie', 'Kleur explosie', 'Ook Kleur explosie komt in je keuzes naar voren. Een uitgesproken kleur of opvallend object kan de ruimte een speels accent geven.'],
            'Modern' => ['modern', 'Modern', 'Ook Modern komt in je keuzes naar voren. Strakke lijnen en rustige vlakken kunnen voor een heldere, verzorgde uitstraling zorgen.'],
            'Modern Scandinavisch' => ['scandinavisch', 'Modern Scandinavisch', 'Ook Modern Scandinavisch komt in je keuzes naar voren. Licht hout, zachte stoffen en eenvoudige meubels kunnen het geheel fris en warm maken.'],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('actieveStijlenProvider')]
    public function elk_van_de_zes_actieve_stijlen_heeft_de_verwachte_invloedtekst_in_de_database(string $styleKey, string $label, string $expectedText): void
    {
        // Migratie zelf uitvoeren i.p.v. de tekst hier los te dupliceren — zo test dit ook meteen
        // dat de migratie de juiste tekst bij de juiste stijl zet.
        StyleProfile::create(['style_key' => $styleKey, 'label' => $label, 'slug' => Str::slug($label)]);
        (require database_path('migrations/2026_09_30_090000_fill_advice_secondary_for_active_styles.php'))->up();

        $profile = StyleProfile::where('style_key', $styleKey)->first();
        $this->assertSame($expectedText, $profile->advice_secondary);
    }

    #[Test]
    public function de_migratie_overschrijft_nooit_een_al_ingevulde_tekst(): void
    {
        $this->makeStyle('hotelLuxe', 'Hotel luxe', 'Een handmatig door de admin geschreven tekst.');

        (require database_path('migrations/2026_09_30_090000_fill_advice_secondary_for_active_styles.php'))->up();

        $this->assertSame(
            'Een handmatig door de admin geschreven tekst.',
            StyleProfile::where('style_key', 'hotelLuxe')->first()->advice_secondary,
        );
    }
}

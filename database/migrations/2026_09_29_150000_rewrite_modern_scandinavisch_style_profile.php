<?php

use App\Models\StyleProfile;
use Illuminate\Database\Migrations\Migration;

/**
 * "Modern Scandinavisch" is inhoudelijk een andere stijl dan het oude "Scandinavisch" (zie
 * 2026_09_29_140000_rename_scandinavisch_label_to_modern_scandinavisch voor de losse
 * labelwijziging): minder licht/pastel/knus, meer ingetogen/verfijnd/strak — dichter bij "modern"
 * qua vormentaal en kleurgebruik, met behoud van de lichte, natuurlijke basis die de twee stijlen
 * delen. Werkt daarom de volledige redactionele inhoud van het stijlprofiel bij (kenmerken,
 * kleuren, materialen, tips, recept), niet alleen het label.
 *
 * Update() met een expliciete veldenlijst (i.p.v. de hele rij vervangen) zodat style_key/slug/
 * hero_image/product images gewoon "scandinavisch" blijven — die bepalen bestandsnamen en mogen
 * niet wijzigen (zie QuizOption::storeImage()/QuizImageManifest). Geen conditionele where-op-oude-
 * waarde zoals bij een losse typefout-fix: dit is een bewuste, volledige contentvervanging op
 * uitdrukkelijk verzoek, dus een eventuele eerdere kleine admin-aanpassing aan dit specifieke
 * stijlprofiel wordt hier bewust wel overschreven.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Via het Eloquent-model (i.p.v. een losse query-builder ->update()) zodat de 'array'-casts
        // op deze kolommen (zie StyleProfile::$casts) de JSON-encoding gewoon zelf regelen.
        StyleProfile::query()->where('style_key', 'scandinavisch')->first()?->update([
            'subtitle' => 'Licht, strak en verfijnd eenvoudig',
            'long_description' => 'Jij houdt van een licht interieur met een rustige, verfijnde uitstraling. Natuurlijke materialen en een strakke, opgeruimde basis zorgen voor een moderne sfeer die tegelijk warm en uitnodigend blijft.',
            'traits_intro' => 'Op basis van jouw keuzes zien we vooral een voorkeur voor een lichte basis, strakke vormen en een rustige, ingetogen sfeer.',
            'core_traits' => ['Lichte, neutrale kleuren', 'Strakke, eenvoudige vormen', 'Licht hout met een mat karakter', 'Functionele meubels zonder overbodige details', 'Een rustige en verfijnde uitstraling'],
            'base_colors' => [
                ['name' => 'Gebroken wit', 'hex' => '#f6f4ef'],
                ['name' => 'Warm steengrijs', 'hex' => '#d6d0c3'],
                ['name' => 'Licht eikenhoutbeige', 'hex' => '#c7b79c'],
            ],
            'accent_colors' => [
                ['name' => 'Antraciet', 'hex' => '#33363a'],
                ['name' => 'Dennengroen', 'hex' => '#435446'],
                ['name' => 'Roestbruin', 'hex' => '#a9603f'],
            ],
            'color_tip' => 'Gebruik lichte, neutrale tinten als basis en voeg met mate een ingetogen accent toe, zoals antraciet, dennengroen of roestbruin. Zo blijft de sfeer rustig en verfijnd, zonder saai te worden.',
            'materials' => ['Licht eikenhout', 'Wol', 'Linnen', 'Keramiek'],
            'materials_tip' => 'Combineer licht hout met een beperkt aantal zachte stoffen en eenvoudige keramische accessoires. Zo houd je de basis rustig en verfijnd, zonder dat het interieur karakter verliest.',
            'furniture_shapes' => [
                'intro' => 'Kies voor strakke meubels met een lichte uitstraling en eenvoudige, functionele vormen.',
                'items' => ['Banken met slanke, rechte poten', 'Licht houten tafels en kasten zonder overbodige details', 'Compacte, functionele meubels', 'Enkele zachte stoffen in rustige, neutrale kleuren'],
            ],
            'advice_primary' => 'Kies voor strakke meubels met een lichte uitstraling en eenvoudige, functionele vormen.',
            'wat_past_minder_goed' => ['Veel decoratieve accessoires, drukke patronen of felle kleuren kunnen de rustige, verfijnde basis verstoren. Kies liever voor een paar zorgvuldig gekozen accenten in plaats van veel kleine spullen.'],
            'recipe' => [
                ['label' => 'Basis', 'value' => 'Gebroken wit, warm steengrijs en licht eikenhout'],
                ['label' => 'Grote meubels', 'value' => 'Strakke bank en eenvoudige houten tafel'],
                ['label' => 'Accentkleur', 'value' => 'Antraciet of dennengroen'],
                ['label' => 'Materialen', 'value' => 'Licht eikenhout, wol, linnen en keramiek'],
                ['label' => 'Accessoires', 'value' => 'Enkele zorgvuldig gekozen objecten en sfeerverlichting'],
            ],
            'product_tags' => ['licht-eiken', 'linnen', 'antraciet'],
        ]);
    }

    public function down(): void
    {
        // Bewust geen terugdraai-logica: dit is een inhoudelijke stijlrepositionering op verzoek,
        // geen technische correctie — teruggaan naar de oude "Scandinavisch"-tekst is geen zinvolle
        // default voor een rollback.
    }
};

<?php

namespace Database\Seeders;

use App\Models\StyleCombinationAdvice;
use Illuminate\Database\Seeder;

/**
 * Zet alle 21 stijlcombinatie-adviesrecords klaar (6 stijlen -> 15 paren + 6 zelfde-stijl-
 * combinaties, zie het implementatieplan "Partnerfunctie", sectie 7) met redactionele tekst,
 * gebaseerd op de kenmerken/tips uit StyleProfileSeeder per stijl. `updateOrCreate` (i.p.v.
 * firstOrCreate) zodat een latere tekstcorrectie hier ook een al eerder geseede rij bijwerkt —
 * zelfde patroon als BasePaletteSeeder/AccentColorSeeder. `status` staat op 'published': deze
 * tekst is inhoudelijk klaar, maar blijft sowieso onzichtbaar voor bezoekers zolang
 * QuizSetting::partner_feature_enabled uitstaat (zie EnsurePartnerFeatureEnabled) — een stylist
 * kan een los paar via de "Stijlcombinaties"-beheerpagina alsnog op 'concept' zetten voor verdere
 * redactie.
 */
class StyleCombinationAdviceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->advices() as [$styleKeyA, $styleKeyB, $data]) {
            [$canonicalA, $canonicalB] = StyleCombinationAdvice::canonicalPair($styleKeyA, $styleKeyB);

            StyleCombinationAdvice::query()->updateOrCreate(
                ['style_key_a' => $canonicalA, 'style_key_b' => $canonicalB],
                [...$data, 'status' => 'published'],
            );
        }
    }

    /** @return array<int, array{0: string, 1: string, 2: array<string, mixed>}> */
    private function advices(): array
    {
        return [
            ['hotelLuxe', 'hotelLuxe', [
                'title' => 'Hotel luxe & Hotel luxe',
                'intro' => "Beide voorkeuren wijzen op warmte en verfijning. Kies samen welke details de hoofdrol krijgen.",
                'basis_tip' => 'Gebruik beige en taupe als basis, met donker hout voor diepte.',
                'materials_tip' => "Kies enkele rijke materialen, zoals fluweel en marmer, zonder alle oppervlakken druk te maken.",
                'accent_tip' => "Diepgroen of champagne kan de ruimte extra karakter geven.",
                'base_palette_style_key' => null,
                'version' => 1,
            ]],
            ['hotelLuxe', 'japandi', [
                'title' => 'Hotel luxe & Japandi',
                'intro' => "Warme materialen verbinden deze stijlen. Houd de ruimte rustig en voeg luxe toe op enkele plekken.",
                'basis_tip' => "Kies zand, warm wit en hout als basis.",
                'materials_tip' => "Combineer hout en linnen met één rijk detail, zoals een fluwelen stoel of een marmeren blad.",
                'accent_tip' => "Diepgroen geeft wat extra diepte. Gebruik het bijvoorbeeld in een kussen of kunstwerk.",
                'base_palette_style_key' => 'japandi',
                'version' => 1,
            ]],
            ['hotelLuxe', 'kleurExplosie', [
                'title' => 'Hotel luxe & Kleur explosie',
                'intro' => "Een warme, luxe basis geeft felle kleuren een duidelijke plek.",
                'basis_tip' => 'Begin met beige, taupe en donker hout.',
                'materials_tip' => 'Laat één kleurrijk meubel of een opvallend kunstwerk het middelpunt zijn. Een rijke stof kan beide stijlen verbinden.',
                'accent_tip' => 'Kies een uitgesproken kleur, zoals kobaltblauw of smaragdgroen, en laat die op een tweede plek terugkomen.',
                'base_palette_style_key' => 'hotelLuxe',
                'version' => 1,
            ]],
            ['hotelLuxe', 'landelijk', [
                'title' => 'Hotel luxe & Landelijk',
                'intro' => 'Deze stijlen delen warmte en comfort. Het verschil zit in de afwerking: landelijk is losser, Hotel luxe verfijnder.',
                'basis_tip' => 'Kies gebroken wit, warm beige en hout.',
                'materials_tip' => 'Combineer zichtbaar hout en linnen met bijvoorbeeld één fluwelen stoel of een elegante lamp.',
                'accent_tip' => 'Diepgroen of donkerbruin geeft diepte en blijft bij beide stijlen passen.',
                'base_palette_style_key' => 'landelijk',
                'version' => 1,
            ]],
            ['hotelLuxe', 'modern', [
                'title' => 'Hotel luxe & Modern',
                'intro' => 'Een strakke indeling kan goed samengaan met een paar luxe materialen.',
                'basis_tip' => "Gebruik lichte, rustige kleuren en voeg op enkele plekken een warmere tint toe.",
                'materials_tip' => 'Laat gladde meubels samengaan met één zachte stof of een marmeren blad.',
                'accent_tip' => 'Kies antraciet voor contrast en bijvoorbeeld champagne of donkerbruin voor warmte.',
                'base_palette_style_key' => 'modern',
                'version' => 1,
            ]],
            ['hotelLuxe', 'scandinavisch', [
                'title' => 'Hotel luxe & Modern Scandinavisch',
                'intro' => 'Licht hout en een rustige basis houden de ruimte fris. Een rijk detail voegt luxe toe.',
                'basis_tip' => 'Gebruik gebroken wit en licht hout, met een beetje taupe voor diepte.',
                'materials_tip' => 'Kies één blikvanger, zoals een fluwelen fauteuil of een lamp met een verfijnde afwerking.',
                'accent_tip' => 'Dennengroen of diepbruin kan een klein, warm accent zijn.',
                'base_palette_style_key' => 'scandinavisch',
                'version' => 1,
            ]],
            ['japandi', 'japandi', [
                'title' => 'Japandi & Japandi',
                'intro' => 'Jullie keuzes wijzen allebei op rust en natuurlijke materialen. Laat voldoende ruimte tussen de meubels.',
                'basis_tip' => "Kies warm wit, zand en naturel hout.",
                'materials_tip' => 'Combineer hout, linnen en keramiek. Een paar grotere stukken geven meer rust dan veel kleine accessoires.',
                'accent_tip' => 'Zacht olijfgroen of taupe past bij de rustige basis.',
                'base_palette_style_key' => null,
                'version' => 1,
            ]],
            ['japandi', 'kleurExplosie', [
                'title' => 'Japandi & Kleur explosie',
                'intro' => 'Laat natuurlijke materialen de rust bewaren en geef een felle kleur één duidelijke plek.',
                'basis_tip' => 'Kies warm wit, zand en hout als rustige achtergrond.',
                'materials_tip' => 'Voeg bijvoorbeeld een kleurrijk keramisch object, kunstwerk of gelakte stoel toe.',
                'accent_tip' => "Kies één heldere kleur die je graag ziet. Herhaal die beperkt, zodat de ruimte rustig blijft.",
                'base_palette_style_key' => 'japandi',
                'version' => 1,
            ]],
            ['japandi', 'landelijk', [
                'title' => 'Japandi & Landelijk',
                'intro' => 'Beide stijlen gebruiken natuurlijke materialen. Houd de vormen eenvoudig en voeg landelijke zachtheid toe.',
                'basis_tip' => "Gebruik warm wit, zand en naturel hout.",
                'materials_tip' => "Combineer een eenvoudige houten tafel met linnen, wol en een paar stukken aardewerk.",
                'accent_tip' => 'Zacht olijfgroen of warm bruin past bij het natuurlijke palet.',
                'base_palette_style_key' => 'japandi',
                'version' => 1,
            ]],
            ['japandi', 'modern', [
                'title' => 'Japandi & Modern',
                'intro' => 'Deze stijlen delen rustige vormen. Hout en textiel geven een strakke basis meer warmte.',
                'basis_tip' => "Gebruik warm wit en grijsbeige met natuurlijke houttinten.",
                'materials_tip' => "Combineer strakke meubels met hout, linnen of matte keramiek.",
                'accent_tip' => "Gebruik zwart of antraciet in een paar kleine details voor contrast.",
                'base_palette_style_key' => 'japandi',
                'version' => 1,
            ]],
            ['japandi', 'scandinavisch', [
                'title' => 'Japandi & Modern Scandinavisch',
                'intro' => 'Beide stijlen zijn rustig en houden van lichte materialen. Japandi is iets aardser; Modern Scandinavisch voelt frisser.',
                'basis_tip' => 'Kies warm wit en licht hout. Een zandkleur of licht leem maakt het geheel wat warmer.',
                'materials_tip' => "Gebruik matte materialen en eenvoudige meubels. Voeg één handgemaakt keramisch stuk toe voor meer Japandi karakter.",
                'accent_tip' => 'Zacht olijfgroen of een kleine donkere toets geeft diepte zonder veel aandacht te vragen.',
                'base_palette_style_key' => null,
                'version' => 1,
            ]],
            ['kleurExplosie', 'kleurExplosie', [
                'title' => 'Kleur explosie & Kleur explosie',
                'intro' => "Jullie keuzes geven allebei ruimte aan kleur. Spreek af welke kleuren steeds terugkomen.",
                'basis_tip' => 'Kies een rustige achtergrond voor de grotere vlakken.',
                'materials_tip' => 'Mix kleur in meubels, kunst en keramiek, maar herhaal enkele materialen voor samenhang.',
                'accent_tip' => "Laat twee favoriete kleuren terugkomen in verschillende delen van de kamer.",
                'base_palette_style_key' => null,
                'version' => 1,
            ]],
            ['kleurExplosie', 'landelijk', [
                'title' => 'Kleur explosie & Landelijk',
                'intro' => 'Een landelijke basis houdt de ruimte warm. Kleur geeft er een speels accent aan.',
                'basis_tip' => "Begin met gebroken wit, zand en hout.",
                'materials_tip' => 'Voeg kleurrijk keramiek, een gestoffeerde stoel of een opvallend patroon toe.',
                'accent_tip' => 'Okergeel of koraalrood kan goed werken. Laat de gekozen kleur op een paar plekken terugkomen.',
                'base_palette_style_key' => 'landelijk',
                'version' => 1,
            ]],
            ['kleurExplosie', 'modern', [
                'title' => 'Kleur explosie & Modern',
                'intro' => 'Een strak interieur geeft felle kleuren ruimte om op te vallen.',
                'basis_tip' => 'Houd wanden en grote meubels rustig in wit, greige of lichtgrijs.',
                'materials_tip' => 'Een gelakte kast of opvallend kunstwerk voegt kleur toe aan de strakke vormen.',
                'accent_tip' => "Kies één of twee heldere kleuren en herhaal ze gericht in de ruimte.",
                'base_palette_style_key' => 'modern',
                'version' => 1,
            ]],
            ['kleurExplosie', 'scandinavisch', [
                'title' => 'Kleur explosie & Modern Scandinavisch',
                'intro' => 'Een lichte ruimte met licht hout kan goed een vrolijk kleuraccent dragen.',
                'basis_tip' => "Gebruik gebroken wit, licht hout en warm steengrijs.",
                'materials_tip' => 'Voeg kleur toe met een stoel, lamp, keramiek of textiel. Houd de overige vormen eenvoudig.',
                'accent_tip' => 'Kies bijvoorbeeld roze of kobaltblauw en laat die kleur op enkele plekken terugkomen.',
                'base_palette_style_key' => 'scandinavisch',
                'version' => 1,
            ]],
            ['landelijk', 'landelijk', [
                'title' => 'Landelijk & Landelijk',
                'intro' => 'Jullie keuzes wijzen allebei op een warm huis met natuurlijke materialen.',
                'basis_tip' => 'Gebruik gebroken wit, zand en warm hout.',
                'materials_tip' => 'Combineer hout, linnen en wol met meubels waarin je prettig zit.',
                'accent_tip' => 'Vergrijsd groen of warm bruin past bij deze basis.',
                'base_palette_style_key' => null,
                'version' => 1,
            ]],
            ['landelijk', 'modern', [
                'title' => 'Landelijk & Modern',
                'intro' => 'Strakke lijnen en natuurlijke materialen kunnen elkaar aanvullen.',
                'basis_tip' => "Gebruik een rustige basis in gebroken wit en greige, met zichtbaar hout.",
                'materials_tip' => "Combineer een eenvoudig meubel met linnen, wol of een houten tafel met voelbare structuur.",
                'accent_tip' => 'Warm bruin of antraciet werkt als rustig accent.',
                'base_palette_style_key' => 'modern',
                'version' => 1,
            ]],
            ['landelijk', 'scandinavisch', [
                'title' => 'Landelijk & Modern Scandinavisch',
                'intro' => 'Lichte kleuren en natuurlijke materialen verbinden deze stijlen. Landelijk voegt meer structuur en gezelligheid toe.',
                'basis_tip' => 'Kies gebroken wit, zand en licht tot middellicht hout.',
                'materials_tip' => "Combineer eenvoudige meubels met linnen, een wollen kleed of aardewerk.",
                'accent_tip' => 'Vergrijsd groen geeft een zacht, natuurlijk accent.',
                'base_palette_style_key' => null,
                'version' => 1,
            ]],
            ['modern', 'modern', [
                'title' => 'Modern & Modern',
                'intro' => 'Jullie keuzes wijzen allebei op een rustig interieur met heldere lijnen.',
                'basis_tip' => "Gebruik wit, lichtgrijs en greige als basis.",
                'materials_tip' => 'Kies een paar duidelijke meubelvormen en voeg warmte toe met één houtsoort of zachte stof.',
                'accent_tip' => 'Zwart of antraciet geeft contrast zonder veel kleur toe te voegen.',
                'base_palette_style_key' => null,
                'version' => 1,
            ]],
            ['modern', 'scandinavisch', [
                'title' => 'Modern & Modern Scandinavisch',
                'intro' => 'Deze stijlen delen eenvoudige vormen. Licht hout en zachte stoffen geven de strakke basis meer warmte.',
                'basis_tip' => "Gebruik wit en lichtgrijs met een lichte houttint.",
                'materials_tip' => 'Combineer gladde oppervlakken en strakke meubels met licht hout en wol.',
                'accent_tip' => 'Antraciet zorgt voor een duidelijk maar rustig contrast.',
                'base_palette_style_key' => null,
                'version' => 1,
            ]],
            ['scandinavisch', 'scandinavisch', [
                'title' => 'Modern Scandinavisch & Modern Scandinavisch',
                'intro' => 'Jullie keuzes wijzen allebei op een licht interieur met eenvoudige vormen en natuurlijke warmte.',
                'basis_tip' => 'Gebruik gebroken wit, warm steengrijs en licht hout.',
                'materials_tip' => 'Combineer functionele meubels met wol, linnen of een andere zachte textuur.',
                'accent_tip' => 'Dennengroen of antraciet kan een klein, rustig accent zijn.',
                'base_palette_style_key' => null,
                'version' => 1,
            ]],
        ];
    }
}

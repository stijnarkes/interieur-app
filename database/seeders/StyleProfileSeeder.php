<?php

namespace Database\Seeders;

use App\Models\StyleProfile;
use Illuminate\Database\Seeder;

/**
 * Migreert de content van resources/js/quiz/styleProfiles.js naar de database, zodat
 * style_profiles de bron van waarheid is (admin-beheerbaar via StyleProfilesPage).
 * `base_colors`/`accent_colors` zijn hier opgesplitst uit de oorspronkelijke, ongesplitste
 * kleurenlijst — op basis van elke stijl se eigen "Basis"/"Accentkleur"-regel in `recipe`, zodat
 * de resultatenpagina/PDF alleen de veilige basiskleuren toont ("Kleuren ter inspiratie") en geen
 * specifieke accentkleur claimt die we niet per se weten (zie klantfeedback: bv. Japandi toonde
 * olijfgroen als leek het een vaste basiskleur, terwijl het één van meerdere mogelijke accenten
 * is). Voor Kleur explosie is zo'n splitsing niet zinvol — de hele stijl draait om levendige
 * kleuren, er is geen apart "neutraal" basispalet. `advice_secondary`/`advice_tertiary`/
 * `lighting`/`accessories`/`wat_past_goed` bestonden niet in de oude, statische content — die
 * blijven hier bewust leeg, in te vullen door een admin.
 */
class StyleProfileSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->profiles() as $profile) {
            $existing = StyleProfile::where('style_key', $profile['style_key'])->first();

            if ($existing) {
                // materials_image staat hieronder altijd hardcoded op null (een placeholder — de
                // echte foto wordt via StyleProfilesPage geüpload, niet hier geseed). Zonder deze
                // uitzondering zou een hernieuwde run van deze seeder (bv. na een latere
                // contentwijziging) een al geüploade materialenfoto stilzwijgend terugzetten naar
                // leeg — precies het "de foto is weg"-patroon dat we eerder bij de sfeerfoto's
                // zagen, maar dan zonder de bescherming die hero_image toevallig wel heeft (die
                // wijst altijd naar hetzelfde vaste bestandspad, dus een reseed daarvan is
                // onschadelijk).
                unset($profile['materials_image']);
            }

            StyleProfile::updateOrCreate(['style_key' => $profile['style_key']], $profile);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function profiles(): array
    {
        return [
            [
                'style_key' => 'hotelLuxe',
                'label' => 'Hotel luxe',
                'slug' => 'hotel-luxe',
                'subtitle' => 'Warm, elegant en comfortabel met een luxe uitstraling',
                'long_description' => 'Jij houdt van een interieur dat direct sfeer en luxe uitstraalt, zonder dat het afstandelijk aanvoelt. Warme kleuren, rijke materialen en verfijnde details zorgen bij jou voor een comfortabele woonstijl met het gevoel van een stijlvol boutiquehotel.',
                'traits_intro' => 'Op basis van jouw keuzes zien we vooral een voorkeur voor warme kleuren, zachte materialen en een rijke, sfeervolle uitstraling.',
                'core_traits' => ['Warme en diepe kleuren', 'Rijke, zachte materialen', 'Sfeervolle verlichting', 'Elegante vormen', 'Luxe details en accessoires'],
                'hero_image' => '/images/interior/atmosphere/hotel-luxe.webp',
                'base_colors' => [
                    ['name' => 'Warm beige', 'hex' => '#e3d3b8'],
                    ['name' => 'Taupe', 'hex' => '#a8967d'],
                ],
                'accent_colors' => [
                    ['name' => 'Donkerbruin', 'hex' => '#3e2a20'],
                    ['name' => 'Champagne', 'hex' => '#c9a86a'],
                    ['name' => 'Diep groen', 'hex' => '#2e4034'],
                ],
                'color_tip' => 'Gebruik beige en taupe als warme basis en voeg diepere tinten toe voor extra sfeer. Champagnekleurige of donkere accenten geven het geheel een luxe uitstraling zonder dat het te zwaar wordt.',
                'materials' => ['Fluweel', 'Donker hout', 'Marmer', 'Messing'],
                'materials_image' => null,
                'materials_tip' => 'Combineer zachte stoffen met gladde en verfijnde materialen zoals marmer, glas en metaal. Juist het contrast tussen zacht en chic geeft jouw interieur de luxe hotelsfeer die bij je past.',
                'furniture_shapes' => [
                    'intro' => 'Kies meubels die comfortabel aanvoelen, maar tegelijkertijd een elegante en verzorgde uitstraling hebben.',
                    'items' => ['Royale, zachte banken', 'Ronde en organische vormen', 'Donker hout of luxe afwerkingen', 'Statementmeubels met verfijnde details'],
                ],
                'lighting' => null,
                'accessories' => null,
                'advice_primary' => 'Kies meubels die comfortabel aanvoelen, maar tegelijkertijd een elegante en verzorgde uitstraling hebben.',
                'advice_secondary' => null,
                'advice_tertiary' => null,
                'wat_past_goed' => null,
                'wat_past_minder_goed' => ['Veel verschillende kleuren, kleine losse accessoires en te veel verschillende materialen kunnen de luxe uitstraling wat onrustiger maken. Kies liever voor een beperkt kleurenpalet en een paar krachtige blikvangers.'],
                'recipe' => [
                    ['label' => 'Basis', 'value' => 'Warm beige, taupe en donker hout'],
                    ['label' => 'Grote meubels', 'value' => 'Royale bank en elegante fauteuils'],
                    ['label' => 'Accentkleur', 'value' => 'Diep groen, donkerbruin of champagne'],
                    ['label' => 'Materialen', 'value' => 'Fluweel, marmer, hout en messing'],
                    ['label' => 'Accessoires', 'value' => 'Grote lampen, sierkussens en stijlvolle decoratie'],
                ],
                'product_tags' => ['hotel-luxe', 'fluweel', 'marmer', 'messing'],
            ],
            [
                'style_key' => 'japandi',
                'label' => 'Japandi',
                'slug' => 'japandi',
                'subtitle' => 'Rust, warmte en natuurlijke eenvoud',
                'long_description' => 'Rust, warmte en natuurlijke materialen passen helemaal bij jou. Je kiest voor een interieur dat eenvoudig en verfijnd aanvoelt, maar tegelijkertijd warm en comfortabel blijft.',
                'traits_intro' => 'Op basis van jouw keuzes zien we vooral een voorkeur voor rust, natuurlijke materialen en warme tinten.',
                'core_traits' => ['Warme, rustige kleuren', 'Natuurlijke materialen', 'Zachte vormen', 'Een rustige uitstraling', 'Comfort en eenvoud'],
                'hero_image' => '/images/interior/atmosphere/japandi.webp',
                'base_colors' => [
                    ['name' => 'Warm wit', 'hex' => '#f5f0e6'],
                    ['name' => 'Zand', 'hex' => '#ddc9a0'],
                    ['name' => 'Beige', 'hex' => '#cbb188'],
                ],
                'accent_colors' => [
                    ['name' => 'Taupe', 'hex' => '#a8967d'],
                    ['name' => 'Zacht olijfgroen', 'hex' => '#8d9873'],
                    ['name' => 'Terracotta', 'hex' => '#c1694f'],
                ],
                'color_tip' => 'Gebruik de lichte tinten als rustige basis en voeg hout, taupe en een zachte accentkleur toe voor warmte en contrast.',
                'materials' => ['Naturel eiken', 'Linnen', 'Wol / bouclé', 'Keramiek'],
                'materials_image' => null,
                'materials_tip' => 'Combineer natuurlijke materialen met zachte stoffen. Hierdoor blijft je interieur rustig, maar voelt het tegelijkertijd warm en comfortabel.',
                'furniture_shapes' => [
                    'intro' => 'Kies liever voor een paar rustige, sterke meubels dan voor veel verschillende vormen en details.',
                    'items' => ['Zachte en afgeronde vormen', 'Lage, comfortabele banken', 'Rustige meubels zonder veel details', 'Naturel hout en zachte stoffen'],
                ],
                'lighting' => null,
                'accessories' => null,
                'advice_primary' => 'Kies liever voor een paar rustige, sterke meubels dan voor veel verschillende vormen en details.',
                'advice_secondary' => null,
                'advice_tertiary' => null,
                'wat_past_goed' => null,
                'wat_past_minder_goed' => ['Veel felle kleuren, hoogglans meubels en veel kleine accessoires kunnen jouw interieur sneller onrustig maken. Kies liever voor rust, natuurlijke materialen en een beperkt aantal uitgesproken elementen.'],
                'recipe' => [
                    ['label' => 'Basis', 'value' => 'Warm wit, zand en naturel hout'],
                    ['label' => 'Grote meubels', 'value' => 'Lage bank en rustige houten meubels'],
                    ['label' => 'Accentkleur', 'value' => 'Taupe of zacht olijfgroen'],
                    ['label' => 'Materialen', 'value' => 'Eiken, linnen, wol en keramiek'],
                    ['label' => 'Accessoires', 'value' => 'Enkele grote, natuurlijke objecten'],
                ],
                'product_tags' => ['japandi', 'naturel-eiken', 'linnen', 'rustig-interieur'],
            ],
            [
                'style_key' => 'kleurExplosie',
                'label' => 'Kleur explosie',
                'slug' => 'kleur-explosie',
                'subtitle' => 'Energiek, uitgesproken en helemaal van jou',
                'long_description' => 'Jij wordt blij van kleur, karakter en een interieur waarin echt iets gebeurt. Je durft verschillende tinten, vormen en opvallende elementen te combineren en maakt van je huis graag een persoonlijke plek vol energie.',
                'traits_intro' => 'Op basis van jouw keuzes zien we vooral een voorkeur voor uitgesproken kleuren, speelse combinaties en meubels met karakter.',
                'core_traits' => ['Opvallende kleuren', 'Speelse combinaties', 'Bijzondere vormen', 'Persoonlijke accessoires', 'Een energieke uitstraling'],
                'hero_image' => '/images/interior/atmosphere/kleur-explosie.webp',
                'base_colors' => [
                    ['name' => 'Zacht wit', 'hex' => '#f7f4ef'],
                ],
                'accent_colors' => [
                    ['name' => 'Kobaltblauw', 'hex' => '#1d4e89'],
                    ['name' => 'Okergeel', 'hex' => '#e0a730'],
                    ['name' => 'Koraalrood', 'hex' => '#e8583a'],
                    ['name' => 'Smaragdgroen', 'hex' => '#1e7a52'],
                    ['name' => 'Roze', 'hex' => '#d6467e'],
                ],
                'color_tip' => 'Kies één of twee kleuren als duidelijke hoofdtoon en laat andere kleuren terugkomen in kleinere accenten. Zo blijft je interieur levendig, maar ontstaat er toch samenhang.',
                'materials' => ['Gekleurd glas', 'Velours', 'Gelakt hout', 'Keramiek'],
                'materials_image' => null,
                'materials_tip' => 'Mix materialen met verschillende structuren en uitstralingen. Een zachte stof naast glanzend glas of kleurrijk keramiek maakt jouw interieur extra spannend en persoonlijk.',
                'furniture_shapes' => [
                    'intro' => 'Meubels mogen bij jou gezien worden. Kies vormen, kleuren en details die een ruimte karakter geven.',
                    'items' => ['Banken of fauteuils in een uitgesproken kleur', 'Speelse en organische vormen', 'Bijzondere bijzettafels en kasten', 'Statementmeubels met karakter'],
                ],
                'lighting' => null,
                'accessories' => null,
                'advice_primary' => 'Meubels mogen bij jou gezien worden. Kies vormen, kleuren en details die een ruimte karakter geven.',
                'advice_secondary' => null,
                'advice_tertiary' => null,
                'wat_past_goed' => null,
                'wat_past_minder_goed' => ['Wanneer iedere kleur, vorm en accessoire evenveel aandacht vraagt, kan je interieur druk aanvoelen. Laat daarom een paar kleuren en blikvangers de hoofdrol spelen en geef ze voldoende rustige ruimte eromheen.'],
                'recipe' => [
                    ['label' => 'Basis', 'value' => 'Een rustige basiskleur met kleurrijke accenten'],
                    ['label' => 'Grote meubels', 'value' => 'Eén of twee uitgesproken blikvangers'],
                    ['label' => 'Accentkleur', 'value' => 'Kobaltblauw, rood, groen of roze'],
                    ['label' => 'Materialen', 'value' => 'Velours, glas, keramiek en gelakt hout'],
                    ['label' => 'Accessoires', 'value' => 'Kunst, kleurrijke kussens en opvallende verlichting'],
                ],
                'product_tags' => ['kleur-explosie', 'velours', 'gekleurd-glas'],
            ],
            [
                'style_key' => 'landelijk',
                'label' => 'Landelijk',
                'slug' => 'landelijk',
                'subtitle' => 'Warm, vertrouwd en sfeervol wonen',
                'long_description' => 'Jij voelt je prettig in een interieur dat warm, uitnodigend en ontspannen aanvoelt. Natuurlijke materialen, zachte kleuren en comfortabele meubels zorgen voor een huis waarin je graag samenkomt en tot rust komt.',
                'traits_intro' => 'Op basis van jouw keuzes zien we vooral een voorkeur voor warmte, natuurlijke materialen en een comfortabele, huiselijke sfeer.',
                'core_traits' => ['Warme natuurtinten', 'Veel hout', 'Comfortabele meubels', 'Zachte stoffen', 'Een sfeervolle uitstraling'],
                'hero_image' => '/images/interior/atmosphere/landelijk.webp',
                'base_colors' => [
                    ['name' => 'Gebroken wit', 'hex' => '#f4efe4'],
                    ['name' => 'Zand', 'hex' => '#ddc9a0'],
                    ['name' => 'Greige', 'hex' => '#c9bba3'],
                ],
                'accent_colors' => [
                    ['name' => 'Warm bruin', 'hex' => '#7a5230'],
                    ['name' => 'Vergrijsd groen', 'hex' => '#8a9483'],
                    ['name' => 'Mosterdgeel', 'hex' => '#c9971f'],
                ],
                'color_tip' => 'Werk met warme, rustige basiskleuren en voeg bruin- en groentinten toe voor een natuurlijke sfeer. Door kleuren ton-sur-ton te combineren ontstaat een zachte en gezellige uitstraling.',
                'materials' => ['Eikenhout', 'Linnen', 'Wol', 'Natuursteen'],
                'materials_image' => null,
                'materials_tip' => 'Combineer robuust hout met zachte stoffen en natuurlijke structuren. Hierdoor krijgt je interieur karakter, terwijl het tegelijkertijd warm en toegankelijk blijft.',
                'furniture_shapes' => [
                    'intro' => 'Comfort staat centraal. Kies meubels die royaal ogen en uitnodigen om lang te blijven zitten.',
                    'items' => ['Royale, comfortabele banken', 'Houten tafels met een natuurlijke uitstraling', 'Zachte fauteuils', 'Kasten met rustige, klassieke vormen'],
                ],
                'lighting' => null,
                'accessories' => null,
                'advice_primary' => 'Comfort staat centraal. Kies meubels die royaal ogen en uitnodigen om lang te blijven zitten.',
                'advice_secondary' => null,
                'advice_tertiary' => null,
                'wat_past_goed' => null,
                'wat_past_minder_goed' => ['Veel harde contrasten, koude materialen en een heel strakke styling kunnen minder goed aansluiten bij de warmte die jij zoekt. Kies liever voor zachte overgangen, natuurlijke structuren en meubels met een uitnodigende uitstraling.'],
                'recipe' => [
                    ['label' => 'Basis', 'value' => 'Gebroken wit, zand en warm hout'],
                    ['label' => 'Grote meubels', 'value' => 'Royale bank en houten eettafel'],
                    ['label' => 'Accentkleur', 'value' => 'Vergrijsd groen of warm bruin'],
                    ['label' => 'Materialen', 'value' => 'Hout, linnen, wol en natuursteen'],
                    ['label' => 'Accessoires', 'value' => 'Aardewerk, plaids en natuurlijke decoratie'],
                ],
                'product_tags' => ['landelijk', 'eikenhout', 'linnen'],
            ],
            [
                'style_key' => 'modern',
                'label' => 'Modern',
                'slug' => 'modern',
                'subtitle' => 'Rustig, strak en eigentijds',
                'long_description' => 'Jij houdt van een interieur dat overzichtelijk, fris en eigentijds aanvoelt. Strakke lijnen en rustige kleuren vormen de basis, maar er blijft voldoende ruimte voor comfort en persoonlijke accenten.',
                'traits_intro' => 'Op basis van jouw keuzes zien we vooral een voorkeur voor eenvoud, duidelijke lijnen en een rustige, eigentijdse uitstraling.',
                'core_traits' => ['Strakke lijnen', 'Rustige kleuren', 'Functionele meubels', 'Open en overzichtelijke ruimtes', 'Subtiele contrasten'],
                'hero_image' => '/images/interior/atmosphere/modern.webp',
                'base_colors' => [
                    ['name' => 'Wit', 'hex' => '#f5f5f4'],
                    ['name' => 'Lichtgrijs', 'hex' => '#d4d4d2'],
                    ['name' => 'Greige', 'hex' => '#cfc3ac'],
                ],
                'accent_colors' => [
                    ['name' => 'Antraciet', 'hex' => '#33363a'],
                    ['name' => 'Zwart', 'hex' => '#17181a'],
                    ['name' => 'Petrolblauw', 'hex' => '#1f4e5f'],
                ],
                'color_tip' => 'Gebruik lichte neutrale tinten als basis en creëer diepte met grijs, antraciet of zwart. Een warmere houttint kan voorkomen dat je interieur te koel aanvoelt.',
                'materials' => ['Eikenhout', 'Metaal', 'Glas', 'Keramiek'],
                'materials_image' => null,
                'materials_tip' => 'Combineer gladde materialen met hout of textiel om warmte toe te voegen. Zo blijft de moderne uitstraling strak, maar voelt je interieur wel prettig en leefbaar aan.',
                'furniture_shapes' => [
                    'intro' => 'Kies meubels met heldere vormen, een rustige uitstraling en voldoende comfort.',
                    'items' => ['Banken met strakke belijning', 'Slanke tafels en kasten', 'Rustige meubels zonder overbodige details', 'Een combinatie van hout, metaal en zachte stoffen'],
                ],
                'lighting' => null,
                'accessories' => null,
                'advice_primary' => 'Kies meubels met heldere vormen, een rustige uitstraling en voldoende comfort.',
                'advice_secondary' => null,
                'advice_tertiary' => null,
                'wat_past_goed' => null,
                'wat_past_minder_goed' => ['Veel verschillende decoratiestijlen en een overvloed aan kleine accessoires kunnen de rustige basis verstoren. Kies liever voor duidelijke lijnen en een aantal zorgvuldig gekozen accenten.'],
                'recipe' => [
                    ['label' => 'Basis', 'value' => 'Wit, greige en lichtgrijs'],
                    ['label' => 'Grote meubels', 'value' => 'Strakke bank en minimalistische kast'],
                    ['label' => 'Accentkleur', 'value' => 'Zwart of antraciet'],
                    ['label' => 'Materialen', 'value' => 'Hout, glas, metaal en keramiek'],
                    ['label' => 'Accessoires', 'value' => 'Enkele grote objecten en grafische vormen'],
                ],
                'product_tags' => ['modern', 'strak', 'metaal'],
            ],
            [
                'style_key' => 'modernLuxe',
                'label' => 'Modern luxe',
                'slug' => 'modern-luxe',
                'subtitle' => 'Strakke elegantie met een verfijnde uitstraling',
                'long_description' => 'Jij houdt van een interieur dat modern en rustig oogt, maar tegelijkertijd luxe en bijzonder aanvoelt. Strakke lijnen worden gecombineerd met rijke materialen, subtiele glans en verfijnde details.',
                'traits_intro' => 'Op basis van jouw keuzes zien we vooral een voorkeur voor moderne vormen, rustige kleuren en luxe materialen.',
                'core_traits' => ['Strakke, elegante lijnen', 'Luxe materialen', 'Neutrale kleuren', 'Verfijnde details', 'Een rustige, exclusieve uitstraling'],
                'hero_image' => '/images/interior/atmosphere/modern-luxe.webp',
                'base_colors' => [
                    ['name' => 'Warm wit', 'hex' => '#f5f0e6'],
                    ['name' => 'Greige', 'hex' => '#cfc3ac'],
                    ['name' => 'Taupe', 'hex' => '#a8967d'],
                ],
                'accent_colors' => [
                    ['name' => 'Chocoladebruin', 'hex' => '#3e2a20'],
                    ['name' => 'Zwart', 'hex' => '#1a1a1a'],
                    ['name' => 'Bordeaux', 'hex' => '#5c1f2e'],
                ],
                'color_tip' => 'Houd de basis rustig met warm wit, greige en taupe en voeg donkerdere accenten toe voor contrast. Luxe materialen en subtiele glans mogen vervolgens voor extra diepte zorgen.',
                'materials' => ['Marmer', 'Walnoothout', 'Bouclé', 'Brons'],
                'materials_image' => null,
                'materials_tip' => 'Combineer strakke oppervlakken met rijke, zachte stoffen. Een mix van hout, steen en metaal zorgt voor een moderne uitstraling die toch warm en luxe blijft.',
                'furniture_shapes' => [
                    'intro' => 'Ga voor meubels die rustig ogen, maar door vorm of materiaal toch bijzonder aanvoelen.',
                    'items' => ['Royale banken met strakke vormen', 'Ronde of ovale designtafels', 'Meubels van donker of warm hout', 'Verfijnde metalen details'],
                ],
                'lighting' => null,
                'accessories' => null,
                'advice_primary' => 'Ga voor meubels die rustig ogen, maar door vorm of materiaal toch bijzonder aanvoelen.',
                'advice_secondary' => null,
                'advice_tertiary' => null,
                'wat_past_goed' => null,
                'wat_past_minder_goed' => ['Te veel glans, opvallende accessoires of verschillende luxe materialen tegelijk kunnen de rustige uitstraling doorbreken. Laat liever een paar hoogwaardige materialen en meubels het verschil maken.'],
                'recipe' => [
                    ['label' => 'Basis', 'value' => 'Warm wit, greige en taupe'],
                    ['label' => 'Grote meubels', 'value' => 'Royale bank en elegante eettafel'],
                    ['label' => 'Accentkleur', 'value' => 'Chocoladebruin of zwart'],
                    ['label' => 'Materialen', 'value' => 'Marmer, walnoot, bouclé en brons'],
                    ['label' => 'Accessoires', 'value' => 'Designverlichting en enkele luxe objecten'],
                ],
                'product_tags' => ['modern-luxe', 'marmer', 'walnoothout'],
            ],
            [
                'style_key' => 'natuurlijk',
                'label' => 'Natuurlijk',
                'slug' => 'natuurlijk',
                'subtitle' => 'Ontspannen wonen met de natuur als inspiratie',
                'long_description' => 'Jij voelt je het prettigst in een interieur dat rustig, warm en natuurlijk aanvoelt. Aardse kleuren, hout, planten en voelbare materialen brengen buiten naar binnen en zorgen voor een ontspannen sfeer.',
                'traits_intro' => 'Op basis van jouw keuzes zien we vooral een voorkeur voor aardse kleuren, natuurlijke structuren en een warme, ontspannen uitstraling.',
                'core_traits' => ['Aardse kleuren', 'Natuurlijke materialen', 'Organische vormen', 'Veel structuur', 'Een warme en ontspannen sfeer'],
                'hero_image' => '/images/interior/atmosphere/natuurlijk.webp',
                'base_colors' => [
                    ['name' => 'Zand', 'hex' => '#d8c3a0'],
                    ['name' => 'Crème', 'hex' => '#f3ecd9'],
                    ['name' => 'Warm bruin', 'hex' => '#7a5c3e'],
                ],
                'accent_colors' => [
                    ['name' => 'Klei', 'hex' => '#a8623f'],
                    ['name' => 'Olijfgroen', 'hex' => '#6b7a4f'],
                    ['name' => 'Roestbruin', 'hex' => '#9c5233'],
                ],
                'color_tip' => 'Laat zand- en crèmetinten de rustige basis vormen en voeg groen, bruin en kleitinten toe. Zo ontstaat een gelaagd kleurenpalet dat rechtstreeks uit de natuur lijkt te komen.',
                'materials' => ['Massief hout', 'Rotan', 'Linnen', 'Natuursteen'],
                'materials_image' => null,
                'materials_tip' => 'Combineer materialen die je ook echt kunt voelen, zoals grof linnen, hout en steen. Kleine verschillen in structuur en kleurnuance maken jouw interieur juist interessant.',
                'furniture_shapes' => [
                    'intro' => 'Kies meubels met natuurlijke vormen en materialen die ontspannen en toegankelijk aanvoelen.',
                    'items' => ['Banken in zachte, natuurlijke stoffen', 'Organisch gevormde tafels', 'Meubels van zichtbaar hout', 'Rotan of gevlochten details'],
                ],
                'lighting' => null,
                'accessories' => null,
                'advice_primary' => 'Kies meubels met natuurlijke vormen en materialen die ontspannen en toegankelijk aanvoelen.',
                'advice_secondary' => null,
                'advice_tertiary' => null,
                'wat_past_goed' => null,
                'wat_past_minder_goed' => ['Veel kunststof, felle kleuren of zeer glanzende oppervlakken kunnen minder goed aansluiten bij jouw natuurlijke voorkeur. Kies liever voor materialen met een voelbare structuur en kleuren die je ook buiten tegenkomt.'],
                'recipe' => [
                    ['label' => 'Basis', 'value' => 'Crème, zand en naturel hout'],
                    ['label' => 'Grote meubels', 'value' => 'Zachte bank en houten tafel'],
                    ['label' => 'Accentkleur', 'value' => 'Olijfgroen of klei'],
                    ['label' => 'Materialen', 'value' => 'Hout, linnen, rotan en natuursteen'],
                    ['label' => 'Accessoires', 'value' => 'Planten, keramiek en natuurlijke objecten'],
                ],
                'product_tags' => ['natuurlijk', 'rotan', 'massief-hout'],
            ],
            [
                'style_key' => 'scandinavisch',
                'label' => 'Scandinavisch',
                'slug' => 'scandinavisch',
                'subtitle' => 'Licht, fris en gezellig eenvoudig',
                'long_description' => 'Jij houdt van een licht en rustig interieur waarin eenvoud en gezelligheid samenkomen. Functionele meubels, zachte kleuren en natuurlijke materialen zorgen voor een frisse basis die toch warm en uitnodigend voelt.',
                'traits_intro' => 'Op basis van jouw keuzes zien we vooral een voorkeur voor lichte kleuren, eenvoudige vormen en een frisse, comfortabele sfeer.',
                'core_traits' => ['Lichte kleuren', 'Eenvoudige vormen', 'Licht hout', 'Praktische meubels', 'Een frisse en gezellige uitstraling'],
                'hero_image' => '/images/interior/atmosphere/scandinavisch.webp',
                'base_colors' => [
                    ['name' => 'Helder wit', 'hex' => '#fbfbf9'],
                    ['name' => 'Licht beige', 'hex' => '#eee2cb'],
                    ['name' => 'Zacht grijs', 'hex' => '#dfe1e0'],
                ],
                'accent_colors' => [
                    ['name' => 'Lichtblauw', 'hex' => '#a9c2d0'],
                    ['name' => 'Saliegroen', 'hex' => '#9caf88'],
                    ['name' => 'Mosterdgeel', 'hex' => '#d2a441'],
                ],
                'color_tip' => 'Gebruik wit en lichte neutrale tinten om de ruimte fris te houden. Voeg zachte pastel- of natuurtinten toe voor warmte en een subtiel kleuraccent.',
                'materials' => ['Licht eiken', 'Wol', 'Katoen', 'Keramiek'],
                'materials_image' => null,
                'materials_tip' => 'Combineer licht hout met zachte stoffen en eenvoudige keramische accessoires. Zo behoud je de frisse uitstraling, terwijl je interieur toch warm en gezellig blijft.',
                'furniture_shapes' => [
                    'intro' => 'Ga voor praktische meubels met een lichte uitstraling en eenvoudige, vriendelijke vormen.',
                    'items' => ['Banken op slanke poten', 'Licht houten tafels en kasten', 'Compacte, functionele meubels', 'Zachte stoffen in lichte kleuren'],
                ],
                'lighting' => null,
                'accessories' => null,
                'advice_primary' => 'Ga voor praktische meubels met een lichte uitstraling en eenvoudige, vriendelijke vormen.',
                'advice_secondary' => null,
                'advice_tertiary' => null,
                'wat_past_goed' => null,
                'wat_past_minder_goed' => ['Veel donkere, zware meubels en een overvloed aan decoratie kunnen je interieur minder licht en ruimtelijk laten aanvoelen. Kies liever voor luchtige meubels en een paar persoonlijke accessoires die echt iets toevoegen.'],
                'recipe' => [
                    ['label' => 'Basis', 'value' => 'Wit, licht beige en licht hout'],
                    ['label' => 'Grote meubels', 'value' => 'Lichte bank en eenvoudige houten tafel'],
                    ['label' => 'Accentkleur', 'value' => 'Saliegroen of lichtblauw'],
                    ['label' => 'Materialen', 'value' => 'Licht eiken, wol, katoen en keramiek'],
                    ['label' => 'Accessoires', 'value' => 'Zachte plaids, eenvoudige vazen en sfeerverlichting'],
                ],
                'product_tags' => ['scandinavisch', 'licht-eiken', 'wol'],
            ],
        ];
    }
}

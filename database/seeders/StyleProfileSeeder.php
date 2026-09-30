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
 * kleuren, er is geen apart "neutraal" basispalet. `advice_tertiary`/`lighting`/`accessories`/
 * `wat_past_goed` bestonden niet in de oude, statische content — die blijven hier bewust leeg, in
 * te vullen door een admin. `advice_secondary` is dat inmiddels niet meer: zie
 * QuizResultTextComposer::secondaryInfluenceSentence(), dat dit veld gebruikt als deze stijl als
 * "invloed" is vastgesteld bij een ándere hoofdstijl — hieronder dus wél gevuld voor de zes actief
 * gebruikte stijlen (niet voor modernLuxe/natuurlijk, die verder ook nog nergens content hebben).
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
                'subtitle' => 'Warm, elegant en comfortabel',
                'long_description' => 'Uit je keuzes komt Hotel luxe het sterkst naar voren. Je lijkt te houden van warme kleuren, zachte stoffen en details die een ruimte bijzonder maken. Denk aan een comfortabele basis met één of twee luxe blikvangers, zoals een mooie lamp of een meubel met een rijke stof.',
                'traits_intro' => 'Op basis van jouw keuzes zien we vooral een voorkeur voor warme kleuren, zachte materialen en een rijke, sfeervolle uitstraling.',
                'core_traits' => ['Warme kleuren met diepte', 'Zachte stoffen, zoals fluweel', 'Sfeervolle verlichting', 'Elegante meubels en vormen', 'Een paar verfijnde details'],
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
                'materials' => ['Fluweel of een andere zachte meubelstof', 'Donker hout', 'Marmer of steen met een verfijnde tekening', 'Messing in kleine details'],
                'materials_image' => null,
                'materials_tip' => 'Een zachte stof naast hout of steen geeft de ruimte een rijke uitstraling. Gebruik glans gericht, bijvoorbeeld in een lamp of een klein metalen detail.',
                'furniture_shapes' => [
                    'intro' => 'Kies comfortabele meubels met rustige, elegante vormen. Eén opvallend meubel is vaak genoeg om de toon te zetten.',
                    'items' => ['Een royale bank met een rijke stof', 'Een fauteuil met een elegante vorm', 'Een tafel van donker hout of steen', 'Een lamp of kast met een verfijnd detail'],
                ],
                'lighting' => null,
                'accessories' => null,
                'advice_primary' => 'Kies comfortabele meubels met rustige, elegante vormen. Eén opvallend meubel is vaak genoeg om de toon te zetten.',
                'advice_secondary' => 'Ook Hotel luxe komt in je keuzes naar voren. Met een rijke stof, sfeervolle verlichting of een verfijnd detail kun je de ruimte extra luxe geven.',
                'advice_tertiary' => null,
                'wat_past_goed' => null,
                'wat_past_minder_goed' => ['Veel losse accessoires en allerlei verschillende glanzende materialen kunnen de ruimte druk maken. Herhaal liever een paar materialen en kies één of twee blikvangers.'],
                'recipe' => [
                    ['label' => 'Basis', 'value' => 'Warm beige, taupe en donker hout'],
                    ['label' => 'Grote meubels', 'value' => 'Een royale bank en een elegante fauteuil'],
                    ['label' => 'Accentkleur', 'value' => 'Smaragdgroen, bordeaux of cognac'],
                    ['label' => 'Materialen', 'value' => 'Een zachte stof, hout, steen en een beetje messing'],
                    ['label' => 'Accessoires', 'value' => 'Een sfeervolle lamp en enkele grotere accessoires'],
                ],
                'product_tags' => ['hotel-luxe', 'fluweel', 'marmer', 'messing'],
            ],
            [
                'style_key' => 'japandi',
                'label' => 'Japandi',
                'slug' => 'japandi',
                'subtitle' => 'Rust, warmte en natuurlijke eenvoud',
                'long_description' => 'Uit je keuzes komt Japandi het sterkst naar voren. Rustige kleuren, natuurlijk hout en eenvoudige vormen spreken je aan. Met linnen, keramiek en een paar goed gekozen meubels blijft de ruimte rustig én warm.',
                'traits_intro' => 'Op basis van jouw keuzes zien we vooral een voorkeur voor rust, natuurlijke materialen en warme tinten.',
                'core_traits' => ['Zachte aardetinten', 'Hout, linnen en keramiek', 'Eenvoudige, lage vormen', 'Ruimte rond de meubels', 'Weinig, bewust gekozen accessoires'],
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
                'materials' => ['Naturel of warm eikenhout', 'Linnen', 'Wol of bouclé in een rustige kleur', 'Matte keramiek'],
                'materials_image' => null,
                'materials_tip' => 'Hout, linnen en matte keramiek geven de ruimte structuur zonder veel versiering. Laat de materialen zichtbaar zijn en houd de vormen eenvoudig.',
                'furniture_shapes' => [
                    'intro' => 'Kies enkele meubels die goed bij elkaar passen en laat er ruimte omheen. Comfort blijft belangrijk.',
                    'items' => ['Een lage of rustig gevormde bank', 'Een eenvoudige houten tafel', 'Zacht afgeronde hoeken', 'Gesloten opbergruimte voor kleine spullen'],
                ],
                'lighting' => null,
                'accessories' => null,
                'advice_primary' => 'Kies enkele meubels die goed bij elkaar passen en laat er ruimte omheen. Comfort blijft belangrijk.',
                'advice_secondary' => 'Ook Japandi komt in je keuzes naar voren. Natuurlijke materialen en eenvoudige vormen kunnen voor meer rust en warmte zorgen.',
                'advice_tertiary' => null,
                'wat_past_goed' => null,
                'wat_past_minder_goed' => ['Felle kleuren, hoogglans en veel kleine accessoires halen de aandacht weg van de rustige basis. Kies liever voor enkele grotere, natuurlijke objecten.'],
                'recipe' => [
                    ['label' => 'Basis', 'value' => 'Warm krijtwit, zand en naturel hout'],
                    ['label' => 'Grote meubels', 'value' => 'Een rustig gevormde bank en een eenvoudige houten tafel'],
                    ['label' => 'Accentkleur', 'value' => 'Gedempt olijfgroen of kaneelbruin'],
                    ['label' => 'Materialen', 'value' => 'Hout, linnen, wol en matte keramiek'],
                    ['label' => 'Accessoires', 'value' => 'Een paar grote objecten van natuurlijke materialen'],
                ],
                'product_tags' => ['japandi', 'naturel-eiken', 'linnen', 'rustig-interieur'],
            ],
            [
                'style_key' => 'kleurExplosie',
                'label' => 'Kleur explosie',
                'slug' => 'kleur-explosie',
                'subtitle' => 'Kleur en karakter in huis',
                'long_description' => 'Uit je keuzes komt Kleur explosie het sterkst naar voren. Je lijkt ruimte te willen geven aan uitgesproken kleuren en bijzondere vormen. Kies een paar kleuren die je echt mooi vindt en laat die terugkomen in meubels, kunst of accessoires. Zo krijgt de ruimte een duidelijk geheel.',
                'traits_intro' => 'Op basis van jouw keuzes zien we vooral een voorkeur voor uitgesproken kleuren, speelse combinaties en meubels met karakter.',
                'core_traits' => ['Heldere of verrassende kleuren', 'Speelse combinaties', 'Opvallende vormen en patronen', 'Persoonlijke vondsten', 'Een herkenbaar kleurplan'],
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
                'materials' => ['Gekleurd glas', 'Velours of een andere zachte stof', 'Gelakt hout', 'Keramiek met kleur of patroon'],
                'materials_image' => null,
                'materials_tip' => 'Combineer kleur en structuur bewust. Laat bijvoorbeeld een zachte stof terugkomen naast gekleurd glas of keramiek en herhaal een paar kleuren in de ruimte.',
                'furniture_shapes' => [
                    'intro' => 'Een meubel mag opvallen door kleur, vorm of patroon. Kies welke stukken de hoofdrol krijgen en geef ze ruimte.',
                    'items' => ['Een bank of fauteuil in een duidelijke kleur', 'Een tafel met een speelse vorm', 'Een kast of bijzettafel met kleur', 'Een opvallende lamp of kunstwerk'],
                ],
                'lighting' => null,
                'accessories' => null,
                'advice_primary' => 'Een meubel mag opvallen door kleur, vorm of patroon. Kies welke stukken de hoofdrol krijgen en geef ze ruimte.',
                'advice_secondary' => 'Ook Kleur explosie komt in je keuzes naar voren. Een uitgesproken kleur of opvallend object kan de ruimte een speels accent geven.',
                'advice_tertiary' => null,
                'wat_past_goed' => null,
                'wat_past_minder_goed' => ['Als elke kleur en elk meubel evenveel aandacht vraagt, kan de ruimte rommelig voelen. Laat twee of drie kleuren terugkomen en zorg voor rustige vlakken ertussen.'],
                'recipe' => [
                    ['label' => 'Basis', 'value' => 'Een licht basispalet of een zachte gekleurde basis'],
                    ['label' => 'Grote meubels', 'value' => 'Eén of twee opvallende meubels'],
                    ['label' => 'Accentkleur', 'value' => 'Bijvoorbeeld kobaltblauw met fuchsia, of groen met oranje'],
                    ['label' => 'Materialen', 'value' => 'Gekleurd glas, keramiek, stof en gelakt hout'],
                    ['label' => 'Accessoires', 'value' => 'Kunst en verlichting die de gekozen kleuren herhalen'],
                ],
                'product_tags' => ['kleur-explosie', 'velours', 'gekleurd-glas'],
            ],
            [
                'style_key' => 'landelijk',
                'label' => 'Landelijk',
                'slug' => 'landelijk',
                'subtitle' => 'Warm, natuurlijk en vertrouwd',
                'long_description' => 'Uit je keuzes komt Landelijk het sterkst naar voren. Je lijkt je prettig te voelen bij natuurlijke materialen, warme tinten en meubels waarin je graag neerploft. Hout, linnen en zachte stoffen geven de ruimte een ontspannen en gastvrije sfeer.',
                'traits_intro' => 'Op basis van jouw keuzes zien we vooral een voorkeur voor warmte, natuurlijke materialen en een comfortabele, huiselijke sfeer.',
                'core_traits' => ['Warme, zachte kleuren', 'Hout met zichtbare structuur', 'Comfortabele meubels', 'Linnen, wol en aardewerk', 'Een uitnodigende sfeer'],
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
                'materials' => ['Eikenhout met zichtbare nerf', 'Linnen', 'Wol', 'Natuursteen of aardewerk'],
                'materials_image' => null,
                'materials_tip' => 'Hout met zichtbare structuur en zachte stoffen geven de ruimte warmte. Een klein verschil in kleur en textuur mag je juist zien.',
                'furniture_shapes' => [
                    'intro' => 'Kies meubels die prettig zitten en een natuurlijke uitstraling hebben. Een royale vorm mag, zolang de ruimte overzichtelijk blijft.',
                    'items' => ['Een comfortabele bank', 'Een houten eettafel met zichtbare nerf', 'Een zachte fauteuil', 'Een kast met eenvoudige, klassieke details'],
                ],
                'lighting' => null,
                'accessories' => null,
                'advice_primary' => 'Kies meubels die prettig zitten en een natuurlijke uitstraling hebben. Een royale vorm mag, zolang de ruimte overzichtelijk blijft.',
                'advice_secondary' => 'Ook Landelijk komt in je keuzes naar voren. Natuurlijk hout en comfortabele stoffen kunnen de ruimte warm en huiselijk maken.',
                'advice_tertiary' => null,
                'wat_past_goed' => null,
                'wat_past_minder_goed' => ['Heel strakke, glanzende meubels en harde zwart-witcontrasten kunnen de warme sfeer verzwakken. Voeg liever natuurlijke texturen en zachte overgangen toe.'],
                'recipe' => [
                    ['label' => 'Basis', 'value' => 'Roomwit, zand en warm hout'],
                    ['label' => 'Grote meubels', 'value' => 'Een comfortabele bank en een houten eettafel'],
                    ['label' => 'Accentkleur', 'value' => 'Saliegroen, zachte terracotta of kastanjebruin'],
                    ['label' => 'Materialen', 'value' => 'Hout, linnen, wol en natuursteen'],
                    ['label' => 'Accessoires', 'value' => 'Aardewerk, een kleed en enkele persoonlijke accessoires'],
                ],
                'product_tags' => ['landelijk', 'eikenhout', 'linnen'],
            ],
            [
                'style_key' => 'modern',
                'label' => 'Modern',
                'slug' => 'modern',
                'subtitle' => 'Strak, rustig en eigentijds',
                'long_description' => 'Uit je keuzes komt Modern het sterkst naar voren. Je kiest waarschijnlijk graag voor heldere lijnen, rustige vlakken en meubels zonder veel versiering. Een licht palet met duidelijke contrasten houdt het interieur overzichtelijk. Een paar warme materialen maken het persoonlijk.',
                'traits_intro' => 'Op basis van jouw keuzes zien we vooral een voorkeur voor eenvoud, duidelijke lijnen en een rustige, eigentijdse uitstraling.',
                'core_traits' => ['Heldere, strakke lijnen', 'Rustige vlakken en kleuren', 'Functionele meubels', 'Gladde afwerkingen', 'Contrast met zwart of antraciet'],
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
                'materials' => ['Metaal met een rustige afwerking', 'Glas', 'Glad keramiek of steen', 'Hout als warm accent'],
                'materials_image' => null,
                'materials_tip' => 'Gladde oppervlakken en strakke vormen horen bij Modern. Voeg één warme houtsoort of een zachte stof toe, zodat de ruimte prettig blijft om in te wonen.',
                'furniture_shapes' => [
                    'intro' => 'Kies meubels met heldere lijnen en weinig versiering. De vorm en afwerking bepalen hier de uitstraling.',
                    'items' => ['Een bank met een rechte belijning', 'Een slanke tafel', 'Een kast met rustige, vlakke fronten', 'Eén opvallend meubel of kunstwerk'],
                ],
                'lighting' => null,
                'accessories' => null,
                'advice_primary' => 'Kies meubels met heldere lijnen en weinig versiering. De vorm en afwerking bepalen hier de uitstraling.',
                'advice_secondary' => 'Ook Modern komt in je keuzes naar voren. Strakke lijnen en rustige vlakken kunnen voor een heldere, verzorgde uitstraling zorgen.',
                'advice_tertiary' => null,
                'wat_past_goed' => null,
                'wat_past_minder_goed' => ['Veel verschillende patronen, decoratiestijlen en kleine accessoires maken de heldere lijnen minder zichtbaar. Kies liever een paar duidelijke accenten.'],
                'recipe' => [
                    ['label' => 'Basis', 'value' => 'Wit, lichtgrijs of greige'],
                    ['label' => 'Grote meubels', 'value' => 'Een strak gevormde bank en een kast met vlakke fronten'],
                    ['label' => 'Accentkleur', 'value' => 'Zwart, diep groen of een enkele heldere kleur'],
                    ['label' => 'Materialen', 'value' => 'Metaal, glas, gladde steen en een beetje hout'],
                    ['label' => 'Accessoires', 'value' => 'Enkele grotere objecten of grafische kunst'],
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
                'label' => 'Modern Scandinavisch',
                'slug' => 'scandinavisch',
                'subtitle' => 'Licht, eenvoudig en warm',
                'long_description' => 'Uit je keuzes komt Modern Scandinavisch het sterkst naar voren. Je lijkt te vallen voor lichte ruimtes, strakke maar vriendelijke meubels en de warmte van licht hout. Matte afwerkingen en zachte stoffen geven de ruimte comfort, terwijl de basis fris en opgeruimd blijft.',
                'traits_intro' => 'Op basis van jouw keuzes zien we vooral een voorkeur voor een lichte basis, strakke vormen en een rustige, ingetogen sfeer.',
                'core_traits' => ['Lichte, neutrale kleuren', 'Licht hout met een matte afwerking', 'Eenvoudige, functionele meubels', 'Zachte stoffen en rustige texturen', 'Een fris en warm geheel'],
                'hero_image' => '/images/interior/atmosphere/scandinavisch.webp',
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
                'materials' => ['Licht eikenhout met een matte afwerking', 'Wol', 'Linnen', 'Matte keramiek'],
                'materials_image' => null,
                'materials_tip' => 'Licht hout, matte oppervlakken en zachte stoffen zorgen samen voor een rustige, warme ruimte. Herhaal die materialen in meubels en accessoires.',
                'furniture_shapes' => [
                    'intro' => 'Kies eenvoudige meubels die prettig werken in het dagelijks leven. Lichte houttinten en zachte stoffen houden de vormen vriendelijk.',
                    'items' => ['Een bank met een eenvoudige vorm en zichtbare poten', 'Een lichte houten tafel', 'Een functionele kast zonder veel details', 'Een comfortabele stoel met een zachte stof'],
                ],
                'lighting' => null,
                'accessories' => null,
                'advice_primary' => 'Kies eenvoudige meubels die prettig werken in het dagelijks leven. Lichte houttinten en zachte stoffen houden de vormen vriendelijk.',
                'advice_secondary' => 'Ook Modern Scandinavisch komt in je keuzes naar voren. Licht hout, zachte stoffen en eenvoudige meubels kunnen het geheel fris en warm maken.',
                'advice_tertiary' => null,
                'wat_past_goed' => null,
                'wat_past_minder_goed' => ['Een volledig grijs palet, veel zwart metaal of glanzende oppervlakken kan het natuurlijke karakter minder zichtbaar maken. Gebruik licht hout en zachte stoffen als vaste basis.'],
                'recipe' => [
                    ['label' => 'Basis', 'value' => 'Gebroken wit, warm steengrijs en licht eikenhout'],
                    ['label' => 'Grote meubels', 'value' => 'Een eenvoudige bank en een lichte houten tafel'],
                    ['label' => 'Accentkleur', 'value' => 'Dennengroen of een klein accent antraciet'],
                    ['label' => 'Materialen', 'value' => 'Licht hout, wol, linnen en matte keramiek'],
                    ['label' => 'Accessoires', 'value' => 'Een paar functionele objecten en warme verlichting'],
                ],
                'product_tags' => ['licht-eiken', 'linnen', 'antraciet'],
            ],
        ];
    }
}

<?php

use App\Models\BasePalette;
use App\Models\StyleCombinationAdvice;
use App\Models\StyleProfile;
use Illuminate\Database\Migrations\Migration;

/**
 * Herschrijft de klanttekst van zes stijlprofielen (Hotel luxe, Japandi, Kleur explosie, Landelijk,
 * Modern, Modern Scandinavisch) naar een vlottere, natuurlijkere versie — aangeleverd als PDF,
 * overgenomen per veld. Modern luxe en Natuurlijk hebben geen bijgewerkte tekst gekregen (die
 * stonden al leeg in de admin) en blijven hier dus ongemoeid.
 *
 * Zelfde voorzichtige patroon als 2026_09_17_110000_fix_moderns_possessive_typo_in_style_
 * combination_advices: elk veld wordt alleen bijgewerkt als de huidige waarde nog exact de
 * verwachte oude tekst is — een admin die een van deze velden intussen zelf alweer herschreven
 * heeft, wordt hier dus nooit overschreven. Kleuren/hexcodes blijven overal ongemoeid, op één
 * uitzondering na (zie updateBasePalettes(): het derde Modern Scandinavisch-palet kreeg ook een
 * nieuwe naam en één hernoemde kleur, zelfde hex).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->updateStyleProfiles();
        $this->updateBasePalettes();
        $this->updateCombinations();
    }

    public function down(): void
    {
        // Bewust geen terugdraai-logica: dit is een tekstuele verbeterslag, geen structuurwijziging.
    }

    /** @return array<int, array{0: string, 1: mixed, 2: mixed}> field => [old, new] per stijl. */
    private function styleProfileFixes(): array
    {
        return [
            'hotelLuxe' => [
                'subtitle' => ['Warm, elegant en comfortabel met een luxe uitstraling', 'Warm, elegant en comfortabel'],
                'long_description' => ['Jij houdt van een interieur dat direct sfeer en luxe uitstraalt, zonder dat het afstandelijk aanvoelt. Warme kleuren, rijke materialen en verfijnde details zorgen bij jou voor een comfortabele woonstijl met het gevoel van een stijlvol boutiquehotel.', 'Uit je keuzes komt Hotel luxe het sterkst naar voren. Je lijkt te houden van warme kleuren, zachte stoffen en details die een ruimte bijzonder maken. Denk aan een comfortabele basis met één of twee luxe blikvangers, zoals een mooie lamp of een meubel met een rijke stof.'],
                'core_traits' => [['Warme en diepe kleuren', 'Rijke, zachte materialen', 'Sfeervolle verlichting', 'Elegante vormen', 'Luxe details en accessoires'], ['Warme kleuren met diepte', 'Zachte stoffen, zoals fluweel', 'Sfeervolle verlichting', 'Elegante meubels en vormen', 'Een paar verfijnde details']],
                'materials' => [['Fluweel', 'Donker hout', 'Marmer', 'Messing'], ['Fluweel of een andere zachte meubelstof', 'Donker hout', 'Marmer of steen met een verfijnde tekening', 'Messing in kleine details']],
                'materials_tip' => ['Combineer zachte stoffen met gladde en verfijnde materialen zoals marmer, glas en metaal. Juist het contrast tussen zacht en chic geeft jouw interieur de luxe hotelsfeer die bij je past.', 'Een zachte stof naast hout of steen geeft de ruimte een rijke uitstraling. Gebruik glans gericht, bijvoorbeeld in een lamp of een klein metalen detail.'],
                'furniture_shapes' => [['intro' => 'Kies meubels die comfortabel aanvoelen, maar tegelijkertijd een elegante en verzorgde uitstraling hebben.', 'items' => ['Royale, zachte banken', 'Ronde en organische vormen', 'Donker hout of luxe afwerkingen', 'Statementmeubels met verfijnde details']], ['intro' => 'Kies comfortabele meubels met rustige, elegante vormen. Eén opvallend meubel is vaak genoeg om de toon te zetten.', 'items' => ['Een royale bank met een rijke stof', 'Een fauteuil met een elegante vorm', 'Een tafel van donker hout of steen', 'Een lamp of kast met een verfijnd detail']]],
                'recipe' => [[['label' => 'Basis', 'value' => 'Warm beige, taupe en donker hout'], ['label' => 'Grote meubels', 'value' => 'Royale bank en elegante fauteuils'], ['label' => 'Accentkleur', 'value' => 'Diep groen, donkerbruin of champagne'], ['label' => 'Materialen', 'value' => 'Fluweel, marmer, hout en messing'], ['label' => 'Accessoires', 'value' => 'Grote lampen, sierkussens en stijlvolle decoratie']], [['label' => 'Basis', 'value' => 'Warm beige, taupe en donker hout'], ['label' => 'Grote meubels', 'value' => 'Een royale bank en een elegante fauteuil'], ['label' => 'Accentkleur', 'value' => 'Smaragdgroen, bordeaux of cognac'], ['label' => 'Materialen', 'value' => 'Een zachte stof, hout, steen en een beetje messing'], ['label' => 'Accessoires', 'value' => 'Een sfeervolle lamp en enkele grotere accessoires']]],
                'wat_past_minder_goed' => [['Veel verschillende kleuren, kleine losse accessoires en te veel verschillende materialen kunnen de luxe uitstraling wat onrustiger maken. Kies liever voor een beperkt kleurenpalet en een paar krachtige blikvangers.'], ['Veel losse accessoires en allerlei verschillende glanzende materialen kunnen de ruimte druk maken. Herhaal liever een paar materialen en kies één of twee blikvangers.']],
            ],
            'japandi' => [
                'subtitle' => ['Rust, warmte en natuurlijke eenvoud', 'Rust, warmte en natuurlijke eenvoud'],
                'long_description' => ['Rust, warmte en natuurlijke materialen passen helemaal bij jou. Je kiest voor een interieur dat eenvoudig en verfijnd aanvoelt, maar tegelijkertijd warm en comfortabel blijft.', 'Uit je keuzes komt Japandi het sterkst naar voren. Rustige kleuren, natuurlijk hout en eenvoudige vormen spreken je aan. Met linnen, keramiek en een paar goed gekozen meubels blijft de ruimte rustig én warm.'],
                'core_traits' => [['Warme, rustige kleuren', 'Natuurlijke materialen', 'Zachte vormen', 'Een rustige uitstraling', 'Comfort en eenvoud'], ['Zachte aardetinten', 'Hout, linnen en keramiek', 'Eenvoudige, lage vormen', 'Ruimte rond de meubels', 'Weinig, bewust gekozen accessoires']],
                'materials' => [['Naturel eiken', 'Linnen', 'Wol / bouclé', 'Keramiek'], ['Naturel of warm eikenhout', 'Linnen', 'Wol of bouclé in een rustige kleur', 'Matte keramiek']],
                'materials_tip' => ['Combineer natuurlijke materialen met zachte stoffen. Hierdoor blijft je interieur rustig, maar voelt het tegelijkertijd warm en comfortabel.', 'Hout, linnen en matte keramiek geven de ruimte structuur zonder veel versiering. Laat de materialen zichtbaar zijn en houd de vormen eenvoudig.'],
                'furniture_shapes' => [['intro' => 'Kies liever voor een paar rustige, sterke meubels dan voor veel verschillende vormen en details.', 'items' => ['Zachte en afgeronde vormen', 'Lage, comfortabele banken', 'Rustige meubels zonder veel details', 'Naturel hout en zachte stoffen']], ['intro' => 'Kies enkele meubels die goed bij elkaar passen en laat er ruimte omheen. Comfort blijft belangrijk.', 'items' => ['Een lage of rustig gevormde bank', 'Een eenvoudige houten tafel', 'Zacht afgeronde hoeken', 'Gesloten opbergruimte voor kleine spullen']]],
                'recipe' => [[['label' => 'Basis', 'value' => 'Warm wit, zand en naturel hout'], ['label' => 'Grote meubels', 'value' => 'Lage bank en rustige houten meubels'], ['label' => 'Accentkleur', 'value' => 'Taupe of zacht olijfgroen'], ['label' => 'Materialen', 'value' => 'Eiken, linnen, wol en keramiek'], ['label' => 'Accessoires', 'value' => 'Enkele grote, natuurlijke objecten']], [['label' => 'Basis', 'value' => 'Warm krijtwit, zand en naturel hout'], ['label' => 'Grote meubels', 'value' => 'Een rustig gevormde bank en een eenvoudige houten tafel'], ['label' => 'Accentkleur', 'value' => 'Gedempt olijfgroen of kaneelbruin'], ['label' => 'Materialen', 'value' => 'Hout, linnen, wol en matte keramiek'], ['label' => 'Accessoires', 'value' => 'Een paar grote objecten van natuurlijke materialen']]],
                'wat_past_minder_goed' => [['Veel felle kleuren, hoogglans meubels en veel kleine accessoires kunnen jouw interieur sneller onrustig maken. Kies liever voor rust, natuurlijke materialen en een beperkt aantal uitgesproken elementen.'], ['Felle kleuren, hoogglans en veel kleine accessoires halen de aandacht weg van de rustige basis. Kies liever voor enkele grotere, natuurlijke objecten.']],
            ],
            'kleurExplosie' => [
                'subtitle' => ['Energiek, uitgesproken en helemaal van jou', 'Kleur en karakter in huis'],
                'long_description' => ['Jij wordt blij van kleur, karakter en een interieur waarin echt iets gebeurt. Je durft verschillende tinten, vormen en opvallende elementen te combineren en maakt van je huis graag een persoonlijke plek vol energie.', 'Uit je keuzes komt Kleur explosie het sterkst naar voren. Je lijkt ruimte te willen geven aan uitgesproken kleuren en bijzondere vormen. Kies een paar kleuren die je echt mooi vindt en laat die terugkomen in meubels, kunst of accessoires. Zo krijgt de ruimte een duidelijk geheel.'],
                'core_traits' => [['Opvallende kleuren', 'Speelse combinaties', 'Bijzondere vormen', 'Persoonlijke accessoires', 'Een energieke uitstraling'], ['Heldere of verrassende kleuren', 'Speelse combinaties', 'Opvallende vormen en patronen', 'Persoonlijke vondsten', 'Een herkenbaar kleurplan']],
                'materials' => [['Gekleurd glas', 'Velours', 'Gelakt hout', 'Keramiek'], ['Gekleurd glas', 'Velours of een andere zachte stof', 'Gelakt hout', 'Keramiek met kleur of patroon']],
                'materials_tip' => ['Mix materialen met verschillende structuren en uitstralingen. Een zachte stof naast glanzend glas of kleurrijk keramiek maakt jouw interieur extra spannend en persoonlijk.', 'Combineer kleur en structuur bewust. Laat bijvoorbeeld een zachte stof terugkomen naast gekleurd glas of keramiek en herhaal een paar kleuren in de ruimte.'],
                'furniture_shapes' => [['intro' => 'Meubels mogen bij jou gezien worden. Kies vormen, kleuren en details die een ruimte karakter geven.', 'items' => ['Banken of fauteuils in een uitgesproken kleur', 'Speelse en organische vormen', 'Bijzondere bijzettafels en kasten', 'Statementmeubels met karakter']], ['intro' => 'Een meubel mag opvallen door kleur, vorm of patroon. Kies welke stukken de hoofdrol krijgen en geef ze ruimte.', 'items' => ['Een bank of fauteuil in een duidelijke kleur', 'Een tafel met een speelse vorm', 'Een kast of bijzettafel met kleur', 'Een opvallende lamp of kunstwerk']]],
                'recipe' => [[['label' => 'Basis', 'value' => 'Een rustige basiskleur met kleurrijke accenten'], ['label' => 'Grote meubels', 'value' => 'Eén of twee uitgesproken blikvangers'], ['label' => 'Accentkleur', 'value' => 'Kobaltblauw, rood, groen of roze'], ['label' => 'Materialen', 'value' => 'Velours, glas, keramiek en gelakt hout'], ['label' => 'Accessoires', 'value' => 'Kunst, kleurrijke kussens en opvallende verlichting']], [['label' => 'Basis', 'value' => 'Een licht basispalet of een zachte gekleurde basis'], ['label' => 'Grote meubels', 'value' => 'Eén of twee opvallende meubels'], ['label' => 'Accentkleur', 'value' => 'Bijvoorbeeld kobaltblauw met fuchsia, of groen met oranje'], ['label' => 'Materialen', 'value' => 'Gekleurd glas, keramiek, stof en gelakt hout'], ['label' => 'Accessoires', 'value' => 'Kunst en verlichting die de gekozen kleuren herhalen']]],
                'wat_past_minder_goed' => [['Wanneer iedere kleur, vorm en accessoire evenveel aandacht vraagt, kan je interieur druk aanvoelen. Laat daarom een paar kleuren en blikvangers de hoofdrol spelen en geef ze voldoende rustige ruimte eromheen.'], ['Als elke kleur en elk meubel evenveel aandacht vraagt, kan de ruimte rommelig voelen. Laat twee of drie kleuren terugkomen en zorg voor rustige vlakken ertussen.']],
            ],
            'landelijk' => [
                'subtitle' => ['Warm, vertrouwd en sfeervol wonen', 'Warm, natuurlijk en vertrouwd'],
                'long_description' => ['Jij voelt je prettig in een interieur dat warm, uitnodigend en ontspannen aanvoelt. Natuurlijke materialen, zachte kleuren en comfortabele meubels zorgen voor een huis waarin je graag samenkomt en tot rust komt.', 'Uit je keuzes komt Landelijk het sterkst naar voren. Je lijkt je prettig te voelen bij natuurlijke materialen, warme tinten en meubels waarin je graag neerploft. Hout, linnen en zachte stoffen geven de ruimte een ontspannen en gastvrije sfeer.'],
                'core_traits' => [['Warme natuurtinten', 'Veel hout', 'Comfortabele meubels', 'Zachte stoffen', 'Een sfeervolle uitstraling'], ['Warme, zachte kleuren', 'Hout met zichtbare structuur', 'Comfortabele meubels', 'Linnen, wol en aardewerk', 'Een uitnodigende sfeer']],
                'materials' => [['Eikenhout', 'Linnen', 'Wol', 'Natuursteen'], ['Eikenhout met zichtbare nerf', 'Linnen', 'Wol', 'Natuursteen of aardewerk']],
                'materials_tip' => ['Combineer robuust hout met zachte stoffen en natuurlijke structuren. Hierdoor krijgt je interieur karakter, terwijl het tegelijkertijd warm en toegankelijk blijft.', 'Hout met zichtbare structuur en zachte stoffen geven de ruimte warmte. Een klein verschil in kleur en textuur mag je juist zien.'],
                'furniture_shapes' => [['intro' => 'Comfort staat centraal. Kies meubels die royaal ogen en uitnodigen om lang te blijven zitten.', 'items' => ['Royale, comfortabele banken', 'Houten tafels met een natuurlijke uitstraling', 'Zachte fauteuils', 'Kasten met rustige, klassieke vormen']], ['intro' => 'Kies meubels die prettig zitten en een natuurlijke uitstraling hebben. Een royale vorm mag, zolang de ruimte overzichtelijk blijft.', 'items' => ['Een comfortabele bank', 'Een houten eettafel met zichtbare nerf', 'Een zachte fauteuil', 'Een kast met eenvoudige, klassieke details']]],
                'recipe' => [[['label' => 'Basis', 'value' => 'Gebroken wit, zand en warm hout'], ['label' => 'Grote meubels', 'value' => 'Royale bank en houten eettafel'], ['label' => 'Accentkleur', 'value' => 'Vergrijsd groen of warm bruin'], ['label' => 'Materialen', 'value' => 'Hout, linnen, wol en natuursteen'], ['label' => 'Accessoires', 'value' => 'Aardewerk, plaids en natuurlijke decoratie']], [['label' => 'Basis', 'value' => 'Roomwit, zand en warm hout'], ['label' => 'Grote meubels', 'value' => 'Een comfortabele bank en een houten eettafel'], ['label' => 'Accentkleur', 'value' => 'Saliegroen, zachte terracotta of kastanjebruin'], ['label' => 'Materialen', 'value' => 'Hout, linnen, wol en natuursteen'], ['label' => 'Accessoires', 'value' => 'Aardewerk, een kleed en enkele persoonlijke accessoires']]],
                'wat_past_minder_goed' => [['Veel harde contrasten, koude materialen en een heel strakke styling kunnen minder goed aansluiten bij de warmte die jij zoekt. Kies liever voor zachte overgangen, natuurlijke structuren en meubels met een uitnodigende uitstraling.'], ['Heel strakke, glanzende meubels en harde zwart-witcontrasten kunnen de warme sfeer verzwakken. Voeg liever natuurlijke texturen en zachte overgangen toe.']],
            ],
            'modern' => [
                'subtitle' => ['Rustig, strak en eigentijds', 'Strak, rustig en eigentijds'],
                'long_description' => ['Jij houdt van een interieur dat overzichtelijk, fris en eigentijds aanvoelt. Strakke lijnen en rustige kleuren vormen de basis, maar er blijft voldoende ruimte voor comfort en persoonlijke accenten.', 'Uit je keuzes komt Modern het sterkst naar voren. Je kiest waarschijnlijk graag voor heldere lijnen, rustige vlakken en meubels zonder veel versiering. Een licht palet met duidelijke contrasten houdt het interieur overzichtelijk. Een paar warme materialen maken het persoonlijk.'],
                'core_traits' => [['Strakke lijnen', 'Rustige kleuren', 'Functionele meubels', 'Open en overzichtelijke ruimtes', 'Subtiele contrasten'], ['Heldere, strakke lijnen', 'Rustige vlakken en kleuren', 'Functionele meubels', 'Gladde afwerkingen', 'Contrast met zwart of antraciet']],
                'materials' => [['Eikenhout', 'Metaal', 'Glas', 'Keramiek'], ['Metaal met een rustige afwerking', 'Glas', 'Glad keramiek of steen', 'Hout als warm accent']],
                'materials_tip' => ['Combineer gladde materialen met hout of textiel om warmte toe te voegen. Zo blijft de moderne uitstraling strak, maar voelt je interieur wel prettig en leefbaar aan.', 'Gladde oppervlakken en strakke vormen horen bij Modern. Voeg één warme houtsoort of een zachte stof toe, zodat de ruimte prettig blijft om in te wonen.'],
                'furniture_shapes' => [['intro' => 'Kies meubels met heldere vormen, een rustige uitstraling en voldoende comfort.', 'items' => ['Banken met strakke belijning', 'Slanke tafels en kasten', 'Rustige meubels zonder overbodige details', 'Een combinatie van hout, metaal en zachte stoffen']], ['intro' => 'Kies meubels met heldere lijnen en weinig versiering. De vorm en afwerking bepalen hier de uitstraling.', 'items' => ['Een bank met een rechte belijning', 'Een slanke tafel', 'Een kast met rustige, vlakke fronten', 'Eén opvallend meubel of kunstwerk']]],
                'recipe' => [[['label' => 'Basis', 'value' => 'Wit, greige en lichtgrijs'], ['label' => 'Grote meubels', 'value' => 'Strakke bank en minimalistische kast'], ['label' => 'Accentkleur', 'value' => 'Zwart of antraciet'], ['label' => 'Materialen', 'value' => 'Hout, glas, metaal en keramiek'], ['label' => 'Accessoires', 'value' => 'Enkele grote objecten en grafische vormen']], [['label' => 'Basis', 'value' => 'Wit, lichtgrijs of greige'], ['label' => 'Grote meubels', 'value' => 'Een strak gevormde bank en een kast met vlakke fronten'], ['label' => 'Accentkleur', 'value' => 'Zwart, diep groen of een enkele heldere kleur'], ['label' => 'Materialen', 'value' => 'Metaal, glas, gladde steen en een beetje hout'], ['label' => 'Accessoires', 'value' => 'Enkele grotere objecten of grafische kunst']]],
                'wat_past_minder_goed' => [['Veel verschillende decoratiestijlen en een overvloed aan kleine accessoires kunnen de rustige basis verstoren. Kies liever voor duidelijke lijnen en een aantal zorgvuldig gekozen accenten.'], ['Veel verschillende patronen, decoratiestijlen en kleine accessoires maken de heldere lijnen minder zichtbaar. Kies liever een paar duidelijke accenten.']],
            ],
            'scandinavisch' => [
                'subtitle' => ['Licht, strak en verfijnd eenvoudig', 'Licht, eenvoudig en warm'],
                'long_description' => ['Jij houdt van een licht interieur met een rustige, verfijnde uitstraling. Natuurlijke materialen en een strakke, opgeruimde basis zorgen voor een moderne sfeer die tegelijk warm en uitnodigend blijft.', 'Uit je keuzes komt Modern Scandinavisch het sterkst naar voren. Je lijkt te vallen voor lichte ruimtes, strakke maar vriendelijke meubels en de warmte van licht hout. Matte afwerkingen en zachte stoffen geven de ruimte comfort, terwijl de basis fris en opgeruimd blijft.'],
                'core_traits' => [['Lichte, neutrale kleuren', 'Strakke, eenvoudige vormen', 'Licht hout met een mat karakter', 'Functionele meubels zonder overbodige details', 'Een rustige en verfijnde uitstraling'], ['Lichte, neutrale kleuren', 'Licht hout met een matte afwerking', 'Eenvoudige, functionele meubels', 'Zachte stoffen en rustige texturen', 'Een fris en warm geheel']],
                'materials' => [['Licht eikenhout', 'Wol', 'Linnen', 'Keramiek'], ['Licht eikenhout met een matte afwerking', 'Wol', 'Linnen', 'Matte keramiek']],
                'materials_tip' => ['Combineer licht hout met een beperkt aantal zachte stoffen en eenvoudige keramische accessoires. Zo houd je de basis rustig en verfijnd, zonder dat het interieur karakter verliest.', 'Licht hout, matte oppervlakken en zachte stoffen zorgen samen voor een rustige, warme ruimte. Herhaal die materialen in meubels en accessoires.'],
                'furniture_shapes' => [['intro' => 'Kies voor strakke meubels met een lichte uitstraling en eenvoudige, functionele vormen.', 'items' => ['Banken met slanke, rechte poten', 'Licht houten tafels en kasten zonder overbodige details', 'Compacte, functionele meubels', 'Enkele zachte stoffen in rustige, neutrale kleuren']], ['intro' => 'Kies eenvoudige meubels die prettig werken in het dagelijks leven. Lichte houttinten en zachte stoffen houden de vormen vriendelijk.', 'items' => ['Een bank met een eenvoudige vorm en zichtbare poten', 'Een lichte houten tafel', 'Een functionele kast zonder veel details', 'Een comfortabele stoel met een zachte stof']]],
                'recipe' => [[['label' => 'Basis', 'value' => 'Gebroken wit, warm steengrijs en licht eikenhout'], ['label' => 'Grote meubels', 'value' => 'Strakke bank en eenvoudige houten tafel'], ['label' => 'Accentkleur', 'value' => 'Antraciet of dennengroen'], ['label' => 'Materialen', 'value' => 'Licht eikenhout, wol, linnen en keramiek'], ['label' => 'Accessoires', 'value' => 'Enkele zorgvuldig gekozen objecten en sfeerverlichting']], [['label' => 'Basis', 'value' => 'Gebroken wit, warm steengrijs en licht eikenhout'], ['label' => 'Grote meubels', 'value' => 'Een eenvoudige bank en een lichte houten tafel'], ['label' => 'Accentkleur', 'value' => 'Dennengroen of een klein accent antraciet'], ['label' => 'Materialen', 'value' => 'Licht hout, wol, linnen en matte keramiek'], ['label' => 'Accessoires', 'value' => 'Een paar functionele objecten en warme verlichting']]],
                'wat_past_minder_goed' => [['Veel decoratieve accessoires, drukke patronen of felle kleuren kunnen de rustige, verfijnde basis verstoren. Kies liever voor een paar zorgvuldig gekozen accenten in plaats van veel kleine spullen.'], ['Een volledig grijs palet, veel zwart metaal of glanzende oppervlakken kan het natuurlijke karakter minder zichtbaar maken. Gebruik licht hout en zachte stoffen als vaste basis.']],
            ],
        ];
    }

    private function updateStyleProfiles(): void
    {
        foreach ($this->styleProfileFixes() as $styleKey => $fields) {
            $profile = StyleProfile::where('style_key', $styleKey)->first();
            if (! $profile) {
                continue;
            }

            $changes = [];
            foreach ($fields as $field => [$old, $new]) {
                if ($profile->{$field} == $old) {
                    $changes[$field] = $new;
                }
            }

            if ($changes !== []) {
                $profile->update($changes);
            }
        }
    }

    /** @return array<int, array{style_key: string, old_name: string, new_name: string, old_description: string, new_description: string, color_renames: array}> */
    private function basePaletteFixes(): array
    {
        return [
            [
                'style_key' => 'hotelLuxe',
                'old_name' => 'Licht en elegant',
                'new_name' => 'Licht en elegant',
                'old_description' => 'Een lichte, zachte basis met een verfijnde uitstraling.',
                'new_description' => 'Een lichte basis voor een elegante ruimte.',
                'color_renames' => [],
            ],
            [
                'style_key' => 'hotelLuxe',
                'old_name' => 'Warm en geborgen',
                'new_name' => 'Warm en geborgen',
                'old_description' => 'Warme tinten voor een comfortabele, luxe sfeer.',
                'new_description' => 'Warme beige- en bruintinten geven de ruimte diepte.',
                'color_renames' => [],
            ],
            [
                'style_key' => 'hotelLuxe',
                'old_name' => 'Diep en sfeervol',
                'new_name' => 'Diep en sfeervol',
                'old_description' => 'Rijke bruintinten voor een intieme, uitgesproken sfeer.',
                'new_description' => 'Donkerbruin maakt de sfeer intiem; gebruik de lichte tint om het geheel open te houden.',
                'color_renames' => [],
            ],
            [
                'style_key' => 'japandi',
                'old_name' => 'Licht en verstild',
                'new_name' => 'Licht en verstild',
                'old_description' => 'Lichte, natuurlijke tinten voor eenvoud en rust.',
                'new_description' => 'Lichte, natuurlijke tinten voor een rustige ruimte.',
                'color_renames' => [],
            ],
            [
                'style_key' => 'japandi',
                'old_name' => 'Warm en aards',
                'new_name' => 'Warm en aards',
                'old_description' => 'Zachte aardetinten voor een natuurlijke, warme uitstraling.',
                'new_description' => 'Zachte aardetinten geven iets meer warmte.',
                'color_renames' => [],
            ],
            [
                'style_key' => 'japandi',
                'old_name' => 'Rustig met diepte',
                'new_name' => 'Rustig met diepte',
                'old_description' => 'Gedempte tinten met donkerbruin voor een geborgen geheel.',
                'new_description' => 'Bruin geeft diepte, terwijl zandkleur het geheel rustig houdt.',
                'color_renames' => [],
            ],
            [
                'style_key' => 'kleurExplosie',
                'old_name' => 'Rustige basis',
                'new_name' => 'Rustige basis',
                'old_description' => 'Een lichte achtergrond waarop jouw kleuraccenten opvallen.',
                'new_description' => 'Een rustige achtergrond waarop kleur opvalt.',
                'color_renames' => [],
            ],
            [
                'style_key' => 'kleurExplosie',
                'old_name' => 'Zacht en zonnig',
                'new_name' => 'Zacht en zonnig',
                'old_description' => 'Vriendelijke roze- en perziktinten voor een vrolijke basis.',
                'new_description' => 'Zachte roze- en perziktinten als vriendelijke kleurbasis.',
                'color_renames' => [],
            ],
            [
                'style_key' => 'kleurExplosie',
                'old_name' => 'Fris en speels',
                'new_name' => 'Fris en speels',
                'old_description' => 'Lichte blauwe en groene tinten voor een levendig geheel.',
                'new_description' => 'Lichtblauw en zachtgroen geven een frisse basis voor fellere accenten.',
                'color_renames' => [],
            ],
            [
                'style_key' => 'landelijk',
                'old_name' => 'Licht en luchtig',
                'new_name' => 'Licht en luchtig',
                'old_description' => 'Zachte natuurtinten voor een ontspannen, lichte woning.',
                'new_description' => 'Lichte natuurtinten geven een open en zachte basis.',
                'color_renames' => [],
            ],
            [
                'style_key' => 'landelijk',
                'old_name' => 'Warm en huiselijk',
                'new_name' => 'Warm en huiselijk',
                'old_description' => 'Warme zand- en leemtinten voor een vertrouwd thuisgevoel.',
                'new_description' => 'Zand en leem zorgen voor een warme, huiselijke sfeer.',
                'color_renames' => [],
            ],
            [
                'style_key' => 'landelijk',
                'old_name' => 'Rustiek en geborgen',
                'new_name' => 'Rustiek en geborgen',
                'old_description' => 'Natuurlijke bruintinten voor een knusse sfeer met karakter.',
                'new_description' => 'Bruintinten geven diepte; roomwit houdt de ruimte licht.',
                'color_renames' => [],
            ],
            [
                'style_key' => 'modern',
                'old_name' => 'Helder en minimalistisch',
                'new_name' => 'Helder en minimalistisch',
                'old_description' => 'Wit en lichtgrijs voor een frisse, rustige uitstraling.',
                'new_description' => 'Wit en lichtgrijs geven een helder, rustig geheel.',
                'color_renames' => [],
            ],
            [
                'style_key' => 'modern',
                'old_name' => 'Warm en rustig',
                'new_name' => 'Warm en rustig',
                'old_description' => 'Zachte neutrale tinten voor een modern interieur met warmte.',
                'new_description' => 'Greige en zandbeige houden de basis strak maar warm.',
                'color_renames' => [],
            ],
            [
                'style_key' => 'modern',
                'old_name' => 'Strak met contrast',
                'new_name' => 'Strak met contrast',
                'old_description' => 'Een lichte basis met grijs en antraciet voor duidelijke contrasten.',
                'new_description' => 'Grijs en antraciet geven een duidelijk contrast.',
                'color_renames' => [],
            ],
            [
                'style_key' => 'scandinavisch',
                'old_name' => 'Licht en mat',
                'new_name' => 'Licht en mat',
                'old_description' => 'Gebroken wit en een mat, warm grijs voor een rustige, verfijnde basis.',
                'new_description' => 'Gebroken wit en licht hout geven een frisse, warme basis.',
                'color_renames' => [],
            ],
            [
                'style_key' => 'scandinavisch',
                'old_name' => 'Rustig en geaard',
                'new_name' => 'Rustig en geaard',
                'old_description' => 'Zachte, aardse taupetinten voor een kalme, ingetogen sfeer.',
                'new_description' => 'Taupe en greige geven iets meer warmte; combineer ze met licht hout.',
                'color_renames' => [],
            ],
            [
                'style_key' => 'scandinavisch',
                'old_name' => 'Koel en helder',
                'new_name' => 'Fris met grijs',
                'old_description' => 'Heldere grijstinten met een vleugje kilte voor een strakke, lichte basis.',
                'new_description' => 'Melkwit en grijs houden het licht. Voeg hout en stof toe voor een zachte uitstraling.',
                'color_renames' => [['old_name' => 'Zacht antracietgrijs', 'hex' => '#A6A6A2', 'new_name' => 'Middengrijs']],
            ],
        ];
    }

    private function updateBasePalettes(): void
    {
        foreach ($this->basePaletteFixes() as $fix) {
            $palette = BasePalette::where('style_key', $fix['style_key'])->where('name', $fix['old_name'])->first();
            if (! $palette) {
                continue;
            }

            $changes = [];
            if ($palette->name === $fix['old_name']) {
                $changes['name'] = $fix['new_name'];
            }
            if ($palette->description === $fix['old_description']) {
                $changes['description'] = $fix['new_description'];
            }

            if ($fix['color_renames'] !== []) {
                $colors = $palette->colors;
                $colorsChanged = false;
                foreach ($colors as $index => $color) {
                    foreach ($fix['color_renames'] as $rename) {
                        if (($color['name'] ?? null) === $rename['old_name'] && strtoupper((string) ($color['hex'] ?? '')) === strtoupper($rename['hex'])) {
                            $colors[$index]['name'] = $rename['new_name'];
                            $colorsChanged = true;
                        }
                    }
                }
                if ($colorsChanged) {
                    $changes['colors'] = $colors;
                }
            }

            if ($changes !== []) {
                $palette->update($changes);
            }
        }
    }

    /** @return array<int, array{0: string, 1: string, 2: string, 3: string, 4: string}> [styleKeyA, styleKeyB, field, old, new] */
    private function combinationFixes(): array
    {
        return [
            ['hotelLuxe', 'hotelLuxe', 'intro', 'Jullie zijn het roerend eens: het mag allebei net wat rijker en verfijnder. Dat maakt inrichten opvallend eenvoudig — de basis staat al vast, het wordt vooral samen genieten van de details.', 'Beide voorkeuren wijzen op warmte en verfijning. Kies samen welke details de hoofdrol krijgen.'],
            ['hotelLuxe', 'hotelLuxe', 'basis_tip', 'Blijf bij een warme, ietwat donkere basis van beige, taupe en donker hout. Met zoveel gedeelde smaak hoeven jullie geen compromis te zoeken — alleen samen de juiste tint te kiezen.', 'Gebruik beige en taupe als basis, met donker hout voor diepte.'],
            ['hotelLuxe', 'hotelLuxe', 'materials_tip', 'Ga voor de volle mix die bij deze stijl hoort: fluweel, marmer, hout en messing. Omdat jullie hier allebei van houden, mag het gerust wat rijker en gelaagder dan bij één persoon alleen.', 'Kies enkele rijke materialen, zoals fluweel en marmer, zonder alle oppervlakken druk te maken.'],
            ['hotelLuxe', 'hotelLuxe', 'accent_tip', 'Kies met z\'n tweeën één uitgesproken accentkleur — diep groen of champagne werkt bij jullie allebei — en gebruik die dubbel zo veel als je normaal zou doen.', 'Diepgroen of champagne kan de ruimte extra karakter geven.'],
            ['hotelLuxe', 'japandi', 'intro', 'De één houdt van weelde en glans, de ander van rust en eenvoud — samen vinden jullie elkaar in warmte: allebei kiezen jullie voor een interieur dat zacht en uitnodigend aanvoelt, alleen met een ander volume.', 'Warme materialen verbinden deze stijlen. Houd de ruimte rustig en voeg luxe toe op enkele plekken.'],
            ['hotelLuxe', 'japandi', 'basis_tip', 'Kies een gedeelde basis van warm hout en zandtinten — dat past bij Japandi\'s rust én bij hotel luxe\'s warmte — en laat de luxe vooral terugkomen in details en accessoires, niet in de hele ruimte.', 'Kies zand, warm wit en hout als basis.'],
            ['hotelLuxe', 'japandi', 'materials_tip', 'Combineer Japandi\'s natuurlijke hout en linnen met één of twee rijkere materialen uit hotel luxe, zoals fluweel of marmer, als bewust contrast in plaats van door de hele ruimte.', 'Combineer hout en linnen met één rijk detail, zoals een fluwelen stoel of een marmeren blad.'],
            ['hotelLuxe', 'japandi', 'accent_tip', 'Eén gedeelde, diepe kleur — zoals donkergroen — werkt in beide werelden: rustig genoeg voor Japandi, rijk genoeg voor hotel luxe.', 'Diepgroen geeft wat extra diepte. Gebruik het bijvoorbeeld in een kussen of kunstwerk.'],
            ['hotelLuxe', 'kleurExplosie', 'intro', 'Verfijnde luxe en uitbundige kleur lijken elkaars tegenpolen, maar delen één ding: allebei houden jullie van een interieur met karakter en durf, niet van iets behoudends.', 'Een warme, luxe basis geeft felle kleuren een duidelijke plek.'],
            ['hotelLuxe', 'kleurExplosie', 'basis_tip', 'Laat hotel luxe de rustige basis leveren — warm beige en donker hout — zodat er een stevig fundament ligt waarop vervolgens vrij gespeeld kan worden met kleurrijke accenten.', 'Begin met beige, taupe en donker hout.'],
            ['hotelLuxe', 'kleurExplosie', 'materials_tip', 'Fluweel en marmer uit hotel luxe combineren verrassend goed met glanzend keramiek en gelakt hout uit kleur explosie — beide stijlen houden namelijk van een beetje glans en statement.', 'Laat één kleurrijk meubel of een opvallend kunstwerk het middelpunt zijn. Een rijke stof kan beide stijlen verbinden.'],
            ['hotelLuxe', 'kleurExplosie', 'accent_tip', 'Eén uitgesproken kleur, zoals smaragdgroen of robijnrood, in een rijk materiaal (fluweel, glanzend keramiek) geeft precies de mix van luxe én lef die jullie allebei zoeken.', 'Kies een uitgesproken kleur, zoals kobaltblauw of smaragdgroen, en laat die op een tweede plek terugkomen.'],
            ['hotelLuxe', 'landelijk', 'intro', 'Allebei houden jullie van warmte en gezelligheid — het verschil zit vooral in de verfijning: strak en chic tegenover robuust en vertrouwd.', 'Deze stijlen delen warmte en comfort. Het verschil zit in de afwerking: landelijk is losser, Hotel luxe verfijnder.'],
            ['hotelLuxe', 'landelijk', 'basis_tip', 'Warm hout en zachte, gebroken wittinten vormen bij beide stijlen de basis — kies samen voor een iets verfijndere houtsoort, zodat het landelijke karakter blijft maar met net wat meer chique uitstraling.', 'Kies gebroken wit, warm beige en hout.'],
            ['hotelLuxe', 'landelijk', 'materials_tip', 'Combineer landelijk hout en linnen met fluweel of een subtiele glanzende afwerking — dat brengt de luxe erin zonder dat het huis zijn vertrouwde, warme karakter verliest.', 'Combineer zichtbaar hout en linnen met bijvoorbeeld één fluwelen stoel of een elegante lamp.'],
            ['hotelLuxe', 'landelijk', 'accent_tip', 'Warm groen of diepbruin werkt bij allebei: aards genoeg voor landelijk, rijk genoeg voor hotel luxe.', 'Diepgroen of donkerbruin geeft diepte en blijft bij beide stijlen passen.'],
            ['hotelLuxe', 'modern', 'intro', 'Strak en rustig versus rijk en sfeervol: samen vinden jullie een interieur dat er verzorgd en volwassen uitziet, met precies genoeg glans om niet kil te worden.', 'Een strakke indeling kan goed samengaan met een paar luxe materialen.'],
            ['hotelLuxe', 'modern', 'basis_tip', 'Ga uit van modern\'s lichte, neutrale basis (wit, greige, lichtgrijs) en voeg de warmte van hotel luxe toe via hout en textiel — zo blijft het rustig, maar niet steriel.', 'Gebruik lichte, rustige kleuren en voeg op enkele plekken een warmere tint toe.'],
            ['hotelLuxe', 'modern', 'materials_tip', 'Laat de strakke, gladde oppervlakken van modern (glas, metaal) in gesprek gaan met fluweel of marmer uit hotel luxe — precies dat contrast maakt een interieur interessant in plaats van kil of te druk.', 'Laat gladde meubels samengaan met één zachte stof of een marmeren blad.'],
            ['hotelLuxe', 'modern', 'accent_tip', 'Antraciet of zwart, gecombineerd met één warmere tint zoals champagne of chocoladebruin, geeft de rust van modern en de warmte van hotel luxe tegelijk.', 'Kies antraciet voor contrast en bijvoorbeeld champagne of donkerbruin voor warmte.'],
            ['hotelLuxe', 'scandinavisch', 'intro', 'Fris en licht tegenover rijk en sfeervol — samen zoeken jullie de balans tussen een luchtig huis en een warme, verwennende sfeer.', 'Licht hout en een rustige basis houden de ruimte fris. Een rijk detail voegt luxe toe.'],
            ['hotelLuxe', 'scandinavisch', 'basis_tip', 'Houd de basis licht, zoals Modern Scandinavisch dat wil, met wit en lichte houttinten — en voeg de luxe toe via één donkerder, rijker accentmeubel of -muur in plaats van de hele ruimte te verzwaren.', 'Gebruik gebroken wit en licht hout, met een beetje taupe voor diepte.'],
            ['hotelLuxe', 'scandinavisch', 'materials_tip', 'Licht hout en zachte stoffen uit Modern Scandinavisch design combineren goed met één luxueus materiaal, zoals fluweel of marmer, als bewust hoogtepunt in een verder rustige ruimte.', 'Kies één blikvanger, zoals een fluwelen fauteuil of een lamp met een verfijnde afwerking.'],
            ['hotelLuxe', 'scandinavisch', 'accent_tip', 'Een zachte, aardse tint zoals taupe of roestbruin voelt bij allebei op zijn plek — verfijnd genoeg voor Modern Scandinavisch, warm genoeg voor hotel luxe.', 'Dennengroen of diepbruin kan een klein, warm accent zijn.'],
            ['japandi', 'japandi', 'intro', 'Jullie zoeken allebei dezelfde rust — dit wordt een van de makkelijkste combinaties om samen vorm te geven.', 'Jullie keuzes wijzen allebei op rust en natuurlijke materialen. Laat voldoende ruimte tussen de meubels.'],
            ['japandi', 'japandi', 'basis_tip', 'Blijf bij de vertrouwde, lichte basis van warm wit, zand en naturel hout — dezelfde smaak betekent vooral samen zoeken naar de juiste natuurlijke materialen, niet naar compromissen.', 'Kies warm wit, zand en naturel hout.'],
            ['japandi', 'japandi', 'materials_tip', 'Eiken, linnen, wol en keramiek blijven de kern — kies er samen een paar bewuste, iets grotere stukken van in plaats van veel kleine spullen, dat past bij hoe rustig Japandi wil blijven.', 'Combineer hout, linnen en keramiek. Een paar grotere stukken geven meer rust dan veel kleine accessoires.'],
            ['japandi', 'japandi', 'accent_tip', 'Eén zachte kleur, zoals taupe of olijfgroen, is meer dan genoeg — zo bewaren jullie de rust die jullie allebei zoeken.', 'Zacht olijfgroen of taupe past bij de rustige basis.'],
            ['japandi', 'kleurExplosie', 'intro', 'Ingetogen rust tegenover uitbundige kleur — dit is misschien wel het grootste contrast, maar juist dat maakt het een leuke uitdaging: rust als basis, kleur als hoogtepunt.', 'Laat natuurlijke materialen de rust bewaren en geef een felle kleur één duidelijke plek.'],
            ['japandi', 'kleurExplosie', 'basis_tip', 'Laat Japandi de basis bepalen — warm wit, zand en hout — zodat er een kalme achtergrond ontstaat waartegen kleurrijke accenten juist extra goed tot hun recht komen.', 'Kies warm wit, zand en hout als rustige achtergrond.'],
            ['japandi', 'kleurExplosie', 'materials_tip', 'Hout en linnen blijven de ondertoon; voeg daar bewust één of twee kleurrijke materialen aan toe, zoals gelakt hout of keramiek, als duidelijke blikvangers in plaats van door de hele ruimte.', 'Voeg bijvoorbeeld een kleurrijk keramisch object, kunstwerk of gelakte stoel toe.'],
            ['japandi', 'kleurExplosie', 'accent_tip', 'Kies met z\'n tweeën één uitgesproken kleur (zoals kobaltblauw) en gebruik die spaarzaam maar zichtbaar — genoeg kleur om blij van te worden, genoeg rust om Japandi recht te doen.', 'Kies één heldere kleur die je graag ziet. Herhaal die beperkt, zodat de ruimte rustig blijft.'],
            ['japandi', 'landelijk', 'intro', 'Twee stijlen die allebei natuurlijke materialen en warmte vooropstellen — het verschil zit vooral in de vorm: strak en minimalistisch tegenover vol en vertrouwd.', 'Beide stijlen gebruiken natuurlijke materialen. Houd de vormen eenvoudig en voeg landelijke zachtheid toe.'],
            ['japandi', 'landelijk', 'basis_tip', 'Warm hout en zachte, aardse tinten vormen bij beide de basis — kies samen voor rustigere vormen, gevuld met de knusheid van landelijke stoffen en accessoires.', 'Gebruik warm wit, zand en naturel hout.'],
            ['japandi', 'landelijk', 'materials_tip', 'Eiken en linnen zijn bij beide stijlen thuis — combineer landelijk aardewerk met Japandi\'s strakkere keramiek voor een mix die zowel warm als rustig aanvoelt.', 'Combineer een eenvoudige houten tafel met linnen, wol en een paar stukken aardewerk.'],
            ['japandi', 'landelijk', 'accent_tip', 'Olijfgroen of warm bruin werkt in beide werelden — hou het bij één kleur zodat het rustig blijft.', 'Zacht olijfgroen of warm bruin past bij het natuurlijke palet.'],
            ['japandi', 'modern', 'intro', 'Allebei houden jullie van rust en eenvoud — het verschil is de temperatuur: warm en zacht tegenover strak en koel.', 'Deze stijlen delen rustige vormen. Hout en textiel geven een strakke basis meer warmte.'],
            ['japandi', 'modern', 'basis_tip', 'Neem modern\'s lichte, neutrale basis en voeg Japandi\'s warme houttinten toe — dat voorkomt dat het geheel te kil aanvoelt, zonder de rust van modern te verliezen.', 'Gebruik warm wit en grijsbeige met natuurlijke houttinten.'],
            ['japandi', 'modern', 'materials_tip', 'Combineer strakke, gladde oppervlakken uit modern met natuurlijk hout en linnen uit Japandi — precies die balans tussen strak en zacht maakt deze twee stijlen een sterk koppel.', 'Combineer strakke meubels met hout, linnen of matte keramiek.'],
            ['japandi', 'modern', 'accent_tip', 'Antraciet of zwart als accent past bij modern; hou de hoeveelheid beperkt zodat Japandi\'s rust intact blijft.', 'Gebruik zwart of antraciet in een paar kleine details voor contrast.'],
            ['japandi', 'scandinavisch', 'intro', 'Allebei licht, rustig en natuurlijk — jullie zitten dichter bij elkaar dan de meeste combinaties, met net een ander accent: warmer en aards tegenover fris en luchtig.', 'Beide stijlen zijn rustig en houden van lichte materialen. Japandi is iets aardser; Modern Scandinavisch voelt frisser.'],
            ['japandi', 'scandinavisch', 'basis_tip', 'Licht hout en zachte wittinten werken voor jullie allebei — kies samen of het geheel iets warmer (Japandi) of iets frisser (Modern Scandinavisch) mag aanvoelen, en laat die keuze de basis kleuren.', 'Kies warm wit en licht hout. Een zandkleur of licht leem maakt het geheel wat warmer.'],
            ['japandi', 'scandinavisch', 'materials_tip', 'Eiken, wol en linnen zijn bij beide stijlen vertrouwd terrein — een mix van Japandi\'s iets rustiekere keramiek en Modern Scandinavisch\' strakkere accessoires geeft genoeg afwisseling.', 'Gebruik matte materialen en eenvoudige meubels. Voeg één handgemaakt keramisch stuk toe voor meer Japandi karakter.'],
            ['japandi', 'scandinavisch', 'accent_tip', 'Een zachte pasteltint of taupe past bij beide — licht genoeg voor Modern Scandinavisch, warm genoeg voor Japandi.', 'Zacht olijfgroen of een kleine donkere toets geeft diepte zonder veel aandacht te vragen.'],
            ['kleurExplosie', 'kleurExplosie', 'intro', 'Twee mensen die van kleur, karakter en lef houden — dit wordt een huis waarin nooit iets saais zal staan.', 'Jullie keuzes geven allebei ruimte aan kleur. Spreek af welke kleuren steeds terugkomen.'],
            ['kleurExplosie', 'kleurExplosie', 'basis_tip', 'Eén rustige basiskleur blijft nodig als rustpunt, ook als jullie er allebei van houden om uit te pakken — anders vecht straks alles om aandacht in plaats van dat het samen klopt.', 'Kies een rustige achtergrond voor de grotere vlakken.'],
            ['kleurExplosie', 'kleurExplosie', 'materials_tip', 'Mix gerust materialen met verschillende structuren en glans — velours, glas, keramiek en gelakt hout — dat past bij hoe jullie allebei naar een interieur kijken: gedurfd en persoonlijk.', 'Mix kleur in meubels, kunst en keramiek, maar herhaal enkele materialen voor samenhang.'],
            ['kleurExplosie', 'kleurExplosie', 'accent_tip', 'Spreek samen af welke twee kleuren de hoofdrol krijgen (bijvoorbeeld kobaltblauw én roze) — met twee liefhebbers van kleur is dat belangrijker dan ooit, anders wordt het al snel te veel.', 'Laat twee favoriete kleuren terugkomen in verschillende delen van de kamer.'],
            ['kleurExplosie', 'landelijk', 'intro', 'Warm en vertrouwd tegenover fel en uitgesproken — samen zoeken jullie een huis dat gezellig blijft, maar nooit saai wordt.', 'Een landelijke basis houdt de ruimte warm. Kleur geeft er een speels accent aan.'],
            ['kleurExplosie', 'landelijk', 'basis_tip', 'Laat landelijk de warme, natuurlijke basis leveren — gebroken wit, zand en hout — zodat felle accenten daar juist tegen kunnen opvallen in plaats van overweldigen.', 'Begin met gebroken wit, zand en hout.'],
            ['kleurExplosie', 'landelijk', 'materials_tip', 'Combineer landelijk hout en natuursteen met kleurrijk keramiek of een gelakt meubelstuk — de robuustheid van landelijk vangt de felheid mooi op.', 'Voeg kleurrijk keramiek, een gestoffeerde stoel of een opvallend patroon toe.'],
            ['kleurExplosie', 'landelijk', 'accent_tip', 'Kies een kleur die ook in de natuur voorkomt, zoals dieprood of okergeel — die brengt karakter zonder los te komen te staan van de landelijke basis.', 'Okergeel of koraalrood kan goed werken. Laat de gekozen kleur op een paar plekken terugkomen.'],
            ['kleurExplosie', 'modern', 'intro', 'Strak en rustig tegenover fel en speels — dit koppel vindt elkaar in een interieur waar kleur juist opvalt dóórdat de rest zo rustig is.', 'Een strak interieur geeft felle kleuren ruimte om op te vallen.'],
            ['kleurExplosie', 'modern', 'basis_tip', 'Houd de basis strak en neutraal, zoals modern dat wil — wit, greige, lichtgrijs — zodat er letterlijk ruimte overblijft voor felle kleuraccenten.', 'Houd wanden en grote meubels rustig in wit, greige of lichtgrijs.'],
            ['kleurExplosie', 'modern', 'materials_tip', 'Gladde, strakke materialen als glas en metaal vormen een mooi decor voor glanzend keramiek of gelakte meubels — het contrast maakt beide stijlen sterker.', 'Een gelakte kast of opvallend kunstwerk voegt kleur toe aan de strakke vormen.'],
            ['kleurExplosie', 'modern', 'accent_tip', 'Eén heldere kleur, zoals kobaltblauw of felroze, op een verder rustige achtergrond geeft precies de impact die kleur explosie zoekt, zonder modern\'s rust te verstoren.', 'Kies één of twee heldere kleuren en herhaal ze gericht in de ruimte.'],
            ['kleurExplosie', 'scandinavisch', 'intro', 'Licht en ingetogen tegenover fel en uitbundig — samen ontstaat een huis dat fris aanvoelt, met net dat vrolijke tikkeltje extra.', 'Een lichte ruimte met licht hout kan goed een vrolijk kleuraccent dragen.'],
            ['kleurExplosie', 'scandinavisch', 'basis_tip', 'Modern Scandinavisch\' lichte, neutrale basis (wit en licht hout) geeft precies de rust die nodig is om felle accenten te laten stralen zonder dat het te druk wordt.', 'Gebruik gebroken wit, licht hout en warm steengrijs.'],
            ['kleurExplosie', 'scandinavisch', 'materials_tip', 'Licht hout en eenvoudige keramiek uit Modern Scandinavisch design combineren verrassend goed met één of twee gelakte of glanzende kleuraccenten.', 'Voeg kleur toe met een stoel, lamp, keramiek of textiel. Houd de overige vormen eenvoudig.'],
            ['kleurExplosie', 'scandinavisch', 'accent_tip', 'Eén vrolijke kleur, zoals felroze of geel, mag hier gerust de show stelen — zolang de basis licht en rustig blijft, verdraagt Modern Scandinavisch dat goed.', 'Kies bijvoorbeeld roze of kobaltblauw en laat die kleur op enkele plekken terugkomen.'],
            ['landelijk', 'landelijk', 'intro', 'Allebei houden jullie van warmte, gezelligheid en een huis waar je graag samenkomt — deze combinatie spreekt eigenlijk voor zich.', 'Jullie keuzes wijzen allebei op een warm huis met natuurlijke materialen.'],
            ['landelijk', 'landelijk', 'basis_tip', 'Blijf bij de vertrouwde basis van gebroken wit, zand en warm hout — met dezelfde smaak wordt kiezen vooral een kwestie van samen de mooiste houtsoort en stoffen uitzoeken.', 'Gebruik gebroken wit, zand en warm hout.'],
            ['landelijk', 'landelijk', 'materials_tip', 'Hout, linnen, wol en natuursteen vormen de kern — voeg gerust wat meer natuurlijke accessoires toe dan je alleen zou doen, dat past bij hoe knus jullie het samen willen hebben.', 'Combineer hout, linnen en wol met meubels waarin je prettig zit.'],
            ['landelijk', 'landelijk', 'accent_tip', 'Vergrijsd groen of warm bruin blijft de veilige, mooie keuze — een kleur die bij jullie allebei al vertrouwd aanvoelt.', 'Vergrijsd groen of warm bruin past bij deze basis.'],
            ['landelijk', 'modern', 'intro', 'Warm en vertrouwd tegenover strak en fris — samen zoeken jullie een huis dat er verzorgd uitziet zonder de gezelligheid te verliezen.', 'Strakke lijnen en natuurlijke materialen kunnen elkaar aanvullen.'],
            ['landelijk', 'modern', 'basis_tip', 'Neem modern\'s lichte, rustige basis (wit, greige, lichtgrijs) en voeg landelijke warmte toe via hout en textiel — dat voorkomt dat het geheel kil aanvoelt.', 'Gebruik een rustige basis in gebroken wit en greige, met zichtbaar hout.'],
            ['landelijk', 'modern', 'materials_tip', 'Combineer modern\'s strakke oppervlakken met landelijk hout en linnen — het ruwere, natuurlijke materiaal zorgt voor precies de balans die beide stijlen nodig hebben.', 'Combineer een eenvoudig meubel met linnen, wol of een houten tafel met voelbare structuur.'],
            ['landelijk', 'modern', 'accent_tip', 'Warm bruin of antraciet werkt bij allebei — hou het bij één duidelijke kleur zodat het rustig en verzorgd blijft.', 'Warm bruin of antraciet werkt als rustig accent.'],
            ['landelijk', 'scandinavisch', 'intro', 'Allebei houden jullie van een warm, natuurlijk huis — het verschil zit in de sfeer: vol en vertrouwd tegenover licht en fris.', 'Lichte kleuren en natuurlijke materialen verbinden deze stijlen. Landelijk voegt meer structuur en gezelligheid toe.'],
            ['landelijk', 'scandinavisch', 'basis_tip', 'Kies een basis die tussen de twee in zit: iets lichter dan puur landelijk, iets warmer dan puur Modern Scandinavisch — denk aan zand, gebroken wit en licht tot middel hout.', 'Kies gebroken wit, zand en licht tot middellicht hout.'],
            ['landelijk', 'scandinavisch', 'materials_tip', 'Landelijk aardewerk en natuursteen combineren goed met Modern Scandinavisch\' lichtere hout en eenvoudige keramiek — beide stijlen houden van materiaal dat je kunt voelen.', 'Combineer eenvoudige meubels met linnen, een wollen kleed of aardewerk.'],
            ['landelijk', 'scandinavisch', 'accent_tip', 'Een zachte, aardse tint zoals olijfgroen past bij allebei — natuurlijk genoeg voor landelijk, rustig genoeg voor Modern Scandinavisch.', 'Vergrijsd groen geeft een zacht, natuurlijk accent.'],
            ['modern', 'modern', 'intro', 'Allebei houden jullie van rust, ruimte en strakke lijnen — dit wordt een overzichtelijk huis waarin niets toevallig oogt.', 'Jullie keuzes wijzen allebei op een rustig interieur met heldere lijnen.'],
            ['modern', 'modern', 'basis_tip', 'Blijf bij de vertrouwde lichte basis — wit, greige, lichtgrijs — en bouw daar met z\'n tweeën bewust laag voor laag diepte in op met grijstinten en hout.', 'Gebruik wit, lichtgrijs en greige als basis.'],
            ['modern', 'modern', 'materials_tip', 'Hout, glas, metaal en keramiek blijven de kern — kies samen voor enkele grote, sterke stukken in plaats van veel kleine accessoires, dat past bij hoe modern het liefst oogt.', 'Kies een paar duidelijke meubelvormen en voeg warmte toe met één houtsoort of zachte stof.'],
            ['modern', 'modern', 'accent_tip', 'Zwart of antraciet als vaste accentkleur geeft genoeg contrast zonder de rust te verstoren — precies wat jullie allebei zoeken.', 'Zwart of antraciet geeft contrast zonder veel kleur toe te voegen.'],
            ['modern', 'scandinavisch', 'intro', 'Allebei houden jullie van rust en een strakke basis — het verschil zit vooral in de warmte: koel en strak tegenover licht en natuurlijk.', 'Deze stijlen delen eenvoudige vormen. Licht hout en zachte stoffen geven de strakke basis meer warmte.'],
            ['modern', 'scandinavisch', 'basis_tip', 'Beide stijlen beginnen met een lichte, neutrale basis — voeg Modern Scandinavisch hout en zachte stoffen toe aan modern\'s strakkere vormen zodat het geheel niet te koud wordt.', 'Gebruik wit en lichtgrijs met een lichte houttint.'],
            ['modern', 'scandinavisch', 'materials_tip', 'Gladde oppervlakken uit modern (glas, metaal) in combinatie met licht hout en wol uit Modern Scandinavisch design zorgen voor precies genoeg warmte in een verder strakke ruimte.', 'Combineer gladde oppervlakken en strakke meubels met licht hout en wol.'],
            ['modern', 'scandinavisch', 'accent_tip', 'Antraciet als gedeeld vertrekpunt geeft het beste van beide: de rust van modern, de natuurlijke warmte van Modern Scandinavisch.', 'Antraciet zorgt voor een duidelijk maar rustig contrast.'],
            ['scandinavisch', 'scandinavisch', 'intro', 'Allebei houden jullie van een licht, fris en verfijnd huis — een van de makkelijkste combinaties om samen invulling aan te geven.', 'Jullie keuzes wijzen allebei op een licht interieur met eenvoudige vormen en natuurlijke warmte.'],
            ['scandinavisch', 'scandinavisch', 'basis_tip', 'Blijf bij wit en lichte neutrale tinten als basis — met dezelfde smaak wordt de keuze vooral welke ingetogen accentkleur jullie er samen aan toevoegen.', 'Gebruik gebroken wit, warm steengrijs en licht hout.'],
            ['scandinavisch', 'scandinavisch', 'materials_tip', 'Licht hout, zachte stoffen en eenvoudige keramische accessoires blijven de kern — voeg gerust wat meer variatie in structuur toe dan je alleen zou doen, dat houdt het interessant zonder de rust te verliezen.', 'Combineer functionele meubels met wol, linnen of een andere zachte textuur.'],
            ['scandinavisch', 'scandinavisch', 'accent_tip', 'Een ingetogen accentkleur, zoals antraciet of dennengroen, past bij jullie allebei en houdt het geheel licht en verfijnd.', 'Dennengroen of antraciet kan een klein, rustig accent zijn.'],
        ];
    }

    private function updateCombinations(): void
    {
        foreach ($this->combinationFixes() as [$styleKeyA, $styleKeyB, $field, $old, $new]) {
            [$canonicalA, $canonicalB] = StyleCombinationAdvice::canonicalPair($styleKeyA, $styleKeyB);

            StyleCombinationAdvice::query()
                ->where('style_key_a', $canonicalA)
                ->where('style_key_b', $canonicalB)
                ->where($field, $old)
                ->update([$field => $new]);
        }
    }
};

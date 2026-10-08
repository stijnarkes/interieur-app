<?php

use App\Models\StyleProfile;
use Illuminate\Database\Migrations\Migration;

/**
 * Herschrijft de klanttekst van zes stijlprofielen naar een warmere, persoonlijkere toon —
 * aangeleverd als Word-document, overgenomen per veld. subtitle, core_traits, materials,
 * furniture_shapes.items en recipe zijn in deze ronde ongewijzigd gebleven (niet aangeleverd als
 * nieuwe tekst), dus die staan hier niet in. furniture_shapes is een los geval: alleen het
 * 'intro'-veld daarbinnen wijzigt, 'items' blijft gelijk, dus dat wordt losstaand bijgewerkt i.p.v.
 * via de generieke vergelijking hieronder.
 *
 * Zelfde voorzichtige patroon als 2026_09_29_180000_improve_style_profile_copy: elk veld wordt
 * alleen bijgewerkt als de huidige waarde nog exact de verwachte oude tekst is — een admin die een
 * van deze velden intussen zelf alweer herschreven heeft, wordt hier dus nooit overschreven.
 */
return new class extends Migration
{
    public function up(): void
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

            $furnitureShapes = $profile->furniture_shapes;
            $newIntro = $this->furnitureIntroFixes()[$styleKey] ?? null;
            if ($newIntro && is_array($furnitureShapes) && ($furnitureShapes['intro'] ?? null) === $newIntro[0]) {
                $furnitureShapes['intro'] = $newIntro[1];
                $changes['furniture_shapes'] = $furnitureShapes;
            }

            if ($changes !== []) {
                $profile->update($changes);
            }
        }
    }

    public function down(): void
    {
        // Bewust geen terugdraai-logica: dit is een tekstuele verbeterslag, geen structuurwijziging.
    }

    /** @return array<int, array{0: string, 1: string}> styleKey => [oude intro, nieuwe intro] */
    private function furnitureIntroFixes(): array
    {
        return [
            'hotelLuxe' => ['Kies comfortabele meubels met rustige, elegante vormen. Eén opvallend meubel is vaak genoeg om de toon te zetten.', 'Ga voor meubels waarin je heerlijk kunt ontspannen, met rustige en elegante vormen. Een bijzonder meubel mag best de aandacht trekken. Eén mooie blikvanger maakt vaak al het verschil.'],
            'japandi' => ['Kies enkele meubels die goed bij elkaar passen en laat er ruimte omheen. Comfort blijft belangrijk.', 'Bij Japandi hoef je niet veel meubels neer te zetten om het gezellig te maken. Kies een paar mooie, comfortabele meubels die bij elkaar passen en geef ze voldoende ruimte.'],
            'kleurExplosie' => ['Een meubel mag opvallen door kleur, vorm of patroon. Kies welke stukken de hoofdrol krijgen en geef ze ruimte.', 'In jouw interieur mag een meubel best opvallen! Kies bijvoorbeeld voor een bijzondere kleur, speelse vorm of mooi patroon. Bepaal welke meubels de blikvangers worden en geef ze de ruimte.'],
            'landelijk' => ['Kies meubels die prettig zitten en een natuurlijke uitstraling hebben. Een royale vorm mag, zolang de ruimte overzichtelijk blijft.', 'Kies vooral meubels waarin je lekker kunt zitten en die een natuurlijke uitstraling hebben. Een royale bank of tafel past hier prima, zolang er genoeg ruimte overblijft om van je interieur te genieten.'],
            'modern' => ['Kies meubels met heldere lijnen en weinig versiering. De vorm en afwerking bepalen hier de uitstraling.', 'Kies meubels met strakke lijnen en weinig versiering. Juist de vorm en afwerking maken het verschil. Een opvallend meubel of kunstwerk kan een mooie blikvanger zijn.'],
            'scandinavisch' => ['Kies eenvoudige meubels die prettig werken in het dagelijks leven. Lichte houttinten en zachte stoffen houden de vormen vriendelijk.', 'Ga voor eenvoudige, praktische meubels waar je elke dag plezier van hebt. Licht hout en zachte stoffen geven de strakke vormen een vriendelijke, gezellige uitstraling.'],
        ];
    }

    /** @return array<string, array<string, array{0: mixed, 1: mixed}>> stijl => veld => [oud, nieuw] */
    private function styleProfileFixes(): array
    {
        return [
            'hotelLuxe' => [
                'long_description' => ['Uit je keuzes komt Hotel luxe het sterkst naar voren. Je lijkt te houden van warme kleuren, zachte stoffen en details die een ruimte bijzonder maken. Denk aan een comfortabele basis met één of twee luxe blikvangers, zoals een mooie lamp of een meubel met een rijke stof.', 'Wat leuk, Hotel luxe past helemaal bij jouw keuzes! Jij houdt van warme kleuren, heerlijk zachte stoffen en mooie details die nét dat beetje extra geven. Met een comfortabele basis en een paar luxe blikvangers maak je van jouw interieur een sfeervolle plek.'],
                'advice_secondary' => ['Ook Hotel luxe komt in je keuzes naar voren. Met een rijke stof, sfeervolle verlichting of een verfijnd detail kun je de ruimte extra luxe geven.', 'Ook Hotel luxe past bij jouw smaak! Denk aan een rijke stof, warme verlichting of een mooi detail dat jouw interieur net dat beetje extra geeft.'],
                'traits_intro' => ['Op basis van jouw keuzes zien we vooral een voorkeur voor warme kleuren, zachte materialen en een rijke, sfeervolle uitstraling.', 'Jij houdt van warme kleuren, zachte materialen en een interieur dat luxe uitstraalt én fijn aanvoelt. Dat zie je terug in deze kenmerken:'],
                'color_tip' => ['Gebruik beige en taupe als warme basis en voeg diepere tinten toe voor extra sfeer. Champagnekleurige of donkere accenten geven het geheel een luxe uitstraling zonder dat het te zwaar wordt.', 'Begin met beige en taupe voor een warme basis. Voeg daarna wat diepere kleuren toe voor extra sfeer. Met een champagnekleurig of donker accent geef je jouw interieur een luxe uitstraling, zonder dat het te zwaar wordt.'],
                'materials_tip' => ['Een zachte stof naast hout of steen geeft de ruimte een rijke uitstraling. Gebruik glans gericht, bijvoorbeeld in een lamp of een klein metalen detail.', 'Zachte stoffen, hout en steen zijn een prachtige combinatie. Voeg hier en daar een subtiel glanzend detail toe, bijvoorbeeld in een lamp of accessoire. Zo voelt het geheel luxe, maar blijft het gezellig.'],
                'wat_past_minder_goed' => [['Veel losse accessoires en allerlei verschillende glanzende materialen kunnen de ruimte druk maken. Herhaal liever een paar materialen en kies één of twee blikvangers.'], ['Een luxe uitstraling zit vaak juist in de balans. Te veel kleine accessoires of glanzende materialen kunnen wat druk worden. Kies liever een paar materialen die je laat terugkomen en geef één of twee mooie blikvangers de ruimte.']],
            ],
            'japandi' => [
                'long_description' => ['Uit je keuzes komt Japandi het sterkst naar voren. Rustige kleuren, natuurlijk hout en eenvoudige vormen spreken je aan. Met linnen, keramiek en een paar goed gekozen meubels blijft de ruimte rustig én warm.', 'Jouw keuzes passen helemaal bij Japandi! Jij wordt blij van rust, natuurlijke materialen en zachte kleuren. Met warm hout, linnen en een paar zorgvuldig gekozen meubels maak je een interieur dat rustig oogt, maar toch heerlijk warm aanvoelt.'],
                'advice_secondary' => ['Ook Japandi komt in je keuzes naar voren. Natuurlijke materialen en eenvoudige vormen kunnen voor meer rust en warmte zorgen.', 'Ook Japandi zien we terug in jouw keuzes! Met natuurlijke materialen en eenvoudige vormen breng je op een mooie manier extra rust en warmte in huis.'],
                'traits_intro' => ['Op basis van jouw keuzes zien we vooral een voorkeur voor rust, natuurlijke materialen en warme tinten.', 'Jij houdt van rust, warme tinten en materialen uit de natuur. Dit zijn de kenmerken die goed bij jouw woonstijl passen:'],
                'color_tip' => ['Gebruik de lichte tinten als rustige basis en voeg hout, taupe en een zachte accentkleur toe voor warmte en contrast.', 'Kies lichte, zachte kleuren als basis. Combineer ze met warm hout en taupe en voeg eventueel een rustige accentkleur toe. Zo krijgt je interieur nét wat meer warmte en diepte.'],
                'materials_tip' => ['Hout, linnen en matte keramiek geven de ruimte structuur zonder veel versiering. Laat de materialen zichtbaar zijn en houd de vormen eenvoudig.', 'Hout, linnen en mat keramiek passen prachtig bij elkaar. Laat de natuurlijke materialen voor zich spreken en houd de vormen eenvoudig. Zo creëer je rust zonder dat het kil wordt.'],
                'wat_past_minder_goed' => [['Felle kleuren, hoogglans en veel kleine accessoires halen de aandacht weg van de rustige basis. Kies liever voor enkele grotere, natuurlijke objecten.'], ['Wil je die rustige Japandi-sfeer behouden? Gebruik dan liever niet te veel felle kleuren, glanzende oppervlakken of kleine accessoires. Met een paar grotere items van natuurlijke materialen creëer je juist een mooi geheel.']],
            ],
            'kleurExplosie' => [
                'long_description' => ['Uit je keuzes komt Kleur explosie het sterkst naar voren. Je lijkt ruimte te willen geven aan uitgesproken kleuren en bijzondere vormen. Kies een paar kleuren die je echt mooi vindt en laat die terugkomen in meubels, kunst of accessoires. Zo krijgt de ruimte een duidelijk geheel.', 'Wat een vrolijke woonstijl past bij jou: Kleur explosie! Jij durft kleur en bijzondere vormen een plek te geven in huis. Kies een paar favoriete kleuren en laat die terugkomen in meubels, kunst en accessoires. Zo ontstaat een speels interieur dat helemaal als jij voelt.'],
                'advice_secondary' => ['Ook Kleur explosie komt in je keuzes naar voren. Een uitgesproken kleur of opvallend object kan de ruimte een speels accent geven.', 'Ook Kleur explosie past bij jouw keuzes! Met een opvallende kleur of een bijzonder item geef je jouw interieur in één keer een speelse twist.'],
                'traits_intro' => ['Op basis van jouw keuzes zien we vooral een voorkeur voor uitgesproken kleuren, speelse combinaties en meubels met karakter.', 'Jij houdt van kleur, verrassende combinaties en meubels met karakter. Dit zijn de kenmerken die jouw woonstijl zo levendig maken:'],
                'color_tip' => ['Kies één of twee kleuren als duidelijke hoofdtoon en laat andere kleuren terugkomen in kleinere accenten. Zo blijft je interieur levendig, maar ontstaat er toch samenhang.', 'Kies één of twee kleuren die de hoofdrol krijgen en gebruik andere tinten als kleinere accenten. Laat je favoriete kleuren op verschillende plekken terugkomen. Zo blijft het vrolijk én vormt alles samen een mooi geheel.'],
                'materials_tip' => ['Combineer kleur en structuur bewust. Laat bijvoorbeeld een zachte stof terugkomen naast gekleurd glas of keramiek en herhaal een paar kleuren in de ruimte.', 'Combineer gerust verschillende materialen en structuren! Denk aan zachte stoffen, gekleurd glas en keramiek. Door een paar kleuren steeds terug te laten komen, zorg je voor samenhang tussen al die leuke details.'],
                'wat_past_minder_goed' => [['Als elke kleur en elk meubel evenveel aandacht vraagt, kan de ruimte rommelig voelen. Laat twee of drie kleuren terugkomen en zorg voor rustige vlakken ertussen.'], ['Kleur mag de hoofdrol spelen, maar niet alles hoeft om aandacht te vragen. Laat twee of drie favoriete kleuren terugkomen en wissel af met wat rustige vlakken. Zo blijft je interieur speels zonder rommelig te worden.']],
            ],
            'landelijk' => [
                'long_description' => ['Uit je keuzes komt Landelijk het sterkst naar voren. Je lijkt je prettig te voelen bij natuurlijke materialen, warme tinten en meubels waarin je graag neerploft. Hout, linnen en zachte stoffen geven de ruimte een ontspannen en gastvrije sfeer.', 'Landelijk past prachtig bij jouw keuzes! Jij houdt van een warm en gezellig thuis, met natuurlijke materialen en meubels waar je heerlijk in kunt neerploffen. Denk aan hout, linnen en zachte stoffen die samen zorgen voor een ontspannen, huiselijke sfeer.'],
                'advice_secondary' => ['Ook Landelijk komt in je keuzes naar voren. Natuurlijk hout en comfortabele stoffen kunnen de ruimte warm en huiselijk maken.', 'Ook Landelijk zien we terug in jouw smaak! Met natuurlijk hout en comfortabele stoffen geef je jouw interieur een extra warm en huiselijk gevoel.'],
                'traits_intro' => ['Op basis van jouw keuzes zien we vooral een voorkeur voor warmte, natuurlijke materialen en een comfortabele, huiselijke sfeer.', 'Jij voelt je thuis bij warme kleuren, natuurlijke materialen en meubels die vooral ook lekker comfortabel zijn. Dit zijn de kenmerken die bij jou passen:'],
                'color_tip' => ['Werk met warme, rustige basiskleuren en voeg bruin- en groentinten toe voor een natuurlijke sfeer. Door kleuren ton-sur-ton te combineren ontstaat een zachte en gezellige uitstraling.', 'Begin met zachte, warme basiskleuren en voeg bruin- en groentinten toe. Door verschillende tinten van dezelfde kleur te combineren, ontstaat er een rustig, gezellig geheel.'],
                'materials_tip' => ['Hout met zichtbare structuur en zachte stoffen geven de ruimte warmte. Een klein verschil in kleur en textuur mag je juist zien.', 'Hout met een mooie zichtbare nerf en zachte stoffen brengen volop warmte in huis. Kleine verschillen in kleur en structuur zijn juist mooi: die geven jouw interieur karakter.'],
                'wat_past_minder_goed' => [['Heel strakke, glanzende meubels en harde zwart-witcontrasten kunnen de warme sfeer verzwakken. Voeg liever natuurlijke texturen en zachte overgangen toe.'], ['Wil je die warme, landelijke sfeer vasthouden? Gebruik dan niet te veel glanzende, strakke meubels of harde zwart-witcontrasten. Zachte kleuren en natuurlijke materialen laten alles juist mooi in elkaar overlopen.']],
            ],
            'modern' => [
                'long_description' => ['Uit je keuzes komt Modern het sterkst naar voren. Je kiest waarschijnlijk graag voor heldere lijnen, rustige vlakken en meubels zonder veel versiering. Een licht palet met duidelijke contrasten houdt het interieur overzichtelijk. Een paar warme materialen maken het persoonlijk.', 'Modern sluit mooi aan bij jouw keuzes! Jij houdt van strakke lijnen, rustige kleuren en meubels zonder al te veel poespas. Met een lichte basis, een paar duidelijke contrasten en wat warme materialen creëer je een fris interieur dat toch persoonlijk aanvoelt.'],
                'advice_secondary' => ['Ook Modern komt in je keuzes naar voren. Strakke lijnen en rustige vlakken kunnen voor een heldere, verzorgde uitstraling zorgen.', 'Ook Modern past bij jouw smaak! Met strakke lijnen en rustige kleuren geef je jouw interieur een frisse, verzorgde uitstraling.'],
                'traits_intro' => ['Op basis van jouw keuzes zien we vooral een voorkeur voor eenvoud, duidelijke lijnen en een rustige, eigentijdse uitstraling.', 'Jij houdt van eenvoud, duidelijke lijnen en een eigentijdse uitstraling. Deze kenmerken sluiten goed aan bij jouw woonstijl:'],
                'color_tip' => ['Gebruik lichte neutrale tinten als basis en creëer diepte met grijs, antraciet of zwart. Een warmere houttint kan voorkomen dat je interieur te koel aanvoelt.', 'Begin met lichte, neutrale kleuren en voeg wat grijs, antraciet of zwart toe voor contrast. Met een warme houttint maak je het geheel nét wat gezelliger.'],
                'materials_tip' => ['Gladde oppervlakken en strakke vormen horen bij Modern. Voeg één warme houtsoort of een zachte stof toe, zodat de ruimte prettig blijft om in te wonen.', 'Glas, metaal en steen passen mooi bij de strakke uitstraling van Modern. Voeg een warme houtsoort of een zachte stof toe voor een fijne balans. Zo blijft je interieur strak én prettig om in te wonen.'],
                'wat_past_minder_goed' => [['Veel verschillende patronen, decoratiestijlen en kleine accessoires maken de heldere lijnen minder zichtbaar. Kies liever een paar duidelijke accenten.'], ['Bij Modern komt een rustige basis het mooist tot zijn recht. Te veel patronen, kleine accessoires of verschillende decoratiestijlen kunnen afleiden. Kies daarom voor een paar duidelijke accenten die echt iets toevoegen.']],
            ],
            'scandinavisch' => [
                'long_description' => ['Uit je keuzes komt Modern Scandinavisch het sterkst naar voren. Je lijkt te vallen voor lichte ruimtes, strakke maar vriendelijke meubels en de warmte van licht hout. Matte afwerkingen en zachte stoffen geven de ruimte comfort, terwijl de basis fris en opgeruimd blijft.', 'Modern Scandinavisch past helemaal bij jouw keuzes! Jij houdt van lichte ruimtes, eenvoudige meubels en de warme uitstraling van licht hout. Met zachte stoffen en matte materialen maak je een interieur dat fris en opgeruimd oogt, maar vooral ook gezellig aanvoelt.'],
                'advice_secondary' => ['Ook Modern Scandinavisch komt in je keuzes naar voren. Licht hout, zachte stoffen en eenvoudige meubels kunnen het geheel fris en warm maken.', 'Ook Modern Scandinavisch past bij jouw smaak! Met licht hout, zachte stoffen en eenvoudige meubels geef je jouw interieur een frisse én warme uitstraling.'],
                'traits_intro' => ['Op basis van jouw keuzes zien we vooral een voorkeur voor een lichte basis, strakke vormen en een rustige, ingetogen sfeer.', 'Jij houdt van lichte kleuren, eenvoudige vormen en een rustige, warme sfeer. Dit zijn de kenmerken die mooi aansluiten bij jouw woonstijl:'],
                'color_tip' => ['Gebruik lichte, neutrale tinten als basis en voeg met mate een ingetogen accent toe, zoals antraciet, dennengroen of roestbruin. Zo blijft de sfeer rustig en verfijnd, zonder saai te worden.', 'Kies lichte, neutrale kleuren als basis en voeg hier en daar een rustig accent toe, zoals antraciet, dennengroen of roestbruin. Zo blijft jouw interieur fris en in balans, met nét wat extra karakter.'],
                'materials_tip' => ['Licht hout, matte oppervlakken en zachte stoffen zorgen samen voor een rustige, warme ruimte. Herhaal die materialen in meubels en accessoires.', 'Licht hout, matte materialen en zachte stoffen zijn een fijne combinatie. Laat ze op verschillende plekken terugkomen, van je meubels tot je accessoires. Zo vormt alles samen een rustig en warm geheel.'],
                'wat_past_minder_goed' => [['Een volledig grijs palet, veel zwart metaal of glanzende oppervlakken kan het natuurlijke karakter minder zichtbaar maken. Gebruik licht hout en zachte stoffen als vaste basis.'], ['Wil je het lichte en natuurlijke gevoel behouden? Gebruik dan liever niet te veel grijs, zwart metaal of glanzende materialen. Met licht hout en zachte stoffen blijft jouw interieur fris én warm.']],
            ],
        ];
    }
};

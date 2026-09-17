<?php

use App\Models\StyleCombinationAdvice;
use Illuminate\Database\Migrations\Migration;

/**
 * Zet de 21 redactionele stijlcombinatie-adviezen (zie StyleCombinationAdviceSeeder, waar deze
 * inhoud oorspronkelijk vandaan komt) ook daadwerkelijk op productie neer. Draait als migratie
 * i.p.v. alleen als los `db:seed`-commando — precies om de fout te voorkomen die hier is gebeurd:
 * de tekst stond alleen in de lokale ontwikkeldatabase, dus elke combinatie op productie viel
 * terug op de generieke "volgt nog"-tekst (zie PartnerComparisonService::suggestionsFor()). Zelfde
 * aanpak als 2026_09_16_141703_replace_accent_color_catalog: updateOrCreate zodat een latere
 * tekstcorrectie hier ook op een al gevulde omgeving landt, zonder een handmatige redactionele
 * wijziging via de "Stijlcombinaties"-beheerpagina te overschrijven — zie hieronder.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->advices() as [$styleKeyA, $styleKeyB, $data]) {
            [$canonicalA, $canonicalB] = StyleCombinationAdvice::canonicalPair($styleKeyA, $styleKeyB);

            $existing = StyleCombinationAdvice::query()
                ->where('style_key_a', $canonicalA)
                ->where('style_key_b', $canonicalB)
                ->first();

            // Nooit overschrijven als een admin deze combinatie intussen al zelf bewerkt heeft
            // (versie > 1, zie StyleCombinationAdvicesPage::editAdviceAction()) — deze migratie mag
            // alleen de oorspronkelijke, nog ongewijzigde tekst neerzetten/herstellen.
            if ($existing && $existing->version > 1) {
                continue;
            }

            StyleCombinationAdvice::query()->updateOrCreate(
                ['style_key_a' => $canonicalA, 'style_key_b' => $canonicalB],
                [...$data, 'status' => 'published'],
            );
        }
    }

    public function down(): void
    {
        // Bewust geen terugdraai-logica: zelfde redenering als replace_accent_color_catalog —
        // dit is redactionele inhoud, geen schema, en hoeft niet ongedaan gemaakt te kunnen worden.
    }

    /** @return array<int, array{0: string, 1: string, 2: array<string, mixed>}> */
    private function advices(): array
    {
        return [
            ['hotelLuxe', 'hotelLuxe', [
                'title' => 'Hotel luxe & Hotel luxe',
                'intro' => "Jullie zijn het roerend eens: het mag allebei net wat rijker en verfijnder. Dat maakt inrichten opvallend eenvoudig — de basis staat al vast, het wordt vooral samen genieten van de details.",
                'basis_tip' => 'Blijf bij een warme, ietwat donkere basis van beige, taupe en donker hout. Met zoveel gedeelde smaak hoeven jullie geen compromis te zoeken — alleen samen de juiste tint te kiezen.',
                'materials_tip' => "Ga voor de volle mix die bij deze stijl hoort: fluweel, marmer, hout en messing. Omdat jullie hier allebei van houden, mag het gerust wat rijker en gelaagder dan bij één persoon alleen.",
                'accent_tip' => "Kies met z'n tweeën één uitgesproken accentkleur — diep groen of champagne werkt bij jullie allebei — en gebruik die dubbel zo veel als je normaal zou doen.",
                'base_palette_style_key' => null,
                'version' => 1,
            ]],
            ['hotelLuxe', 'japandi', [
                'title' => 'Hotel luxe & Japandi',
                'intro' => "De één houdt van weelde en glans, de ander van rust en eenvoud — samen vinden jullie elkaar in warmte: allebei kiezen jullie voor een interieur dat zacht en uitnodigend aanvoelt, alleen met een ander volume.",
                'basis_tip' => "Kies een gedeelde basis van warm hout en zandtinten — dat past bij Japandi's rust én bij hotel luxe's warmte — en laat de luxe vooral terugkomen in details en accessoires, niet in de hele ruimte.",
                'materials_tip' => "Combineer Japandi's natuurlijke hout en linnen met één of twee rijkere materialen uit hotel luxe, zoals fluweel of marmer, als bewust contrast in plaats van door de hele ruimte.",
                'accent_tip' => "Eén gedeelde, diepe kleur — zoals donkergroen — werkt in beide werelden: rustig genoeg voor Japandi, rijk genoeg voor hotel luxe.",
                'base_palette_style_key' => 'japandi',
                'version' => 1,
            ]],
            ['hotelLuxe', 'kleurExplosie', [
                'title' => 'Hotel luxe & Kleur explosie',
                'intro' => "Verfijnde luxe en uitbundige kleur lijken elkaars tegenpolen, maar delen één ding: allebei houden jullie van een interieur met karakter en durf, niet van iets behoudends.",
                'basis_tip' => 'Laat hotel luxe de rustige basis leveren — warm beige en donker hout — zodat er een stevig fundament ligt waarop vervolgens vrij gespeeld kan worden met kleurrijke accenten.',
                'materials_tip' => 'Fluweel en marmer uit hotel luxe combineren verrassend goed met glanzend keramiek en gelakt hout uit kleur explosie — beide stijlen houden namelijk van een beetje glans en statement.',
                'accent_tip' => 'Eén uitgesproken kleur, zoals smaragdgroen of robijnrood, in een rijk materiaal (fluweel, glanzend keramiek) geeft precies de mix van luxe én lef die jullie allebei zoeken.',
                'base_palette_style_key' => 'hotelLuxe',
                'version' => 1,
            ]],
            ['hotelLuxe', 'landelijk', [
                'title' => 'Hotel luxe & Landelijk',
                'intro' => 'Allebei houden jullie van warmte en gezelligheid — het verschil zit vooral in de verfijning: strak en chic tegenover robuust en vertrouwd.',
                'basis_tip' => 'Warm hout en zachte, gebroken wittinten vormen bij beide stijlen de basis — kies samen voor een iets verfijndere houtsoort, zodat het landelijke karakter blijft maar met net wat meer chique uitstraling.',
                'materials_tip' => 'Combineer landelijk hout en linnen met fluweel of een subtiele glanzende afwerking — dat brengt de luxe erin zonder dat het huis zijn vertrouwde, warme karakter verliest.',
                'accent_tip' => 'Warm groen of diepbruin werkt bij allebei: aards genoeg voor landelijk, rijk genoeg voor hotel luxe.',
                'base_palette_style_key' => 'landelijk',
                'version' => 1,
            ]],
            ['hotelLuxe', 'modern', [
                'title' => 'Hotel luxe & Modern',
                'intro' => 'Strak en rustig versus rijk en sfeervol: samen vinden jullie een interieur dat er verzorgd en volwassen uitziet, met precies genoeg glans om niet kil te worden.',
                'basis_tip' => "Ga uit van modern's lichte, neutrale basis (wit, greige, lichtgrijs) en voeg de warmte van hotel luxe toe via hout en textiel — zo blijft het rustig, maar niet steriel.",
                'materials_tip' => 'Laat de strakke, gladde oppervlakken van modern (glas, metaal) in gesprek gaan met fluweel of marmer uit hotel luxe — precies dat contrast maakt een interieur interessant in plaats van kil of te druk.',
                'accent_tip' => 'Antraciet of zwart, gecombineerd met één warmere tint zoals champagne of chocoladebruin, geeft de rust van modern en de warmte van hotel luxe tegelijk.',
                'base_palette_style_key' => 'modern',
                'version' => 1,
            ]],
            ['hotelLuxe', 'scandinavisch', [
                'title' => 'Hotel luxe & Scandinavisch',
                'intro' => 'Fris en licht tegenover rijk en sfeervol — samen zoeken jullie de balans tussen een luchtig huis en een warme, verwennende sfeer.',
                'basis_tip' => 'Houd de basis licht, zoals Scandinavisch dat wil, met wit en lichte houttinten — en voeg de luxe toe via één donkerder, rijker accentmeubel of -muur in plaats van de hele ruimte te verzwaren.',
                'materials_tip' => 'Licht hout en zachte stoffen uit Scandinavisch design combineren goed met één luxueus materiaal, zoals fluweel of marmer, als bewust hoogtepunt in een verder rustige ruimte.',
                'accent_tip' => 'Een zachte, warme tint zoals oudroze of taupe voelt bij allebei op zijn plek — licht genoeg voor Scandinavisch, verfijnd genoeg voor hotel luxe.',
                'base_palette_style_key' => 'scandinavisch',
                'version' => 1,
            ]],
            ['japandi', 'japandi', [
                'title' => 'Japandi & Japandi',
                'intro' => 'Jullie zoeken allebei dezelfde rust — dit wordt een van de makkelijkste combinaties om samen vorm te geven.',
                'basis_tip' => "Blijf bij de vertrouwde, lichte basis van warm wit, zand en naturel hout — dezelfde smaak betekent vooral samen zoeken naar de juiste natuurlijke materialen, niet naar compromissen.",
                'materials_tip' => 'Eiken, linnen, wol en keramiek blijven de kern — kies er samen een paar bewuste, iets grotere stukken van in plaats van veel kleine spullen, dat past bij hoe rustig Japandi wil blijven.',
                'accent_tip' => 'Eén zachte kleur, zoals taupe of olijfgroen, is meer dan genoeg — zo bewaren jullie de rust die jullie allebei zoeken.',
                'base_palette_style_key' => null,
                'version' => 1,
            ]],
            ['japandi', 'kleurExplosie', [
                'title' => 'Japandi & Kleur explosie',
                'intro' => 'Ingetogen rust tegenover uitbundige kleur — dit is misschien wel het grootste contrast, maar juist dat maakt het een leuke uitdaging: rust als basis, kleur als hoogtepunt.',
                'basis_tip' => 'Laat Japandi de basis bepalen — warm wit, zand en hout — zodat er een kalme achtergrond ontstaat waartegen kleurrijke accenten juist extra goed tot hun recht komen.',
                'materials_tip' => 'Hout en linnen blijven de ondertoon; voeg daar bewust één of twee kleurrijke materialen aan toe, zoals gelakt hout of keramiek, als duidelijke blikvangers in plaats van door de hele ruimte.',
                'accent_tip' => "Kies met z'n tweeën één uitgesproken kleur (zoals kobaltblauw) en gebruik die spaarzaam maar zichtbaar — genoeg kleur om blij van te worden, genoeg rust om Japandi recht te doen.",
                'base_palette_style_key' => 'japandi',
                'version' => 1,
            ]],
            ['japandi', 'landelijk', [
                'title' => 'Japandi & Landelijk',
                'intro' => 'Twee stijlen die allebei natuurlijke materialen en warmte vooropstellen — het verschil zit vooral in de vorm: strak en minimalistisch tegenover vol en vertrouwd.',
                'basis_tip' => "Warm hout en zachte, aardse tinten vormen bij beide de basis — kies samen voor rustigere vormen, gevuld met de knusheid van landelijke stoffen en accessoires.",
                'materials_tip' => "Eiken en linnen zijn bij beide stijlen thuis — combineer landelijk aardewerk met Japandi's strakkere keramiek voor een mix die zowel warm als rustig aanvoelt.",
                'accent_tip' => 'Olijfgroen of warm bruin werkt in beide werelden — hou het bij één kleur zodat het rustig blijft.',
                'base_palette_style_key' => 'japandi',
                'version' => 1,
            ]],
            ['japandi', 'modern', [
                'title' => 'Japandi & Modern',
                'intro' => 'Allebei houden jullie van rust en eenvoud — het verschil is de temperatuur: warm en zacht tegenover strak en koel.',
                'basis_tip' => "Neem modern's lichte, neutrale basis en voeg Japandi's warme houttinten toe — dat voorkomt dat het geheel te kil aanvoelt, zonder de rust van modern te verliezen.",
                'materials_tip' => "Combineer strakke, gladde oppervlakken uit modern met natuurlijk hout en linnen uit Japandi — precies die balans tussen strak en zacht maakt deze twee stijlen een sterk koppel.",
                'accent_tip' => "Antraciet of zwart als accent past bij modern; hou de hoeveelheid beperkt zodat Japandi's rust intact blijft.",
                'base_palette_style_key' => 'japandi',
                'version' => 1,
            ]],
            ['japandi', 'scandinavisch', [
                'title' => 'Japandi & Scandinavisch',
                'intro' => 'Allebei licht, rustig en natuurlijk — jullie zitten dichter bij elkaar dan de meeste combinaties, met net een ander accent: warmer en aards tegenover fris en luchtig.',
                'basis_tip' => 'Licht hout en zachte wittinten werken voor jullie allebei — kies samen of het geheel iets warmer (Japandi) of iets frisser (Scandinavisch) mag aanvoelen, en laat die keuze de basis kleuren.',
                'materials_tip' => "Eiken, wol en linnen zijn bij beide stijlen vertrouwd terrein — een mix van Japandi's iets rustiekere keramiek en Scandinavisch' speelsere accessoires geeft genoeg afwisseling.",
                'accent_tip' => 'Een zachte pasteltint of taupe past bij beide — licht genoeg voor Scandinavisch, warm genoeg voor Japandi.',
                'base_palette_style_key' => null,
                'version' => 1,
            ]],
            ['kleurExplosie', 'kleurExplosie', [
                'title' => 'Kleur explosie & Kleur explosie',
                'intro' => "Twee mensen die van kleur, karakter en lef houden — dit wordt een huis waarin nooit iets saais zal staan.",
                'basis_tip' => 'Eén rustige basiskleur blijft nodig als rustpunt, ook als jullie er allebei van houden om uit te pakken — anders vecht straks alles om aandacht in plaats van dat het samen klopt.',
                'materials_tip' => 'Mix gerust materialen met verschillende structuren en glans — velours, glas, keramiek en gelakt hout — dat past bij hoe jullie allebei naar een interieur kijken: gedurfd en persoonlijk.',
                'accent_tip' => "Spreek samen af welke twee kleuren de hoofdrol krijgen (bijvoorbeeld kobaltblauw én roze) — met twee liefhebbers van kleur is dat belangrijker dan ooit, anders wordt het al snel te veel.",
                'base_palette_style_key' => null,
                'version' => 1,
            ]],
            ['kleurExplosie', 'landelijk', [
                'title' => 'Kleur explosie & Landelijk',
                'intro' => 'Warm en vertrouwd tegenover fel en uitgesproken — samen zoeken jullie een huis dat gezellig blijft, maar nooit saai wordt.',
                'basis_tip' => "Laat landelijk de warme, natuurlijke basis leveren — gebroken wit, zand en hout — zodat felle accenten daar juist tegen kunnen opvallen in plaats van overweldigen.",
                'materials_tip' => 'Combineer landelijk hout en natuursteen met kleurrijk keramiek of een gelakt meubelstuk — de robuustheid van landelijk vangt de felheid mooi op.',
                'accent_tip' => 'Kies een kleur die ook in de natuur voorkomt, zoals dieprood of okergeel — die brengt karakter zonder los te komen te staan van de landelijke basis.',
                'base_palette_style_key' => 'landelijk',
                'version' => 1,
            ]],
            ['kleurExplosie', 'modern', [
                'title' => 'Kleur explosie & Modern',
                'intro' => 'Strak en rustig tegenover fel en speels — dit koppel vindt elkaar in een interieur waar kleur juist opvalt dóórdat de rest zo rustig is.',
                'basis_tip' => 'Houd de basis strak en neutraal, zoals modern dat wil — wit, greige, lichtgrijs — zodat er letterlijk ruimte overblijft voor felle kleuraccenten.',
                'materials_tip' => 'Gladde, strakke materialen als glas en metaal vormen een mooi decor voor glanzend keramiek of gelakte meubels — het contrast maakt beide stijlen sterker.',
                'accent_tip' => "Eén heldere kleur, zoals kobaltblauw of felroze, op een verder rustige achtergrond geeft precies de impact die kleur explosie zoekt, zonder modern's rust te verstoren.",
                'base_palette_style_key' => 'modern',
                'version' => 1,
            ]],
            ['kleurExplosie', 'scandinavisch', [
                'title' => 'Kleur explosie & Scandinavisch',
                'intro' => 'Licht en ingetogen tegenover fel en uitbundig — samen ontstaat een huis dat fris aanvoelt, met net dat vrolijke tikkeltje extra.',
                'basis_tip' => "Scandinavisch' lichte, neutrale basis (wit en licht hout) geeft precies de rust die nodig is om felle accenten te laten stralen zonder dat het te druk wordt.",
                'materials_tip' => 'Licht hout en eenvoudige keramiek uit Scandinavisch design combineren verrassend goed met één of twee gelakte of glanzende kleuraccenten.',
                'accent_tip' => 'Eén vrolijke kleur, zoals felroze of geel, mag hier gerust de show stelen — zolang de basis licht en rustig blijft, verdraagt Scandinavisch dat goed.',
                'base_palette_style_key' => 'scandinavisch',
                'version' => 1,
            ]],
            ['landelijk', 'landelijk', [
                'title' => 'Landelijk & Landelijk',
                'intro' => 'Allebei houden jullie van warmte, gezelligheid en een huis waar je graag samenkomt — deze combinatie spreekt eigenlijk voor zich.',
                'basis_tip' => 'Blijf bij de vertrouwde basis van gebroken wit, zand en warm hout — met dezelfde smaak wordt kiezen vooral een kwestie van samen de mooiste houtsoort en stoffen uitzoeken.',
                'materials_tip' => 'Hout, linnen, wol en natuursteen vormen de kern — voeg gerust wat meer natuurlijke accessoires toe dan je alleen zou doen, dat past bij hoe knus jullie het samen willen hebben.',
                'accent_tip' => 'Vergrijsd groen of warm bruin blijft de veilige, mooie keuze — een kleur die bij jullie allebei al vertrouwd aanvoelt.',
                'base_palette_style_key' => null,
                'version' => 1,
            ]],
            ['landelijk', 'modern', [
                'title' => 'Landelijk & Modern',
                'intro' => 'Warm en vertrouwd tegenover strak en fris — samen zoeken jullie een huis dat er verzorgd uitziet zonder de gezelligheid te verliezen.',
                'basis_tip' => "Neem modern's lichte, rustige basis (wit, greige, lichtgrijs) en voeg landelijke warmte toe via hout en textiel — dat voorkomt dat het geheel kil aanvoelt.",
                'materials_tip' => "Combineer modern's strakke oppervlakken met landelijk hout en linnen — het ruwere, natuurlijke materiaal zorgt voor precies de balans die beide stijlen nodig hebben.",
                'accent_tip' => 'Warm bruin of antraciet werkt bij allebei — hou het bij één duidelijke kleur zodat het rustig en verzorgd blijft.',
                'base_palette_style_key' => 'modern',
                'version' => 1,
            ]],
            ['landelijk', 'scandinavisch', [
                'title' => 'Landelijk & Scandinavisch',
                'intro' => 'Allebei houden jullie van een warm, natuurlijk huis — het verschil zit in de sfeer: vol en vertrouwd tegenover licht en fris.',
                'basis_tip' => 'Kies een basis die tussen de twee in zit: iets lichter dan puur landelijk, iets warmer dan puur Scandinavisch — denk aan zand, gebroken wit en licht tot middel hout.',
                'materials_tip' => "Landelijk aardewerk en natuursteen combineren goed met Scandinavisch' lichtere hout en eenvoudige keramiek — beide stijlen houden van materiaal dat je kunt voelen.",
                'accent_tip' => 'Een zachte, aardse tint zoals olijfgroen past bij allebei — natuurlijk genoeg voor landelijk, rustig genoeg voor Scandinavisch.',
                'base_palette_style_key' => null,
                'version' => 1,
            ]],
            ['modern', 'modern', [
                'title' => 'Modern & Modern',
                'intro' => 'Allebei houden jullie van rust, ruimte en strakke lijnen — dit wordt een overzichtelijk huis waarin niets toevallig oogt.',
                'basis_tip' => "Blijf bij de vertrouwde lichte basis — wit, greige, lichtgrijs — en bouw daar met z'n tweeën bewust laag voor laag diepte in op met grijstinten en hout.",
                'materials_tip' => 'Hout, glas, metaal en keramiek blijven de kern — kies samen voor enkele grote, sterke stukken in plaats van veel kleine accessoires, dat past bij hoe modern het liefst oogt.',
                'accent_tip' => 'Zwart of antraciet als vaste accentkleur geeft genoeg contrast zonder de rust te verstoren — precies wat jullie allebei zoeken.',
                'base_palette_style_key' => null,
                'version' => 1,
            ]],
            ['modern', 'scandinavisch', [
                'title' => 'Modern & Scandinavisch',
                'intro' => 'Strak en koel tegenover licht en gezellig — samen vinden jullie een huis dat er verzorgd uitziet, maar wel warm blijft aanvoelen.',
                'basis_tip' => "Beide stijlen beginnen met een lichte, neutrale basis — voeg Scandinavisch hout en zachte stoffen toe aan modern's strakkere vormen zodat het geheel niet te koud wordt.",
                'materials_tip' => 'Gladde oppervlakken uit modern (glas, metaal) in combinatie met licht hout en wol uit Scandinavisch design zorgen voor precies genoeg warmte in een verder strakke ruimte.',
                'accent_tip' => 'Een zachte pasteltint naast antraciet geeft het beste van beide: de rust van modern, de gezelligheid van Scandinavisch.',
                'base_palette_style_key' => null,
                'version' => 1,
            ]],
            ['scandinavisch', 'scandinavisch', [
                'title' => 'Scandinavisch & Scandinavisch',
                'intro' => 'Allebei houden jullie van een licht, fris en gezellig huis — een van de makkelijkste combinaties om samen invulling aan te geven.',
                'basis_tip' => 'Blijf bij wit en lichte neutrale tinten als basis — met dezelfde smaak wordt de keuze vooral welke zachte pasteltint jullie er samen aan toevoegen.',
                'materials_tip' => 'Licht hout, zachte stoffen en eenvoudige keramische accessoires blijven de kern — voeg gerust wat meer variatie in structuur toe dan je alleen zou doen, dat houdt het interessant zonder de frisheid te verliezen.',
                'accent_tip' => 'Een zachte pasteltint, zoals lichtroze of mintgroen, past bij jullie allebei en houdt het geheel licht en gezellig.',
                'base_palette_style_key' => null,
                'version' => 1,
            ]],
        ];
    }
};

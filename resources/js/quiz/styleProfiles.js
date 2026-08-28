/**
 * Centrale, uitbreidbare configuratie per woonstijl. Dit is de enige plek waar Japandi/
 * Hotel Chique/etc.-specifieke content staat — de resultatenpagina en de quizvragen
 * (via data.js) lezen dit generiek uit, er staat nergens stijl-specifieke tekst hardcoded
 * verspreid door componenten.
 *
 * Een nieuwe woonstijl toevoegen = één nieuw object aan STYLE_PROFILES toevoegen; de
 * quizvragen (buildOptions in data.js) en de resultatenpagina passen zich daar automatisch
 * op aan.
 *
 * @typedef {object} StyleColor
 * @property {string} name
 * @property {string} hex
 *
 * @typedef {object} StyleMaterial
 * @property {string} name
 * @property {string} image  pad naar een textuur/sfeerfoto; valt terug op placeholder zolang die niet bestaat
 *
 * @typedef {object} RecipeItem
 * @property {string} label
 * @property {string} value
 *
 * @typedef {object} StyleProfile
 * @property {string} key            interne sleutel, zie InteriorStyle in data.js
 * @property {string} slug           kebab-case, gebruikt in afbeeldingspaden
 * @property {string} label          weergavenaam, bv. "Japandi"
 * @property {string} subtitle       korte persoonlijke tagline voor de hero
 * @property {string} longDescription persoonlijke introductiezin voor de hero
 * @property {string} traitsIntro    korte intro boven de kenmerken-sectie
 * @property {string[]} traits       ca. 5 herkenbare kenmerken
 * @property {string} heroImage      grote sfeerafbeelding voor de hero
 * @property {StyleColor[]} colors   ca. 5 kleuren
 * @property {string} colorTip
 * @property {StyleMaterial[]} materials ca. 4 materialen
 * @property {string} materialsTip
 * @property {{ intro: string, items: string[] }} furnitureAdvice
 * @property {RecipeItem[]} recipe   precies 5 onderdelen: Basis/Grote meubels/Accentkleur/Materialen/Accessoires
 * @property {string} avoid          adviserend geformuleerd, nooit "fout"
 * @property {string[]} productTags  voor toekomstige productmatching (bv. Shopify-tags)
 */

/** @type {StyleProfile[]} */
const STYLE_PROFILES = [
  {
    key: "japandi",
    slug: "japandi",
    label: "Japandi",
    subtitle: "Rust, warmte en natuurlijke eenvoud",
    longDescription: "Rust, warmte en natuurlijke materialen passen helemaal bij jou. Je kiest voor een interieur dat eenvoudig en verfijnd aanvoelt, maar tegelijkertijd warm en comfortabel blijft.",
    traitsIntro: "Op basis van jouw keuzes zien we vooral een voorkeur voor rust, natuurlijke materialen en warme tinten.",
    traits: ["Warme, rustige kleuren", "Natuurlijke materialen", "Zachte vormen", "Een rustige uitstraling", "Comfort en eenvoud"],
    heroImage: "/images/interior/atmosphere/japandi.webp",
    colors: [
      { name: "Warm wit", hex: "#f5f0e6" },
      { name: "Zand", hex: "#ddc9a0" },
      { name: "Beige", hex: "#cbb188" },
      { name: "Taupe", hex: "#a8967d" },
      { name: "Zacht olijfgroen", hex: "#8d9873" },
    ],
    colorTip: "Gebruik de lichte tinten als rustige basis en voeg hout, taupe en een zachte accentkleur toe voor warmte en contrast.",
    materials: [
      { name: "Naturel eiken", image: "/images/interior/materials/japandi-oak.webp" },
      { name: "Linnen", image: "/images/interior/materials/japandi-linen.webp" },
      { name: "Wol / bouclé", image: "/images/interior/materials/japandi-boucle.webp" },
      { name: "Keramiek", image: "/images/interior/materials/japandi-ceramic.webp" },
    ],
    materialsTip: "Combineer natuurlijke materialen met zachte stoffen. Hierdoor blijft je interieur rustig, maar voelt het tegelijkertijd warm en comfortabel.",
    furnitureAdvice: {
      intro: "Kies liever voor een paar rustige, sterke meubels dan voor veel verschillende vormen en details.",
      items: ["Zachte en afgeronde vormen", "Lage, comfortabele banken", "Rustige meubels zonder veel details", "Naturel hout en zachte stoffen"],
    },
    recipe: [
      { label: "Basis", value: "Warm wit, zand en naturel hout" },
      { label: "Grote meubels", value: "Rustige kleuren en zachte vormen" },
      { label: "Accentkleur", value: "Taupe of zacht olijfgroen" },
      { label: "Materialen", value: "Hout, linnen, wol en keramiek" },
      { label: "Accessoires", value: "Kies liever een paar grotere accessoires dan veel kleine decoraties." },
    ],
    avoid: "Veel felle kleuren, hoogglans meubels en veel kleine accessoires kunnen jouw interieur sneller onrustig maken. Kies liever voor rust, natuurlijke materialen en een beperkt aantal uitgesproken elementen.",
    productTags: ["japandi", "naturel-eiken", "linnen", "rustig-interieur"],
  },
  {
    key: "hotelChique",
    slug: "hotel-chique",
    label: "Hotel Chique",
    subtitle: "Luxe, warmte en verfijnde elegantie",
    longDescription: "Luxe en gelaagdheid passen helemaal bij jou. Je houdt van een interieur dat rijk aanvoelt zonder opzichtig te zijn — met warme donkere tinten, verfijnde materialen en een vleugje glamour.",
    traitsIntro: "Op basis van jouw keuzes zien we vooral een voorkeur voor luxe materialen, donkere tinten en elegante vormen.",
    traits: ["Rijke, donkere tinten", "Luxe materialen", "Elegante vormen", "Een verzorgde uitstraling", "Subtiele glamour"],
    heroImage: "/images/interior/atmosphere/hotel-chique.webp",
    colors: [
      { name: "Donker taupe", hex: "#4a3a2c" },
      { name: "Chocoladebruin", hex: "#3c2a1e" },
      { name: "Champagne", hex: "#d9c7a3" },
      { name: "Marmerwit", hex: "#ece6da" },
      { name: "Diep bordeaux", hex: "#5c2a30" },
    ],
    colorTip: "Gebruik donkere tinten als basis en voeg champagne, marmerwit of een diepe accentkleur toe voor een verfijnd contrast.",
    materials: [
      { name: "Marmer", image: "/images/interior/materials/hotel-chique-marble.webp" },
      { name: "Messing", image: "/images/interior/materials/hotel-chique-brass.webp" },
      { name: "Fluweel", image: "/images/interior/materials/hotel-chique-velvet.webp" },
      { name: "Donker hout", image: "/images/interior/materials/hotel-chique-dark-wood.webp" },
    ],
    materialsTip: "Combineer harde, glanzende materialen zoals marmer en messing met zachte stoffen als fluweel voor een rijke, gelaagde uitstraling.",
    furnitureAdvice: {
      intro: "Kies voor een paar statement-stukken in plaats van veel kleinere meubels.",
      items: ["Royale, elegante vormen", "Fluwelen of gestoffeerde bekleding", "Metalen details in messing of goud", "Donker hout met een verzorgde afwerking"],
    },
    recipe: [
      { label: "Basis", value: "Donker taupe, chocoladebruin en marmerwit" },
      { label: "Grote meubels", value: "Royale vormen in rijke stoffen" },
      { label: "Accentkleur", value: "Champagne of diep bordeaux" },
      { label: "Materialen", value: "Marmer, messing, fluweel en donker hout" },
      { label: "Accessoires", value: "Kies voor enkele verfijnde accessoires met een luxe uitstraling." },
    ],
    avoid: "Té speelse kleuren, goedkoop ogende materialen en een overdaad aan decoratie kunnen de luxe uitstraling verstoren. Kies liever voor minder, maar wel verfijnde elementen.",
    productTags: ["hotel-chique", "marmer", "messing", "fluweel", "luxe"],
  },
  {
    key: "industrial",
    slug: "industrial",
    label: "Industrieel",
    subtitle: "Stoer, robuust en vol karakter",
    longDescription: "Stoere materialen en een krachtige uitstraling passen helemaal bij jou. Je houdt van een interieur met karakter — eerlijk, robuust en met oog voor detail in ruwe materialen.",
    traitsIntro: "Op basis van jouw keuzes zien we vooral een voorkeur voor robuuste materialen, donkere tinten en krachtige lijnen.",
    traits: ["Donkere, stoere tinten", "Robuuste materialen", "Krachtige, rechte lijnen", "Een authentieke uitstraling", "Karaktervolle details"],
    heroImage: "/images/interior/atmosphere/industrial.webp",
    colors: [
      { name: "Antraciet", hex: "#3d3f42" },
      { name: "Betongrijs", hex: "#8a8d8f" },
      { name: "Zwart", hex: "#232323" },
      { name: "Roestbruin", hex: "#7a4a2f" },
      { name: "Warm grijs", hex: "#ada79c" },
    ],
    colorTip: "Gebruik antraciet en betongrijs als basis en voeg zwart en roestbruin toe voor diepte en karakter.",
    materials: [
      { name: "Beton", image: "/images/interior/materials/industrial-concrete.webp" },
      { name: "Zwart metaal", image: "/images/interior/materials/industrial-metal.webp" },
      { name: "Donker hout", image: "/images/interior/materials/industrial-dark-wood.webp" },
      { name: "Leer", image: "/images/interior/materials/industrial-leather.webp" },
    ],
    materialsTip: "Combineer ruwe materialen zoals beton en metaal met donker hout en leer voor een interieur dat stoer aanvoelt, maar niet kil.",
    furnitureAdvice: {
      intro: "Kies voor robuuste meubels met een eerlijke, rauwe uitstraling.",
      items: ["Strakke, robuuste vormen", "Zwart metalen frames en details", "Donker hout of leer", "Weinig franje, veel karakter"],
    },
    recipe: [
      { label: "Basis", value: "Antraciet, betongrijs en zwart" },
      { label: "Grote meubels", value: "Robuuste vormen in metaal en hout" },
      { label: "Accentkleur", value: "Roestbruin of warm grijs" },
      { label: "Materialen", value: "Beton, metaal, donker hout en leer" },
      { label: "Accessoires", value: "Kies voor stoere, functionele accessoires zonder overbodige versiering." },
    ],
    avoid: "Té zachte pasteltinten, veel kleine sierlijke decoraties en glanzende oppervlakken passen minder bij deze stijl. Kies liever voor eerlijke materialen en een beperkte, krachtige kleurstelling.",
    productTags: ["industrieel", "beton", "metaal", "leer"],
  },
  {
    key: "biophilic",
    slug: "biophilic",
    label: "Biophilic / Botanisch",
    subtitle: "Groen, natuurlijk en verbonden met de natuur",
    longDescription: "Groen en natuurlijk voelen als thuis voor jou. Je houdt van een interieur vol leven, natuurlijke materialen en organische vormen — een ruimte die ademt en verbonden aanvoelt met de natuur.",
    traitsIntro: "Op basis van jouw keuzes zien we vooral een voorkeur voor groen, natuurlijke materialen en organische vormen.",
    traits: ["Veel groen en planten", "Natuurlijke materialen", "Organische vormen", "Een frisse, levendige uitstraling", "Verbinding met de natuur"],
    heroImage: "/images/interior/atmosphere/biophilic.webp",
    colors: [
      { name: "Mosgroen", hex: "#6b7a4f" },
      { name: "Olijfgroen", hex: "#8a9270" },
      { name: "Zandbeige", hex: "#d9c9a3" },
      { name: "Terracotta", hex: "#b3663f" },
      { name: "Warm wit", hex: "#f3efe4" },
    ],
    colorTip: "Gebruik zandbeige en warm wit als rustige basis en breng leven in huis met mosgroen, olijfgroen en een vleugje terracotta.",
    materials: [
      { name: "Rotan", image: "/images/interior/materials/biophilic-rattan.webp" },
      { name: "Naturel hout", image: "/images/interior/materials/biophilic-wood.webp" },
      { name: "Linnen", image: "/images/interior/materials/biophilic-linen.webp" },
      { name: "Natuursteen", image: "/images/interior/materials/biophilic-stone.webp" },
    ],
    materialsTip: "Combineer natuurlijke vezels zoals rotan met hout en linnen voor een interieur dat licht, organisch en levendig aanvoelt.",
    furnitureAdvice: {
      intro: "Kies voor organische vormen en materialen die de natuur naar binnen halen.",
      items: ["Ronde, organische vormen", "Rotan en gevlochten materialen", "Veel (grote) planten", "Natuurlijke, ademende stoffen"],
    },
    recipe: [
      { label: "Basis", value: "Zandbeige, warm wit en naturel hout" },
      { label: "Grote meubels", value: "Organische vormen in natuurlijke materialen" },
      { label: "Accentkleur", value: "Mosgroen of terracotta" },
      { label: "Materialen", value: "Rotan, hout, linnen en natuursteen" },
      { label: "Accessoires", value: "Voeg planten en natuurlijke texturen toe als sfeermakers." },
    ],
    avoid: "Strakke, kille materialen zoals blinkend metaal en glas in overvloed passen minder bij deze stijl. Kies liever voor zachte, natuurlijke texturen en voldoende groen.",
    productTags: ["biophilic", "botanisch", "rotan", "planten"],
  },
  {
    key: "modernCountry",
    slug: "modern-country",
    label: "Landelijk modern",
    subtitle: "Warm, comfortabel en tijdloos",
    longDescription: "Warmte en comfort staan voorop in jouw interieur, met een moderne en rustige twist op het traditionele landelijke wonen. Je houdt van een huis dat uitnodigt en tijdloos aanvoelt.",
    traitsIntro: "Op basis van jouw keuzes zien we vooral een voorkeur voor warme tinten, comfortabele stoffen en een tijdloze uitstraling.",
    traits: ["Warme, natuurlijke tinten", "Comfortabele stoffen", "Een moderne, rustige twist", "Tijdloze vormen", "Een uitnodigende sfeer"],
    heroImage: "/images/interior/atmosphere/modern-country.webp",
    colors: [
      { name: "Zacht greige", hex: "#e4dcc9" },
      { name: "Warm wit", hex: "#f5f1e6" },
      { name: "Beige", hex: "#d8c6a3" },
      { name: "Oker", hex: "#b98a4a" },
      { name: "Donkergroen", hex: "#4d5c42" },
    ],
    colorTip: "Gebruik greige en warm wit als basis en voeg beige, oker of een gedempt donkergroen toe voor warmte en diepte.",
    materials: [
      { name: "Massief hout", image: "/images/interior/materials/modern-country-wood.webp" },
      { name: "Linnen", image: "/images/interior/materials/modern-country-linen.webp" },
      { name: "Wol", image: "/images/interior/materials/modern-country-wool.webp" },
      { name: "Natuursteen", image: "/images/interior/materials/modern-country-stone.webp" },
    ],
    materialsTip: "Combineer massief hout met linnen en wol voor een interieur dat comfortabel en tijdloos aanvoelt, zonder overdreven landelijk te worden.",
    furnitureAdvice: {
      intro: "Kies voor comfortabele, tijdloze meubels zonder overdaad aan details.",
      items: ["Comfortabele, zachte vormen", "Massief hout met een warme uitstraling", "Rustige, wasbare stoffen", "Tijdloze, functionele modellen"],
    },
    recipe: [
      { label: "Basis", value: "Zacht greige, warm wit en massief hout" },
      { label: "Grote meubels", value: "Comfortabele vormen in rustige kleuren" },
      { label: "Accentkleur", value: "Oker of gedempt donkergroen" },
      { label: "Materialen", value: "Hout, linnen, wol en natuursteen" },
      { label: "Accessoires", value: "Kies voor warme, natuurlijke accessoires die uitnodigen." },
    ],
    avoid: "Overdreven traditioneel landelijke details en drukke patronen kunnen de moderne rust verstoren. Kies liever voor eenvoud, warmte en een rustige basis.",
    productTags: ["landelijk-modern", "massief-hout", "linnen", "tijdloos"],
  },
  {
    key: "retroVintage",
    slug: "retro-vintage",
    label: "Retro / Vintage",
    subtitle: "Karaktervol met invloeden uit vervlogen decennia",
    longDescription: "Karakter en persoonlijkheid staan voorop in jouw interieur. Je houdt van opvallende kleuren, ronde vormen en meubels met een verhaal — een huis dat nooit saai aanvoelt.",
    traitsIntro: "Op basis van jouw keuzes zien we vooral een voorkeur voor opvallende kleuren, ronde vormen en karaktervolle details.",
    traits: ["Karaktervolle kleuren", "Ronde, speelse vormen", "Opvallende patronen", "Een persoonlijke uitstraling", "Invloeden uit vervlogen decennia"],
    heroImage: "/images/interior/atmosphere/retro-vintage.webp",
    colors: [
      { name: "Oker", hex: "#c1793f" },
      { name: "Roestrood", hex: "#a34a2f" },
      { name: "Olijfgroen", hex: "#7a8250" },
      { name: "Donkerbruin", hex: "#4a3323" },
      { name: "Warm crème", hex: "#ede2c8" },
    ],
    colorTip: "Gebruik warm crème als rustige basis en durf oker, roestrood of olijfgroen als uitgesproken accentkleur te gebruiken.",
    materials: [
      { name: "Teak / walnoot", image: "/images/interior/materials/retro-vintage-teak.webp" },
      { name: "Velours", image: "/images/interior/materials/retro-vintage-velvet.webp" },
      { name: "Glas", image: "/images/interior/materials/retro-vintage-glass.webp" },
      { name: "Keramiek", image: "/images/interior/materials/retro-vintage-ceramic.webp" },
    ],
    materialsTip: "Combineer warm hout zoals teak of walnoot met velours en glas voor een interieur vol karakter en textuur.",
    furnitureAdvice: {
      intro: "Kies voor meubels met een verhaal en een herkenbare, ronde vorm.",
      items: ["Ronde, modulaire vormen", "Velours of geribbelde stoffen", "Teak of walnoot hout", "Grafische patronen als accent"],
    },
    recipe: [
      { label: "Basis", value: "Warm crème, teak en walnoot" },
      { label: "Grote meubels", value: "Ronde vormen in velours" },
      { label: "Accentkleur", value: "Oker, roestrood of olijfgroen" },
      { label: "Materialen", value: "Hout, velours, glas en keramiek" },
      { label: "Accessoires", value: "Kies voor een paar sprekende vintage accessoires met een verhaal." },
    ],
    avoid: "Strakke, minimalistische lijnen en een té neutrale kleurstelling kunnen het karakter van deze stijl juist wegnemen. Durf kleur en patroon te gebruiken.",
    productTags: ["retro", "vintage", "teak", "velours"],
  },
];

function getStyleProfile(key) {
  return STYLE_PROFILES.find((profile) => profile.key === key) ?? null;
}

export { STYLE_PROFILES, getStyleProfile };

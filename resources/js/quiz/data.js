/**
 * Centrale databron voor de interieurstijltest.
 *
 * De test draait om 6 vaste woonstijlen. De volledige stijlinhoud (kleuren, materialen,
 * meubeladvies, interieurrecept, etc.) staat in styleProfiles.js — dit bestand gebruikt
 * daarvan alleen key/slug om de quizvragen op te bouwen. Iedere vraag (QUESTIONS) heeft
 * precies 6 opties — één per stijl — opgebouwd via `buildOptions()`, zodat "6 stappen,
 * 6 stijlen" een structurele garantie is en niet alleen een afspraak.
 *
 * @typedef {"japandi"|"hotelChique"|"industrial"|"biophilic"|"modernCountry"|"retroVintage"} InteriorStyle
 *
 * Elke optie heeft minimaal: id, title, image, primaryStyle (één van de 6 InteriorStyle-
 * waarden — geen gewogen sub-scores, dat is bewust simpel gehouden, zie scoring.js).
 *
 * `image` verwijst naar het pad waar de definitieve AI-afbeelding straks komt te staan.
 * Zolang dat bestand niet bestaat, toont de OptionCard automatisch een nette placeholder.
 * Vervang dus alleen het bestand op dat pad — geen codewijziging nodig. Elke vraagmap
 * verwacht steeds dezelfde 6 bestandsnamen (één per stijl-slug, zie styleProfiles.js).
 *
 * Uitzondering: de "colorPreference"-vraag (COLOR_PREFERENCE_QUESTION hieronder) heeft geen
 * image/primaryStyle en staat los van de woonstijlscore — de gebruiker kiest daar een
 * kleurensfeer (één van 8 kant-en-klare paletten), geen stijl. Zie paletteEngine.js voor hoe
 * die keuze wél wordt gebruikt (het persoonlijke kleurenpalet op de resultatenpagina).
 */

import { STYLE_PROFILES } from "./styleProfiles.js";
import { PALETTE_OPTIONS } from "./paletteData.js";

/**
 * Bouwt de 6 opties van een vraag: één per stijl in STYLE_PROFILES, in dezelfde volgorde.
 * @param {string} questionSlug bv. "floor" — gebruikt als id-prefix
 * @param {string} category mapnaam onder /images/interior/ voor deze vraag
 * @param {Record<InteriorStyle,string>} titlesByStyle titel per stijl-key
 * @param {Partial<Record<InteriorStyle,{hex:string,family:string,temperature:string}>>} [colorByStyle]
 *   optionele kleurmetadata per stijl-key — gebruikt door paletteEngine.js voor het persoonlijke
 *   kleurenpalet, niet voor de woonstijlscore.
 */
function buildOptions(questionSlug, category, titlesByStyle, colorByStyle) {
  return STYLE_PROFILES.map(({ key, slug }) => {
    const color = colorByStyle?.[key];
    return {
      id: `${questionSlug}-${slug}`,
      title: titlesByStyle[key],
      image: `/images/interior/${category}/${slug}.webp`,
      primaryStyle: key,
      ...(color ? { colorHex: color.hex, colorFamily: color.family, colorTemperature: color.temperature } : {}),
    };
  });
}

/**
 * De kleurvoorkeur-vraag heeft een fundamenteel ander optie-model dan de overige vragen: geen
 * productfoto/primaryStyle, en een enkele keuze uit 8 kant-en-klare sfeerpaletten (i.p.v. losse
 * kleurswatches). Deze keuze telt bewust niet mee in de woonstijlscore (zie scoring.js) — hij
 * voedt alleen paletteEngine.js.
 */
const COLOR_PREFERENCE_QUESTION = {
  id: "colorPreference",
  section: "materials-colors",
  type: "color-preference",
  title: "Welke kleurensfeer spreekt jou het meeste aan?",
  subtitle: "Welke combinatie voelt het meest als jij?",
  options: PALETTE_OPTIONS.map((palette) => ({
    id: palette.id,
    title: palette.name,
    colors: palette.colors,
  })),
};

const SECTIONS = [
  {
    id: "materials-colors",
    title: "Kleur & materiaal",
    tagline: "Ontdek welke kleuren, materialen en afwerkingen het beste bij jouw smaak passen.",
    wrapUp: null,
    cta: "Beginnen",
  },
  {
    id: "objects",
    title: "Meubels & accessoires",
    tagline: "Welke meubels en vormen passen het beste bij jouw ideale interieur?",
    wrapUp: "Mooi! We weten nu welke materialen en kleuren je aanspreken.",
    cta: "Verder naar meubels",
  },
];

const QUESTIONS = [
  // --- Onderdeel 1: Kleur & materiaal ---
  COLOR_PREFERENCE_QUESTION,
  {
    id: "floor",
    section: "materials-colors",
    title: "Welke vloer spreekt jou het meeste aan?",
    options: buildOptions(
      "floor",
      "floors",
      {
        japandi: "Lichte naturel eiken plank",
        hotelChique: "Donkere visgraat",
        industrial: "Betonlook / donkere tegel",
        biophilic: "Warme natuurlijke houtvloer",
        modernCountry: "Naturel eiken brede plank",
        retroVintage: "Warme donkere parketvloer / vintage houtlook",
      },
      {
        japandi: { hex: "#d7b98a", family: "wood", temperature: "warm" },
        hotelChique: { hex: "#4a3323", family: "wood", temperature: "warm" },
        industrial: { hex: "#6e6f6b", family: "concrete", temperature: "cool" },
        biophilic: { hex: "#b98956", family: "wood", temperature: "warm" },
        modernCountry: { hex: "#c9a876", family: "wood", temperature: "warm" },
        retroVintage: { hex: "#6b4226", family: "wood", temperature: "warm" },
      }
    ),
  },
  {
    id: "wallColor",
    section: "materials-colors",
    title: "Welke wandkleur past het beste bij jou?",
    options: buildOptions(
      "wall-color",
      "walls",
      {
        japandi: "Warm beige / zand",
        hotelChique: "Donker taupe / chocoladebruin",
        industrial: "Grijs / antraciet",
        biophilic: "Olijfgroen / mosgroen",
        modernCountry: "Zacht greige / warm wit",
        retroVintage: "Oker / terracotta / gedempte kleur",
      },
      {
        japandi: { hex: "#d9c3a4", family: "neutral", temperature: "warm" },
        hotelChique: { hex: "#4a3323", family: "brown", temperature: "warm" },
        industrial: { hex: "#55575c", family: "gray", temperature: "cool" },
        biophilic: { hex: "#6b7a4f", family: "green", temperature: "warm" },
        modernCountry: { hex: "#ece3d3", family: "neutral", temperature: "warm" },
        retroVintage: { hex: "#c1793f", family: "orange", temperature: "warm" },
      }
    ),
  },
  {
    id: "wallFinish",
    section: "materials-colors",
    title: "Welke wandafwerking spreekt jou het meeste aan?",
    options: buildOptions(
      "wall-finish",
      "wall-finishes",
      {
        japandi: "Rustige linnenstructuur",
        hotelChique: "Luxe donker textuurbehang",
        industrial: "Betonlook / baksteenlook",
        biophilic: "Botanisch patroon / natuurlijke wand",
        modernCountry: "Subtiele landelijke structuur",
        retroVintage: "Grafisch retro patroon",
      },
      {
        japandi: { hex: "#e4d9c5", family: "neutral", temperature: "warm" },
        hotelChique: { hex: "#3e2e22", family: "neutral", temperature: "warm" },
        industrial: { hex: "#8a6f5c", family: "brick", temperature: "warm" },
        biophilic: { hex: "#6e7a52", family: "green", temperature: "warm" },
        modernCountry: { hex: "#e8dfc9", family: "neutral", temperature: "warm" },
        retroVintage: { hex: "#c77b45", family: "orange", temperature: "warm" },
      }
    ),
  },
  {
    id: "sofaMaterial",
    section: "materials-colors",
    title: "Welke kleur en stof spreekt jou het meeste aan?",
    options: buildOptions(
      "sofa-material",
      "sofa-materials",
      {
        japandi: "Zandkleurige geweven stof",
        hotelChique: "Beige/taupe velours of luxe stof",
        industrial: "Cognac leer",
        biophilic: "Groen bouclé / natuurlijke stof",
        modernCountry: "Warme beige zachte stof",
        retroVintage: "Oker, roest of olijfgroen velours",
      },
      {
        japandi: { hex: "#cbb188", family: "neutral", temperature: "warm" },
        hotelChique: { hex: "#b7a189", family: "neutral", temperature: "warm" },
        industrial: { hex: "#a3623a", family: "brown", temperature: "warm" },
        biophilic: { hex: "#7a8f66", family: "green", temperature: "warm" },
        modernCountry: { hex: "#ddc9a8", family: "neutral", temperature: "warm" },
        retroVintage: { hex: "#b4622f", family: "orange", temperature: "warm" },
      }
    ),
  },

  // --- Onderdeel 2: Objecten ---
  {
    id: "sofaModel",
    section: "objects",
    title: "Welke bank zou jij het liefst in je woonkamer zetten?",
    options: buildOptions("sofa-model", "sofas", {
      japandi: "Lage organische bank",
      hotelChique: "Royale elegante bank",
      industrial: "Strakke robuuste bank",
      biophilic: "Organische ronde bank",
      modernCountry: "Comfortabele zachte bank",
      retroVintage: "Ronde modulaire retrobank",
    }),
  },
  {
    id: "coffeeTable",
    section: "objects",
    title: "Welke salontafel past het beste bij jouw smaak?",
    options: buildOptions("coffee-table", "coffee-tables", {
      japandi: "Licht hout / organisch",
      hotelChique: "Marmer / donker natuursteen",
      industrial: "Zwart metaal + hout",
      biophilic: "Organisch hout / natuursteen",
      modernCountry: "Massief licht hout",
      retroVintage: "Donker hout / ronde retrotafel",
    }),
  },
  {
    id: "diningTable",
    section: "objects",
    title: "Welke eettafel zou jij kiezen?",
    options: buildOptions("dining-table", "dining-tables", {
      japandi: "Deens ovaal licht hout",
      hotelChique: "Donker hout / natuursteen",
      industrial: "Robuuste tafel met metalen onderstel",
      biophilic: "Organisch gevormd massief hout",
      modernCountry: "Grote landelijke houten tafel",
      retroVintage: "Rond/ovaal donker hout met retrovorm",
    }),
  },
  {
    id: "diningChair",
    section: "objects",
    title: "Welke eetkamerstoel spreekt jou het meeste aan?",
    options: buildOptions("dining-chair", "dining-chairs", {
      japandi: "Houten stoel met lichte bekleding",
      hotelChique: "Luxe gestoffeerde fauteuilstoel",
      industrial: "Cognac leren stoel met metaal",
      biophilic: "Organische stoel in groen/naturel",
      modernCountry: "Comfortabele stoffen stoel",
      retroVintage: "Kuipstoel in velours / retrovorm",
    }),
  },
  {
    id: "lighting",
    section: "objects",
    title: "Welke verlichting past het beste bij jouw interieur?",
    options: buildOptions("lighting", "lighting", {
      japandi: "Papier / zachte organische hanglamp",
      hotelChique: "Messing / glas / luxe kroonlamp",
      industrial: "Zwart metalen hanglamp",
      biophilic: "Rotan / natuurlijke vezels",
      modernCountry: "Warme landelijke lamp",
      retroVintage: "Glazen bol / jaren 60-70 lamp",
    }),
  },
  {
    id: "rug",
    section: "objects",
    title: "Welke stijl vloerkleed spreekt jou het meeste aan?",
    options: buildOptions(
      "rug",
      "rugs",
      {
        japandi: "Laagpolig naturel",
        hotelChique: "Donker luxe hoogpolig kleed",
        industrial: "Grafisch grijs / antraciet",
        biophilic: "Organische vorm in groen/naturel",
        modernCountry: "Zacht beige kleed",
        retroVintage: "Grafisch retro patroon",
      },
      {
        japandi: { hex: "#d3c0a0", family: "neutral", temperature: "warm" },
        hotelChique: { hex: "#3d2e28", family: "neutral", temperature: "warm" },
        industrial: { hex: "#4b4d50", family: "gray", temperature: "cool" },
        biophilic: { hex: "#7c8a5e", family: "green", temperature: "warm" },
        modernCountry: { hex: "#d9c9a8", family: "neutral", temperature: "warm" },
        retroVintage: { hex: "#b4622f", family: "orange", temperature: "warm" },
      }
    ),
  },
  {
    id: "cabinet",
    section: "objects",
    title: "Welke kast of dressoir zou jij kiezen?",
    options: buildOptions("cabinet", "cabinets", {
      japandi: "Licht eiken minimalistisch",
      hotelChique: "Donker walnoot / luxe details",
      industrial: "Zwart metaal + hout",
      biophilic: "Organisch hout / natuurlijke kast",
      modernCountry: "Licht hout met rustige landelijke uitstraling",
      retroVintage: "Teak / walnoot retro dressoir",
    }),
  },
];

export { QUESTIONS, SECTIONS };

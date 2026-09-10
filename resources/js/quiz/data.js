/**
 * Centrale databron voor de interieurstijltest.
 *
 * De test draait om 8 vaste woonstijlen (zie STYLE_PROFILES in styleProfiles.js — dit bestand
 * gebruikt daarvan alleen key/slug/label om de quizvragen op te bouwen). Dit is alleen de
 * **fallback**-databron: iedere vraag heeft hier precies zoveel opties als er stijlen zijn —
 * één per stijl — opgebouwd via `buildOptions()`. Zolang `/api/quiz-config` op tijd antwoordt
 * (zie remoteConfig.js) wordt deze lijst per vraag volledig vervangen door de admin-ingestelde
 * opties, en kan het aantal dus afwijken (minder na deactiveren, meer na admin-toevoegingen).
 *
 * Elke optie heeft minimaal: id, title, image, primaryStyle (één van de STYLE_PROFILES-keys —
 * geen gewogen sub-scores, dat is bewust simpel gehouden, zie scoring.js).
 *
 * `image` verwijst naar het pad waar de definitieve foto straks komt te staan. Zolang dat
 * bestand niet bestaat, toont de OptionCard automatisch een nette placeholder. Vervang dus
 * alleen het bestand op dat pad — geen codewijziging nodig.
 */

import { STYLE_PROFILES } from "./styleProfiles.js";

/**
 * Bouwt de fallback-opties van een vraag: één per stijl in STYLE_PROFILES, in dezelfde volgorde.
 * Zonder eigen titel per stijl (`titlesByStyle`) valt de titel terug op de stijlnaam zelf — dit
 * is toch alleen de nooduitwijk-content voor als de live, admin-ingestelde opties niet op tijd
 * ophalen, dus een generieke titel is hier geen probleem.
 * @param {string} questionSlug bv. "floor" — gebruikt als id-prefix
 * @param {string} category mapnaam onder /images/interior/ voor deze vraag
 * @param {Record<string,string>} [titlesByStyle] optionele titel per stijl-key
 */
function buildOptions(questionSlug, category, titlesByStyle) {
  return STYLE_PROFILES.map(({ key, slug, label }) => ({
    id: `${questionSlug}-${slug}`,
    title: titlesByStyle?.[key] ?? label,
    image: `/images/interior/${category}/${slug}.webp`,
    primaryStyle: key,
    styles: [key],
  }));
}

// `image` is de fallback zolang /api/quiz-config niet (op tijd) antwoordt — zie
// remoteConfig.js's applyTransitionPhotos(), die dit veld overschrijft met de echte,
// disk-onafhankelijke URL zodra de fetch lukt.
const SECTIONS = [
  {
    id: "materials-colors",
    title: "Kleur & materiaal",
    tagline: "Ontdek welke kleuren, materialen en afwerkingen het beste bij jouw smaak passen.",
    wrapUp: null,
    cta: "Beginnen",
    image: "/images/interior/transitions/materials-colors.webp",
  },
  {
    id: "objects",
    title: "Meubels & accessoires",
    tagline: "Welke meubels en vormen passen het beste bij jouw ideale interieur?",
    wrapUp: "Mooi! We weten nu welke materialen en kleuren je aanspreken.",
    cta: "Verder naar meubels",
    image: "/images/interior/transitions/objects.webp",
  },
];

const QUESTIONS = [
  // --- Onderdeel 1: Kleur & materiaal ---
  {
    id: "floor",
    section: "materials-colors",
    title: "Welke vloer spreekt jou het meeste aan?",
    options: buildOptions("floor", "floors"),
  },
  {
    id: "wallColor",
    section: "materials-colors",
    title: "Welke wandkleur past het beste bij jou?",
    options: buildOptions("wall-color", "walls"),
  },
  {
    id: "wallFinish",
    section: "materials-colors",
    title: "Welke wandafwerking spreekt jou het meeste aan?",
    options: buildOptions("wall-finish", "wall-finishes"),
  },
  {
    id: "sofaMaterial",
    section: "materials-colors",
    title: "Welke kleur en stof spreekt jou het meeste aan?",
    options: buildOptions("sofa-material", "sofa-materials"),
  },

  // --- Onderdeel 2: Objecten ---
  {
    id: "sofaModel",
    section: "objects",
    title: "Welke bank zou jij het liefst in je woonkamer zetten?",
    options: buildOptions("sofa-model", "sofas"),
  },
  {
    id: "coffeeTable",
    section: "objects",
    title: "Welke salontafel past het beste bij jouw smaak?",
    options: buildOptions("coffee-table", "coffee-tables"),
  },
  {
    id: "diningTable",
    section: "objects",
    title: "Welke eettafel zou jij kiezen?",
    options: buildOptions("dining-table", "dining-tables"),
  },
  {
    id: "diningChair",
    section: "objects",
    title: "Welke eetkamerstoel spreekt jou het meeste aan?",
    options: buildOptions("dining-chair", "dining-chairs"),
  },
  {
    id: "lighting",
    section: "objects",
    title: "Welke verlichting past het beste bij jouw interieur?",
    options: buildOptions("lighting", "lighting"),
  },
  {
    id: "rug",
    section: "objects",
    title: "Welke stijl vloerkleed spreekt jou het meeste aan?",
    options: buildOptions("rug", "rugs"),
  },
  {
    id: "cabinet",
    section: "objects",
    title: "Welke kast of dressoir zou jij kiezen?",
    options: buildOptions("cabinet", "cabinets"),
  },
];

export { QUESTIONS, SECTIONS };

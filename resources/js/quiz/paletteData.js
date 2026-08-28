/**
 * De 8 vaste sfeerpaletten voor de "kleurvoorkeur"-vraag (eerste stap van Kleur & materiaal).
 * Bewust ingedeeld op sfeer, niet op woonstijl: de gebruiker kiest hier de kleurensfeer die hem
 * aanspreekt, geen woonstijl — dit blijft daarom los van de woonstijlscore (zie data.js/
 * scoring.js), net als de losse kleurkeuze die dit vervangt. Zie paletteEngine.js voor hoe het
 * gekozen palet vervolgens samen met de overige kleur-dragende antwoorden (vloer, bank, wand)
 * het uiteindelijke persoonlijke kleurenpalet op de resultatenpagina vormt.
 *
 * Deze paletten (en de kleuren daarbinnen) zijn admin-beheerbaar via het scherm "Kleurvoorkeur"
 * (QuizPalettesPage) — de inhoud hieronder is alleen de statische fallback voor als die fetch
 * faalt of traag is, zie remoteConfig.js.
 *
 * @typedef {object} PaletteColor
 * @property {string} name
 * @property {string} hex
 *
 * @typedef {object} PaletteOption
 * @property {string} id     kebab-case, gebruikt als option-id in de quizvraag
 * @property {string} name   sfeernaam, getoond op de keuzekaart — bewust geen stijlnaam
 * @property {PaletteColor[]} colors  4 kleuren; de eerste weegt het zwaarst in het uiteindelijke palet
 */

/** @type {PaletteOption[]} */
const PALETTE_OPTIONS = [
  {
    id: "warm-earthy",
    name: "Warm & aards",
    colors: [
      { name: "Zand", hex: "#d8c5a7" },
      { name: "Terracotta", hex: "#b3663f" },
      { name: "Oker", hex: "#b98a4a" },
      { name: "Donkerbruin", hex: "#4a3323" },
    ],
  },
  {
    id: "soft-light",
    name: "Zacht & licht",
    colors: [
      { name: "Warm wit", hex: "#f5f0e6" },
      { name: "Greige", hex: "#c9bea6" },
      { name: "Beige", hex: "#cbb188" },
      { name: "Taupe", hex: "#a8967d" },
    ],
  },
  {
    id: "dark-dramatic",
    name: "Donker & dramatisch",
    colors: [
      { name: "Antraciet", hex: "#3d3f42" },
      { name: "Diepblauw", hex: "#2c3a4d" },
      { name: "Bordeaux", hex: "#5c2530" },
      { name: "Donkerbruin", hex: "#4a3323" },
    ],
  },
  {
    id: "fresh-cool",
    name: "Fris & koel",
    // Bewust andere kleuren dan de rest van het bestand: dit palet moet duidelijk kóél aanvoelen
    // (in plaats van warm-neutraal-grijs), zodat het zich helder onderscheidt van "Monochroom &
    // strak" — zie feedbackronde.
    colors: [
      { name: "IJsblauw", hex: "#c3d4d9" },
      { name: "Staalblauw", hex: "#4a6472" },
      { name: "Koel lichtgrijs", hex: "#cdd3d3" },
      { name: "Gebroken wit", hex: "#eceff0" },
    ],
  },
  {
    id: "green-natural",
    name: "Groen & natuurlijk",
    colors: [
      { name: "Mosgroen", hex: "#5f6b4a" },
      { name: "Olijfgroen", hex: "#74765a" },
      { name: "Zand", hex: "#d8c5a7" },
      { name: "Warm wit", hex: "#f5f0e6" },
    ],
  },
  {
    id: "rich-refined",
    name: "Rijk & verfijnd",
    colors: [
      { name: "Bordeaux", hex: "#5c2530" },
      { name: "Donkerbruin", hex: "#4a3323" },
      { name: "Beige", hex: "#cbb188" },
      { name: "Warm wit", hex: "#f5f0e6" },
    ],
  },
  {
    id: "monochrome-sharp",
    name: "Monochroom & strak",
    colors: [
      { name: "Lichtgrijs", hex: "#d3d0c9" },
      { name: "Antraciet", hex: "#3d3f42" },
      { name: "Warm wit", hex: "#f5f0e6" },
      { name: "Donkerbruin", hex: "#4a3323" },
    ],
  },
  {
    id: "bold-colorful",
    name: "Kleurrijk & gedurfd",
    colors: [
      { name: "Terracotta", hex: "#b3663f" },
      { name: "Oker", hex: "#b98a4a" },
      { name: "Mosgroen", hex: "#5f6b4a" },
      { name: "Bordeaux", hex: "#5c2530" },
    ],
  },
];

export { PALETTE_OPTIONS };

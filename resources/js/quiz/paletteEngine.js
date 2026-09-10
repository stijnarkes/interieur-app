/**
 * Stelt het persoonlijke kleurenpalet op de resultatenpagina samen. Vervangt het oude
 * "winnende stijl → vast palet"-model: het palet wordt nu vooral gebouwd uit de expliciete
 * kleurvoorkeur en de kleur-dragende antwoorden (wandkleur, bankkleur & stof, vloer,
 * wandafwerking, vloerkleed), met de vaste stijlkleuren (styleProfiles.js) alleen nog als
 * aanvulling/fallback/harmonisatie. Zie de projectbriefing voor de volledige prioriteitsredenering.
 */

import { QUESTIONS } from "./data.js";
import { PALETTE_OPTIONS } from "./paletteData.js";

const ROLE_ORDER = ["Basis", "Hoofdkleur", "Accentkleur", "Materiaaltoon", "Contrastkleur"];
const SIMILAR_THRESHOLD = 45; // RGB-afstand; kleiner dan dit oogt als "bijna dezelfde kleur"

function hexToRgb(hex) {
  const clean = hex.replace("#", "");
  const value = parseInt(clean, 16);
  return { r: (value >> 16) & 255, g: (value >> 8) & 255, b: value & 255 };
}

function luminance(hex) {
  const { r, g, b } = hexToRgb(hex);
  return 0.299 * r + 0.587 * g + 0.114 * b;
}

function brightnessOf(hex) {
  const l = luminance(hex);
  if (l > 195) return "light";
  if (l < 90) return "dark";
  return "medium";
}

function colorDistance(hexA, hexB) {
  const a = hexToRgb(hexA);
  const b = hexToRgb(hexB);
  return Math.sqrt((a.r - b.r) ** 2 + (a.g - b.g) ** 2 + (a.b - b.b) ** 2);
}

function isTooSimilarToAny(hex, chosenColors) {
  return chosenColors.some((c) => colorDistance(hex, c.hex) < SIMILAR_THRESHOLD);
}

/** @returns {object[]} alle gekozen opties voor deze vraag (0 tot maxSelections stuks). */
function findAnsweredOptions(answers, questionId) {
  const question = QUESTIONS.find((item) => item.id === questionId);
  const optionIds = answers?.[questionId];
  if (!question || !Array.isArray(optionIds)) return [];
  return optionIds.map((id) => question.options.find((option) => option.id === id)).filter(Boolean);
}

/** @returns {object[]} alle gekozen sfeerpaletten (0 tot maxSelections stuks). */
function chosenPalettes(answers) {
  const ids = answers?.colorPreference;
  if (!Array.isArray(ids)) return [];
  return ids.map((id) => PALETTE_OPTIONS.find((palette) => palette.id === id)).filter(Boolean);
}

function explicitColorChoices(answers) {
  return chosenPalettes(answers).flatMap((palette) =>
    palette.colors.map((color) => ({ name: color.name, hex: color.hex, brightness: brightnessOf(color.hex) })),
  );
}

/** @returns {object[]} kleurkandidaten van alle gekozen opties bij deze vraag (0 tot maxSelections stuks). */
function questionColorCandidates(answers, questionId) {
  return findAnsweredOptions(answers, questionId)
    .filter((option) => option.colorHex)
    .map((option) => ({ name: option.title, hex: option.colorHex, brightness: brightnessOf(option.colorHex) }));
}

function styleColorCandidates(primaryStyle) {
  if (!primaryStyle?.colors) return [];
  return primaryStyle.colors.map((color) => ({
    name: color.name,
    hex: color.hex,
    brightness: brightnessOf(color.hex),
  }));
}

/**
 * @param {Record<string, string|string[]>} answers
 * @param {import('./styleProfiles.js').StyleProfile|null} primaryStyle
 * @returns {{ name: string, hex: string, role: string }[]} ongeveer 5 kleuren
 */
function composePersonalPalette(answers, primaryStyle) {
  const explicit = explicitColorChoices(answers);
  const wallColors = questionColorCandidates(answers, "wallColor");
  const sofaColors = questionColorCandidates(answers, "sofaMaterial");
  const floorColors = questionColorCandidates(answers, "floor");
  const wallFinishColors = questionColorCandidates(answers, "wallFinish");
  const rugColors = questionColorCandidates(answers, "rug");
  // Bij meerdere keuzes op één vraag telt de eerst gekozen kleur het zwaarst voor de vaste
  // rollen hieronder — alle overige kleuren doen wel mee in de algemene kandidatenpool.
  const wallColor = wallColors[0] ?? null;
  const sofaColor = sofaColors[0] ?? null;
  const floorColor = floorColors[0] ?? null;
  const wallFinishColor = wallFinishColors[0] ?? null;
  const rugColor = rugColors[0] ?? null;
  const styleColors = styleColorCandidates(primaryStyle);
  const styleColorsByLuminance = [...styleColors].sort((a, b) => luminance(a.hex) - luminance(b.hex));
  const allCandidates = [...explicit, ...wallColors, ...sofaColors, ...floorColors, ...wallFinishColors, ...rugColors, ...styleColors].filter(Boolean);
  // Voor stijlen die van nature geen echt lichte kleur hebben (bv. Industrieel), is de
  // "lichtste beschikbare" kleur een eerlijkere Basis dan geforceerd de (donkere) wandkleur.
  const lightestOverall = [...allCandidates].sort((a, b) => luminance(b.hex) - luminance(a.hex))[0];

  /** @type {Record<string, { name: string, hex: string, role: string }>} */
  const assigned = {};

  function assign(role, candidates) {
    if (assigned[role]) return;
    const chosenSoFar = Object.values(assigned);
    const match = candidates.find((c) => c && !isTooSimilarToAny(c.hex, chosenSoFar));
    if (match) {
      assigned[role] = { name: match.name, hex: match.hex, role };
    }
  }

  // Basis: een lichte, rustige kleur — bij voorkeur de wandkleur (het grootste vlak in huis).
  assign("Basis", [
    wallColor?.brightness === "light" ? wallColor : null,
    explicit.find((c) => c.brightness === "light"),
    ...styleColorsByLuminance.filter((c) => c.brightness === "light"),
    lightestOverall,
  ]);

  // Hoofdkleur: de meest bepalende kleur — eerste expliciete keuze weegt het zwaarst.
  assign("Hoofdkleur", [explicit[0], wallColor, sofaColor, styleColors[1], styleColors[0]]);

  // Accentkleur: nog niet gebruikte expliciete keuzes eerst, anders een stijlaccent.
  assign("Accentkleur", [explicit[1], explicit[2], sofaColor, styleColors[2], styleColors[3]]);

  // Materiaaltoon: hout-/textuurtinten (vloer, wandafwerking) apart van gewone verfkleuren.
  assign("Materiaaltoon", [floorColor, wallFinishColor, rugColor, styleColors[0]]);

  // Contrastkleur: de donkerste bruikbare, onderscheidende kleur.
  assign("Contrastkleur", [
    explicit.find((c) => c.brightness === "dark"),
    [...styleColorsByLuminance].reverse()[0],
    rugColor,
    sofaColor,
  ]);

  // Vul eventueel nog lege rollen op zodat er altijd zoveel mogelijk richting 5 kleuren getoond
  // worden, ook als bovenstaande voorkeurslijsten door de gelijkenis-check niets opleverden.
  function fillRemaining(threshold) {
    for (const role of ROLE_ORDER) {
      if (assigned[role]) continue;
      const chosenSoFar = Object.values(assigned);
      // Een "Contrastkleur" die zelf licht is, is geen contrast — voor die rol dringen we hier
      // niet aan met een lichte kleur; liever die rol overslaan dan hem verkeerd labelen.
      const pool = role === "Contrastkleur"
        ? allCandidates.filter((c) => c.brightness !== "light")
        : allCandidates;
      const fallback = pool.find(
        (c) => !chosenSoFar.some((chosen) => colorDistance(c.hex, chosen.hex) < threshold)
      );
      if (fallback) {
        assigned[role] = { name: fallback.name, hex: fallback.hex, role };
      }
    }
  }

  fillRemaining(SIMILAR_THRESHOLD);
  // Een stijl met een krap, harmonieus palet (bv. Japandi) levert soms geen 5 voldoend
  // onderscheidende kandidaten op met de normale drempel. Liever een net iets minder
  // uitgesproken 5e kleur tonen dan een zichtbaar onvolledig palet — dus nog één ronde
  // met een lossere drempel (alleen bijna-identieke kleuren blijven dan geweerd).
  fillRemaining(15);

  return ROLE_ORDER.map((role) => assigned[role]).filter(Boolean);
}

/** Korte, dynamische uitleg boven het kleurenpalet. */
function buildPaletteExplanation(answers, primaryStyle) {
  const palettes = chosenPalettes(answers);

  if (palettes.length === 0) {
    return primaryStyle
      ? `Dit kleurenpalet is opgebouwd rond de tinten die passen bij jouw ${primaryStyle.label}-stijl.`
      : "";
  }

  const styleText = primaryStyle
    ? ` Daarom hebben we die sfeer gecombineerd met tinten uit jouw ${primaryStyle.label}-stijl die daar goed bij passen.`
    : "";

  const sfeerNamen = palettes.map((palette) => `"${palette.name}"`).join(" en ");
  const sfeerWoord = palettes.length > 1 ? "sferen" : "sfeer";

  return `Je koos zelf voor de ${sfeerWoord} ${sfeerNamen}.${styleText}`;
}

export { composePersonalPalette, buildPaletteExplanation };

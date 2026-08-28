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

function findAnsweredOption(answers, questionId) {
  const question = QUESTIONS.find((item) => item.id === questionId);
  const optionId = answers?.[questionId];
  if (!question || !optionId || Array.isArray(optionId)) return null;
  return question.options.find((option) => option.id === optionId) ?? null;
}

function chosenPalette(answers) {
  const id = answers?.colorPreference;
  if (!id || typeof id !== "string") return null;
  return PALETTE_OPTIONS.find((palette) => palette.id === id) ?? null;
}

function explicitColorChoices(answers) {
  const palette = chosenPalette(answers);
  if (!palette) return [];
  return palette.colors.map((color) => ({ name: color.name, hex: color.hex, brightness: brightnessOf(color.hex) }));
}

function questionColorCandidate(answers, questionId) {
  const option = findAnsweredOption(answers, questionId);
  if (!option?.colorHex) return null;
  return { name: option.title, hex: option.colorHex, brightness: brightnessOf(option.colorHex) };
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
  const wallColor = questionColorCandidate(answers, "wallColor");
  const sofaColor = questionColorCandidate(answers, "sofaMaterial");
  const floorColor = questionColorCandidate(answers, "floor");
  const wallFinishColor = questionColorCandidate(answers, "wallFinish");
  const rugColor = questionColorCandidate(answers, "rug");
  const styleColors = styleColorCandidates(primaryStyle);
  const styleColorsByLuminance = [...styleColors].sort((a, b) => luminance(a.hex) - luminance(b.hex));
  const allCandidates = [...explicit, wallColor, sofaColor, floorColor, wallFinishColor, rugColor, ...styleColors].filter(Boolean);
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
  const palette = chosenPalette(answers);

  if (!palette) {
    return primaryStyle
      ? `Dit kleurenpalet is opgebouwd rond de tinten die passen bij jouw ${primaryStyle.label}-stijl.`
      : "";
  }

  const styleText = primaryStyle
    ? ` Daarom hebben we die sfeer gecombineerd met tinten uit jouw ${primaryStyle.label}-stijl die daar goed bij passen.`
    : "";

  return `Je koos zelf voor de sfeer "${palette.name}".${styleText}`;
}

export { composePersonalPalette, buildPaletteExplanation };

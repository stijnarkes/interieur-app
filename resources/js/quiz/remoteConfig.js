import { QUESTIONS } from "./data.js";
import { PALETTE_OPTIONS } from "./paletteData.js";
import { STYLE_PROFILES } from "./styleProfiles.js";

/**
 * Haalt de admin-bewerkbare inhoud (vragen/opties/sfeerpaletten/materialen) op bij
 * /api/quiz-config en muteert de bestaande QUESTIONS/PALETTE_OPTIONS/STYLE_PROFILES in place —
 * nooit de exports zelf herschrijven. ES-module-bindings zijn gedeelde objectreferenties, dus
 * elke consument (scoring.js, moodboard.js, questionStep.js, optionCard.js,
 * materialsSection.js, paletteEngine.js) leest deze wijziging automatisch mee, zonder zelf
 * aangepast te hoeven worden.
 *
 * Bij een falende of trage fetch gebeurt er niets: de quiz draait dan gewoon door op de
 * statische data.js/paletteData.js-inhoud die al in de bundel zit — inclusief de oorspronkelijke
 * 11 vragen en 8 sfeerpaletten in hun oorspronkelijke volgorde.
 */
async function loadRemoteQuizConfig() {
  try {
    const response = await fetch("/api/quiz-config", {
      headers: { Accept: "application/json" },
      signal: AbortSignal.timeout(3000),
    });
    if (!response.ok) return;

    const config = await response.json();
    // Volgorde is belangrijk: applyQuestions() herbouwt QUESTIONS (met lege options-lijsten),
    // applyOptions() vult die vervolgens.
    applyQuestions(config.questions);
    applyOptions(config.options);
    applyPalettes(config.palettes);
    applyMaterials(config.materials);
  } catch {
    // Same-origin fetch mislukt of duurt te lang — geen probleem, zie docblock hierboven.
  }
}

/**
 * Herbouwt QUESTIONS in de door de admin ingestelde volgorde (zie QuizOptionsPage) — dit is wat
 * "vragen herordenen" en "nieuwe vraag toevoegen" op de klant-quiz laat doorwerken. De
 * kleurvoorkeur-vraag (altijd de eerste, geen tegenhanger in quiz_questions) blijft ongemoeid.
 */
function applyQuestions(remoteQuestions) {
  if (!Array.isArray(remoteQuestions) || remoteQuestions.length === 0) return;

  const colorPreferenceQuestion = QUESTIONS.find((question) => question.type === "color-preference");
  const rebuilt = remoteQuestions.map((question) => ({
    id: question.id,
    section: question.section,
    title: question.title,
    options: [],
  }));

  QUESTIONS.length = 0;
  QUESTIONS.push(colorPreferenceQuestion, ...rebuilt);
}

/**
 * Vervangt de optielijst per vraag volledig door de opties die de admin daar actief voor heeft
 * staan — dus niet alleen de oorspronkelijke 6 overschrijven, maar ook admin-toegevoegde extra
 * keuzes tonen en gedeactiveerde opties laten verdwijnen. `image` komt altijd van de API mee
 * (zie QuizConfigController), dus dit werkt ook voor opties zonder tegenhanger in data.js.
 */
function applyOptions(remoteOptions) {
  if (!Array.isArray(remoteOptions) || remoteOptions.length === 0) return;

  const byQuestion = new Map();
  remoteOptions.forEach((option) => {
    const list = byQuestion.get(option.questionId) ?? [];
    list.push({
      id: option.id,
      title: option.title,
      image: option.image,
      primaryStyle: option.primaryStyle,
      ...(option.colorHex ? { colorHex: option.colorHex, colorFamily: option.colorFamily, colorTemperature: option.colorTemperature } : {}),
    });
    byQuestion.set(option.questionId, list);
  });

  QUESTIONS.forEach((question) => {
    if (question.type === "color-preference") return;
    if (!byQuestion.has(question.id)) return;

    question.options = byQuestion.get(question.id);
  });
}

/**
 * Herbouwt PALETTE_OPTIONS én de optielijst van de kleurvoorkeur-vraag zelf in de door de admin
 * ingestelde volgorde (zie QuizPalettesPage) — dit laat "paletten herordenen" en "palet/kleur
 * toevoegen of verwijderen" op de klant-quiz doorwerken. paletteEngine.js leest PALETTE_OPTIONS
 * rechtstreeks (los van de vraagstructuur) om het gekozen palet naar kleuren te vertalen.
 */
function applyPalettes(remotePalettes) {
  if (!Array.isArray(remotePalettes) || remotePalettes.length === 0) return;

  PALETTE_OPTIONS.length = 0;
  PALETTE_OPTIONS.push(...remotePalettes);

  const colorPreferenceQuestion = QUESTIONS.find((question) => question.type === "color-preference");
  if (colorPreferenceQuestion) {
    colorPreferenceQuestion.options = remotePalettes.map((palette) => ({
      id: palette.id,
      title: palette.name,
      colors: palette.colors,
    }));
  }
}

/**
 * Vervangt `materials` per stijl (zie "Materialen die bij jou passen" op de resultatenpagina)
 * door wat de admin daar per stijl voor heeft staan — zie ImageManagerPage. Een stijl zonder
 * eigen tegenhanger in de respons (bv. de fetch bevat toevallig geen rijen voor die stijl)
 * behoudt gewoon de statische materialen uit styleProfiles.js.
 */
function applyMaterials(remoteMaterialsByStyle) {
  if (!remoteMaterialsByStyle || typeof remoteMaterialsByStyle !== "object") return;

  STYLE_PROFILES.forEach((style) => {
    const materials = remoteMaterialsByStyle[style.key];
    if (Array.isArray(materials) && materials.length > 0) {
      style.materials = materials;
    }
  });
}

export { loadRemoteQuizConfig };

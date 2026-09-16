/**
 * Haalt het servergeberekende quizresultaat op bij /api/quiz-result — vervangt scoring.js's
 * client-side composeResult(). De server bepaalt scores/traits/ruimtes en primaire/secundaire/
 * tertiaire stijl (zie QuizScoringService); de client stuurt alleen de ruwe antwoorden en toont
 * wat terugkomt. Zelfde timeout-conventie als remoteConfig.js's fetchQuizConfig().
 *
 * @param {Record<string, string[]>} answers  questionId => geselecteerde option-id's
 * @param {string|null} [partnerClaimToken]  alleen gezet tijdens de geïsoleerde partnertest — zie
 *   QuizResultController::store()'s optionele partnerClaimToken-veld.
 * @returns {Promise<object>} de resultaatpayload, of gooit een Error bij een falende/tragere aanvraag
 */
async function fetchQuizResult(answers, partnerClaimToken = null) {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";

  const response = await fetch("/api/quiz-result", {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-TOKEN": csrf },
    body: JSON.stringify(partnerClaimToken ? { answers, partnerClaimToken } : { answers }),
    signal: AbortSignal.timeout(10000),
  });

  if (!response.ok) throw new Error(`Onverwachte status ${response.status}`);

  return response.json();
}

/**
 * Bewaart de 1-2 accentkleuren die de bezoeker koos bij het al berekende resultaat (zie
 * accentColorStep.js/quiz.js's renderResult()). De server herberekent zelf welke kleuren voor dit
 * resultaat toegestaan zijn en verwerpt de rest — deze call kan dus nooit een kleur laten
 * "wegschrijven" die niet bij het berekende stijlprofiel hoort.
 *
 * @param  string  resultUuid
 * @param  number[]  accentColorIds  1 of 2 accentkleur-id's
 * @returns {Promise<object>} de opgeslagen kleuren ({accentColors: [...]})
 */
async function saveAccentColors(resultUuid, accentColorIds) {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";

  const response = await fetch(`/api/quiz-result/${resultUuid}/accent-colors`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-TOKEN": csrf },
    body: JSON.stringify({ accentColorIds }),
    signal: AbortSignal.timeout(10000),
  });

  if (!response.ok) throw new Error(`Onverwachte status ${response.status}`);

  return response.json();
}

/**
 * Bewaart het basispalet dat de bezoeker koos bij het al berekende resultaat (zie
 * basePaletteStep.js/quiz.js's renderResult()) — analoog aan saveAccentColors() hierboven. De
 * server herberekent zelf of dit palet (nog) bij de primaire stijl van dít resultaat hoort.
 *
 * @param  string  resultUuid
 * @param  number  basePaletteId
 * @returns {Promise<object>} het opgeslagen palet ({basePalette: {...}})
 */
async function saveBasePalette(resultUuid, basePaletteId) {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";

  const response = await fetch(`/api/quiz-result/${resultUuid}/base-palette`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-TOKEN": csrf },
    body: JSON.stringify({ basePaletteId }),
    signal: AbortSignal.timeout(10000),
  });

  if (!response.ok) throw new Error(`Onverwachte status ${response.status}`);

  return response.json();
}

export { fetchQuizResult, saveBasePalette, saveAccentColors };

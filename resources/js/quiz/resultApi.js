/**
 * Haalt het servergeberekende quizresultaat op bij /api/quiz-result — vervangt scoring.js's
 * client-side composeResult(). De server bepaalt scores/traits/ruimtes en primaire/secundaire/
 * tertiaire stijl (zie QuizScoringService); de client stuurt alleen de ruwe antwoorden en toont
 * wat terugkomt. Zelfde timeout-conventie als remoteConfig.js's fetchQuizConfig().
 *
 * @param {Record<string, string[]>} answers  questionId => geselecteerde option-id's
 * @returns {Promise<object>} de resultaatpayload, of gooit een Error bij een falende/tragere aanvraag
 */
async function fetchQuizResult(answers) {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";

  const response = await fetch("/api/quiz-result", {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-TOKEN": csrf },
    body: JSON.stringify({ answers }),
    signal: AbortSignal.timeout(10000),
  });

  if (!response.ok) throw new Error(`Onverwachte status ${response.status}`);

  return response.json();
}

export { fetchQuizResult };

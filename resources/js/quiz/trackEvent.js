/**
 * Vuurt een trechter-event ("gestart"/"vraag bereikt") — puur informatief voor
 * App\Models\QuizEvent, dus altijd fire-and-forget: een mislukte/tragere aanvraag hier mag de
 * quiz zelf nooit vertragen of breken. Anders dan resultApi.js's functies gooit dit daarom bewust
 * nooit een Error naar de aanroeper.
 *
 * @param {string} name  "quiz_started" of "question_reached"
 * @param {string} [questionKey]  alleen bij "question_reached"
 */
function trackQuizEvent(name, questionKey) {
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";

    fetch("/api/quiz-events", {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-TOKEN": csrf },
      body: JSON.stringify(questionKey ? { name, questionKey } : { name }),
      signal: AbortSignal.timeout(5000),
      keepalive: true,
    }).catch(() => {});
  } catch {
    // localStorage/fetch kan in zeldzame gevallen (bv. privémodus) synchroon gooien — nooit de
    // quiz hierop laten stranden.
  }
}

export { trackQuizEvent };

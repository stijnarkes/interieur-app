/**
 * Centrale databron voor de interieurstijltest.
 *
 * SECTIONS is vaste, niet-admin-bewerkbare copy voor de twee onderdelen van de test. QUESTIONS
 * begint bewust leeg: de vragen/opties zijn volledig admin-beheerd (zie QuizOptionsPage) en
 * worden bij het laden van de pagina opgehaald via /api/quiz-config (zie remoteConfig.js en
 * app.js) — pas als dat gelukt is, kan de bezoeker de test starten. Zonder die live inhoud is er
 * geen zinvolle noodinhoud om op terug te vallen (welke vragen/foto's zouden dat moeten zijn?),
 * dus app.js toont in dat geval een foutmelding met een "opnieuw proberen"-knop in plaats van de
 * test te starten met verzonnen of verouderde vragen.
 */

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

// Gevuld door remoteConfig.js's applyQuestions()/applyOptions() zodra /api/quiz-config
// antwoordt — zie de docblock hierboven.
const QUESTIONS = [];

export { QUESTIONS, SECTIONS };

import { QUESTIONS, SECTIONS } from "./data.js";
import { RESULT_HERO_COPY, REPORT_TEASER_COPY, LEAD_FORM_COPY, ACCENT_COLOR_STEP_COPY } from "./copy.js";

/**
 * Haalt de admin-bewerkbare inhoud (vragen/opties) op bij /api/quiz-config en muteert de
 * bestaande QUESTIONS in place — nooit de export zelf herschrijven. ES-module-bindings zijn
 * gedeelde objectreferenties, dus elke consument (questionStep.js, optionCard.js) leest deze
 * wijziging automatisch mee, zonder zelf aangepast te hoeven worden. Stijlinhoud (materialen,
 * sfeerfoto's, adviesteksten) komt sinds de servergestuurde resultaatberekening niet meer via
 * deze route de klant-quiz binnen — zie QuizResultController/QuizScoringService, die dat direct
 * uit de database (style_profiles) leest.
 *
 * Bij een falende fetch (ook na de automatische retry hieronder, met een ruimer tweede
 * tijdslimiet voor het geval de server net wakker moest worden na een stille periode) valt de
 * quiz terug op de statische data.js-inhoud die al in de bundel zit. Die inhoud loopt inmiddels
 * qua vragen/opties uit de pas met wat een admin er intussen van gemaakt heeft (vragen
 * verwijderd/toegevoegd), dus app.js wacht bewust op het resultaat hiervan (of het lukt of niet)
 * voordat de bezoeker de test kan starten — anders zou een bezoeker die net iets te snel op
 * "Start" klikt, of een langzame/falende fetch treft, een deels verouderde vragenlijst kunnen
 * krijgen. Retourneert of het live ophalen is gelukt, zodat app.js dat kan tonen.
 */
async function fetchQuizConfig(timeoutMs) {
  const response = await fetch("/api/quiz-config", {
    headers: { Accept: "application/json" },
    signal: AbortSignal.timeout(timeoutMs),
  });
  if (!response.ok) throw new Error(`Onverwachte status ${response.status}`);

  return response.json();
}

/** @returns {Promise<boolean>} of het live ophalen gelukt is (false = teruggevallen op de statische bundel) */
async function loadRemoteQuizConfig() {
  let config;
  try {
    config = await fetchQuizConfig(6000);
  } catch (firstError) {
    try {
      // Tweede poging krijgt een ruimer tijdslimiet — een net "opgestart" serverinstantie
      // (na een stille periode) heeft soms wat langer nodig voor de allereerste aanvraag.
      config = await fetchQuizConfig(15000);
    } catch (secondError) {
      console.warn(
        "Kon /api/quiz-config niet ophalen, quiz valt terug op de meegebundelde standaardinhoud (admin-wijzigingen zijn nu niet zichtbaar).",
        secondError,
      );
      return false;
    }
  }

  // Volgorde is belangrijk: applyQuestions() herbouwt QUESTIONS (met lege options-lijsten),
  // applyOptions() vult die vervolgens.
  applyQuestions(config.questions);
  applyOptions(config.options);
  applyTransitionPhotos(config.transitionPhotos);
  applySections(config.sections);
  applyCopy(config.copy);

  return true;
}

/**
 * Herbouwt QUESTIONS in de door de admin ingestelde volgorde (zie QuizOptionsPage) — dit is wat
 * "vragen herordenen" en "nieuwe vraag toevoegen" op de klant-quiz laat doorwerken.
 */
function applyQuestions(remoteQuestions) {
  if (!Array.isArray(remoteQuestions) || remoteQuestions.length === 0) return;

  const rebuilt = remoteQuestions.map((question) => ({
    id: question.id,
    section: question.section,
    title: question.title,
    maxSelections: question.maxSelections ?? 1,
    imageDisplayMode: question.imageDisplayMode ?? "contain",
    options: [],
  }));

  QUESTIONS.length = 0;
  QUESTIONS.push(...rebuilt);
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
      styles: option.styles,
      ...(option.colorHex ? { colorHex: option.colorHex, colorFamily: option.colorFamily, colorTemperature: option.colorTemperature } : {}),
    });
    byQuestion.set(option.questionId, list);
  });

  QUESTIONS.forEach((question) => {
    if (!byQuestion.has(question.id)) return;

    question.options = byQuestion.get(question.id);
  });
}

/**
 * Vervangt de overgangsschermfoto per sectie door de echte URL uit de API — zelfde reden als
 * applyAtmosphere() hierboven, maar dan voor SECTIONS (zie sectionTransition.js/quiz.js, die
 * section.image gebruiken i.p.v. zelf een pad samen te stellen).
 */
function applyTransitionPhotos(remotePhotosBySection) {
  if (!remotePhotosBySection || typeof remotePhotosBySection !== "object") return;

  SECTIONS.forEach((section) => {
    const image = remotePhotosBySection[section.id];
    if (image) {
      section.image = image;
    }
  });
}

/**
 * Vervangt de titel/tagline/afsluitzin/knoptekst per overgangsscherm door de admin-beheerde
 * inhoud (zie TekstenPage/QuizTransitionSection) — zelfde in-place-mutatietechniek als
 * applyTransitionPhotos() hierboven, maar dan voor tekst i.p.v. de foto.
 */
function applySections(remoteSectionsById) {
  if (!remoteSectionsById || typeof remoteSectionsById !== "object") return;

  SECTIONS.forEach((section) => {
    const remote = remoteSectionsById[section.id];
    if (!remote) return;

    if (remote.title) section.title = remote.title;
    if (remote.tagline) section.tagline = remote.tagline;
    if (remote.wrapUp) section.wrapUp = remote.wrapUp;
    if (remote.cta) section.cta = remote.cta;
  });
}

/**
 * Vervangt de stijl-onafhankelijke resultatenpagina-teksten (zie copy.js) door de admin-beheerde
 * inhoud (TekstenPage/SiteContent) — Object.assign muteert elk *_COPY-object in place, zodat
 * resultHero.js/reportTeaser.js/lead.js niets zelf hoeven te doen om dit mee te krijgen.
 */
function applyCopy(remoteCopy) {
  if (!remoteCopy || typeof remoteCopy !== "object") return;

  if (remoteCopy.resultHero) Object.assign(RESULT_HERO_COPY, remoteCopy.resultHero);
  if (remoteCopy.reportTeaser) Object.assign(REPORT_TEASER_COPY, remoteCopy.reportTeaser);
  if (remoteCopy.leadForm) Object.assign(LEAD_FORM_COPY, remoteCopy.leadForm);
  if (remoteCopy.accentColorStep) Object.assign(ACCENT_COLOR_STEP_COPY, remoteCopy.accentColorStep);
}

export { loadRemoteQuizConfig };

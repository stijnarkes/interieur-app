import { QUESTIONS, SECTIONS } from "./data.js";
import { STYLE_PROFILES } from "./styleProfiles.js";

/**
 * Haalt de admin-bewerkbare inhoud (vragen/opties/materialen) op bij /api/quiz-config en muteert
 * de bestaande QUESTIONS/STYLE_PROFILES in place — nooit de exports zelf herschrijven.
 * ES-module-bindings zijn gedeelde objectreferenties, dus elke consument (scoring.js,
 * questionStep.js, optionCard.js, materialsSection.js) leest deze wijziging automatisch mee,
 * zonder zelf aangepast te hoeven worden.
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
  applyMaterials(config.materials);
  applyAtmosphere(config.atmosphere);
  applyTransitionPhotos(config.transitionPhotos);

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

/**
 * Vervangt de sfeerfoto (hero op de resultatenpagina) per stijl door de echte, disk-onafhankelijke
 * URL uit de API — zonder dit zou styleProfiles.js's hardcoded `/images/interior/atmosphere/...`-pad
 * ervan uitgaan dat die foto's altijd onder public/ van deze site staan, wat niet meer klopt zodra
 * de opslag naar S3 verhuist (zie QuizImageManifest).
 */
function applyAtmosphere(remoteAtmosphereByStyle) {
  if (!remoteAtmosphereByStyle || typeof remoteAtmosphereByStyle !== "object") return;

  STYLE_PROFILES.forEach((style) => {
    const heroImage = remoteAtmosphereByStyle[style.key];
    if (heroImage) {
      style.heroImage = heroImage;
    }
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

export { loadRemoteQuizConfig };

// v2: answers[questionId] is nu altijd een array van option-id's (i.p.v. een losse string), voor
// vragen met een instelbaar maximum aantal keuzes — zie toggleAnswer(). De versiebump zorgt dat
// een oude (scalar) sessie in localStorage niet per ongeluk met het nieuwe formaat botst; een
// bezoeker die midden in een oude sessie zit begint dan gewoon fris.
const STORAGE_KEY = "interieur_stijltest_v2";

function loadState() {
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY);
    if (!raw) return { started: false, step: 0, answers: {}, completed: false };
    const parsed = JSON.parse(raw);
    return {
      started: Boolean(parsed.started),
      step: Number(parsed.step) || 0,
      answers: parsed.answers && typeof parsed.answers === "object" ? parsed.answers : {},
      completed: Boolean(parsed.completed),
    };
  } catch {
    return { started: false, step: 0, answers: {}, completed: false };
  }
}

function persist(state) {
  try {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
  } catch {
    // localStorage niet beschikbaar (bv. privémodus) — quiz werkt dan zonder refresh-persistentie.
  }
}

function createQuizState() {
  let state = loadState();

  return {
    get() {
      return state;
    },
    start() {
      state = { ...state, started: true, step: 0 };
      persist(state);
    },
    // Bij maxSelections===1 vervangt een nieuwe keuze direct de vorige (huidig
    // radiobutton-gedrag). Bij een hoger maximum: al gekozen -> loslaten; nog ruimte -> toevoegen;
    // vol -> genegeerd (kaarten tonen dit als disabled, zie optionCard.js).
    toggleAnswer(questionId, optionId, maxSelections = 1) {
      const current = state.answers[questionId] || [];
      let next;

      if (current.includes(optionId)) {
        next = current.filter((id) => id !== optionId);
      } else if (maxSelections <= 1) {
        next = [optionId];
      } else if (current.length < maxSelections) {
        next = [...current, optionId];
      } else {
        return;
      }

      state = { ...state, answers: { ...state.answers, [questionId]: next } };
      persist(state);
    },
    goToStep(step) {
      state = { ...state, step };
      persist(state);
    },
    complete() {
      state = { ...state, completed: true };
      persist(state);
    },
    reset() {
      state = { started: false, step: 0, answers: {}, completed: false };
      persist(state);
    },
  };
}

export { createQuizState };

const STORAGE_KEY = "interieur_stijltest_v1";

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
    setAnswer(questionId, optionId) {
      state = { ...state, answers: { ...state.answers, [questionId]: optionId } };
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

import { QUESTIONS, SECTIONS } from "./data.js";
import { fetchQuizResult } from "./resultApi.js";
import { createQuizState } from "./state.js";
import { createSectionStepper } from "./components/sectionStepper.js";
import { createQuizProgress } from "./components/quizProgress.js";
import { renderSectionTransition } from "./components/sectionTransition.js";
import { renderQuestionStep } from "./components/questionStep.js";
import { renderStyleResult } from "./components/styleResult.js";
import { renderReportTeaser } from "./components/reportTeaser.js";
import { renderLeadForm } from "./components/lead.js";
import { createLoadingScene } from "./components/loadingScene.js";

/**
 * Downloadt een foto onopvallend op de achtergrond, zodat de browser 'm al gecachet heeft tegen
 * de tijd dat de gebruiker 'm daadwerkelijk te zien krijgt. Deze foto's zijn bij een eerste
 * bezoek vaak nog niet aanwezig in Cloudflare's edge-cache, dus het eerste ophalen ervan duurt
 * merkbaar lang — door dat alvast te doen terwijl de bezoeker nog op het scherm ervóór zit, valt
 * die eenmalige trage ophaalslag niet meer samen met de daadwerkelijke schermwissel.
 */
function prefetchImage(url) {
  if (!url) return;
  new Image().src = url;
}

/**
 * Zoals prefetchImage(), maar afgewacht — gebruikt op het ene moment waar dat zinvol is (tijdens
 * de laadscene na het klikken op de startknop, zie de click-handler onderin initQuiz()): de
 * bezoeker wacht daar toch al even, dus die tijd wordt hier echt benut om de eerste overgangsfoto
 * klaar te zetten i.p.v.
 * een fire-and-forget die evengoed nog kan lopen zodra het overgangsscherm verschijnt. Een eigen,
 * korte timeout voorkomt dat een trage/kapotte foto de bottleneck wordt — het overgangsscherm valt
 * zelf al netjes terug op "geen foto" (zie sectionTransition.js), dus hier hoeft nooit op gewacht
 * te worden voorbij deze cap.
 */
function prefetchImagePromise(url, timeoutMs) {
  if (!url) return Promise.resolve();

  return new Promise((resolve) => {
    const img = new Image();
    const done = () => resolve();
    img.addEventListener("load", done, { once: true });
    img.addEventListener("error", done, { once: true });
    img.src = url;
    setTimeout(done, timeoutMs);
  });
}

function prefetchQuestionImages(question) {
  question.options?.forEach((option) => prefetchImage(option.image));
}

function prefetchTransitionPhoto(section) {
  prefetchImage(section.image);
}

function sectionIndexOf(step) {
  return SECTIONS.findIndex((section) => section.id === QUESTIONS[step].section);
}

function questionsInSection(sectionId) {
  return QUESTIONS.filter((question) => question.section === sectionId);
}

function positionInSection(step) {
  const question = QUESTIONS[step];
  const siblings = questionsInSection(question.section);
  return {
    index: siblings.findIndex((sibling) => sibling.id === question.id),
    total: siblings.length,
  };
}

function initQuiz(root) {
  const els = {
    start: root.querySelector("#quizStart"),
    startBtn: root.querySelector("#startQuizBtn"),
    loading: root.querySelector("#quizLoading"),
    loadingMount: root.querySelector("#quizLoadingMount"),
    stepperWrap: root.querySelector("#stepperWrap"),
    journey: root.querySelector("#quizJourney"),
    stepperMount: root.querySelector("#sectionStepperMount"),
    transition: root.querySelector("#quizTransition"),
    steps: root.querySelector("#quizSteps"),
    progressMount: root.querySelector("#quizProgressMount"),
    stepMount: root.querySelector("#quizStepMount"),
    backBtn: root.querySelector("#quizBackBtn"),
    nextBtn: root.querySelector("#quizNextBtn"),
    result: root.querySelector("#quizResult"),
    styleResultMount: root.querySelector("#styleResultMount"),
    reportTeaserMount: root.querySelector("#reportTeaserMount"),
    leadMount: root.querySelector("#quizLeadMount"),
    restartBtn: root.querySelector("#restartQuizBtn"),
  };

  const state = createQuizState();
  // Voorkomt dat meerdere klikken op de startknop de test meerdere keren starten — de knop wordt
  // ook meteen uitgeschakeld, maar deze vlag dekt ook een eventuele dubbele event-afvuring af.
  let starting = false;
  // De stepper toont, naast de echte vraag-onderdelen, ook "Jouw woonstijl" als afsluitende
  // stap — die licht pas op zodra de resultaatpagina wordt getoond (zie renderResult()).
  // Deze extra stap bestaat alleen visueel in de stepper en heeft geen eigen vragen: de
  // sectie-logica hieronder blijft uitsluitend op SECTIONS (de echte vraaggroepen) werken.
  const stepperSections = [...SECTIONS, { id: "result", title: "Jouw woonstijl" }];
  const stepper = createSectionStepper(stepperSections, QUESTIONS.length);
  els.stepperMount.appendChild(stepper.element);
  const progress = createQuizProgress();
  els.progressMount.appendChild(progress.element);

  /** Elke schermovergang begint bovenaan — anders land je op de resterende scrollpositie van
   *  het vorige scherm, wat op mobiel al snel midden in de nieuwe vraag/pagina uitkomt. */
  function scrollToTop() {
    window.scrollTo({ top: 0, behavior: "auto" });
  }

  function showScreen(screen) {
    els.start.hidden = screen !== "start";
    els.loading.hidden = screen !== "loading";
    els.stepperWrap.hidden = !(screen === "transition" || screen === "result");
    els.journey.hidden = !(screen === "transition" || screen === "steps");
    els.transition.hidden = screen !== "transition";
    els.steps.hidden = screen !== "steps";
    els.result.hidden = screen !== "result";
    scrollToTop();
  }

  /**
   * Wacht op de zachte fade-out van het startscherm (zie .quiz-start.is-leaving in app.css)
   * vóórdat de laadscene verschijnt — met een korte fallback-timeout zodat dit nooit blijft
   * hangen als transitionend om wat voor reden dan ook niet vuurt (o.a. bij
   * prefers-reduced-motion: reduce, waar er helemaal geen transitie is ingesteld).
   */
  function waitForStartFadeOut() {
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
      return Promise.resolve();
    }

    return new Promise((resolve) => {
      let done = false;
      const finish = () => {
        if (done) return;
        done = true;
        els.start.removeEventListener("transitionend", finish);
        resolve();
      };
      els.start.addEventListener("transitionend", finish, { once: true });
      setTimeout(finish, 350);
    });
  }

  /**
   * `step` is altijd de eerste vraag van het onderdeel waar dit overgangsscherm naartoe leidt —
   * zowel bij de start van een nieuw onderdeel (vanuit "Volgende") als wanneer je via de
   * terugknop op die eerste vraag opnieuw op dit scherm belandt. Het "terug"-doel volgt daaruit
   * vanzelf: vóór het eerste onderdeel was je op het startscherm, daarna was je op de laatste
   * vraag van het vorige onderdeel (altijd direct de rij ervoor, want QUESTIONS staat op
   * sectievolgorde — zie sectionIndexOf/positionInSection).
   */
  function showSectionTransition(step) {
    const sectionIndex = sectionIndexOf(step);
    stepper.update(sectionIndex, step);
    prefetchQuestionImages(QUESTIONS[step]);
    renderSectionTransition(els.transition, {
      sectionIndex,
      totalSections: stepperSections.length,
      section: SECTIONS[sectionIndex],
      onContinue: () => {
        showScreen("steps");
        renderStep();
      },
      onBack: sectionIndex === 0
        ? () => showScreen("start")
        : () => {
            state.goToStep(step - 1);
            showScreen("steps");
            renderStep();
          },
    });
    showScreen("transition");
  }

  function renderStep({ scroll = false } = {}) {
    const { step, answers } = state.get();
    const question = QUESTIONS[step];
    const sectionIndex = sectionIndexOf(step);
    const { index, total } = positionInSection(step);

    stepper.update(sectionIndex, step);
    progress.update(SECTIONS[sectionIndex].title, index + 1, total);

    renderQuestionStep(els.stepMount, question, answers[question.id] || [], (optionId) => {
      state.toggleAnswer(question.id, optionId, question.maxSelections ?? 1);
      renderStep();
    });
    updateNextButton();
    if (scroll) scrollToTop();

    const nextStep = step + 1;
    if (nextStep < QUESTIONS.length) {
      if (QUESTIONS[nextStep].section !== question.section) {
        prefetchTransitionPhoto(SECTIONS[sectionIndexOf(nextStep)]);
      } else {
        prefetchQuestionImages(QUESTIONS[nextStep]);
      }
    }
  }

  function updateNextButton() {
    const { step, answers } = state.get();
    const question = QUESTIONS[step];
    els.nextBtn.disabled = !(answers[question.id]?.length > 0);
    els.nextBtn.textContent = step === QUESTIONS.length - 1 ? "Bekijk mijn resultaat" : "Volgende";
  }

  function restart() {
    state.reset();
    starting = false;
    els.startBtn.disabled = false;
    els.start.classList.remove("is-leaving");
    showScreen("start");
  }

  /**
   * Toont eerst een laadstatus in het heldenblok, haalt dan het servergeberekende resultaat op
   * (zie resultApi.js) en rendert pas daarna de rest van de resultatenpagina. Lukt het ophalen
   * niet, dan krijgt de bezoeker een foutmelding met een "opnieuw proberen"-knop i.p.v. een lege
   * of kapotte pagina — zelfde aanpak als app.js hanteert voor het laden van /api/quiz-config.
   */
  async function renderResult() {
    stepper.update(stepperSections.length, QUESTIONS.length);
    state.complete();
    showScreen("result");

    els.styleResultMount.innerHTML = "";
    els.reportTeaserMount.innerHTML = "";
    els.leadMount.innerHTML = "";

    const loading = document.createElement("p");
    loading.className = "section-intro";
    loading.textContent = "Even geduld, we stellen je persoonlijke resultaat samen...";
    els.styleResultMount.appendChild(loading);

    const { answers } = state.get();

    let result;
    try {
      result = await fetchQuizResult(answers);
    } catch (error) {
      els.styleResultMount.innerHTML = "";
      const errorMessage = document.createElement("p");
      errorMessage.className = "section-intro";
      errorMessage.textContent = "Je resultaat kon niet worden opgehaald. Probeer het opnieuw.";
      els.styleResultMount.appendChild(errorMessage);

      const retryBtn = document.createElement("button");
      retryBtn.type = "button";
      retryBtn.className = "btn btn-primary";
      retryBtn.textContent = "Opnieuw proberen";
      retryBtn.addEventListener("click", renderResult);
      els.styleResultMount.appendChild(retryBtn);
      return;
    }

    renderStyleResult(els.styleResultMount, result);
    renderReportTeaser(els.reportTeaserMount, { result });
    renderLeadForm(els.leadMount, { result });
  }

  /**
   * Start pas na een klik, nooit automatisch — toont eerst de opbouw-animatie (zie
   * loadingScene.js) op hetzelfde achtergrondkleurverloop als het startscherm, en laat pas
   * daarna het eerste overgangsscherm verschijnen. De animatie duurt zelf ongeveer 1,2-1,4
   * seconde; die tijd wordt ondertussen ook echt benut om de eerste overgangsfoto te laden. Duurt
   * dat onverhoopt langer, dan blijft de opgebouwde kamer met een subtiele puls zichtbaar (regelt
   * de component zelf) i.p.v. de opbouw te herhalen.
   */
  els.startBtn.addEventListener("click", async () => {
    if (starting) return;
    starting = true;
    els.startBtn.disabled = true;

    state.reset();
    state.start();

    els.start.classList.add("is-leaving");
    await waitForStartFadeOut();

    els.loadingMount.innerHTML = "";
    const scene = createLoadingScene({ heading: "Jouw woonstijl begint hier" });
    els.loadingMount.appendChild(scene.element);
    showScreen("loading");

    await Promise.all([scene.start(), prefetchImagePromise(SECTIONS[0].image, 4000)]);

    showSectionTransition(0);
  });

  els.nextBtn.addEventListener("click", () => {
    const { step } = state.get();
    if (step >= QUESTIONS.length - 1) {
      renderResult();
      return;
    }
    const nextStep = step + 1;
    state.goToStep(nextStep);
    if (QUESTIONS[nextStep].section !== QUESTIONS[step].section) {
      showSectionTransition(nextStep);
    } else {
      renderStep({ scroll: true });
    }
  });

  els.backBtn.addEventListener("click", () => {
    const { step } = state.get();
    const { index } = positionInSection(step);
    if (index === 0) {
      showSectionTransition(step);
      return;
    }
    state.goToStep(step - 1);
    renderStep({ scroll: true });
  });

  els.restartBtn.addEventListener("click", restart);

  // Landt altijd op het startscherm; "Start de stijltest" begint altijd fris.
  showScreen("start");
  // Alvast de eerste overgangsfoto ophalen terwijl de bezoeker de intro leest — dat is het
  // eerstvolgende scherm na een klik op "Start de stijlanalyse".
  prefetchTransitionPhoto(SECTIONS[0]);
}

export { initQuiz };

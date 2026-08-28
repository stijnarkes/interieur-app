import { QUESTIONS, SECTIONS } from "./data.js";
import { composeResult } from "./scoring.js";
import { createQuizState } from "./state.js";
import { createSectionStepper } from "./components/sectionStepper.js";
import { createQuizProgress } from "./components/quizProgress.js";
import { renderSectionTransition } from "./components/sectionTransition.js";
import { renderQuestionStep } from "./components/questionStep.js";
import { renderColorQuestionStep } from "./components/colorQuestionStep.js";
import { renderStyleResult } from "./components/styleResult.js";
import { renderReportTeaser } from "./components/reportTeaser.js";
import { renderLeadForm } from "./components/lead.js";

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

function prefetchQuestionImages(question) {
  question.options?.forEach((option) => prefetchImage(option.image));
}

function prefetchTransitionPhoto(section) {
  prefetchImage(`/images/interior/transitions/${section.id}.webp`);
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
    els.stepperWrap.hidden = !(screen === "transition" || screen === "result");
    els.journey.hidden = !(screen === "transition" || screen === "steps");
    els.transition.hidden = screen !== "transition";
    els.steps.hidden = screen !== "steps";
    els.result.hidden = screen !== "result";
    scrollToTop();
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

    const renderer = question.type === "color-preference" ? renderColorQuestionStep : renderQuestionStep;
    renderer(els.stepMount, question, answers[question.id], (optionId) => {
      state.setAnswer(question.id, optionId);
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
    els.nextBtn.disabled = !answers[question.id];
    els.nextBtn.textContent = step === QUESTIONS.length - 1 ? "Bekijk mijn resultaat" : "Volgende";
  }

  function restart() {
    state.reset();
    showScreen("start");
  }

  function renderResult() {
    const { answers } = state.get();
    const result = composeResult(answers);
    renderStyleResult(els.styleResultMount, result);
    renderReportTeaser(els.reportTeaserMount, { result, answers });
    renderLeadForm(els.leadMount, { result, answers });
    // stepperSections.length (i.p.v. SECTIONS.length) ligt voorbij alle echte indexen, dus ook
    // "Jouw woonstijl" zelf krijgt hierdoor is-done (groen) in plaats van is-active (bruin) — de
    // hele test is immers afgerond, er is geen "huidige stap" meer.
    stepper.update(stepperSections.length, QUESTIONS.length);
    state.complete();
    showScreen("result");
  }

  els.startBtn.addEventListener("click", () => {
    state.reset();
    state.start();
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

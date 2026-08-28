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

  function showScreen(screen) {
    els.start.hidden = screen !== "start";
    els.stepperWrap.hidden = !(screen === "transition" || screen === "result");
    els.journey.hidden = !(screen === "transition" || screen === "steps");
    els.transition.hidden = screen !== "transition";
    els.steps.hidden = screen !== "steps";
    els.result.hidden = screen !== "result";
  }

  function showSectionTransition(step) {
    const sectionIndex = sectionIndexOf(step);
    stepper.update(sectionIndex, step);
    renderSectionTransition(els.transition, {
      sectionIndex,
      totalSections: stepperSections.length,
      section: SECTIONS[sectionIndex],
      onContinue: () => {
        showScreen("steps");
        renderStep();
      },
    });
    showScreen("transition");
    els.journey.scrollIntoView({ behavior: "auto", block: "start" });
  }

  function renderStep() {
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
    els.backBtn.disabled = step === 0;
    updateNextButton();
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
    stepper.update(SECTIONS.length, QUESTIONS.length);
    state.complete();
    showScreen("result");
    els.result.scrollIntoView({ behavior: "auto", block: "start" });
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
      renderStep();
    }
  });

  els.backBtn.addEventListener("click", () => {
    const { step } = state.get();
    if (step === 0) return;
    state.goToStep(step - 1);
    renderStep();
  });

  els.restartBtn.addEventListener("click", restart);

  // Landt altijd op het startscherm; "Start de stijltest" begint altijd fris.
  showScreen("start");
}

export { initQuiz };

import { QUESTIONS, SECTIONS } from "./data.js";
import { fetchQuizResult, completePartnerResult } from "./resultApi.js";
import { createQuizState } from "./state.js";
import { createSectionStepper } from "./components/sectionStepper.js";
import { createQuizProgress } from "./components/quizProgress.js";
import { renderSectionTransition } from "./components/sectionTransition.js";
import { renderQuestionStep } from "./components/questionStep.js";
import { renderStyleResult } from "./components/styleResult.js";
import { renderBasePaletteStep } from "./components/basePaletteStep.js";
import { renderAccentColorStep } from "./components/accentColorStep.js";
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

// Minimale hoogte die de stage sowieso krijgt tijdens de startanimatie, ook op een korte
// viewport — genoeg voor de opbouw-illustratie zelf plus wat ademruimte om te centreren.
const STAGE_HEIGHT_FLOOR = 400;
// Marge t.o.v. de viewporthoogte: voorkomt dat de gereserveerde hoogte op een klein
// browservenster (bv. laptop) groter wordt dan wat sowieso al zichtbaar/scrollbaar is.
const STAGE_HEIGHT_VIEWPORT_MARGIN = 40;

// Duur van de schuifovergang tussen twee vragen (zie swapStepPanel()) — rustig (~350ms), geen
// stuiter/rotatie. Moet gelijk blijven aan de transition-duur van .quiz-step-transitioning in
// app.css.
const STEP_TRANSITION_MS = 350;

/**
 * @param {HTMLElement} root
 * @param {object} [options]
 * @param {string|null} [options.partnerClaimToken] — alleen gezet wanneer dit de geïsoleerde
 *   partnertest is (zie resources/js/partner.js). De bezoeker doorloopt hiermee exact dezelfde
 *   test/basispalet-/accentkleurstappen als de individuele quiz; pas ná die stappen (op het moment
 *   dat anders het uitnodigingsblok zou tonen) koppelt completePartnerResult() dit resultaat aan de
 *   juiste partner_participants-rij (zie QuizResultController). In de normale, individuele quiz
 *   altijd null/ongezet.
 * @param {(result: object, info: {completed: boolean}) => void} [options.onCompleted] — vuurt pas
 *   nadat het VOLLEDIGE resultaat vaststaat (inclusief een eventueel gekozen basispalet/
 *   accentkleuren) én de server dit aan de partnerkoppeling heeft vastgeplakt — nooit eerder, want
 *   dan zou de gezamenlijke vergelijking deze deelnemer altijd zonder gekozen kleuren laten zien.
 *   `info.completed` is `false` als die laatste serverkoppeling onverwacht mislukte. Vervangt hier
 *   het (voor de partnertest zinloze) individuele uitnodigingsblok — zie resources/js/partner.js.
 */
function initQuiz(root, options = {}) {
  const { partnerClaimToken = null, onCompleted = null } = options;
  const els = {
    stage: root.querySelector("#quizStage"),
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
    stepViewport: root.querySelector("#quizStepViewport"),
    stepPanel: root.querySelector("#quizStepPanel"),
    stepMount: root.querySelector("#quizStepMount"),
    backBtn: root.querySelector("#quizBackBtn"),
    nextBtn: root.querySelector("#quizNextBtn"),
    result: root.querySelector("#quizResult"),
    styleResultMount: root.querySelector("#styleResultMount"),
    basePaletteMount: root.querySelector("#basePaletteMount"),
    accentColorMount: root.querySelector("#accentColorMount"),
    reportTeaserMount: root.querySelector("#reportTeaserMount"),
    leadCard: root.querySelector("#quizLeadCard"),
    leadMount: root.querySelector("#quizLeadMount"),
    partnerInviteMount: root.querySelector("#partnerInviteMount"),
    restartBtn: root.querySelector("#restartQuizBtn"),
  };

  const state = createQuizState();
  // Voorkomt dat meerdere klikken op de startknop de test meerdere keren starten — de knop wordt
  // ook meteen uitgeschakeld, maar deze vlag dekt ook een eventuele dubbele event-afvuring af.
  let starting = false;
  // Volgt de lopende vraagovergang (zie swapStepPanel()) zodat razendsnel doorklikken die netjes
  // afrondt i.p.v. twee overlappende overgangen tegelijk te laten lopen.
  let stepTransitionTimer = null;
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

  /**
   * Meet de daadwerkelijk gerenderde hoogte van het (nog zichtbare) startscherm en zet die als
   * minimumhoogte op de gedeelde stage — vóórdat het startscherm wegvaagt. Zo blijft de kaart
   * tijdens laden/overgang minstens even hoog als het startscherm zelf (geen inklappende
   * lay-out), maar nooit hoger dan nodig is voor de huidige viewport (geen nodeloos lege ruimte
   * op een kort browservenster). Puur op meting gebaseerd — geen vaste pixelwaarde — dus dit past
   * zich vanzelf aan desktop én mobiel aan.
   */
  function reserveStageHeight() {
    const startHeight = els.start.getBoundingClientRect().height;
    const viewportCap = Math.max(window.innerHeight - STAGE_HEIGHT_VIEWPORT_MARGIN, STAGE_HEIGHT_FLOOR);
    const reserved = Math.min(Math.max(startHeight, STAGE_HEIGHT_FLOOR), viewportCap);
    els.stage.style.minHeight = `${reserved}px`;
  }

  /** Geeft de gereserveerde hoogte weer vrij zodra de echte quizinhoud (vanaf de eerste vraag)
   *  het overneemt — anders zou elke volgende stap onnodig veel lege ruimte overhouden. Buiten de
   *  startanimatie om is dit een no-op (er staat dan toch al geen inline hoogte). */
  function releaseStageHeight() {
    els.stage.style.minHeight = "";
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
        // Vanaf hier is de echte quizinhoud leidend voor de hoogte — de reservering die tijdens
        // de startanimatie is gezet (zie reserveStageHeight()) mag dan los, anders houdt elke
        // vraag onnodig de hoogte van het startscherm aan. Bij een latere sectie-overgang (niet
        // de allereerste) staat er toch al geen inline hoogte meer, dus dan is dit een no-op.
        releaseStageHeight();
        showScreen("steps");
        renderStep();
      },
      onBack: sectionIndex === 0
        // Volledige restart() i.p.v. alleen showScreen("start"): anders blijven de
        // "starting"-vlag, de uitgeschakelde startknop en de is-leaving-fade-klasse hangen, en
        // lijkt "Ontdek mijn woonstijl" na teruggaan defect. Op dit punt zijn er nog geen
        // antwoorden gegeven, dus de state.reset() binnen restart() verandert hier niets extra's.
        ? restart
        : () => {
            state.goToStep(step - 1);
            showScreen("steps");
            renderStep();
          },
    });
    showScreen("transition");
  }

  /**
   * Ruimt een eventueel nog lopende (of onafgemaakte) kaartovergang direct op: verwijdert een
   * achtergebleven "geest" (zie hieronder) en zet #quizStepViewport/#quizStepPanel terug in hun
   * normale, niet-animerende staat. Wordt aan het begin van élke overgang aangeroepen (ook de
   * instante variant) zodat razendsnel doorklikken nooit twee overlappende overgangen tegelijk
   * laat lopen.
   */
  function finishStepTransition() {
    if (stepTransitionTimer) {
      clearTimeout(stepTransitionTimer);
      stepTransitionTimer = null;
    }
    els.stepViewport.querySelectorAll(".quiz-step-ghost").forEach((ghost) => ghost.remove());
    els.stepViewport.classList.remove("is-animating-step");
    els.stepViewport.style.height = "";
    els.stepPanel.classList.remove("quiz-step-transitioning", "quiz-step-offset-left", "quiz-step-offset-right");
  }

  /**
   * Wisselt de inhoud van #quizStepMount, met een schuif+fade-overgang van de hele kaart (vraag,
   * afbeeldingen én de Terug-/Volgende-knoppen samen) wanneer `direction` is meegegeven ("forward"
   * voor Volgende, "back" voor Terug). Zonder richting (eerste vraag van een sectie, of opnieuw
   * renderen na het kiezen van een optie) wisselt de inhoud instant — dat is geen "vraagovergang"
   * maar een directe weergave.
   *
   * De Terug-/Volgende-knoppen zelf (#quizBackBtn/#quizNextBtn) blijven altijd exact dezelfde,
   * al bestaande DOM-elementen met hun al gekoppelde click-handlers — nooit gedupliceerd. Wat
   * wegschuift is een niet-interactieve "geest": een `cloneNode(true)` van de kaart zoals die er
   * vóór de wissel uitzag (dus met de oude vraag én de toen geldende knopstatus), met
   * `pointer-events: none`/`inert` zodat hij nooit aan te klikken is. Zo is er tijdens de overgang
   * altijd precies één klikbare knoppenset (nooit dubbele navigatie), terwijl het toch oogt alsof
   * de hele kaart naar links/rechts wegschuift.
   */
  function swapStepPanel(newContent, direction) {
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    finishStepTransition();

    if (!direction || reducedMotion) {
      els.stepMount.innerHTML = "";
      els.stepMount.appendChild(newContent);
      return;
    }

    // Meet de hoogte van de huidige kaart terwijl die nog normaal in de flow staat (nog geen
    // .is-animating-step) — anders klapt de viewport in zodra geest en kaart zo meteen absoluut
    // gepositioneerd over elkaar staan.
    const previousHeight = els.stepPanel.getBoundingClientRect().height;

    const ghost = els.stepPanel.cloneNode(true);
    ghost.classList.add("quiz-step-ghost");
    ghost.removeAttribute("id");
    ghost.querySelectorAll("[id]").forEach((el) => el.removeAttribute("id"));
    ghost.setAttribute("aria-hidden", "true");
    ghost.setAttribute("inert", "");

    els.stepViewport.classList.add("is-animating-step");
    els.stepViewport.style.height = `${previousHeight}px`;
    els.stepViewport.appendChild(ghost);

    // Echte inhoud wisselt instant, ín hetzelfde, altijd-klikbare paneel — zie docblok hierboven.
    // De knopstatus (zie updateNextButton()) wordt door de aanroeper (renderStep()) direct hierna
    // bijgewerkt; de geest hierboven is dan al gemaakt en toont dus terecht nog de oude staat.
    els.stepMount.innerHTML = "";
    els.stepMount.appendChild(newContent);

    const exitClass = direction === "back" ? "quiz-step-offset-right" : "quiz-step-offset-left";
    const enterFromClass = direction === "back" ? "quiz-step-offset-left" : "quiz-step-offset-right";

    els.stepPanel.classList.add(enterFromClass);

    // Nu de kaart absoluut gepositioneerd is (via .is-animating-step > *) geeft dit alsnog de
    // natuurlijke inhoudshoogte terug, én forceert de reflow die nodig is om de beginstaat
    // hierboven (nog zonder transition) daadwerkelijk te laten "vastklikken" vóórdat de overgang
    // hieronder start — anders wordt de sprong naar de eindstaat niet als animatie gezien.
    const newHeight = els.stepPanel.getBoundingClientRect().height;
    els.stepViewport.style.height = `${Math.max(previousHeight, newHeight)}px`;

    ghost.classList.add("quiz-step-transitioning", exitClass);
    els.stepPanel.classList.add("quiz-step-transitioning");
    els.stepPanel.classList.remove(enterFromClass);

    stepTransitionTimer = setTimeout(() => {
      finishStepTransition();
    }, STEP_TRANSITION_MS);
  }

  function renderStep({ scroll = false, direction = null } = {}) {
    const { step, answers } = state.get();
    const question = QUESTIONS[step];
    const sectionIndex = sectionIndexOf(step);
    const { index, total } = positionInSection(step);

    stepper.update(sectionIndex, step);
    progress.update(SECTIONS[sectionIndex].title, index + 1, total);

    const stepContent = document.createElement("div");
    renderQuestionStep(stepContent, question, answers[question.id] || [], (optionId) => {
      state.toggleAnswer(question.id, optionId, question.maxSelections ?? 1);
      renderStep();
    });
    swapStepPanel(stepContent, direction);
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
    releaseStageHeight();
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
    els.basePaletteMount.innerHTML = "";
    els.accentColorMount.innerHTML = "";
    els.reportTeaserMount.innerHTML = "";
    els.leadMount.innerHTML = "";
    els.leadCard.hidden = true;
    if (els.partnerInviteMount) els.partnerInviteMount.innerHTML = "";

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

    // Pas hier — nooit direct na fetchQuizResult() hierboven — staat het volledige, definitieve
    // resultaat van déze bezoeker vast (inclusief een eventueel gekozen basispalet/accentkleuren,
    // zie renderAccentStep()/onDone() hieronder). Dit is dus ook het juiste moment om de
    // geïsoleerde partnertest af te ronden: eerder afronden zou de partner_snapshot altijd zonder
    // gekozen kleuren bevriezen, ongeacht wat de bezoeker daarna nog koos.
    const showReportAndLead = async () => {
      renderReportTeaser(els.reportTeaserMount, { result });
      els.leadCard.hidden = false;

      renderLeadForm(els.leadMount, {
        result,
        // Meegestuurd naar /api/quiz-lead zodat de server weet dat dít de geïsoleerde partnertest
        // is — voorkomt dat GenerateAndSendQuizResultPdfJob voor de partner zelf nog een (zinloze,
        // want al deelnemer) nieuwe uitnodiging aanmaakt. Voor de initiator zelf maakt die job de
        // uitnodiging juist automatisch aan en zet 'm direct in de bevestigingsmail — geen knop op
        // deze pagina meer nodig.
        partnerClaimToken,
        // Het afronden van de partnertest hergebruikt naam/e-mailadres van dit formulier (zie
        // QuizResultController::linkPartnerParticipant()) — dus pas doen zodra dat formulier
        // daadwerkelijk verstuurd is (en dus zeker een Submission-rij bestaat).
        onSubmitted: async () => {
          if (partnerClaimToken) {
            let completed = true;
            try {
              await completePartnerResult(result.resultUuid, partnerClaimToken);
            } catch {
              completed = false;
            }
            if (onCompleted) {
              onCompleted(result, { completed });
            }
          }
        },
      });
    };

    // De accentkleurstap toont een kleur die exact de hex deelt met een kleur uit het gekozen
    // basispalet als "Zit al in je basis" (zie accentColorStep.js) — dus opnieuw renderen bij elke
    // (latere) wijziging van dat palet, niet alleen bij de eerste keer. `state.get().accentColorIds`
    // bewaart een eerder gemaakte keuze; de component zelf filtert die alsnog op de huidige
    // overlap, zodat een net "in de basis" beland kleurtje nooit stilzwijgend gekozen blijft.
    const renderAccentStep = (basePaletteColors) => {
      if (result.accentColorOptions?.length > 0) {
        renderAccentColorStep(els.accentColorMount, {
          options: result.accentColorOptions,
          resultUuid: result.resultUuid,
          basePaletteColors,
          initialSelectedIds: state.get().accentColorIds,
          onSelectionChange: (ids) => state.setAccentColorIds(ids),
          onDone: (chosenColors) => {
            state.setAccentColorIds(chosenColors.map((color) => color.id));
            showReportAndLead();
          },
        });
      } else {
        showReportAndLead();
      }
    };

    // Het basispalet valt vóór de accentkleurstap: elke stijl heeft zijn eigen paletten (nooit
    // gemengd met een secundaire stijl, zie QuizResultController::store()), en de kleuren die de
    // accentstap moet uitsluiten zijn pas bekend zodra het palet vaststaat. Geen aangeboden
    // paletten (bv. een stijl zonder catalogus) -> meteen door naar de accentkleurstap, exact het
    // gedrag dat die stap zelf ook al had bij een lege catalogus.
    if (result.basePaletteOptions?.length > 0) {
      renderBasePaletteStep(els.basePaletteMount, {
        options: result.basePaletteOptions,
        resultUuid: result.resultUuid,
        primaryStyleLabel: result.primaryStyle?.label ?? "",
        initialPaletteId: state.get().basePaletteId,
        onDone: (chosenPalette) => {
          state.setBasePaletteId(chosenPalette.id);
          renderAccentStep(chosenPalette.colors ?? []);
        },
      });
    } else {
      renderAccentStep([]);
    }
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

    // Meet de hoogte vóórdat het startscherm iets van zijn lay-out verliest (de is-leaving-klasse
    // hieronder verandert alleen opacity/transform, maar meet voor de zekerheid eerst).
    reserveStageHeight();

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
      renderStep({ scroll: true, direction: "forward" });
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
    renderStep({ scroll: true, direction: "back" });
  });

  els.restartBtn.addEventListener("click", restart);

  // Landt altijd op het startscherm; "Start de stijltest" begint altijd fris.
  showScreen("start");
  // Alvast de eerste overgangsfoto ophalen terwijl de bezoeker de intro leest — dat is het
  // eerstvolgende scherm na een klik op "Start de stijlanalyse".
  prefetchTransitionPhoto(SECTIONS[0]);
}

export { initQuiz };

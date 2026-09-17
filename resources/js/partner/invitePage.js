import { initQuiz } from "../quiz/quiz.js";
import { loadRemoteQuizConfig } from "../quiz/remoteConfig.js";
import { loadPartnerAccess, savePartnerAccess } from "./partnerState.js";

/**
 * GET /gezamenlijk/uitnodiging/{inviteToken} — sectie 4.B van het implementatieplan. Claimt bij
 * de eerste klik atomair een plek als partner (zie PartnerLinkController::claim()), bewaart het
 * toegangstoken lokaal, en start daarna precies dezelfde, geïsoleerde quiz als de individuele
 * test (inclusief basispalet-/accentkleurstappen en het eigen aanvraagformulier) — pas ná die
 * volledige test koppelt initQuiz() dit resultaat aan de partnerkoppeling, zie
 * QuizResultController::completePartnerResult().
 */
function initInvitePage(root) {
  const inviteToken = root.dataset.inviteToken;
  const landingShell = root.querySelector("#partnerInviteLandingShell");
  const landingMount = root.querySelector("#partnerInviteLandingMount");
  const quizWrap = root.querySelector("#partnerInviteQuizWrap");
  const quizRoot = quizWrap.querySelector("#quizRoot");

  async function startQuizWithToken(accessToken) {
    landingShell.hidden = true;
    quizWrap.hidden = false;

    const startBtn = quizRoot.querySelector("#startQuizBtn");
    const loadError = quizRoot.querySelector("#quizLoadError");

    const loaded = await loadRemoteQuizConfig();
    if (!loaded) {
      loadError.hidden = false;
      startBtn.disabled = false;
      startBtn.textContent = "Opnieuw proberen";
      startBtn.onclick = () => startQuizWithToken(accessToken);
      return;
    }

    startBtn.disabled = false;
    initQuiz(quizRoot, {
      partnerClaimToken: accessToken,
      // Vuurt pas als het VOLLEDIGE resultaat van deze bezoeker vaststaat (inclusief een eventueel
      // gekozen basispalet/accentkleuren) — zie quiz.js's showReportAndLead(). Vervangt hier het
      // (voor de partnertest zinloze) individuele uitnodigingsblok door een duidelijke link naar
      // het gezamenlijke resultaat, i.p.v. de bezoeker daar meteen naartoe te sturen — zo ziet
      // de partner ook nog gewoon zijn/haar eigen rapportteaser/aanvraagformulier hierboven.
      onCompleted: (result, { completed }) => {
        renderJointResultCta(accessToken, completed);
      },
    });
  }

  function renderJointResultCta(accessToken, completed) {
    const mount = quizRoot.querySelector("#partnerInviteMount");
    if (!mount) return;

    mount.innerHTML = "";
    const card = document.createElement("div");
    card.className = "cta card partner-invite";

    const heading = document.createElement("h3");
    heading.textContent = completed
      ? "Jullie gezamenlijke woonstijl staat klaar"
      : "Bijna klaar";
    card.appendChild(heading);

    const intro = document.createElement("p");
    intro.className = "section-intro";
    intro.textContent = completed
      ? "Bekijk hier het gezamenlijke advies, gebaseerd op jullie beide uitslagen."
      : "Je eigen resultaat is opgeslagen, maar het koppelen aan jullie gezamenlijke advies is niet gelukt. Probeer de link hieronder over een paar minuten opnieuw.";
    card.appendChild(intro);

    const actions = document.createElement("div");
    actions.className = "actions";
    const link = document.createElement("a");
    link.className = "btn btn-primary";
    link.href = `/gezamenlijk/${accessToken}`;
    link.textContent = "Bekijk jullie gezamenlijke woonstijl";
    actions.appendChild(link);
    card.appendChild(actions);

    mount.appendChild(card);
  }

  function renderInvalid(preview) {
    landingMount.innerHTML = "";
    const heading = document.createElement("h1");
    heading.textContent = "Deze uitnodiging is niet (meer) geldig";
    landingMount.appendChild(heading);

    const body = document.createElement("p");
    body.className = "section-intro";
    body.textContent = preview.expired
      ? "Deze uitnodiging is verlopen. Vraag een nieuwe link aan bij degene die je uitnodigde."
      : preview.alreadyClaimed
        ? "Deze uitnodiging is al gebruikt."
        : "Deze link bestaat niet (meer).";
    landingMount.appendChild(body);
  }

  function renderLandingCard(preview) {
    landingMount.innerHTML = "";

    const heading = document.createElement("h1");
    heading.textContent = preview.initiatorName
      ? `${preview.initiatorName} nodigt je uit voor de woonstijltest`
      : "Je bent uitgenodigd voor de woonstijltest";
    landingMount.appendChild(heading);

    const intro = document.createElement("p");
    intro.className = "section-intro";
    intro.textContent = "Doe de test onafhankelijk van je partner, want jullie antwoorden blijven voor elkaar verborgen. Aan het eind krijg je, net als je partner, je eigen persoonlijke woonstijl, plus een gezamenlijk advies over hoe jullie stijlen mooi met elkaar te combineren zijn.";
    landingMount.appendChild(intro);

    const actions = document.createElement("div");
    actions.className = "actions";
    const startBtn = document.createElement("button");
    startBtn.type = "button";
    startBtn.className = "btn btn-primary";
    startBtn.textContent = "Start de test";
    actions.appendChild(startBtn);
    landingMount.appendChild(actions);

    const status = document.createElement("p");
    status.className = "hint";
    status.setAttribute("aria-live", "polite");
    landingMount.appendChild(status);

    startBtn.addEventListener("click", async () => {
      startBtn.disabled = true;
      status.textContent = "Bezig...";

      try {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";
        const response = await fetch(`/api/partner-links/${inviteToken}/claim`, {
          method: "POST",
          headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-TOKEN": csrf },
          body: JSON.stringify({}),
          signal: AbortSignal.timeout(10000),
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || "Claimen mislukt.");

        savePartnerAccess(inviteToken, { accessToken: data.accessToken, role: "partner" });
        startQuizWithToken(data.accessToken);
      } catch (error) {
        status.textContent = error.message || "Er ging iets mis. Probeer het nog eens.";
        startBtn.disabled = false;
      }
    });
  }

  async function boot() {
    landingMount.innerHTML = '<p class="hint">Bezig met laden...</p>';

    // Hervatten op hetzelfde apparaat: al eerder geclaimd? Dan meteen door naar de test (of, als
    // die al is afgerond, komt onCompleted() vanzelf niet meer voor — de bezoeker klikt dan
    // gewoon opnieuw door de al-afgeronde vragen, wat onschadelijk is, want de server accepteert
    // toch alleen de eerste koppeling per rol).
    const saved = loadPartnerAccess(inviteToken);
    if (saved?.accessToken) {
      startQuizWithToken(saved.accessToken);
      return;
    }

    let preview;
    try {
      const response = await fetch(`/api/partner-links/${inviteToken}/preview`, {
        headers: { Accept: "application/json" },
        signal: AbortSignal.timeout(10000),
      });
      preview = await response.json();
    } catch {
      landingMount.innerHTML = '<p class="error">Deze uitnodiging kon niet geladen worden. Controleer je internetverbinding.</p>';
      return;
    }

    if (!preview.valid) {
      renderInvalid(preview);
      return;
    }

    renderLandingCard(preview);
  }

  boot();
}

export { initInvitePage };

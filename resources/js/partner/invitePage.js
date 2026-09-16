import { initQuiz } from "../quiz/quiz.js";
import { loadRemoteQuizConfig } from "../quiz/remoteConfig.js";
import { loadPartnerAccess, savePartnerAccess } from "./partnerState.js";

/**
 * GET /gezamenlijk/uitnodiging/{inviteToken} — sectie 4.B van het implementatieplan. Claimt bij
 * de eerste klik atomair een plek als partner (zie PartnerLinkController::claim()), bewaart het
 * toegangstoken lokaal, en start daarna precies dezelfde, geïsoleerde quiz als de individuele
 * test — alleen met dat toegangstoken meegestuurd zodat het resultaat aan deze partnerkoppeling
 * gekoppeld wordt (zie initQuiz()'s partnerClaimToken-optie).
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
      onCompleted: () => {
        window.location.href = `/gezamenlijk/${accessToken}`;
      },
    });
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
    intro.textContent = "Doe de test onafhankelijk van je partner — jullie antwoorden blijven voor elkaar verborgen. Na afloop zien jullie samen een gezamenlijk advies, nooit elkaars losse keuzes.";
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

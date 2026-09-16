import { createCheckIcon } from "./checkIcon.js";
import { createLoadingScene } from "./loadingScene.js";
import { LEAD_FORM_COPY } from "../copy.js";

/** Voorkomt HTML-injectie wanneer een eerder ingevulde naam/e-mailadres via innerHTML wordt teruggezet. */
function escapeHtml(value) {
  return value.replace(/[&<>"']/g, (char) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  })[char]);
}

function renderLeadForm(container, { result }) {
  /**
   * Gedeeld met de "Opnieuw versturen"-knop in de successtatus. Stuurt alleen de verwijzing naar
   * het al server-side berekende resultaat (resultUuid) mee — de PDF-inhoud zelf bouwt
   * QuizLeadController op uit QuizResult/StyleProfile, niet meer uit client-aangeleverde velden.
   * PDF-generatie + mailverzending gebeuren op de achtergrond (zie GenerateAndSendQuizResultPdfJob)
   * — dit antwoord bevestigt dus meestal alleen dat de aanvraag in behandeling is genomen
   * (`status: 'queued'`), niet dat de e-mail al onderweg is. Ruime timeout (45s) als extra marge
   * voor een trage verbinding, al hoeft deze aanroep zelf niet meer op de PDF/mail te wachten.
   * Idempotent aan de serverkant (zelfde resultUuid + al verstuurd/nog vers in behandeling = geen
   * dubbele mail, maar een eerder mislukte of vastgelopen poging wordt bij een retry wél opnieuw
   * geprobeerd — zie QuizLeadController), dus een timeout hier mag gerust een nieuwe poging
   * suggereren i.p.v. een definitieve foutmelding.
   *
   * Geeft altijd het geparste antwoord terug (incl. een `status`-veld: 'sent', 'queued' of
   * 'failed') zodat de aanroeper nooit alleen op de HTTP-status hoeft te vertrouwen — die is
   * bewust ook 200 bij een mislukte verzending, want de gegevens van de bezoeker zijn dan wél
   * degelijk opgeslagen.
   */
  async function submitLead({ name, email, marketingOptIn }) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";

    let response;
    try {
      response = await fetch("/api/quiz-lead", {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-TOKEN": csrf },
        body: JSON.stringify({
          resultUuid: result.resultUuid,
          name,
          email,
          marketingOptIn,
        }),
        signal: AbortSignal.timeout(45000),
      });
    } catch {
      throw new Error("We konden niet bevestigen of het verzenden is gelukt. Probeer het nog eens.");
    }

    let json = {};
    try {
      json = await response.json();
    } catch {
      // Geen geldige JSON-body — val terug op de statuscode hieronder.
    }

    if (!response.ok) throw new Error(json.message || "Verzenden mislukt. Probeer het nog eens.");
    return json;
  }

  /**
   * @param {{name?: string, email?: string, marketingOptIn?: boolean, statusMessage?: string}} prefill
   *   Gebruikt om na een mislukte aanvraag of een netwerkfout het formulier opnieuw te tonen met
   *   de al ingevulde gegevens (nooit de bezoeker laten overtypen) en een uitleg wat er misging.
   */
  function renderForm({ name = "", email = "", marketingOptIn = false, statusMessage = "" } = {}) {
    container.innerHTML = "";

    const heading = document.createElement("h3");
    heading.textContent = LEAD_FORM_COPY.heading;
    container.appendChild(heading);

    const intro = document.createElement("p");
    intro.className = "section-intro";
    intro.textContent = LEAD_FORM_COPY.intro;
    container.appendChild(intro);

    const form = document.createElement("form");
    form.className = "lead-form";
    form.noValidate = true;

    const nameField = document.createElement("div");
    nameField.className = "field";
    nameField.innerHTML = `
      <label for="leadName">Voornaam</label>
      <input id="leadName" name="leadName" type="text" autocomplete="given-name" required value="${escapeHtml(name)}" />
      <p class="error" id="leadNameError" aria-live="polite"></p>
    `;

    const emailField = document.createElement("div");
    emailField.className = "field";
    emailField.innerHTML = `
      <label for="leadEmail">E-mailadres</label>
      <input id="leadEmail" name="leadEmail" type="email" autocomplete="email" required value="${escapeHtml(email)}" />
      <p class="error" id="leadEmailError" aria-live="polite"></p>
    `;

    const optInField = document.createElement("div");
    optInField.className = "field checkbox-row";
    optInField.innerHTML = `
      <input id="leadOptIn" name="leadOptIn" type="checkbox" ${marketingOptIn ? "checked" : ""} />
      <label for="leadOptIn">${LEAD_FORM_COPY.optInLabel}</label>
    `;

    const actions = document.createElement("div");
    actions.className = "actions";
    const submitBtn = document.createElement("button");
    submitBtn.type = "submit";
    submitBtn.className = "btn btn-primary";
    submitBtn.innerHTML = `
      ${LEAD_FORM_COPY.submitLabel}
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
    `;
    actions.appendChild(submitBtn);

    const status = document.createElement("p");
    status.className = statusMessage ? "error" : "hint";
    status.setAttribute("aria-live", "polite");
    status.textContent = statusMessage;

    const reassurance = document.createElement("p");
    reassurance.className = "lead-form-reassurance";
    reassurance.textContent = LEAD_FORM_COPY.reassurance;

    form.appendChild(nameField);
    form.appendChild(emailField);
    form.appendChild(optInField);
    form.appendChild(actions);
    form.appendChild(status);
    form.appendChild(reassurance);
    container.appendChild(form);

    const nameInput = form.querySelector("#leadName");
    const nameError = form.querySelector("#leadNameError");
    const emailInput = form.querySelector("#leadEmail");
    const emailError = form.querySelector("#leadEmailError");

    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      nameError.textContent = "";
      emailError.textContent = "";

      const name = nameInput.value.trim();
      const email = emailInput.value.trim();
      let hasError = false;

      if (!name) {
        nameError.textContent = "Vul je voornaam in.";
        hasError = true;
      }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        emailError.textContent = "Vul een geldig e-mailadres in.";
        hasError = true;
      }
      if (hasError) return;

      const marketingOptIn = form.querySelector("#leadOptIn").checked;

      // Vervangt het hele formulier door de laadscene — dat sluit vanzelf een dubbele aanvraag
      // uit zolang de aanvraag loopt (de verzendknop bestaat dan even niet meer in de DOM).
      container.innerHTML = "";
      const scene = createLoadingScene({
        heading: "We maken jouw woonstijlrapport klaar",
        subtext: "Een momentje, we bereiden je persoonlijke PDF voor en sturen deze naar je e-mailadres.",
      });
      container.appendChild(scene.element);

      // De aanvraag zelf komt nu meestal razendsnel terug (alleen opslaan + een taak inplannen,
      // zie QuizLeadController) — Promise.allSettled zorgt dat de rustige opbouw-animatie altijd
      // haar volledige, eigen duur afspeelt i.p.v. halverwege abrupt te worden vervangen, ook als
      // de aanvraag mislukt.
      const [, leadResult] = await Promise.allSettled([scene.start(), submitLead({ name, email, marketingOptIn })]);

      if (leadResult.status === "rejected") {
        renderForm({ name, email, marketingOptIn, statusMessage: leadResult.reason.message });
        return;
      }

      const response = leadResult.value;
      if (response.status === "sent") {
        renderSuccess({ name, email, marketingOptIn });
      } else if (response.status === "queued") {
        // De aanvraag is ontvangen en wordt op de achtergrond verwerkt (zie
        // GenerateAndSendQuizResultPdfJob) — nooit al claimen dat de e-mail verstuurd is, dat
        // weten we op dit moment nog niet.
        renderQueued();
      } else {
        // De server bevestigt hier expliciet geen geslaagde verzending (bv. email_status
        // 'failed') — nooit een succesmelding tonen die de app niet kan waarmaken. Gegevens
        // blijven behouden: het formulier verschijnt opnieuw, voorgevuld, met de reden erbij.
        renderForm({ name, email, marketingOptIn, statusMessage: response.message });
      }
    });
  }

  /**
   * Bevestigt alleen dat de aanvraag ontvangen is — geen "opnieuw versturen"-knop hier, want er is
   * nog niets verstuurd om opnieuw te proberen (en de server zou een nieuwe poging binnen enkele
   * minuten toch als dubbele aanvraag negeren, zie QuizLeadController).
   */
  function renderQueued() {
    container.innerHTML = "";

    const queued = document.createElement("div");
    queued.className = "lead-form-success";
    queued.setAttribute("role", "status");
    queued.setAttribute("aria-live", "polite");

    queued.appendChild(createCheckIcon("lead-form-success-icon", 26));

    const title = document.createElement("p");
    title.className = "lead-form-success-title";
    title.textContent = LEAD_FORM_COPY.queuedTitle;
    queued.appendChild(title);

    const body = document.createElement("p");
    body.className = "section-intro";
    body.textContent = LEAD_FORM_COPY.queuedBody;
    queued.appendChild(body);

    const expectTitle = document.createElement("p");
    expectTitle.className = "report-checklist-intro";
    expectTitle.textContent = LEAD_FORM_COPY.expectTitle;
    queued.appendChild(expectTitle);

    const expectList = document.createElement("ul");
    expectList.className = "report-checklist";
    LEAD_FORM_COPY.expectItems.forEach((item) => {
      const li = document.createElement("li");
      li.appendChild(createCheckIcon());
      const text = document.createElement("span");
      text.textContent = item;
      li.appendChild(text);
      expectList.appendChild(li);
    });
    queued.appendChild(expectList);

    container.appendChild(queued);
  }

  /** Losstaande bevestigingsweergave — vervangt het hele formulier, geen restje ervan blijft staan. */
  function renderSuccess({ name, email, marketingOptIn }) {
    container.innerHTML = "";

    const success = document.createElement("div");
    success.className = "lead-form-success";

    success.appendChild(createCheckIcon("lead-form-success-icon", 26));

    const title = document.createElement("p");
    title.className = "lead-form-success-title";
    title.textContent = LEAD_FORM_COPY.successTitle;
    success.appendChild(title);

    const body = document.createElement("p");
    body.className = "section-intro";
    body.textContent = LEAD_FORM_COPY.successBody.replace("{name}", name).replace("{email}", email);
    success.appendChild(body);

    const spamHint = document.createElement("p");
    spamHint.className = "hint";
    spamHint.textContent = LEAD_FORM_COPY.spamHint;
    success.appendChild(spamHint);

    const expectTitle = document.createElement("p");
    expectTitle.className = "report-checklist-intro";
    expectTitle.textContent = LEAD_FORM_COPY.expectTitle;
    success.appendChild(expectTitle);

    const expectList = document.createElement("ul");
    expectList.className = "report-checklist";
    LEAD_FORM_COPY.expectItems.forEach((item) => {
      const li = document.createElement("li");
      li.appendChild(createCheckIcon());
      const text = document.createElement("span");
      text.textContent = item;
      li.appendChild(text);
      expectList.appendChild(li);
    });
    success.appendChild(expectList);

    const followUp = document.createElement("div");
    followUp.className = "lead-form-success-actions";

    const resendBtn = document.createElement("button");
    resendBtn.type = "button";
    resendBtn.className = "btn btn-secondary";
    resendBtn.textContent = LEAD_FORM_COPY.resendLabel;
    followUp.appendChild(resendBtn);

    const resendStatus = document.createElement("p");
    resendStatus.className = "hint";
    resendStatus.setAttribute("aria-live", "polite");
    followUp.appendChild(resendStatus);

    resendBtn.addEventListener("click", async () => {
      resendBtn.disabled = true;
      resendStatus.textContent = "Bezig met opnieuw versturen...";

      try {
        const response = await submitLead({ name, email, marketingOptIn });
        resendStatus.textContent = response.status === "sent"
          ? "Opnieuw verstuurd!"
          : response.message;
      } catch (error) {
        resendStatus.textContent = error.message;
      } finally {
        resendBtn.disabled = false;
      }
    });

    success.appendChild(followUp);
    container.appendChild(success);
  }

  renderForm();
}

export { renderLeadForm };

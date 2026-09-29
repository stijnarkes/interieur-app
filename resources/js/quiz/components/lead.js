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

/**
 * @param {{result: object, previewMode?: boolean, onSubmitted?: () => void, partnerClaimToken?: ?string}} params
 *   `onSubmitted` vuurt precies één keer, zodra de server een definitief antwoord gaf op de EERSTE
 *   inzending (sent/queued/failed maken voor dit doel geen verschil — in alle drie de gevallen
 *   heeft QuizLeadController al een Submission-rij met dit e-mailadres opgeslagen, vóórdat 'ie aan
 *   PDF/mail begint). quiz.js gebruikt dit om pas ná deze inzending de geïsoleerde partnertest af
 *   te ronden (zie completePartnerResult()) — dat moment heeft ook een gegarandeerd e-mailadres
 *   nodig, namelijk voor het automatisch versturen van het gezamenlijke resultaat. Vuurt nooit in
 *   previewMode (er wordt dan niets echt opgeslagen) en niet opnieuw bij een latere "Opnieuw
 *   versturen"-klik (die stap is dan al gebeurd).
 *   `partnerClaimToken` wordt, indien gezet, meegestuurd naar /api/quiz-lead zodat de server weet
 *   dat dit de geïsoleerde partnertest is — zie GenerateAndSendQuizResultPdfJob, dat anders voor
 *   de partner zelf nog een (zinloze) nieuwe partneruitnodiging zou aanmaken.
 */
function renderLeadForm(container, { result, previewMode = false, onSubmitted, partnerClaimToken = null }) {
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
          ...(partnerClaimToken ? { partnerClaimToken } : {}),
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
   * Vraagt na wat er écht gebeurd is met een aanvraag waarvan het antwoord niet aankwam — een wat
   * langere aanvraag (PDF genereren kost realistisch een paar seconden) kan de verbinding tussen
   * browser en server laten verbreken vóórdat het antwoord terugkomt, terwijl de server intussen
   * gewoon doorwerkt en de mail alsnog verstuurt. In plaats van die afgebroken verbinding meteen
   * als mislukking te behandelen, blijft dit een tijdje pollen (GET, geen bijwerkende actie) tot
   * er een definitief 'sent'/'failed' bekend is, of geeft na te veel pogingen alsnog niets terug
   * (dan weten we het écht niet, en toont de aanroeper een eerlijke "kon niet bevestigen"-melding).
   */
  async function pollForOutcome(resultUuid, { attempts = 20, intervalMs = 3000 } = {}) {
    for (let attempt = 0; attempt < attempts; attempt++) {
      await new Promise((resolve) => setTimeout(resolve, intervalMs));

      try {
        const response = await fetch(`/api/quiz-lead/${resultUuid}`, {
          headers: { Accept: "application/json" },
          signal: AbortSignal.timeout(10000),
        });

        if (response.ok) {
          const json = await response.json();
          if (json.status === "sent" || json.status === "failed") {
            return json;
          }
          // status 'queued' — de server is nog bezig, gewoon blijven pollen.
        }
      } catch {
        // Netwerkfout tijdens het navragen zelf — gewoon bij de volgende poging opnieuw proberen.
      }
    }

    return null;
  }

  /**
   * Wacht de echte uitkomst af wanneer de aanvraag zelf alleen "queued" teruggaf — zie
   * QuizLeadController: het antwoord op de aanvraag bevestigt bewust nooit meer dan "ontvangen",
   * de PDF/mail worden daarna pas gemaakt. In plaats van dat als eigen (tussentijdse) status aan
   * de bezoeker te tonen, blijft de laadscene gewoon staan en wordt hier nagevraagd wat de
   * uiteindelijke uitkomst is — de bezoeker krijgt zo altijd meteen de definitieve "verzonden"/
   * "mislukt"-melding te zien, nooit een tussenstap.
   *
   * Kortere interval dan pollForOutcome()'s eigen standaard (1s i.p.v. 3s): die achtergrondtaak
   * start vrijwel meteen na het antwoord hierboven (zie QuizLeadController), dus de uitkomst is
   * meestal al binnen een paar tellen bekend. Blijft die onverhoopt tóch onbekend (pollForOutcome()
   * geeft dan null terug), dan valt dit terug op het oorspronkelijke 'queued'-antwoord — de
   * aanroeper behandelt dat verder hetzelfde als "kon niet bevestigen".
   */
  async function resolveOutcome(response) {
    if (response.status !== "queued") return response;

    const outcome = await pollForOutcome(result.resultUuid, { attempts: 30, intervalMs: 1000 });

    return outcome ?? response;
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

      // Voorbeeldweergave (zie QuizPreviewController/preview.js): nooit een echte aanvraag
      // versturen, maar wel meteen de bevestigingstekst tonen — zo ziet de admin ook precies die
      // tekst zonder dat er een e-mail de deur uit gaat.
      if (previewMode) {
        renderSuccess({ name, email, marketingOptIn });
        return;
      }

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

      let response;
      if (leadResult.status === "rejected") {
        // De aanvraag zelf kreeg geen antwoord — dat kan ook gewoon een verbinding zijn die
        // afbrak terwijl de server nog gewoon doorwerkte. Eerst navragen wat er echt gebeurd is
        // (de laadscene blijft intussen gewoon zichtbaar) i.p.v. meteen een mislukking claimen.
        response = await pollForOutcome(result.resultUuid);
        if (!response) {
          renderForm({ name, email, marketingOptIn, statusMessage: leadResult.reason.message });
          return;
        }
      } else {
        response = leadResult.value;
      }

      // Vanaf hier staat vast dat de server een Submission-rij met dit e-mailadres heeft
      // opgeslagen (zie de docblock hierboven) — ongeacht of het versturen zelf lukte.
      onSubmitted?.();

      // De laadscene blijft gewoon staan zolang response.status "queued" is — zie
      // resolveOutcome() hierboven, dat pas teruggeeft zodra de definitieve uitkomst bekend is
      // (of, in het zeldzame geval dat dat niet lukt, het oorspronkelijke 'queued'-antwoord).
      response = await resolveOutcome(response);

      if (response.status === "sent") {
        renderSuccess({ name, email, marketingOptIn });
      } else if (response.status === "queued") {
        // resolveOutcome() kon ook na de extra navraag geen definitief antwoord vinden — nooit
        // een "mislukt" claimen die niet vaststaat, en ook geen "gelukt" dat we niet kunnen
        // waarmaken. Gegevens staan al opgeslagen, dus het formulier verschijnt opnieuw met een
        // eerlijke, neutrale tekst i.p.v. de foutmelding hieronder.
        renderForm({
          name, email, marketingOptIn,
          statusMessage: "We konden nog niet bevestigen of het gelukt is. Je gegevens zijn wel opgeslagen — probeer het gerust opnieuw.",
        });
      } else {
        // De server bevestigt hier expliciet geen geslaagde verzending (bv. email_status
        // 'failed') — nooit een succesmelding tonen die de app niet kan waarmaken. Gegevens
        // blijven behouden: het formulier verschijnt opnieuw, voorgevuld, met de reden erbij.
        renderForm({ name, email, marketingOptIn, statusMessage: response.message });
      }
    });
  }

  /**
   * Zelfde soort knop als in de bevestigingsmail (email_cta_label/email_cta_url, zie
   * QuizResultMail) — hier via lead_cta_label/lead_cta_url (LEAD_FORM_COPY.ctaLabel/ctaUrl), zodat
   * de tekst/link voor dit scherm apart van de mail bijgesteld kan worden via TekstenPage.
   */
  function createCtaBlock() {
    const cta = document.createElement("div");
    cta.className = "lead-form-cta";

    const link = document.createElement("a");
    link.className = "btn btn-primary lead-form-cta-btn";
    link.href = LEAD_FORM_COPY.ctaUrl;
    link.target = "_blank";
    link.rel = "noopener";
    link.innerHTML = `
      ${LEAD_FORM_COPY.ctaLabel}
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
    `;
    cta.appendChild(link);

    return cta;
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
    success.appendChild(createCtaBlock());

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
      if (previewMode) {
        resendStatus.textContent = "Voorbeeldweergave — er wordt niets echt opnieuw verstuurd.";
        return;
      }

      resendBtn.disabled = true;
      resendStatus.textContent = "Bezig met opnieuw versturen...";

      try {
        const response = await resolveOutcome(await submitLead({ name, email, marketingOptIn }));
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

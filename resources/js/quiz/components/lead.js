import { QUESTIONS } from "../data.js";
import { composePersonalPalette, buildPaletteExplanation } from "../paletteEngine.js";
import { createCheckIcon } from "./checkIcon.js";

const EXPECT_ITEMS = [
  "Jouw persoonlijke woonstijl",
  "Kleuren, materialen en vormen die bij je passen",
  "Een persoonlijk moodboard en interieuradvies",
];

/** De productfoto's die de gebruiker koos (voor de moodboard-sectie in de PDF/e-mail). */
function buildMoodboardPayload(answers) {
  return QUESTIONS
    .filter((question) => question.type !== "color-preference")
    .map((question) => {
      const optionId = answers[question.id];
      const option = question.options.find((candidate) => candidate.id === optionId);
      return option ? { title: option.title, image: option.image } : null;
    })
    .filter(Boolean);
}

/** Alleen de velden die de PDF/e-mail nodig hebben — geen key/slug/productTags. */
function buildPrimaryStylePayload(primaryStyle) {
  if (!primaryStyle) return null;
  const { label, subtitle, longDescription, traitsIntro, traits, heroImage, colorTip, materials, materialsTip, furnitureAdvice, recipe, avoid } = primaryStyle;
  return { label, subtitle, longDescription, traitsIntro, traits, heroImage, colorTip, materials, materialsTip, furnitureAdvice, recipe, avoid };
}

function renderLeadForm(container, { result, answers }) {
  /** Gedeeld met de "Opnieuw versturen"-knop in de successtatus — één plek voor het verzoek. */
  async function submitLead({ name, email, marketingOptIn }) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";
    const response = await fetch("/api/quiz-lead", {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-TOKEN": csrf },
      body: JSON.stringify({
        name,
        email,
        marketingOptIn,
        resultName: result.resultName,
        description: result.description,
        topStyles: result.topStyles,
        traits: result.traits,
        primaryStyle: buildPrimaryStylePayload(result.primaryStyle),
        secondaryStyleLabel: result.secondaryStyle?.label ?? null,
        personalPalette: composePersonalPalette(answers, result.primaryStyle),
        colorExplanation: buildPaletteExplanation(answers, result.primaryStyle),
        moodboard: buildMoodboardPayload(answers),
        answers,
      }),
    });

    const json = await response.json();
    if (!response.ok) throw new Error(json.error || "Verzenden mislukt.");
    return json;
  }

  function renderForm() {
    container.innerHTML = "";

    const heading = document.createElement("h3");
    heading.textContent = "Ontvang jouw persoonlijke woonstijlrapport";
    container.appendChild(heading);

    const intro = document.createElement("p");
    intro.className = "section-intro";
    intro.textContent = "Vul hieronder je gegevens in en ontvang jouw complete persoonlijke interieuradvies als PDF in je mailbox.";
    container.appendChild(intro);

    const form = document.createElement("form");
    form.className = "lead-form";
    form.noValidate = true;

    const nameField = document.createElement("div");
    nameField.className = "field";
    nameField.innerHTML = `
      <label for="leadName">Voornaam</label>
      <input id="leadName" name="leadName" type="text" autocomplete="given-name" required />
      <p class="error" id="leadNameError" aria-live="polite"></p>
    `;

    const emailField = document.createElement("div");
    emailField.className = "field";
    emailField.innerHTML = `
      <label for="leadEmail">E-mailadres</label>
      <input id="leadEmail" name="leadEmail" type="email" autocomplete="email" required />
      <p class="error" id="leadEmailError" aria-live="polite"></p>
    `;

    const optInField = document.createElement("div");
    optInField.className = "field checkbox-row";
    optInField.innerHTML = `
      <input id="leadOptIn" name="leadOptIn" type="checkbox" />
      <label for="leadOptIn">Ik ontvang graag af en toe wooninspiratie, tips en acties van Boer Staphorst.</label>
    `;

    const actions = document.createElement("div");
    actions.className = "actions";
    const submitBtn = document.createElement("button");
    submitBtn.type = "submit";
    submitBtn.className = "btn btn-primary";
    submitBtn.innerHTML = `
      Stuur mijn woonstijlrapport
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
    `;
    actions.appendChild(submitBtn);

    const status = document.createElement("p");
    status.className = "hint";
    status.setAttribute("aria-live", "polite");

    const reassurance = document.createElement("p");
    reassurance.className = "lead-form-reassurance";
    reassurance.textContent = "Je ontvangt jouw rapport direct per e-mail. Geen verplichtingen.";

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

      submitBtn.disabled = true;
      status.textContent = "Bezig met versturen...";

      try {
        await submitLead({ name, email, marketingOptIn });
        renderSuccess({ name, email, marketingOptIn });
      } catch (error) {
        status.textContent = error.message;
        submitBtn.disabled = false;
      }
    });
  }

  /** Losstaande bevestigingsweergave — vervangt het hele formulier, geen restje ervan blijft staan. */
  function renderSuccess({ name, email, marketingOptIn }) {
    container.innerHTML = "";

    const success = document.createElement("div");
    success.className = "lead-form-success";

    success.appendChild(createCheckIcon("lead-form-success-icon", 26));

    const title = document.createElement("p");
    title.className = "lead-form-success-title";
    title.textContent = "Je woonstijlrapport is verzonden";
    success.appendChild(title);

    const body = document.createElement("p");
    body.className = "section-intro";
    body.textContent = `Bedankt, ${name}. We hebben jouw persoonlijke woonstijlrapport verstuurd naar ${email}.`;
    success.appendChild(body);

    const spamHint = document.createElement("p");
    spamHint.className = "hint";
    spamHint.textContent = "Nog geen e-mail? Kijk voor de zekerheid even in je spam.";
    success.appendChild(spamHint);

    const expectTitle = document.createElement("p");
    expectTitle.className = "report-checklist-intro";
    expectTitle.textContent = "Wat kun je verwachten?";
    success.appendChild(expectTitle);

    const expectList = document.createElement("ul");
    expectList.className = "report-checklist";
    EXPECT_ITEMS.forEach((item) => {
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
    resendBtn.textContent = "Opnieuw versturen";
    followUp.appendChild(resendBtn);

    const resendStatus = document.createElement("p");
    resendStatus.className = "hint";
    resendStatus.setAttribute("aria-live", "polite");
    followUp.appendChild(resendStatus);

    resendBtn.addEventListener("click", async () => {
      resendBtn.disabled = true;
      resendStatus.textContent = "Bezig met opnieuw versturen...";

      try {
        await submitLead({ name, email, marketingOptIn });
        resendStatus.textContent = "Opnieuw verstuurd!";
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

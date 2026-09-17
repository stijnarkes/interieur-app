import { FEATURE_FLAGS } from "../featureFlags.js";

const SHARE_CONFIRMATION_TEXT_VERSION = "v1";

/**
 * Uitnodigingsblok onder het individuele resultaat ("Ontdek jullie gezamenlijke woonstijl") — zie
 * het implementatieplan. Blijft volledig leeg/onzichtbaar zolang de partnerfunctie uitstaat (zie
 * QuizSetting::partner_feature_enabled/EnsurePartnerFeatureEnabled) — dit component doet dan
 * bewust helemaal niets, niet eens een lege kaart, i.p.v. de server een 404 te laten teruggeven.
 *
 * @param {HTMLElement} container
 * @param {{result: object}} params  result.resultUuid is vereist (zie QuizResultController::store()).
 */
function renderPartnerInvite(container, { result }) {
  if (!FEATURE_FLAGS.partnerFeatureEnabled) {
    container.innerHTML = "";
    return;
  }

  container.innerHTML = "";

  const card = document.createElement("div");
  card.className = "cta card partner-invite";

  const heading = document.createElement("h3");
  heading.textContent = "Ontdek jullie gezamenlijke woonstijl";
  card.appendChild(heading);

  const intro = document.createElement("p");
  intro.className = "section-intro";
  intro.textContent = "Nodig je partner uit voor een eigen, onafhankelijke test. Jullie zien geen antwoorden van elkaar — alleen het gezamenlijke advies dat we op basis van beide uitslagen samenstellen.";
  card.appendChild(intro);

  // Optioneel: de link naar het gezamenlijke resultaat kan (bewust, zie App\Support\PartnerToken)
  // nooit achteraf opnieuw opgevraagd worden — wie 'm niet bewaart en geen adres opgeeft, moet dus
  // zelf de "Kopieer link"-knop hieronder gebruiken vóórdat deze pagina verdwijnt.
  const emailField = document.createElement("div");
  emailField.className = "field";
  emailField.innerHTML = `
    <label for="partnerNotifyEmail">Wil je een seintje zodra jullie gezamenlijke advies klaarstaat? (optioneel)</label>
    <input id="partnerNotifyEmail" type="email" autocomplete="email" placeholder="Jouw e-mailadres" />
  `;
  card.appendChild(emailField);

  const actions = document.createElement("div");
  actions.className = "actions";
  const inviteBtn = document.createElement("button");
  inviteBtn.type = "button";
  inviteBtn.className = "btn btn-secondary";
  inviteBtn.textContent = "Nodig je partner uit";
  actions.appendChild(inviteBtn);
  card.appendChild(actions);

  const status = document.createElement("p");
  status.className = "hint";
  status.setAttribute("aria-live", "polite");
  card.appendChild(status);

  container.appendChild(card);

  inviteBtn.addEventListener("click", async () => {
    const email = card.querySelector("#partnerNotifyEmail")?.value.trim() || "";
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      status.textContent = "Vul een geldig e-mailadres in, of laat het veld leeg.";
      return;
    }

    inviteBtn.disabled = true;
    status.textContent = "Bezig met aanmaken...";

    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";
      const response = await fetch("/api/partner-links", {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-TOKEN": csrf },
        body: JSON.stringify({
          resultUuid: result.resultUuid,
          email: email || undefined,
          shareConfirmationTextVersion: SHARE_CONFIRMATION_TEXT_VERSION,
        }),
        signal: AbortSignal.timeout(10000),
      });

      if (!response.ok) throw new Error();
      const data = await response.json();
      renderInviteLink(data, email);
    } catch {
      status.textContent = "Het aanmaken van de uitnodiging is niet gelukt. Probeer het nog eens.";
      inviteBtn.disabled = false;
    }
  });

  function renderCopyableLink(parent, { label, value, ariaLabel }) {
    const wrap = document.createElement("div");

    const p = document.createElement("p");
    p.className = "section-intro";
    p.textContent = label;
    wrap.appendChild(p);

    const linkRow = document.createElement("div");
    linkRow.className = "field";
    const linkInput = document.createElement("input");
    linkInput.type = "text";
    linkInput.readOnly = true;
    linkInput.value = value;
    linkInput.setAttribute("aria-label", ariaLabel);
    linkRow.appendChild(linkInput);
    wrap.appendChild(linkRow);

    const linkActions = document.createElement("div");
    linkActions.className = "actions";
    const copyBtn = document.createElement("button");
    copyBtn.type = "button";
    copyBtn.className = "btn btn-secondary";
    copyBtn.textContent = "Kopieer link";
    copyBtn.addEventListener("click", async () => {
      try {
        await navigator.clipboard.writeText(value);
        copyBtn.textContent = "Gekopieerd!";
        setTimeout(() => { copyBtn.textContent = "Kopieer link"; }, 2000);
      } catch {
        linkInput.select();
      }
    });
    linkActions.appendChild(copyBtn);
    wrap.appendChild(linkActions);

    parent.appendChild(wrap);
    return linkActions;
  }

  function renderInviteLink(data, notifyEmail) {
    card.innerHTML = "";

    const doneHeading = document.createElement("h3");
    doneHeading.textContent = "Jullie uitnodiging staat klaar";
    card.appendChild(doneHeading);

    const doneIntro = document.createElement("p");
    doneIntro.className = "section-intro";
    doneIntro.textContent = "Deel deze link met je partner. Zodra die de test heeft afgerond, zien jullie allebei het gezamenlijke advies.";
    card.appendChild(doneIntro);

    const shareActions = renderCopyableLink(card, {
      label: "Uitnodigingslink voor je partner",
      value: data.inviteUrl,
      ariaLabel: "Uitnodigingslink",
    });

    const whatsappLink = document.createElement("a");
    whatsappLink.className = "btn btn-outline";
    whatsappLink.target = "_blank";
    whatsappLink.rel = "noopener noreferrer";
    whatsappLink.href = `https://wa.me/?text=${encodeURIComponent(`Doe je mee met mijn woonstijltest? ${data.inviteUrl}`)}`;
    whatsappLink.textContent = "Deel via WhatsApp";
    shareActions.appendChild(whatsappLink);

    // De enige plek waar dit toegangstoken ooit getoond wordt — bewust nooit opnieuw op te vragen
    // (zie App\Support\PartnerToken). Zonder bewaarde link/opgegeven e-mailadres is deze uitslag
    // voor de initiator dus onbereikbaar zodra deze pagina verdwijnt.
    if (data.resultUrl) {
      const divider = document.createElement("hr");
      card.appendChild(divider);

      renderCopyableLink(card, {
        label: notifyEmail
          ? `Bewaar ook deze link naar jullie gezamenlijke resultaat — we sturen 'm bovendien naar ${notifyEmail} zodra die klaarstaat.`
          : "Bewaar deze link — hierop verschijnt straks jullie gezamenlijke resultaat. Zonder deze link (of een opgegeven e-mailadres) kunnen wij 'm niet opnieuw voor je opzoeken.",
        value: data.resultUrl,
        ariaLabel: 'Link naar jullie gezamenlijke resultaat',
      });
    }
  }
}

export { renderPartnerInvite };

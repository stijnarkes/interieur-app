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
    inviteBtn.disabled = true;
    status.textContent = "Bezig met aanmaken...";

    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";
      const response = await fetch("/api/partner-links", {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-TOKEN": csrf },
        body: JSON.stringify({
          resultUuid: result.resultUuid,
          shareConfirmationTextVersion: SHARE_CONFIRMATION_TEXT_VERSION,
        }),
        signal: AbortSignal.timeout(10000),
      });

      if (!response.ok) throw new Error();
      const data = await response.json();
      renderInviteLink(data.inviteUrl);
    } catch {
      status.textContent = "Het aanmaken van de uitnodiging is niet gelukt. Probeer het nog eens.";
      inviteBtn.disabled = false;
    }
  });

  function renderInviteLink(inviteUrl) {
    card.innerHTML = "";

    const doneHeading = document.createElement("h3");
    doneHeading.textContent = "Jullie uitnodiging staat klaar";
    card.appendChild(doneHeading);

    const doneIntro = document.createElement("p");
    doneIntro.className = "section-intro";
    doneIntro.textContent = "Deel deze link met je partner. Zodra die de test heeft afgerond, zien jullie allebei het gezamenlijke advies.";
    card.appendChild(doneIntro);

    const linkRow = document.createElement("div");
    linkRow.className = "field";
    const linkInput = document.createElement("input");
    linkInput.type = "text";
    linkInput.readOnly = true;
    linkInput.value = inviteUrl;
    linkInput.setAttribute("aria-label", "Uitnodigingslink");
    linkRow.appendChild(linkInput);
    card.appendChild(linkRow);

    const linkActions = document.createElement("div");
    linkActions.className = "actions";

    const copyBtn = document.createElement("button");
    copyBtn.type = "button";
    copyBtn.className = "btn btn-secondary";
    copyBtn.textContent = "Kopieer link";
    copyBtn.addEventListener("click", async () => {
      try {
        await navigator.clipboard.writeText(inviteUrl);
        copyBtn.textContent = "Gekopieerd!";
        setTimeout(() => { copyBtn.textContent = "Kopieer link"; }, 2000);
      } catch {
        linkInput.select();
      }
    });
    linkActions.appendChild(copyBtn);

    const whatsappLink = document.createElement("a");
    whatsappLink.className = "btn btn-outline";
    whatsappLink.target = "_blank";
    whatsappLink.rel = "noopener noreferrer";
    whatsappLink.href = `https://wa.me/?text=${encodeURIComponent(`Doe je mee met mijn woonstijltest? ${inviteUrl}`)}`;
    whatsappLink.textContent = "Deel via WhatsApp";
    linkActions.appendChild(whatsappLink);

    card.appendChild(linkActions);
  }
}

export { renderPartnerInvite };

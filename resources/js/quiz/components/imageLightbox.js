/**
 * Losstaande overlay om een keuzefoto vergroot te bekijken. Bewust een eigen module-singleton
 * (één overlay-element, hergebruikt) i.p.v. iets dat binnen optionCard.js zelf leeft: elke
 * stapwissel roept renderQuestionStep() aan, wat de hele kaartenrij weggooit en opnieuw opbouwt
 * (zie questionStep.js). Een overlay die in zo'n kaart zou hangen, zou dus meteen weer verdwijnen
 * zodra hij open staat. Door hem los aan document.body te hangen overleeft hij elke re-render.
 */
let overlay = null;
let overlayImg = null;
let lastFocusedElement = null;

function closeImageLightbox() {
  if (!overlay || overlay.hidden) return;
  overlay.classList.remove("is-visible");
  overlay.hidden = true;
  overlayImg.removeAttribute("src");
  if (lastFocusedElement instanceof HTMLElement) lastFocusedElement.focus();
  lastFocusedElement = null;
}

function ensureOverlay() {
  if (overlay) return overlay;

  overlay = document.createElement("div");
  overlay.className = "image-lightbox";
  overlay.hidden = true;
  overlay.setAttribute("role", "dialog");
  overlay.setAttribute("aria-modal", "true");
  overlay.addEventListener("click", (event) => {
    if (event.target === overlay) closeImageLightbox();
  });

  const closeButton = document.createElement("button");
  closeButton.type = "button";
  closeButton.className = "image-lightbox-close";
  closeButton.setAttribute("aria-label", "Sluiten");
  closeButton.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>';
  closeButton.addEventListener("click", closeImageLightbox);

  overlayImg = document.createElement("img");
  overlayImg.className = "image-lightbox-img";

  overlay.appendChild(closeButton);
  overlay.appendChild(overlayImg);
  document.body.appendChild(overlay);

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && overlay && !overlay.hidden) closeImageLightbox();
  });

  return overlay;
}

function openImageLightbox(src, alt) {
  if (!src) return;

  const node = ensureOverlay();
  overlayImg.src = src;
  overlayImg.alt = alt ?? "";
  lastFocusedElement = document.activeElement;
  node.hidden = false;
  // Eén frame later, zodat de overgang van hidden -> zichtbaar ook echt animeert i.p.v. meteen
  // op volle opacity te starten.
  requestAnimationFrame(() => node.classList.add("is-visible"));
  node.querySelector(".image-lightbox-close").focus();
}

export { openImageLightbox, closeImageLightbox };

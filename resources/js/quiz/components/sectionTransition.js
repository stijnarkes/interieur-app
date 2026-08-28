/**
 * Rendert het korte overgangs-/introscherm tussen twee onderdelen van de stijltest.
 * Bij een geüploade foto (zie QuizImageManifest) overlapt de tekstkaart de onderkant van de
 * foto — de `has-photo`-class op de container schakelt die overlap-marge in via CSS, en blijft
 * bewust uit als de foto ontbreekt/niet laadt, zodat de kaart dan gewoon los blijft staan.
 */
function renderSectionTransition(container, { sectionIndex, totalSections, section, onContinue, onBack }) {
  container.innerHTML = "";
  container.classList.remove("has-photo");

  const photo = document.createElement("img");
  photo.className = "quiz-transition-photo";
  photo.alt = "";
  photo.src = `/images/interior/transitions/${section.id}.webp`;
  photo.addEventListener("load", () => container.classList.add("has-photo"), { once: true });
  photo.addEventListener("error", () => photo.remove(), { once: true });
  container.appendChild(photo);

  const content = document.createElement("div");
  content.className = "quiz-transition-content";

  const eyebrow = document.createElement("p");
  eyebrow.className = "quiz-transition-eyebrow";
  eyebrow.textContent = `Stap ${sectionIndex + 1} van ${totalSections}`;
  content.appendChild(eyebrow);

  const title = document.createElement("h2");
  title.className = "quiz-transition-title";
  title.textContent = section.title;
  content.appendChild(title);

  if (section.wrapUp) {
    const wrapUp = document.createElement("p");
    wrapUp.className = "quiz-transition-wrapup";
    wrapUp.textContent = section.wrapUp;
    content.appendChild(wrapUp);
  }

  const tagline = document.createElement("p");
  tagline.className = "quiz-transition-tagline";
  tagline.textContent = section.tagline;
  content.appendChild(tagline);

  const actions = document.createElement("div");
  actions.className = "actions quiz-transition-actions";

  const backButton = document.createElement("button");
  backButton.type = "button";
  backButton.className = "btn btn-outline";
  backButton.innerHTML = `
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
    Terug
  `;
  backButton.addEventListener("click", onBack);
  actions.appendChild(backButton);

  const button = document.createElement("button");
  button.type = "button";
  button.className = "btn btn-primary";
  button.innerHTML = `
    ${section.cta}
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
  `;
  button.addEventListener("click", onContinue);
  actions.appendChild(button);

  content.appendChild(actions);

  container.appendChild(content);
}

export { renderSectionTransition };

import { createImageTile } from "./imageTile.js";

/** Hero bovenaan de resultatenpagina: de belangrijkste uitslag, direct zichtbaar boven de fold. */
function renderResultHero(container, { primaryStyle, secondaryStyle }) {
  const hero = document.createElement("section");
  hero.className = "result-card result-hero";

  const copy = document.createElement("div");
  copy.className = "result-hero-copy";

  const eyebrow = document.createElement("p");
  eyebrow.className = "result-hero-eyebrow";
  eyebrow.textContent = "Jouw woonstijl is";
  copy.appendChild(eyebrow);

  const heading = document.createElement("h1");
  heading.className = "quiz-result-name";
  heading.textContent = primaryStyle?.label ?? "Jouw persoonlijke woonstijl";
  copy.appendChild(heading);

  const description = document.createElement("p");
  description.className = "section-intro";
  description.textContent = primaryStyle?.longDescription ?? "";
  copy.appendChild(description);

  const matches = document.createElement("div");
  matches.className = "result-hero-matches";

  const primaryMatch = document.createElement("div");
  primaryMatch.className = "result-match result-match--primary";
  primaryMatch.innerHTML = `<span class="result-match-label">Beste match</span><span class="result-match-value">${primaryStyle?.label ?? "-"}</span>`;
  matches.appendChild(primaryMatch);

  if (secondaryStyle) {
    const secondaryMatch = document.createElement("div");
    secondaryMatch.className = "result-match result-match--secondary";
    secondaryMatch.innerHTML = `<span class="result-match-label">Past ook goed bij jou</span><span class="result-match-value">${secondaryStyle.label}</span>`;
    matches.appendChild(secondaryMatch);
  }

  copy.appendChild(matches);
  hero.appendChild(copy);

  if (primaryStyle?.heroImage) {
    const image = createImageTile({
      src: primaryStyle.heroImage,
      alt: `Sfeerbeeld van de ${primaryStyle.label}-stijl`,
      label: primaryStyle.label,
      tintKey: primaryStyle.key,
      className: "result-hero-image",
    });
    hero.appendChild(image);
  }

  container.appendChild(hero);
}

export { renderResultHero };

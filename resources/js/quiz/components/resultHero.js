import { createImageTile } from "./imageTile.js";

/**
 * Hero bovenaan de resultatenpagina — toont voortaan de dynamische stijlcombinatie (bv. "Japandi
 * met een Scandinavisch-invloed") i.p.v. alleen de naam van de winnende stijl, plus de kernwoorden
 * (dominante eigenschappen) als chips en de drie lagen basis/invloed/accent zichtbaar naast elkaar.
 */
function renderResultHero(container, { comboName, intro, primaryStyle, secondaryStyle, tertiaryStyle, keywordChips }) {
  const hero = document.createElement("section");
  hero.className = "result-card result-hero";

  const copy = document.createElement("div");
  copy.className = "result-hero-copy";

  const eyebrow = document.createElement("p");
  eyebrow.className = "result-hero-eyebrow";
  eyebrow.textContent = "Jouw persoonlijke woonstijl";
  copy.appendChild(eyebrow);

  const heading = document.createElement("h1");
  heading.className = "quiz-result-name";
  heading.textContent = comboName ?? primaryStyle?.label ?? "Jouw persoonlijke woonstijl";
  copy.appendChild(heading);

  const description = document.createElement("p");
  description.className = "section-intro";
  description.textContent = intro ?? "";
  copy.appendChild(description);

  if (keywordChips?.length) {
    const chips = document.createElement("div");
    chips.className = "result-hero-chips";
    keywordChips.forEach((label) => {
      const chip = document.createElement("span");
      chip.className = "result-hero-chip";
      chip.textContent = label;
      chips.appendChild(chip);
    });
    copy.appendChild(chips);
  }

  const matches = document.createElement("div");
  matches.className = "result-hero-matches";

  const layers = [
    { style: primaryStyle, label: "Basis", className: "result-match--primary" },
    { style: secondaryStyle, label: "Invloed", className: "result-match--secondary" },
    { style: tertiaryStyle, label: "Accent", className: "result-match--tertiary" },
  ];

  layers
    .filter((layer) => layer.style)
    .forEach(({ style, label, className }) => {
      const match = document.createElement("div");
      match.className = `result-match ${className}`;
      match.innerHTML = `<span class="result-match-label">${label}</span><span class="result-match-value">${style.label}</span>`;
      matches.appendChild(match);
    });

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

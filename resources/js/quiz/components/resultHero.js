import { RESULT_HERO_COPY } from "../copy.js";

/**
 * Hero bovenaan de resultatenpagina: de basisstijl, en alleen waar van toepassing één label voor
 * de tweede invloed — nooit een derde stijl (zie de opdracht "vereenvoudiging woonstijltest").
 */
function renderResultHero(container, { comboName, intro, primaryStyle, secondaryStyle }) {
  const hero = document.createElement("section");
  hero.className = "result-card result-hero";

  const copyBlock = document.createElement("div");
  copyBlock.className = "result-hero-copy";

  const eyebrow = document.createElement("p");
  eyebrow.className = "result-hero-eyebrow";
  eyebrow.textContent = RESULT_HERO_COPY.eyebrow;
  copyBlock.appendChild(eyebrow);

  const heading = document.createElement("h1");
  heading.className = "quiz-result-name";
  heading.textContent = comboName ?? primaryStyle?.label ?? RESULT_HERO_COPY.eyebrow;
  copyBlock.appendChild(heading);

  const description = document.createElement("p");
  description.className = "section-intro";
  description.textContent = intro ?? "";
  copyBlock.appendChild(description);

  const expectation = document.createElement("p");
  expectation.className = "result-hero-expectation";
  expectation.textContent = RESULT_HERO_COPY.expectation;
  copyBlock.appendChild(expectation);

  const matches = document.createElement("div");
  matches.className = "result-hero-matches";

  const layers = [
    { style: primaryStyle, label: RESULT_HERO_COPY.primaryLabel, className: "result-match--primary" },
    { style: secondaryStyle, label: RESULT_HERO_COPY.secondaryLabel, className: "result-match--secondary" },
  ];

  layers
    .filter((layer) => layer.style)
    .forEach(({ style, label, className }) => {
      const match = document.createElement("div");
      match.className = `result-match ${className}`;
      match.innerHTML = `<span class="result-match-label">${label}</span><span class="result-match-value">${style.label}</span>`;
      matches.appendChild(match);
    });

  copyBlock.appendChild(matches);
  hero.appendChild(copyBlock);

  container.appendChild(hero);
}

export { renderResultHero };

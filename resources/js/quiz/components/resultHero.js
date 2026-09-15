/**
 * Hero bovenaan de resultatenpagina: de basisstijl, en alleen waar van toepassing één label voor
 * de tweede invloed — nooit een derde stijl (zie de opdracht "vereenvoudiging woonstijltest").
 */
function renderResultHero(container, { comboName, intro, primaryStyle, secondaryStyle }) {
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

  const expectation = document.createElement("p");
  expectation.className = "result-hero-expectation";
  expectation.textContent =
    "Dit is een eerste richting op basis van wat jij mooi vindt. Onze interieurstylistes helpen je graag om deze stijl te vertalen naar jouw eigen woning.";
  copy.appendChild(expectation);

  const matches = document.createElement("div");
  matches.className = "result-hero-matches";

  const layers = [
    { style: primaryStyle, label: "Basisstijl", className: "result-match--primary" },
    { style: secondaryStyle, label: "Invloed", className: "result-match--secondary" },
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
    // Bewust geen createImageTile()/placeholder-tint hier: die liet dit blok leeg/kapot ogen
    // zodra de sfeerfoto ontbrak. In plaats daarvan tonen we een echte foto, of anders helemaal
    // niets — zelfde patroon als sectionTransition.js's overgangsfoto's.
    const image = document.createElement("span");
    image.className = "img-tile result-hero-image";

    const img = document.createElement("img");
    img.src = primaryStyle.heroImage;
    img.alt = `Sfeerbeeld van de ${primaryStyle.label}-stijl`;
    img.loading = "lazy";
    img.decoding = "async";
    img.addEventListener("error", () => image.remove(), { once: true });

    image.appendChild(img);
    hero.appendChild(image);
  }

  container.appendChild(hero);
}

export { renderResultHero };

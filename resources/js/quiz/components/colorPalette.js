import { composePersonalPalette, buildPaletteExplanation } from "../paletteEngine.js";

/**
 * "Jouw kleurenpalet" — een persoonlijk samengesteld palet (zie paletteEngine.js), niet
 * langer simpelweg het vaste palet van de winnende woonstijl.
 *
 * paletteEngine.js kent elke kleur intern een rol toe (Basis/Hoofdkleur/Accentkleur/
 * Materiaaltoon/Contrastkleur) om tot een gevarieerd palet te komen, maar die rol wordt
 * bewust niet getoond: bij een krap of ongebruikelijk kleurenpalet is niet altijd goed te
 * onderbouwen waaróm een kleur precies die rol kreeg (bv. een lichte kleur die noodgedwongen
 * als "Contrastkleur" werd ingevuld omdat er geen betere kandidaat was).
 */
function renderColorPalette(container, primaryStyle, answers) {
  const palette = composePersonalPalette(answers, primaryStyle);
  if (palette.length === 0) return;

  const card = document.createElement("section");
  card.className = "result-card";

  const title = document.createElement("h3");
  title.textContent = "Jouw kleurenpalet";
  card.appendChild(title);

  const explanation = buildPaletteExplanation(answers, primaryStyle);
  if (explanation) {
    const intro = document.createElement("p");
    intro.className = "section-intro";
    intro.textContent = explanation;
    card.appendChild(intro);
  }

  const grid = document.createElement("div");
  grid.className = "palette-grid";
  palette.forEach((color) => {
    const swatch = document.createElement("article");
    swatch.className = "palette-swatch";

    const block = document.createElement("span");
    block.className = "palette-swatch-color";
    block.style.background = color.hex;

    const name = document.createElement("span");
    name.className = "palette-swatch-name";
    name.textContent = color.name;

    swatch.appendChild(block);
    swatch.appendChild(name);
    grid.appendChild(swatch);
  });
  card.appendChild(grid);

  if (primaryStyle?.colorTip) {
    const tip = document.createElement("p");
    tip.className = "section-tip";
    tip.textContent = primaryStyle.colorTip;
    card.appendChild(tip);
  }

  container.appendChild(card);
}

export { renderColorPalette };

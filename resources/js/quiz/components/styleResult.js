import { renderResultHero } from "./resultHero.js";

/**
 * De resultatenpagina toont bewust alleen het heldenblok (herkenning) — de uitgebreide analyse
 * (kenmerken, kleurenpalet, materialen, meubeladvies, moodboard, interieurrecept, nuance) is
 * gereserveerd voor het persoonlijke PDF-rapport, om het achterlaten van gegevens de moeite
 * waard te maken. Die secties se eigen componenten (./traitsSection.js, ./colorPalette.js,
 * ./materialsSection.js, ./furnitureAdvice.js, ./moodboard.js, ./recipeCard.js,
 * ./avoidSection.js) blijven bewust bestaan en onaangeroerd — de PDF-data komt sowieso uit
 * lead.js/paletteEngine.js, niet uit deze renderers, dus niets van de onderliggende
 * berekeningen/resultaten gaat verloren door ze hier niet meer aan te roepen.
 */
function renderStyleResult(container, result) {
  container.innerHTML = "";

  const { primaryStyle, secondaryStyle } = result;

  renderResultHero(container, { primaryStyle, secondaryStyle });
}

export { renderStyleResult };

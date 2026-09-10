import { renderResultHero } from "./resultHero.js";

/**
 * De resultatenpagina toont bewust alleen het heldenblok (herkenning) — de uitgebreide analyse
 * (kenmerken, kleurenpalet, materialen, meubeladvies, moodboard, interieurrecept, nuance) is
 * gereserveerd voor het persoonlijke PDF-rapport, om het achterlaten van gegevens de moeite
 * waard te maken. De PDF-data komt sowieso uit lead.js, niet uit deze pagina.
 */
function renderStyleResult(container, result) {
  container.innerHTML = "";

  const { primaryStyle, secondaryStyle } = result;

  renderResultHero(container, { primaryStyle, secondaryStyle });
}

export { renderStyleResult };

import { renderResultHero } from "./resultHero.js";

/**
 * De resultatenpagina toont bewust alleen het heldenblok (herkenning) — de uitgebreide analyse
 * (kenmerken, kleurenpalet, materialen, meubeladvies, moodboard, interieurrecept, nuance) is
 * gereserveerd voor het persoonlijke PDF-rapport, om het achterlaten van gegevens de moeite
 * waard te maken. De PDF-data komt server-side uit QuizLeadController, niet uit deze pagina.
 */
function renderStyleResult(container, result) {
  container.innerHTML = "";

  const { comboName, intro, primaryStyle, secondaryStyle } = result;

  renderResultHero(container, { comboName, intro, primaryStyle, secondaryStyle });
}

export { renderStyleResult };

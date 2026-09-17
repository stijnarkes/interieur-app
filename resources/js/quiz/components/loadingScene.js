/**
 * Herbruikbare "rustpunt"-animatie: een minimalistische lijntekening van een woonkamer die
 * stapsgewijs wordt opgebouwd (vloer -> bank -> lamp/plant), met instelbare kop-/onderteksten.
 * Gebruikt door zowel het startscherm (quiz.js) als de PDF-aanvraag (lead.js) — zelfde component,
 * andere teksten, zodat beide plekken altijd hetzelfde rustige gevoel geven.
 *
 * Geen eigen kaart-achtergrond/rand/schaduw: de aanroeper bepaalt de omlijsting, want de ene
 * plek heeft een eigen kaart nodig en de andere hergebruikt een al bestaande kaart-wrapper.
 *
 * De opbouw (vloer -> bank -> lamp -> plant, samen ~1,35s) speelt telkens opnieuw af zolang de
 * component zichtbaar is — met een rustige pauze van de volledig opgebouwde kamer erna, in één
 * doorlopende cyclus van 4 seconden — zodat een langere wachttijd duidelijk aanvoelt als "nog
 * actief bezig" i.p.v. een eenmalig afgespeeld filmpje dat daarna stilvalt. start() geeft een
 * promise terug die resolvet zodra de eerste opbouw klaar is; de cyclus blijft daarna gewoon
 * doorlopen, zonder dat de aanroeper daar iets voor hoeft te doen.
 *
 * Bij prefers-reduced-motion: reduce toont de component meteen de kamer in eindstaat, zonder
 * herhalende animatie; de promise resolvet dan vrijwel direct.
 *
 * Onder de titel staan daarnaast drie op-en-neer wippende puntjes (.loading-scene-dots, hetzelfde
 * idee als een typing-indicator in een chatapp) — de kamer-illustratie alleen bleek voor bezoekers
 * niet altijd direct als "nog bezig" te lezen, deze puntjes zijn een herkenbaarder laadsignaal
 * ernaast.
 */
function createLoadingScene({ heading, subtext = "" }) {
  const element = document.createElement("div");
  element.className = "loading-scene";
  element.setAttribute("role", "status");
  element.setAttribute("aria-live", "polite");

  element.innerHTML = `
    <svg class="loading-scene-svg" viewBox="0 0 240 150" aria-hidden="true">
      <path class="loading-scene-floor" d="M20 122 H220" pathLength="1" />
      <g class="loading-scene-plant">
        <path d="M28 122 L24 100 L44 100 L40 122 Z" />
        <path d="M34 100 C30 85 24 78 20 68" />
        <path d="M34 100 C34 82 34 72 34 60" />
        <path d="M34 100 C38 85 44 78 48 68" />
      </g>
      <g class="loading-scene-sofa">
        <rect x="60" y="96" width="120" height="26" rx="6" />
        <rect x="60" y="72" width="20" height="26" rx="6" />
        <rect x="160" y="72" width="20" height="26" rx="6" />
        <rect x="82" y="76" width="76" height="20" rx="6" />
      </g>
      <g class="loading-scene-lamp">
        <line x1="205" y1="122" x2="205" y2="58" />
        <path d="M192 58 L218 58 L212 40 L198 40 Z" />
        <circle class="loading-scene-lamp-bulb" cx="205" cy="58" r="3" />
      </g>
    </svg>
    <h2 class="loading-scene-heading"></h2>
    <div class="loading-scene-dots" aria-hidden="true">
      <span></span><span></span><span></span>
    </div>
    ${subtext ? '<p class="loading-scene-subtext"></p>' : ""}
  `;

  element.querySelector(".loading-scene-heading").textContent = heading;
  if (subtext) {
    element.querySelector(".loading-scene-subtext").textContent = subtext;
  }

  const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  // Moet exact overeenkomen met de duur van de opbouwfase binnen de CSS-cyclus (zie
  // .loading-scene-loop-* in app.css) — er is geen animationend om op te wachten omdat de
  // animatie oneindig doorloopt.
  const BUILD_DURATION_MS = 1350;

  let buildTimer = null;

  function start() {
    element.classList.add("is-building");

    if (prefersReducedMotion) {
      return Promise.resolve();
    }

    return new Promise((resolve) => {
      buildTimer = setTimeout(resolve, BUILD_DURATION_MS);
    });
  }

  function destroy() {
    clearTimeout(buildTimer);
  }

  return { element, start, destroy };
}

export { createLoadingScene };

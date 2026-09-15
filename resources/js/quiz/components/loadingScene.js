/**
 * Herbruikbare "rustpunt"-animatie: een minimalistische lijntekening van een woonkamer die
 * stapsgewijs wordt opgebouwd (vloer -> bank -> lamp/plant), met instelbare kop-/onderteksten.
 * Gebruikt door zowel het startscherm (quiz.js) als de PDF-aanvraag (lead.js) — zelfde component,
 * andere teksten, zodat beide plekken altijd hetzelfde rustige gevoel geven.
 *
 * Geen eigen kaart-achtergrond/rand/schaduw: de aanroeper bepaalt de omlijsting, want de ene
 * plek heeft een eigen kaart nodig en de andere hergebruikt een al bestaande kaart-wrapper.
 *
 * start() geeft een promise terug die resolvet zodra de eenmalige opbouw klaar is (daarna gaat
 * de component vanzelf over in een subtiele, oneindige "nog bezig"-puls op het lampje, zonder de
 * opbouw te herhalen) — zo hoeft geen enkele aanroeper de opbouwduur zelf te kennen of te timen.
 * Bij prefers-reduced-motion: reduce toont de component meteen de kamer in eindstaat, zonder
 * opbouw-animatie en zonder puls; de promise resolvet dan vrijwel direct.
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
    ${subtext ? '<p class="loading-scene-subtext"></p>' : ""}
  `;

  element.querySelector(".loading-scene-heading").textContent = heading;
  if (subtext) {
    element.querySelector(".loading-scene-subtext").textContent = subtext;
  }

  const plant = element.querySelector(".loading-scene-plant");
  const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  let fallbackTimer = null;

  function start() {
    element.classList.add("is-building");

    if (prefersReducedMotion) {
      element.classList.add("is-waiting");
      return Promise.resolve();
    }

    return new Promise((resolve) => {
      let done = false;
      const finish = () => {
        if (done) return;
        done = true;
        plant.removeEventListener("animationend", finish);
        clearTimeout(fallbackTimer);
        element.classList.add("is-waiting");
        resolve();
      };

      plant.addEventListener("animationend", finish, { once: true });
      // Ruime fallback (opbouw duurt ~1.35s) voor het geval animationend om wat voor reden dan
      // ook nooit vuurt — de component moet nooit voor altijd in "is-building" blijven hangen.
      fallbackTimer = setTimeout(finish, 2500);
    });
  }

  function destroy() {
    clearTimeout(fallbackTimer);
  }

  return { element, start, destroy };
}

export { createLoadingScene };

/**
 * Gedeeld vinkje-icoon (inline svg — er is geen icon-library in het publieke deel van de app).
 * Gebruikt door zowel de rapport-teaser-checklist als de leadformulier-successtatus, zodat
 * beide exact hetzelfde icoon tonen zonder de svg-opmaak te dupliceren.
 */
function createCheckIcon(className = "report-checklist-icon", size = 13) {
  const wrapper = document.createElement("span");
  wrapper.className = className;
  wrapper.innerHTML = `<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>`;
  return wrapper;
}

export { createCheckIcon };

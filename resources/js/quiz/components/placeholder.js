// Elke stijl krijgt een vaste placeholder-tint. Omdat elke vraag precies 6 opties heeft —
// exact één per stijl — zijn de 6 tinten binnen een vraag daardoor altijd verschillend
// (in plaats van een hash over de losse optie-id, die per vraag kon botsen).
const STYLE_TINTS = {
  japandi: 0,
  hotelChique: 1,
  industrial: 2,
  biophilic: 3,
  modernCountry: 4,
  retroVintage: 5,
};

/**
 * Levert de placeholder-tintklasse. Geef een stijl-key door (bv. "japandi") voor de vaste
 * per-stijl tint, of een getal voor variatie binnen een lijst die niet stijl-gebonden is
 * (bv. de 4 materiaalkaarten binnen één stijl).
 */
function placeholderClass(styleKeyOrIndex) {
  if (typeof styleKeyOrIndex === "number") {
    return `ph-${styleKeyOrIndex % 6}`;
  }
  return `ph-${STYLE_TINTS[styleKeyOrIndex] ?? 0}`;
}

export { placeholderClass };

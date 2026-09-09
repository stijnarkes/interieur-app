// Elke stijl krijgt een vaste placeholder-tint. Omdat elke vraag precies 8 opties heeft —
// exact één per stijl — zijn de 8 tinten binnen een vraag daardoor altijd verschillend
// (in plaats van een hash over de losse optie-id, die per vraag kon botsen).
const STYLE_TINTS = {
  hotelLuxe: 0,
  japandi: 1,
  kleurExplosie: 2,
  landelijk: 3,
  modern: 4,
  modernLuxe: 5,
  natuurlijk: 6,
  scandinavisch: 7,
};

/**
 * Levert de placeholder-tintklasse. Geef een stijl-key door (bv. "japandi") voor de vaste
 * per-stijl tint, of een getal voor variatie binnen een lijst die niet stijl-gebonden is
 * (bv. de 4 materiaalkaarten binnen één stijl).
 */
function placeholderClass(styleKeyOrIndex) {
  if (typeof styleKeyOrIndex === "number") {
    return `ph-${styleKeyOrIndex % 8}`;
  }
  return `ph-${STYLE_TINTS[styleKeyOrIndex] ?? 0}`;
}

export { placeholderClass };

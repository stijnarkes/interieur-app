import { placeholderClass } from "./placeholder.js";

/**
 * Bouwt een afbeelding met een nette placeholder-fallback (gedeeld door de resultaat-
 * secties die eigen afbeeldingen tonen — materialen, meubeladvies, hero). Zodra het
 * bestand op `src` bestaat, verschijnt het automatisch; tot die tijd toont dit een
 * warme, merk-consistente tint met een labeltje.
 */
function createImageTile({ src, alt, label, tintKey, className = "" }) {
  const wrap = document.createElement("span");
  wrap.className = `img-tile ${placeholderClass(tintKey)} ${className}`.trim();

  const img = document.createElement("img");
  img.src = src;
  img.alt = alt;
  img.loading = "lazy";
  img.decoding = "async";
  img.addEventListener("error", () => {
    wrap.classList.add("is-placeholder");
    if (label) wrap.style.setProperty("--tile-label", `"${label}"`);
    img.remove();
  }, { once: true });

  wrap.appendChild(img);
  return wrap;
}

export { createImageTile };

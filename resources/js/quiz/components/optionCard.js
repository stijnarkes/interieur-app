/**
 * Rendert één foto-keuzekaart. Toont de afbeelding op `option.image` zodra die bestaat;
 * tot die tijd (of bij een laadfout) valt de kaart automatisch terug op een nette placeholder.
 * Zet later gewoon het echte bestand op hetzelfde pad neer — geen codewijziging nodig.
 * Bewust geen per-stijl placeholder-tint (zie placeholder.js) op deze tegel: alle keuzekaarten
 * delen dezelfde rustige achtergrond (.option-image), ook waar object-fit:contain een rand laat
 * zien rond een foto die niet exact de tegelverhouding heeft — dat oogt rustiger dan wisselende
 * kleuren per stijl, en sluit aan bij de vaste kaartkleur van de sfeerpaletten-vraag.
 */
import { openImageLightbox } from "./imageLightbox.js";

function createOptionCard(option, { questionTitle, selected, disabled = false, imageDisplayMode = "contain", onSelect }) {
  const button = document.createElement("button");
  button.type = "button";
  button.className = "option-card";
  button.classList.toggle("is-selected", selected);
  button.setAttribute("aria-pressed", String(selected));
  button.disabled = disabled;

  const imageWrap = document.createElement("span");
  imageWrap.className = "option-image";
  imageWrap.classList.toggle("is-cover", imageDisplayMode === "cover");

  const img = document.createElement("img");
  img.src = option.image;
  img.alt = `${option.title} — optie bij "${questionTitle}"`;
  img.loading = "lazy";
  img.decoding = "async";
  img.addEventListener("error", () => {
    imageWrap.classList.add("is-placeholder");
    img.remove();
    zoom.remove();
  }, { once: true });

  imageWrap.appendChild(img);

  // Los "vergroot bekijken"-hitvlak, geen <button> (nesten van interactieve elementen in de
  // buitenste .option-card-knop is ongeldige HTML en verwart schermlezers), met een eigen
  // click-handler die niet doorbubbelt naar de kaart — zo blijft de rest van de foto gewoon de
  // klik-om-te-kiezen-trigger, en opent dit knopje alleen de vergrote weergave (imageLightbox.js).
  const zoom = document.createElement("span");
  zoom.className = "option-zoom";
  zoom.setAttribute("role", "button");
  zoom.setAttribute("tabindex", "0");
  zoom.setAttribute("aria-label", `${option.title} vergroot bekijken`);
  zoom.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>';
  zoom.addEventListener("click", (event) => {
    event.stopPropagation();
    openImageLightbox(option.image, img.alt);
  });
  zoom.addEventListener("keydown", (event) => {
    if (event.key !== "Enter" && event.key !== " ") return;
    event.preventDefault();
    event.stopPropagation();
    openImageLightbox(option.image, img.alt);
  });
  imageWrap.appendChild(zoom);

  // Kind van imageWrap (i.p.v. rechtstreeks van de knop) zodat het vinkje altijd op de hoek van
  // de fóto blijft zitten, ongeacht hoeveel padding .option-card rond de afbeelding heeft.
  const check = document.createElement("span");
  check.className = "option-check";
  check.textContent = "✓";
  check.setAttribute("aria-hidden", "true");
  imageWrap.appendChild(check);

  button.appendChild(imageWrap);

  button.addEventListener("click", () => onSelect(option.id));

  return button;
}

export { createOptionCard };
